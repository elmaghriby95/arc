<x-app-layout>
    @include('reports.partials.styles')

    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('reports.title') }}</h1>
                <p class="page-subtitle">{{ __('reports.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="reports-hero">
        <div class="reports-hero-content">
            <h2>{{ __('reports.hero_title') }}</h2>
            <p>{{ __('reports.hero_desc') }}</p>
        </div>
        <div class="reports-hero-badge">
            <span>{{ count(\App\Enums\ReportType::accessibleFor(auth()->user())) }}</span>
            <small>{{ __('reports.available_count') }}</small>
        </div>
    </div>

    @foreach ([1, 2] as $phase)
        @php($phaseReports = \App\Enums\ReportType::accessibleForPhase(auth()->user(), $phase))
        @if ($phaseReports === [])
            @continue
        @endif
        <h3 class="reports-section-title">{{ __('reports.phase_'.$phase) }}</h3>
        <div class="reports-modules">
            @foreach ($phaseReports as $report)
                <a href="{{ route('reports.show', $report) }}" class="reports-module {{ $report->colorClass() }}">
                    <div class="reports-module-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z"/></svg>
                    </div>
                    <div class="reports-module-body">
                        <h3 class="reports-module-title">{{ $report->label() }}</h3>
                        <p class="reports-module-desc">{{ $report->description() }}</p>
                    </div>
                    <span class="reports-module-arrow" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    @endforeach

    @if (\App\Enums\ReportType::accessibleFor(auth()->user()) === [])
        <div class="card card-elevated">
            <div class="card-body">
                <div class="empty-state">
                    <p>{{ __('reports.no_accessible') }}</p>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
