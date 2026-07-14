@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.ops.total_events') }}</span><span class="value">{{ $data['total'] }}</span></td>
            <td><span class="label">{{ __('reports.ops.affected_transactions') }}</span><span class="value">{{ $data['affected_transactions'] }}</span></td>
            <td><span class="label">{{ __('reports.active_users') }}</span><span class="value">{{ $data['active_users'] }}</span></td>
            <td><span class="label">{{ __('reports.ops.shown_events') }}</span><span class="value">{{ $data['shown'] }}</span></td>
        </tr>
    </table>

    @if (! empty($data['truncated']))
        <p style="margin: 0 0 10px; font-size: 10px; color: #92400e;">
            {{ __('reports.ops.pdf_limit_note', [
                'shown' => $data['shown'],
                'total' => $data['total'],
                'limit' => $data['limit'],
            ]) }}
        </p>
    @endif

    <div class="pdf-section">
        <h2>{{ __('reports.ops.timeline_title') }} ({{ $data['events']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('common.date') }}</th>
                    <th>{{ __('reports.event_type_label') }}</th>
                    <th>{{ __('reports.user') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('common.reference_number') }}</th>
                    <th>{{ __('reports.export.col.details') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['events'] as $row)
                    <tr>
                        <td>{{ $row['occurred_at_display'] }}</td>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['actor'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['transaction_ref'] !== '—' ? $row['transaction_ref'] : ($row['folder'] !== '—' ? $row['folder'] : '') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($row['details'], 80) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">{{ __('reports.ops.no_events') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
