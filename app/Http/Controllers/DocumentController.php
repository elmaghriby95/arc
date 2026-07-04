<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\TransactionAttachment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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

    public function show(TransactionAttachment $attachment): View
    {
        $this->authorizeAttachmentAccess($attachment);

        $attachment->load([
            'transaction.department',
            'transaction.transactionType',
            'transaction.status',
            'transaction.folder',
            'uploader',
        ]);

        return view('documents.show', compact('attachment'));
    }

    public function preview(TransactionAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($attachment);

        $path = $attachment->effectiveFilePath();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $kind = $attachment->fileKind();

        if ($kind !== 'image' && $kind !== 'pdf') {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $attachment->effectiveMimeType() ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function download(TransactionAttachment $attachment): StreamedResponse|RedirectResponse
    {
        $this->authorizeAttachmentAccess($attachment);

        $path = $attachment->effectiveFilePath();
        $name = $attachment->effectiveFileName() ?? $attachment->displayName();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return back()->with('error', 'الملف غير موجود.');
        }

        return Storage::disk('local')->download($path, $name);
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
            abort(403, 'لا يمكنك الوصول إلى هذه الوثيقة ضمن نطاقك التنظيمي.');
        }
    }
}
