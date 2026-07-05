@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.departments') }}</span><span class="value">{{ $data['totals']['departments'] }}</span></td>
            <td><span class="label">{{ __('reports.created') }}</span><span class="value">{{ $data['totals']['created'] }}</span></td>
            <td><span class="label">{{ __('reports.archived') }}</span><span class="value">{{ $data['totals']['archived'] }}</span></td>
            <td><span class="label">{{ __('reports.in_progress') }}</span><span class="value">{{ $data['totals']['in_progress'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.department_comparison') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('reports.created') }}</th>
                    <th>{{ __('reports.draft') }}</th>
                    <th>{{ __('reports.in_progress') }}</th>
                    <th>{{ __('reports.archived') }}</th>
                    <th>{{ __('reports.completed_in_period') }}</th>
                    <th>{{ __('reports.completion_rate') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['created'] }}</td>
                        <td>{{ $row['draft'] }}</td>
                        <td>{{ $row['in_progress'] }}</td>
                        <td>{{ $row['archived'] }}</td>
                        <td>{{ $row['completed_in_period'] }}</td>
                        <td>{{ $row['completion_rate'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
