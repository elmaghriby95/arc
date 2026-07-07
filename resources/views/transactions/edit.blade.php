<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('transactions.show', $transaction) }}" class="settings-back-link">{{ __('common.back_to_transaction') }}</a>
                <h2 class="page-title">{{ __('transactions.edit_title') }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('transactions.update', $transaction) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <x-input-label for="archival_reference" :value="__('transactions.archival_reference')" />
                        <x-text-input id="archival_reference" name="archival_reference" type="text" :value="old('archival_reference', $transaction->archival_reference)" required />
                        @error('archival_reference')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <x-input-label for="title" :value="__('transactions.title_label')" />
                        <x-text-input id="title" name="title" type="text" :value="old('title', $transaction->title)" required />
                    </div>

                    <div class="form-group">
                        <x-input-label for="description" :value="__('common.description')" />
                        <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $transaction->description) }}</textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="department_id" :value="__('common.org_unit')" />
                            @include('settings.partials.org-unit-select', [
                                'orgUnits' => $orgUnits,
                                'selected' => old('department_id', $transaction->department_id),
                            ])
                        </div>
                        @include('transactions.partials.folder-select', [
                            'folders' => $folders,
                            'selected' => old('folder_id', $transaction->folder_id),
                        ])
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="transaction_type_id" :value="__('common.transaction_type')" />
                            <select id="transaction_type_id" name="transaction_type_id" class="form-select">
                                <option value="">—</option>
                                @foreach ($transactionTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('transaction_type_id', $transaction->transaction_type_id) == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <x-input-label for="transaction_date" :value="__('transactions.date')" />
                            <x-text-input id="transaction_date" name="transaction_date" type="date" :value="old('transaction_date', $transaction->transaction_date?->format('Y-m-d'))" />
                        </div>
                    </div>

                    <div class="form-group">
                        <x-input-label for="notes" :value="__('common.notes')" />
                        <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $transaction->notes) }}</textarea>
                    </div>

                    <div class="form-actions">
                        <x-primary-button>{{ __('transactions.save_changes') }}</x-primary-button>
                        <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
