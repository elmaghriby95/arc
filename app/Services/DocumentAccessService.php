<?php

namespace App\Services;

use App\Models\DocumentAccessAudit;
use App\Models\TransactionAttachment;
use App\Models\User;
use App\Models\WatermarkSetting;
use Illuminate\Http\RedirectResponse;
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

    public function download(TransactionAttachment $attachment, User $user, Request $request): BinaryFileResponse|StreamedResponse|Response|RedirectResponse
    {
        return $this->serve($attachment, $user, $request, 'download', 'attachment');
    }

    public function print(TransactionAttachment $attachment, User $user, Request $request): BinaryFileResponse|Response|RedirectResponse
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
        return $this->prepareOverlayWatermark($attachment, $user, $request, 'view', forceWhenEnabled: true);
    }

    /**
     * Prepare watermark payload for the print surface (PDF.js / image overlay).
     *
     * @return array{audit: DocumentAccessAudit, context: array<string, mixed>}|null
     */
    public function preparePrintWatermark(TransactionAttachment $attachment, User $user, Request $request): ?array
    {
        return $this->prepareOverlayWatermark($attachment, $user, $request, 'print');
    }

    /**
     * @return array{audit: DocumentAccessAudit, context: array<string, mixed>}|null
     */
    private function prepareOverlayWatermark(
        TransactionAttachment $attachment,
        User $user,
        Request $request,
        string $action,
        bool $forceWhenEnabled = false,
    ): ?array {
        $settings = WatermarkSetting::instance();

        $shouldApply = $forceWhenEnabled
            ? $settings->is_enabled
            : $settings->shouldApplyFor($action);

        if (! $shouldApply || ! $this->watermarkService->supports($attachment)) {
            return null;
        }

        $audit = $this->auditService->createPending($user, $attachment, $action, $request);
        $this->auditService->markSuccess($audit, true);

        $context = $this->watermarkService->withOverlayAssets(
            $this->watermarkService->buildContext($user, $audit)
        );

        if (
            $forceWhenEnabled
            && empty($context['show_center_text'])
            && empty($context['show_footer'])
            && empty($context['show_qr_code'])
        ) {
            $context['center_lines'] = [$user->name, $audit->transaction_id];
            $context['footer'] = implode(' | ', $context['center_lines']);
            $context['show_center_text'] = true;
            $context['show_footer'] = true;
        }

        return [
            'audit' => $audit,
            'context' => $context,
        ];
    }

    private function serve(
        TransactionAttachment $attachment,
        User $user,
        Request $request,
        string $action,
        string $disposition,
    ): BinaryFileResponse|StreamedResponse|Response|RedirectResponse {
        $path = $attachment->effectiveFilePath();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $settings = WatermarkSetting::instance();
        $wantsWatermark = $settings->shouldApplyFor($action);
        $canBurnIn = $this->watermarkService->supportsBurnIn($attachment);
        $kind = $attachment->fileKind();

        $downloadName = $attachment->effectiveFileName() ?? $attachment->displayName();
        $absolute = Storage::disk('local')->path($path);
        $mime = $attachment->effectiveMimeType() ?? 'application/octet-stream';

        // VIEW / PRINT file bytes: always stream original (overlay handles visual watermark).
        if ($action === 'view' || $action === 'print') {
            $audit = $this->resolveViewAudit(
                $attachment,
                $user,
                $request,
                $wantsWatermark && $this->watermarkService->supports($attachment),
                $action,
            );

            return $this->streamOriginal(
                $absolute,
                $mime,
                $downloadName,
                $disposition,
                $audit->transaction_id,
            );
        }

        // DOWNLOAD
        $audit = $this->auditService->createPending($user, $attachment, 'download', $request);

        if ($wantsWatermark) {
            if (! $canBurnIn) {
                $this->auditService->markFailure($audit, 'Watermark burn-in not supported for this file type.');

                return redirect()
                    ->route('documents.show', $attachment)
                    ->with('error', __('messages.watermark.unsupported_type'));
            }

            // PDFs use incremental append; images use GD. Neither path depends on public assets.
            try {
                $copy = $this->watermarkService->createWatermarkedCopy($attachment, $user, $audit);
            } catch (Throwable $first) {
                report($first);

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

                    return redirect()
                        ->route('documents.show', $attachment)
                        ->with('error', __('messages.watermark.download_required'));
                }
            }

            $this->auditService->markSuccess($audit, true);

            $name = pathinfo($downloadName, PATHINFO_FILENAME).'-wm.'.$copy['extension'];
            $response = response()->download(
                $copy['path'],
                $this->safeFilename($name),
                $this->fileHeaders($copy['mime'], null, $audit->transaction_id),
            );

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
        string $action = 'view',
    ): DocumentAccessAudit {
        $token = (string) $request->query('wm', '');

        if ($token !== '') {
            $existing = DocumentAccessAudit::query()
                ->where('transaction_id', $token)
                ->where('user_id', $user->id)
                ->where('attachment_id', $attachment->id)
                ->where('action_type', $action)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $audit = $this->auditService->createPending($user, $attachment, $action, $request);
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
