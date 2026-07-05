<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DocumentType::create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('settings.document-types.index')
            ->with('success', __('messages.document_type.created'));
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code,'.$documentType->id],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $documentType->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.document-types.index')
            ->with('success', __('messages.document_type.updated'));
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        $documentType->delete();

        return redirect()
            ->route('settings.document-types.index')
            ->with('success', __('messages.document_type.deleted'));
    }
}
