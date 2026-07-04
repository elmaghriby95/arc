<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $documents = $this->scopedDocumentsQuery($user)
            ->with(['department', 'category', 'uploader'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($builder) use ($search) {
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('department_id'), function ($query) use ($request, $user) {
                $departmentId = $request->integer('department_id');
                if ($user->canAccessDepartment($departmentId)) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'departments' => $this->scopedDepartments($user),
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'statuses' => DocumentStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        return view('documents.create', [
            'departments' => $this->scopedDepartments($user),
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'statuses' => DocumentStatus::cases(),
            'defaultDepartmentId' => $user->department_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'document_date' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,active,archived'],
            'is_confidential' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $departmentId = $validated['department_id'] ?? $request->user()->department_id;

        if (! $request->user()->canAccessDepartment($departmentId)) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'لا يمكنك ربط الوثيقة بهذه الوحدة التنظيمية.']);
        }

        $file = $request->file('file');
        $path = $file->store('documents/'.date('Y/m'), 'local');

        $document = Document::create([
            'reference_number' => $this->generateReferenceNumber(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'department_id' => $departmentId,
            'uploaded_by' => $request->user()->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'document_date' => $validated['document_date'] ?? null,
            'status' => $validated['status'],
            'is_confidential' => $request->boolean('is_confidential'),
        ]);

        $document->versions()->create([
            'version_number' => 1,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'change_note' => 'الإصدار الأول',
        ]);

        $this->syncTags($document, $validated['tags'] ?? null);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'تم حفظ الوثيقة بنجاح.');
    }

    public function show(Document $document): View
    {
        $this->authorizeDocumentAccess($document);

        $document->load(['department', 'category', 'uploader', 'tags', 'versions.uploader']);

        return view('documents.show', compact('document'));
    }

    public function edit(Document $document): View
    {
        $this->authorizeDocumentAccess($document);

        $user = auth()->user();

        return view('documents.edit', [
            'document' => $document->load('tags'),
            'departments' => $this->scopedDepartments($user),
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'statuses' => DocumentStatus::cases(),
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeDocumentAccess($document);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'document_date' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,active,archived'],
            'is_confidential' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:20480'],
            'change_note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $request->user()->canAccessDepartment($validated['department_id'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'لا يمكنك ربط الوثيقة بهذه الوحدة التنظيمية.']);
        }

        $document->fill([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'document_date' => $validated['document_date'] ?? null,
            'status' => $validated['status'],
            'is_confidential' => $request->boolean('is_confidential'),
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('documents/'.date('Y/m'), 'local');
            $nextVersion = ($document->versions()->max('version_number') ?? 0) + 1;

            $document->versions()->create([
                'version_number' => $nextVersion,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
                'change_note' => $validated['change_note'] ?? 'تحديث الملف',
            ]);

            $document->fill([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        $document->save();
        $this->syncTags($document, $validated['tags'] ?? null);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'تم تحديث الوثيقة بنجاح.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorizeDocumentAccess($document);

        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'تم حذف الوثيقة بنجاح.');
    }

    public function download(Document $document)
    {
        $this->authorizeDocumentAccess($document);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'الملف غير موجود.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    private function generateReferenceNumber(): string
    {
        do {
            $reference = 'ARC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Document::where('reference_number', $reference)->exists());

        return $reference;
    }

    private function syncTags(Document $document, ?string $tags): void
    {
        if ($tags === null) {
            return;
        }

        $tagIds = collect(explode(',', $tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->map(function (string $name) {
                return Tag::firstOrCreate(['name' => $name])->id;
            });

        $document->tags()->sync($tagIds);
    }

    private function scopedDocumentsQuery(User $user)
    {
        $query = Document::query();

        if ($ids = $user->orgScopeDepartmentIds()) {
            $query->whereIn('department_id', $ids);
        }

        return $query;
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

    private function authorizeDocumentAccess(Document $document): void
    {
        if (! auth()->user()?->canAccessDocument($document)) {
            abort(403, 'لا يمكنك الوصول إلى هذه الوثيقة ضمن نطاقك التنظيمي.');
        }
    }
}
