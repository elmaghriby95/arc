<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.hanel-storage.index') }}" class="settings-back-link">{{ __('settings.hanel_storage.occupancy.back') }}</a>
                <h2 class="page-title">{{ __('settings.hanel_storage.occupancy.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.hanel_storage.occupancy.subtitle') }}</p>
            </div>
            @if ($settings->is_enabled)
                <form method="GET" action="{{ route('settings.hanel-storage.occupancy.index') }}" class="inline-form">
                    <input type="hidden" name="refresh_layout" value="1">
                    <button type="submit" class="btn btn-secondary">{{ __('settings.hanel_storage.occupancy.refresh_layout') }}</button>
                </form>
            @endif
            <a href="{{ route('settings.hanel-storage.items.index') }}" class="btn btn-secondary">{{ __('settings.hanel_storage.items.open') }}</a>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="hanel-occupancy-stats">
            <div class="hanel-occupancy-stat">
                <span class="hanel-occupancy-stat-value">{{ $map['stats']['total_slots'] }}</span>
                <span class="hanel-occupancy-stat-label">{{ __('settings.hanel_storage.occupancy.total_slots') }}</span>
            </div>
            <div class="hanel-occupancy-stat hanel-occupancy-stat--occupied">
                <span class="hanel-occupancy-stat-value">{{ $map['stats']['occupied'] }}</span>
                <span class="hanel-occupancy-stat-label">{{ __('settings.hanel_storage.occupancy.occupied') }}</span>
            </div>
            <div class="hanel-occupancy-stat hanel-occupancy-stat--free">
                <span class="hanel-occupancy-stat-value">{{ $map['stats']['free'] }}</span>
                <span class="hanel-occupancy-stat-label">{{ __('settings.hanel_storage.occupancy.free') }}</span>
            </div>
            @if ($settings->is_enabled)
                <div class="hanel-occupancy-stat">
                    <span class="hanel-occupancy-stat-value">{{ $map['stats']['unit_articles'] }}</span>
                    <span class="hanel-occupancy-stat-label">{{ __('settings.hanel_storage.occupancy.unit_articles') }}</span>
                </div>
            @endif
        </div>

        <div class="ref-settings-info">
            <p>{{ __('settings.hanel_storage.occupancy.legend') }}</p>
            @if (($map['layout']['shelves'] ?? []) !== [])
                <p class="text-muted text-sm">
                    {{ __('settings.hanel_storage.occupancy.layout_from_unit', [
                        'shelves' => $map['total_shelves'],
                        'source' => __('settings.hanel_storage.occupancy.layout_source_'.($map['layout']['source'] ?? 'settings_fallback')),
                    ]) }}
                    @if ($settings->layout_synced_at)
                        — {{ $settings->layout_synced_at->format('Y-m-d H:i') }}
                    @endif
                </p>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.hanel_storage.occupancy.map_title') }}</h3>
            </div>
            <div class="card-body">
                <div class="hanel-occupancy-legend">
                    <span class="hanel-occupancy-chip hanel-occupancy-chip--free">{{ __('settings.hanel_storage.occupancy.free') }}</span>
                    <span class="hanel-occupancy-chip hanel-occupancy-chip--occupied">{{ __('settings.hanel_storage.occupancy.occupied') }}</span>
                    <span class="hanel-occupancy-chip hanel-occupancy-chip--arc">{{ __('settings.hanel_storage.occupancy.source_arc') }}</span>
                    <span class="hanel-occupancy-chip hanel-occupancy-chip--unit">{{ __('settings.hanel_storage.occupancy.source_unit') }}</span>
                </div>

                <div class="hanel-occupancy-grid">
                    @foreach ($map['grid'] as $shelfNumber => $compartments)
                        <div class="hanel-occupancy-shelf">
                            <div class="hanel-occupancy-shelf-title">{{ __('settings.hanel_storage.shelf_number') }} {{ $shelfNumber }}</div>
                            <div class="hanel-occupancy-slots">
                                @foreach ($compartments as $compartmentNumber => $slot)
                                    @php
                                        $isOccupied = ($slot['status'] ?? 'free') === 'occupied';
                                        $classes = 'hanel-occupancy-slot';
                                        $classes .= $isOccupied ? ' hanel-occupancy-slot--occupied' : ' hanel-occupancy-slot--free';
                                        if (($slot['source'] ?? null) === 'arc') {
                                            $classes .= ' hanel-occupancy-slot--arc';
                                        } elseif (($slot['source'] ?? null) === 'unit') {
                                            $classes .= ' hanel-occupancy-slot--unit';
                                        }
                                    @endphp
                                    <div class="{{ $classes }}" title="{{ $slot['title'] ?? '' }}">
                                        <span class="hanel-occupancy-slot-label">{{ $compartmentNumber }}</span>
                                        @if ($isOccupied)
                                            <strong>{{ $slot['article_number'] ?: '—' }}</strong>
                                            <small>{{ \Illuminate\Support\Str::limit($slot['title'] ?? '', 18) }}</small>
                                        @else
                                            <small>{{ __('settings.hanel_storage.occupancy.free') }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
