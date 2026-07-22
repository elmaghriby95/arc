@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.stale_transactions.label') }}</span><span class="value">{{ $data['total'] }}</span></td>
            <td><span class="label">{{ __('reports.avg_stale_days') }}</span><span class="value">{{ $data['avg_days'] }}</span></td>
            <td><span class="label">{{ __('reports.max_stale_days') }}</span><span class="value">{{ $data['max_days'] }}</span></td>
            <td><span class="label">{{ __('reports.stale_threshold') }}</span><span class="value">{{ $data['stale_days'] }}+</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.details') }} ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('common.reference_number') }}</th>
                    <th>{{ __('common.title') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('reports.stale_days_col') }}</th>
                    <th>{{ __('reports.creator') }}</th>
                    <th>{{ __('reports.last_activity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['reference_number'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['days_stale'] }}</td>
                        <td>{{ $row['creator'] }}</td>
                        <td>{{ $row['last_activity'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
