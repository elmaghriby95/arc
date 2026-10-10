<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TransactionType;
use App\Services\UserActivityLogger;
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

    public function store(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:transaction_types,code'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $transactionType = TransactionType::create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($actor = $request->user()) {
            $logger->log($actor, 'transaction_type.created', $transactionType, null, $this->snapshot($transactionType), $request);
        }

        return redirect()
            ->route('settings.transaction-types.index')
            ->with('success', __('messages.transaction_type.created'));
    }

    public function update(Request $request, TransactionType $transactionType, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:transaction_types,code,'.$transactionType->id],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $before = $this->snapshot($transactionType);

        $transactionType->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($actor = $request->user()) {
            $logger->logModelChange($actor, 'transaction_type.updated', $transactionType, $before, $this->snapshot($transactionType), $request);
        }

        return redirect()
            ->route('settings.transaction-types.index')
            ->with('success', __('messages.transaction_type.updated'));
    }

    public function destroy(Request $request, TransactionType $transactionType, UserActivityLogger $logger): RedirectResponse
    {
        if ($actor = $request->user()) {
            $logger->log($actor, 'transaction_type.deleted', $transactionType, $this->snapshot($transactionType), null, $request);
        }

        $transactionType->delete();

        return redirect()
            ->route('settings.transaction-types.index')
            ->with('success', __('messages.transaction_type.deleted'));
    }

    /** @return array<string, mixed> */
    private function snapshot(TransactionType $transactionType): array
    {
        return $transactionType->only(['name', 'code', 'description', 'sort_order', 'is_active']);
    }
}
