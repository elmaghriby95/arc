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
            @if ($folders->isEmpty())
                <p class="table-empty">{{ __('folders.empty') }}</p>
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
