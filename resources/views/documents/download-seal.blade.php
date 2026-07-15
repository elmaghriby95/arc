<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ $backUrl }}" class="settings-back-link">{{ __('common.close') }}</a>
                <h2 class="page-title">{{ __('messages.watermark.seal_preparing') }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <div class="card" style="max-width: 40rem; margin: 2rem auto;">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <p id="doc-seal-status" data-doc-seal-status>{{ __('messages.watermark.seal_preparing') }}</p>
                <p class="form-hint" style="margin-top: 1rem;">{{ $attachment->displayName() }}</p>
                <div style="margin-top: 1.5rem;">
                    <a href="{{ $backUrl }}" class="btn btn-secondary">{{ __('common.close') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div
        id="doc-pdf-seal"
        hidden
        data-source-url="{{ $sourceUrl }}"
        data-filename="{{ $filename }}"
        data-watermark="{{ e(json_encode($watermark, JSON_UNESCAPED_UNICODE)) }}"
        data-msg-preparing="{{ e(__('messages.watermark.seal_preparing')) }}"
        data-msg-failed="{{ e(__('messages.watermark.seal_failed')) }}"
        data-msg-done="{{ e(__('messages.watermark.seal_done')) }}"
    ></div>

    @push('scripts')
        <script src="{{ route('assets.pdfjs', ['file' => 'pdf-lib.min.js']) }}"></script>
        <x-inline-js file="document-pdf-download-seal.js" />
    @endpush
</x-app-layout>
