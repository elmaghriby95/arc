<?php

namespace App\Services;

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

        $audit = $this->auditService->createPending($user, $attachment, $action, $request);
        $settings = WatermarkSetting::instance();
        $shouldWatermark = $settings->shouldApplyFor($action)
            && $this->watermarkService->supports($attachment);

        $downloadName = $attachment->effectiveFileName() ?? $attachment->displayName();
        $absolute = Storage::disk('local')->path($path);
        $mime = $attachment->effectiveMimeType() ?? 'application/octet-stream';

        if (! $shouldWatermark) {
            $this->auditService->markSuccess($audit, false);

            return $this->streamOriginal(
                $absolute,
                $mime,
                $downloadName,
                $disposition,
                $audit->transaction_id,
            );
        }

        try {
            // Always burn the watermark into a temporary copy so every PDF page
            // (and the full image) carries the current user's data for view/download/print.
            $copy = $this->watermarkService->createWatermarkedCopy($attachment, $user, $audit);
            $this->auditService->markSuccess($audit, true);

            $name = pathinfo($downloadName, PATHINFO_FILENAME).'-wm.'.$copy['extension'];

            $response = response()->file($copy['path'], $this->fileHeaders(
                $copy['mime'],
                $disposition === 'attachment'
                    ? 'attachment; filename="'.$this->safeFilename($name).'"'
                    : 'inline',
                $audit->transaction_id,
            ));

            if ($response instanceof BinaryFileResponse) {
                $response->deleteFileAfterSend(true);
            }

            return $response;
        } catch (Throwable $e) {
            $this->auditService->markFailure($audit, $e->getMessage());

            report($e);

            abort(500, __('messages.watermark.failed'));
        }
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
