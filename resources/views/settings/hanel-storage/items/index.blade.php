<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.hanel-storage.index') }}" class="settings-back-link">{{ __('settings.hanel_storage.items.back') }}</a>
                <h2 class="page-title">{{ __('settings.hanel_storage.items.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.hanel_storage.items.subtitle') }}</p>
            </div>
            <a href="{{ route('settings.hanel-storage.occupancy.index') }}" class="btn btn-secondary">{{ __('settings.hanel_storage.occupancy.open') }}</a>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @permission('settings.hanel-storage.edit')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.hanel_storage.items.add_title') }}</h3>
            </div>
            <div class="card-body">
                <p class="form-hint">{{ __('settings.hanel_storage.items.add_hint') }}</p>
                <form method="POST" action="{{ route('settings.hanel-storage.items.store') }}" class="ref-form" enctype="multipart/form-data">
                    @csrf
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="item-title" :value="__('settings.hanel_storage.items.item_title')" />
                            <x-text-input id="item-title" name="title" type="text" :value="old('title')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="item-reference" :value="__('settings.hanel_storage.items.reference_number')" />
                            <x-text-input id="item-reference" name="reference_number" type="text" :value="old('reference_number')" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="item-shelf" :value="__('settings.hanel_storage.shelf_number')" />
                            <x-text-input id="item-shelf" name="shelf_number" type="number" min="1" max="999" :value="old('shelf_number', 4)" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="item-article" :value="__('settings.hanel_storage.article_number')" />
                            @if ($settings->is_enabled)
                                @if ($unitArticles === [])
                                    <p class="form-hint text-danger">{{ __('settings.hanel_storage.items.no_unit_articles') }}</p>
                                @endif
                                <select id="item-article" name="article_number" class="form-control" required @disabled($unitArticles === [])>
                                    <option value="">{{ __('settings.hanel_storage.items.select_unit_article') }}</option>
                                    @foreach ($unitArticles as $unitArticle)
                                        <option
                                            value="{{ $unitArticle['articleNumber'] }}"
                                            data-shelf="{{ $unitArticle['shelfNumber'] ?? '' }}"
                                            data-compartment="{{ $unitArticle['compartmentNumber'] ?? '' }}"
                                            data-depth="{{ $unitArticle['compartmentDepthNumber'] ?? '' }}"
                                            @selected(old('article_number') === ($unitArticle['articleNumber'] ?? ''))
                                        >
                                            {{ __('settings.hanel_storage.items.unit_article_option', [
                                                'article' => $unitArticle['articleNumber'],
                                                'shelf' => $unitArticle['shelfNumber'] ?? '?',
                                            ]) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">{{ __('settings.hanel_storage.items.article_required_hint') }}</p>
                            @else
                                <x-text-input id="item-article" name="article_number" type="text" :value="old('article_number')" pattern="[A-Za-z0-9]+" />
                                <p class="form-hint">{{ __('settings.hanel_storage.items.article_hint') }}</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <x-input-label for="item-compartment" :value="__('settings.hanel_storage.compartment_number')" />
                            <x-text-input id="item-compartment" name="compartment_number" type="number" min="1" max="255" :value="old('compartment_number')" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="item-file" :value="__('settings.hanel_storage.items.file')" />
                            <input id="item-file" name="file" type="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.tif,.tiff">
                        </div>
                    </div>
                    <div class="form-group">
                        <x-input-label for="item-description" :value="__('common.description')" />
                        <textarea id="item-description" name="description" rows="3" class="form-control" placeholder="{{ __('settings.hanel_storage.items.description_placeholder') }}">{{ old('description') }}</textarea>
                    </div>
                    @if ($settings->is_enabled)
                    <p class="form-hint">{{ __('settings.hanel_storage.items.unit_sync_required_hint') }}</p>
                    @endif
                    <div class="form-check form-group">
                        <input id="retrieve-shelf" name="retrieve_shelf" type="checkbox" value="1" @checked(old('retrieve_shelf', false))>
                        <x-input-label for="retrieve-shelf" :value="__('settings.hanel_storage.items.retrieve_on_save')" />
                    </div>
                    <x-primary-button>{{ __('settings.hanel_storage.items.save') }}</x-primary-button>
                </form>
            </div>
        </div>
        @endpermission

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.hanel_storage.items.search_title') }}</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('settings.hanel-storage.items.index') }}" class="hanel-items-search">
                    <div class="form-group hanel-items-search-field">
                        <x-input-label for="item-search" :value="__('settings.hanel_storage.items.search_label')" />
                        <x-text-input id="item-search" name="q" type="search" :value="$search" placeholder="{{ __('settings.hanel_storage.items.search_placeholder') }}" />
                    </div>
                    <div class="hanel-items-search-actions">
                        <x-primary-button type="submit">{{ __('common.search') }}</x-primary-button>
                        @if ($search !== '')
                            <a href="{{ route('settings.hanel-storage.items.index') }}" class="btn btn-secondary">{{ __('common.reset') }}</a>
                        @endif
                    </div>
                </form>

                @if ($search !== '')
                    <p class="form-hint">{{ __('settings.hanel_storage.items.search_results', ['count' => $items->total(), 'term' => $search]) }}</p>
                @endif

                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>{{ __('settings.hanel_storage.items.item_title') }}</th>
                                <th>{{ __('common.description') }}</th>
                                <th>{{ __('settings.hanel_storage.items.location') }}</th>
                                <th>{{ __('settings.hanel_storage.items.unit_sync') }}</th>
                                <th>{{ __('settings.hanel_storage.items.file') }}</th>
                                <th>{{ __('settings.hanel_storage.items.last_job') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->title }}</strong>
                                        @if ($item->reference_number)
                                            <div class="text-muted text-sm">{{ $item->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->description, 80) ?: '—' }}</td>
                                    <td>{{ $item->locationLabel() }}</td>
                                    <td>
                                        <span class="settings-badge settings-badge--{{ $item->hanel_sync_status === 'synced' ? 'success' : ($item->hanel_sync_status === 'failed' ? 'danger' : 'muted') }}">
                                            {{ __('settings.hanel_storage.items.sync_'.$item->hanel_sync_status) }}
                                        </span>
                                        @if ($item->hanel_sync_message)
                                            <div class="text-muted text-sm">{{ $item->hanel_sync_message }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->fileExists())
                                            <a href="{{ route('settings.hanel-storage.items.download', $item) }}">{{ $item->displayFileName() }}</a>
                                            <div class="text-muted text-sm">{{ $item->formattedSize() }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $item->last_job_number ?: '—' }}</td>
                                    <td class="table-actions">
                                        @permission('settings.hanel-storage.edit')
                                        @if ($settings->is_enabled && $item->hanel_sync_status !== 'synced')
                                            <form method="POST" action="{{ route('settings.hanel-storage.items.sync', $item) }}" class="inline-form">
                                                @csrf
                                                <input type="hidden" name="q" value="{{ $search }}">
                                                <button type="submit" class="btn btn-sm btn-primary">{{ __('settings.hanel_storage.items.sync_unit') }}</button>
                                            </form>
                                        @endif
                                        @if ($settings->is_enabled)
                                            <form method="POST" action="{{ route('settings.hanel-storage.items.retrieve', $item) }}" class="inline-form">
                                                @csrf
                                                <input type="hidden" name="q" value="{{ $search }}">
                                                <button type="submit" class="btn btn-sm btn-secondary">{{ __('settings.hanel_storage.items.retrieve') }}</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('settings.hanel-storage.items.destroy', $item) }}" class="inline-form" onsubmit="return confirm(@json(__('settings.hanel_storage.items.delete_confirm')))">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="q" value="{{ $search }}">
                                            <button type="submit" class="btn btn-sm btn-danger">{{ __('common.delete') }}</button>
                                        </form>
                                        @endpermission
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">{{ __('settings.hanel_storage.items.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $items->links() }}
            </div>
        </div>
    </div>

    @if ($settings->is_enabled)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const articleSelect = document.getElementById('item-article');
                    const shelfInput = document.getElementById('item-shelf');
                    const compartmentInput = document.getElementById('item-compartment');

                    articleSelect?.addEventListener('change', () => {
                        const option = articleSelect.selectedOptions[0];

                        if (!option || !option.dataset.shelf) {
                            return;
                        }

                        if (shelfInput) {
                            shelfInput.value = option.dataset.shelf;
                        }

                        if (compartmentInput && option.dataset.compartment) {
                            compartmentInput.value = option.dataset.compartment;
                        }
                    });
                });
            </script>
        @endpush
    @endif
</x-app-layout>
