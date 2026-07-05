<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.txn_types.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.txn_types.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.txn_types.add_title') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.transaction-types.store') }}" class="ref-form">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="name" :value="__('common.name')" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="code" :value="__('settings.txn_types.code')" />
                            <x-text-input id="code" name="code" type="text" :value="old('code')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="sort_order" :value="__('settings.txn_types.sort_order')" />
                            <x-text-input id="sort_order" name="sort_order" type="number" :value="old('sort_order', 0)" min="0" />
                        </div>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" :value="__('common.description')" />
                        <textarea id="description" name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-check form-group">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                        <x-input-label for="is_active" :value="__('common.active')" />
                    </div>
                    <x-primary-button>{{ __('common.add') }}</x-primary-button>
                </form>
            </div>
        </div>

        <div class="ref-types-grid">
            @forelse ($transactionTypes as $type)
                <div class="ref-type-card {{ $type->is_active ? '' : 'ref-type-card--inactive' }}">
                    <div class="ref-type-card-icon ref-type-card-icon--txn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                    </div>
                    <div class="ref-type-card-body">
                        <div class="ref-type-card-top">
                            <h3 class="ref-type-card-title">{{ $type->name }}</h3>
                            <span class="org-tree-code">{{ $type->code }}</span>
                        </div>
                        @if ($type->description)
                            <p class="ref-type-card-desc">{{ $type->description }}</p>
                        @endif
                        <div class="ref-type-card-meta">
                            <span class="settings-badge">{{ __('settings.txn_types.sort_order_badge', ['order' => $type->sort_order]) }}</span>
                            @unless ($type->is_active)
                                <span class="settings-badge settings-badge--danger">{{ __('common.inactive') }}</span>
                            @endunless
                        </div>
                    </div>
                    <details class="ref-type-edit">
                        <summary class="ref-type-edit-toggle">{{ __('common.edit') }}</summary>
                        <form method="POST" action="{{ route('settings.transaction-types.update', $type) }}" class="ref-type-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <x-input-label :value="__('common.name')" />
                                <x-text-input name="name" type="text" :value="old('name', $type->name)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('settings.txn_types.code')" />
                                <x-text-input name="code" type="text" :value="old('code', $type->code)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('common.description')" />
                                <textarea name="description" rows="2" class="form-control">{{ old('description', $type->description) }}</textarea>
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('settings.txn_types.sort_order')" />
                                <x-text-input name="sort_order" type="number" :value="old('sort_order', $type->sort_order)" min="0" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $type->is_active))>
                                <x-input-label :value="__('common.active')" />
                            </div>
                            <div class="form-actions">
                                <x-primary-button>{{ __('common.save') }}</x-primary-button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('settings.transaction-types.destroy', $type) }}" class="ref-type-delete-form" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">{{ __('common.delete') }}</button>
                        </form>
                    </details>
                </div>
            @empty
                <div class="settings-empty" style="grid-column:1/-1;">
                    <p>{{ __('settings.txn_types.empty') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
