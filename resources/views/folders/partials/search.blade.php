<form method="GET" action="{{ $action }}" class="folder-browse-search" role="search">
    <label class="folder-browse-search-label" for="{{ $inputId }}">{{ __('folders.search') }}</label>
    <div class="folder-browse-search-row">
        <input
            id="{{ $inputId }}"
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="{{ $placeholder }}"
            class="form-control"
        >
        <button type="submit" class="btn btn-primary">{{ __('folders.search') }}</button>
    </div>
</form>
