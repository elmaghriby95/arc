<div class="page-header">
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
