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
    $showQr = ($context['show_qr_code'] ?? false) && filled($context['qr_svg'] ?? null);
@endphp

@if ($showCenter || $showFooter || $showQr)
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

        @if ($showFooter || $showQr)
            <div class="doc-wm-bottom">
                @if ($showFooter)
                    <div class="doc-wm-footer">{{ $footer }}</div>
                @endif
                @if ($showQr && filled($context['qr_svg'] ?? null))
                    <div class="doc-wm-qr">
                        {!! $context['qr_svg'] !!}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
