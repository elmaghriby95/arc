<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionTypeController extends Controller
{
    public function index(): View
    {
        return view('settings.transaction-types.index', [
            'transactionTypes' => TransactionType::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:transaction_types,code'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        TransactionType::create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('settings.transaction-types.index')
            ->with('success', __('messages.transaction_type.created'));
    }

    public function update(Request $request, TransactionType $transactionType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:transaction_types,code,'.$transactionType->id],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $transactionType->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.transaction-types.index')
            ->with('success', __('messages.transaction_type.updated'));
    }

    public function destroy(TransactionType $transactionType): RedirectResponse
    {
        $transactionType->delete();

        return redirect()
            ->route('settings.transaction-types.index')
            ->with('success', __('messages.transaction_type.deleted'));
    }
}
