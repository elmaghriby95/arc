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

        if (! $shouldWatermark) {
            $this->auditService->markSuccess($audit, false);

            $absolute = Storage::disk('local')->path($path);
            $mime = $attachment->effectiveMimeType() ?? 'application/octet-stream';

            if ($disposition === 'attachment') {
                return response()->download($absolute, $downloadName, [
                    'Content-Type' => $mime,
                    'X-Watermark-Transaction-Id' => $audit->transaction_id,
                ]);
            }

            return response()->file($absolute, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline',
                'X-Watermark-Transaction-Id' => $audit->transaction_id,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'Pragma' => 'no-cache',
            ]);
        }

        try {
            $copy = $this->watermarkService->createWatermarkedCopy($attachment, $user, $audit);
            $this->auditService->markSuccess($audit, true);

            $name = pathinfo($downloadName, PATHINFO_FILENAME).'-wm.'.$copy['extension'];

            $response = response()->file($copy['path'], [
                'Content-Type' => $copy['mime'],
                'Content-Disposition' => $disposition === 'attachment'
                    ? 'attachment; filename="'.$this->safeFilename($name).'"'
                    : 'inline',
                'X-Watermark-Transaction-Id' => $audit->transaction_id,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'Pragma' => 'no-cache',
            ]);

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

    private function safeFilename(string $name): string
    {
        return str_replace(['"', "\r", "\n"], '', $name);
    }
}
