<div class="page-header reports-page-header">
    @if ($systemSettings->hasLogo())
        <img
            src="{{ $systemSettings->logoUrl() }}"
            alt="{{ $systemSettings->appName() }}"
            class="reports-system-logo"
        >
    @endif
    <div>
        <nav class="reports-breadcrumb">
            <a href="{{ route('reports.index') }}">{{ __('reports.title') }}</a>
            <span>/</span>
            <span>{{ $reportType->label() }}</span>
        </nav>
        <h1 class="page-title">{{ $reportType->label() }}</h1>
        <p class="page-subtitle">{{ $reportType->description() }}</p>
    </div>
</div>
