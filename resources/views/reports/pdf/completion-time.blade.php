@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.completed_transactions') }}</span><span class="value">{{ $data['total'] }}</span></td>
            <td><span class="label">{{ __('reports.avg_completion_days') }}</span><span class="value">{{ $data['avg_days'] }}</span></td>
            <td><span class="label">{{ __('reports.max_completion_days') }}</span><span class="value">{{ $data['max_days'] }}</span></td>
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
                    <th>{{ __('reports.total_days') }}</th>
                    <th>{{ __('reports.created_at') }}</th>
                    <th>{{ __('reports.archived_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['reference_number'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['total_days'] }}</td>
                        <td>{{ $row['created_at'] }}</td>
                        <td>{{ $row['archived_at'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
