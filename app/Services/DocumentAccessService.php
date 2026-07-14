<?php

namespace App\Services;

use App\Models\DocumentAccessAudit;
use App\Models\TransactionAttachment;
use App\Models\User;
use App\Models\WatermarkSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentAccessService
{
    public function __construct(
        private readonly DocumentAccessAuditService $auditService,
        private readonly DocumentWatermarkService $watermarkService,
    ) {}

    public function preview(TransactionAttachment $attachment, User $user, Request $request): BinaryFileResponse|Response
    {
        return $this->serve($attachment, $user, $request, 'view', 'inline');
    }

    public function download(TransactionAttachment $attachment, User $user, Request $request): BinaryFileResponse|StreamedResponse|Response
    {
        return $this->serve($attachment, $user, $request, 'download', 'attachment');
    }

    public function print(TransactionAttachment $attachment, User $user, Request $request): BinaryFileResponse|Response
    {
        return $this->serve($attachment, $user, $request, 'print', 'inline');
    }

    /**
     * Prepare current-user watermark payload for client-side viewing overlay.
     *
     * @return array{audit: DocumentAccessAudit, context: array<string, mixed>}|null
     */
    public function prepareViewWatermark(TransactionAttachment $attachment, User $user, Request $request): ?array
    {
        $settings = WatermarkSetting::instance();

        if (! $settings->shouldApplyFor('view') || ! $this->watermarkService->supports($attachment)) {
            return null;
        }

        $audit = $this->auditService->createPending($user, $attachment, 'view', $request);
        $this->auditService->markSuccess($audit, true);

        return [
            'audit' => $audit,
            'context' => $this->watermarkService->withOverlayAssets(
                $this->watermarkService->buildContext($user, $audit)
            ),
        ];
    }

    private function serve(
        TransactionAttachment $attachment,
        User $user,
        Request $request,
        string $action,
        string $disposition,
    ): BinaryFileResponse|StreamedResponse|Response {
        $path = $attachment->effectiveFilePath();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $settings = WatermarkSetting::instance();
        $wantsWatermark = $settings->shouldApplyFor($action);
        $canBurnIn = $this->watermarkService->supportsBurnIn($attachment);

        $downloadName = $attachment->effectiveFileName() ?? $attachment->displayName();
        $absolute = Storage::disk('local')->path($path);
        $mime = $attachment->effectiveMimeType() ?? 'application/octet-stream';

        // VIEW: stream original instantly; live overlay handles display watermark.
        if ($action === 'view') {
            $audit = $this->resolveViewAudit(
                $attachment,
                $user,
                $request,
                $wantsWatermark && $this->watermarkService->supports($attachment),
            );

            return $this->streamOriginal(
                $absolute,
                $mime,
                $downloadName,
                $disposition,
                $audit->transaction_id,
            );
        }

        $audit = $this->auditService->createPending($user, $attachment, $action, $request);

        // DOWNLOAD / PRINT: watermark is mandatory whenever enabled in settings.
        if ($wantsWatermark) {
            if (! $canBurnIn) {
                $this->auditService->markFailure($audit, 'Watermark burn-in not supported for this file type.');

                abort(422, __('messages.watermark.unsupported_type'));
            }

            try {
                $copy = $this->watermarkService->createWatermarkedCopy($attachment, $user, $audit);
            } catch (Throwable $first) {
                report($first);

                // One retry with a lighter stamp (no QR) before failing the download.
                try {
                    $copy = $this->watermarkService->createWatermarkedCopy(
                        $attachment,
                        $user,
                        $audit,
                        reduceFeatures: true,
                    );
                } catch (Throwable $second) {
                    $this->auditService->markFailure($audit, $second->getMessage());
                    report($second);

                    // Never hand out a clean unwatermarked file when watermark is required.
                    abort(500, __('messages.watermark.download_required'));
                }
            }

            $this->auditService->markSuccess($audit, true);

            $name = pathinfo($downloadName, PATHINFO_FILENAME).'-wm.'.$copy['extension'];

            if ($disposition === 'attachment') {
                $response = response()->download(
                    $copy['path'],
                    $this->safeFilename($name),
                    $this->fileHeaders($copy['mime'], null, $audit->transaction_id),
                );
            } else {
                $response = response()->file($copy['path'], $this->fileHeaders(
                    $copy['mime'],
                    'inline',
                    $audit->transaction_id,
                ));
            }

            if ($response instanceof BinaryFileResponse) {
                $response->deleteFileAfterSend(true);
            }

            return $response;
        }

        $this->auditService->markSuccess($audit, false);

        return $this->streamOriginal(
            $absolute,
            $mime,
            $downloadName,
            $disposition,
            $audit->transaction_id,
        );
    }

    private function resolveViewAudit(
        TransactionAttachment $attachment,
        User $user,
        Request $request,
        bool $watermarkApplied,
    ): DocumentAccessAudit {
        $token = (string) $request->query('wm', '');

        if ($token !== '') {
            $existing = DocumentAccessAudit::query()
                ->where('transaction_id', $token)
                ->where('user_id', $user->id)
                ->where('attachment_id', $attachment->id)
                ->where('action_type', 'view')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $audit = $this->auditService->createPending($user, $attachment, 'view', $request);
        $this->auditService->markSuccess($audit, $watermarkApplied);

        return $audit;
    }

    private function streamOriginal(
        string $absolute,
        string $mime,
        string $downloadName,
        string $disposition,
        string $transactionId,
    ): BinaryFileResponse|StreamedResponse {
        if ($disposition === 'attachment') {
            return response()->download($absolute, $downloadName, $this->fileHeaders(
                $mime,
                null,
                $transactionId,
            ));
        }

        return response()->file($absolute, $this->fileHeaders(
            $mime,
            'inline',
            $transactionId,
        ));
    }

    /**
     * @return array<string, string>
     */
    private function fileHeaders(string $mime, ?string $contentDisposition, string $transactionId): array
    {
        $headers = [
            'Content-Type' => $mime,
            'X-Watermark-Transaction-Id' => $transactionId,
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Vary' => 'Cookie, Authorization',
        ];

        if ($contentDisposition !== null) {
            $headers['Content-Disposition'] = $contentDisposition;
        }

        return $headers;
    }

    private function safeFilename(string $name): string
    {
        return str_replace(['"', "\r", "\n"], '', $name);
    }
}
