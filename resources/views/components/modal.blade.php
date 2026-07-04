@props([
    'name',
    'show' => false,
])

<div id="modal-{{ $name }}" class="modal {{ $show ? 'is-open' : '' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-dialog">
        {{ $slot }}
    </div>
</div>
