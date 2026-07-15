@props([
    'user' => null,
    'size' => 'md',
    'class' => '',
])

@php
    $user = $user ?? auth()->user();
    $slug = $user->roleSlug();
    $sizeClass = match ($size) {
        'xs' => 'user-avatar--xs',
        'sm' => 'user-avatar--sm',
        'lg' => 'user-avatar--lg',
        'xl' => 'user-avatar--xl',
        'hero' => 'user-avatar--hero',
        default => '',
    };
    $classes = trim("user-avatar user-avatar--{$slug} {$sizeClass} {$class}");
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>{{ $user->avatarInitial() }}</span>
