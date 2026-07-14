@if ($events->isEmpty())
    <div class="empty-state"><p>{{ __('reports.ops.no_events') }}</p></div>
@else
    <ol class="ops-timeline">
        @foreach ($events as $event)
            <li class="ops-timeline-item ops-timeline-item--{{ $event['event_type'] }}">
                <div class="ops-timeline-marker" aria-hidden="true"></div>
                <div class="ops-timeline-card">
                    <div class="ops-timeline-top">
                        <span class="ops-event-badge ops-event-badge--{{ $event['event_type'] }}">
                            {{ __('reports.event_type.'.$event['event_type']) }}
                        </span>
                        <time class="ops-timeline-time" datetime="{{ $event['occurred_at'] }}">
                            {{ $event['occurred_at_display'] }}
                        </time>
                    </div>
                    <h4 class="ops-timeline-title">{{ $event['label'] }}</h4>
                    <p class="ops-timeline-details">{{ $event['details'] }}</p>
                    <div class="ops-timeline-meta">
                        <span>
                            <strong>{{ __('reports.user') }}:</strong>
                            {{ $event['actor'] }}
                        </span>
                        <span>
                            <strong>{{ __('common.department') }}:</strong>
                            {{ $event['department'] }}
                        </span>
                        @if (($event['folder'] ?? '—') !== '—')
                            <span>
                                <strong>{{ __('reports.export.col.folder') }}:</strong>
                                {{ $event['folder'] }}
                            </span>
                        @endif
                        @if (($event['transaction_ref'] ?? '—') !== '—')
                            <span>
                                <strong>{{ __('common.reference_number') }}:</strong>
                                @if (! empty($event['url']))
                                    <a href="{{ $event['url'] }}"><code>{{ $event['transaction_ref'] }}</code></a>
                                @else
                                    <code>{{ $event['transaction_ref'] }}</code>
                                @endif
                                @if (($event['transaction_title'] ?? '—') !== '—')
                                    — {{ \Illuminate\Support\Str::limit($event['transaction_title'], 48) }}
                                @endif
                            </span>
                        @endif
                    </div>
                    @if (! empty($event['notes']))
                        <p class="ops-timeline-notes">{{ $event['notes'] }}</p>
                    @endif
                    @if (! empty($event['meta']['from_status']) || ! empty($event['meta']['to_status']))
                        <div class="ops-status-flow">
                            <span>{{ $event['meta']['from_status'] ?? '—' }}</span>
                            <span class="ops-status-arrow">→</span>
                            <span class="reports-inline-status" style="--status-color: {{ $event['meta']['to_status_color'] ?? '#1e3a5f' }}">
                                {{ $event['meta']['to_status'] ?? '—' }}
                            </span>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
