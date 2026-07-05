@props(['file'])

@php($path = resource_path('css/'.$file))
@if (is_readable($path))
    <style>{!! file_get_contents($path) !!}</style>
@endif
