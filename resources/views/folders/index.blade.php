<x-app-layout>
    @push('styles')
        <x-inline-css file="folder-browse.css" />
    @endpush

    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('nav.folders') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-body">
            @include('folders.partials.search', [
                'action' => route('folders.index'),
                'inputId' => 'folder-list-search',
                'placeholder' => __('folders.search_folders'),
            ])
            @if ($folders->isEmpty())
                <p class="table-empty">{{ request()->filled('search') ? __('folders.no_results') : __('folders.empty') }}</p>
            @else
                <div class="folder-browse-grid">
                    @foreach ($folders as $folder)
                        @include('folders.partials.card', ['folder' => $folder])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
