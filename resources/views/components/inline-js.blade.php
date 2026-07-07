@props(['file'])

@php($path = resource_path('js/'.$file))
@if (is_readable($path))
    <script>{!! file_get_contents($path) !!}</script>
@endif
