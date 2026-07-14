<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\TransactionAttachment;
use App\Models\User;
use App\Services\DocumentAccessService;
use App\Services\LendingEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $attachments = $this->scopedAttachmentsQuery($user)
            ->with([
                'transaction.department',
                'transaction.transactionType',
                'transaction.status',
                'uploader',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($builder) use ($search) {
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%")
                        ->orWhere('file_name', 'like', "%{$search}%")
                        ->orWhereHas('transaction', function ($transactionQuery) use ($search) {
                            $transactionQuery->where('title', 'like', "%{$search}%")
                                ->orWhere('reference_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('department_id'), function ($query) use ($request, $user) {
                $departmentId = $request->integer('department_id');

                if ($user->canAccessDepartment($departmentId)) {
                    $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->where('department_id', $departmentId));
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('documents.index', [
            'attachments' => $attachments,
            'departments' => $this->scopedDepartments($user),
            'orgUnits' => $this->scopedOrgUnitOptions($user),
        ]);
    }

    public function show(
        TransactionAttachment $attachment,
        LendingEligibilityService $lendingEligibility,
        DocumentAccessService $accessService,
        Request $request,
    ): View {
        $this->authorizeAttachmentAccess($attachment);

        $attachment->load([
            'transaction.department',
            'transaction.transactionType',
            'transaction.status',
            'transaction.folder',
            'uploader',
        ]);

        $transaction = $attachment->transaction;
        $user = $request->user();
        $canShowLendingButton = $transaction
            ? $lendingEligibility->canShowRequestButton($user, $transaction)
            : false;
        $canRequestLending = $transaction
            ? $lendingEligibility->canUserRequest($user, $transaction)
            : false;
        $lendingRequestBlockReason = $transaction
            ? $lendingEligibility->blockingReason($user, $transaction)
            : null;

        $viewWatermark = $accessService->prepareViewWatermark($attachment, $user, $request);
        $previewUrl = route('documents.preview', array_filter([
            'attachment' => $attachment,
            'wm' => $viewWatermark['audit']->transaction_id ?? null,
            'u' => $user->id,
            'n' => (string) \Illuminate\Support\Str::uuid(),
        ]));

        $downloadUrl = route('documents.download', [
            'attachment' => $attachment,
            'u' => $user->id,
            'n' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $clientPdfDownload = $attachment->fileKind() === 'pdf'
            && \App\Models\WatermarkSetting::instance()->shouldApplyFor('download');

        $downloadWatermarkContext = $clientPdfDownload
            ? app(\App\Services\DocumentWatermarkService::class)->buildLiveContext($user, 'download')
            : null;

        return view('documents.show', compact(
            'attachment',
            'canShowLendingButton',
            'canRequestLending',
            'lendingRequestBlockReason',
            'viewWatermark',
            'previewUrl',
            'downloadUrl',
            'clientPdfDownload',
            'downloadWatermarkContext',
        ));
    }

    public function preview(TransactionAttachment $attachment, DocumentAccessService $accessService, Request $request): BinaryFileResponse|Response
    {
        $this->authorizeAttachmentAccess($attachment);

        $kind = $attachment->fileKind();

        if ($kind !== 'image' && $kind !== 'pdf') {
            abort(404);
        }

        return $accessService->preview($attachment, $request->user(), $request);
    }

    public function download(TransactionAttachment $attachment, DocumentAccessService $accessService, Request $request): BinaryFileResponse|StreamedResponse|Response|RedirectResponse
    {
        $this->authorizeAttachmentAccess($attachment);

        $path = $attachment->effectiveFilePath();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return back()->with('error', __('messages.file_not_found'));
        }

        return $accessService->download($attachment, $request->user(), $request);
    }

    public function print(TransactionAttachment $attachment, Request $request): View
    {
        $this->authorizeAttachmentAccess($attachment);

        $kind = $attachment->fileKind();

        if ($kind !== 'image' && $kind !== 'pdf') {
            abort(404);
        }

        $printUrl = route('documents.print.file', [
            'attachment' => $attachment,
            'u' => $request->user()->id,
            'n' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        return view('documents.print', [
            'attachment' => $attachment,
            'printUrl' => $printUrl,
            'isPdf' => $kind === 'pdf',
        ]);
    }

    public function printFile(TransactionAttachment $attachment, DocumentAccessService $accessService, Request $request): BinaryFileResponse|Response
    {
        $this->authorizeAttachmentAccess($attachment);

        $kind = $attachment->fileKind();

        if ($kind !== 'image' && $kind !== 'pdf') {
            abort(404);
        }

        return $accessService->print($attachment, $request->user(), $request);
    }

    private function scopedAttachmentsQuery(User $user)
    {
        return TransactionAttachment::query()->whereHas('transaction', function ($query) use ($user) {
            if ($ids = $user->orgScopeDepartmentIds()) {
                $query->whereIn('department_id', $ids);
            }
        });
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Department> */
    private function scopedDepartments(User $user)
    {
        $query = Department::where('is_active', true)->orderBy('name');

        if ($ids = $user->orgScopeDepartmentIds()) {
            $query->whereIn('id', $ids);
        }

        return $query->get();
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    private function scopedOrgUnitOptions(User $user): array
    {
        $options = Department::optionsForSelect();

        if ($ids = $user->orgScopeDepartmentIds()) {
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array($option['id'], $ids, true)
            ));
        }

        return $options;
    }

    private function authorizeAttachmentAccess(TransactionAttachment $attachment): void
    {
        if (! auth()->user()?->canAccessAttachment($attachment)) {
            abort(403, __('messages.document.access_denied'));
        }
    }
}
