<a href="{{ route('folders.browse', $folder) }}" class="folder-browse-card">
    <span class="folder-browse-card-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h5l2 2h9a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg>
    </span>
    <span class="folder-browse-card-copy">
        <span class="folder-browse-card-name">{{ $folder->name }}</span>
        <span class="folder-browse-card-meta">
            {{ __('folders.transactions_count', ['count' => $folder->visible_transactions_count]) }}
            @if ($folder->is_closed)
                · {{ __('settings.folders.closed_badge') }}
            @endif
        </span>
    </span>
</a>
