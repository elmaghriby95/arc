@props(['kind' => 'file'])

@php
    $icons = [
        'pdf' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 3v5a1 1 0 001 1h5"/>',
        'word' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h4"/>',
        'excel' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v14H4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 5v14M16 5v14M4 10h16M4 14h16"/>',
        'image' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'file' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
    ];
@endphp

<span class="txn-file-icon txn-file-icon--{{ $kind }}" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
        {!! $icons[$kind] ?? $icons['file'] !!}
    </svg>
</span>
