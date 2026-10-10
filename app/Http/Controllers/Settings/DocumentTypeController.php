<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        return view('settings.document-types.index', [
            'documentTypes' => DocumentType::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $documentType = DocumentType::create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($actor = $request->user()) {
            $logger->log($actor, 'document_type.created', $documentType, null, $this->snapshot($documentType), $request);
        }

        return redirect()
            ->route('settings.document-types.index')
            ->with('success', __('messages.document_type.created'));
    }

    public function update(Request $request, DocumentType $documentType, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code,'.$documentType->id],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $before = $this->snapshot($documentType);

        $documentType->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($actor = $request->user()) {
            $logger->logModelChange($actor, 'document_type.updated', $documentType, $before, $this->snapshot($documentType), $request);
        }

        return redirect()
            ->route('settings.document-types.index')
            ->with('success', __('messages.document_type.updated'));
    }

    public function destroy(Request $request, DocumentType $documentType, UserActivityLogger $logger): RedirectResponse
    {
        if ($actor = $request->user()) {
            $logger->log($actor, 'document_type.deleted', $documentType, $this->snapshot($documentType), null, $request);
        }

        $documentType->delete();

        return redirect()
            ->route('settings.document-types.index')
            ->with('success', __('messages.document_type.deleted'));
    }

    /** @return array<string, mixed> */
    private function snapshot(DocumentType $documentType): array
    {
        return $documentType->only(['name', 'code', 'description', 'sort_order', 'is_active']);
    }
}
