@props([
    'context',
])

@php
    $opacity = max(0.05, min(0.6, (float) ($context['opacity'] ?? 0.18)));
    $angle = (int) ($context['angle'] ?? -45);
    $lines = $context['center_lines'] ?? [];
    $footer = $context['footer'] ?? '';
    $showCenter = ($context['show_center_text'] ?? false) && $lines !== [];
    $showFooter = ($context['show_footer'] ?? false) && $footer !== '';
@endphp

@if ($showCenter || $showFooter)
    <div
        class="doc-wm-overlay"
        aria-hidden="true"
        style="--doc-wm-opacity: {{ $opacity }}; --doc-wm-angle: {{ $angle }}deg;"
    >
        @if ($showCenter)
            <div class="doc-wm-center">
                @foreach ($lines as $line)
                    <span>{{ $line }}</span>
                @endforeach
            </div>
        @endif

        @if ($showFooter)
            <div class="doc-wm-bottom">
                <div class="doc-wm-footer">{{ $footer }}</div>
            </div>
        @endif
    </div>
@endif
