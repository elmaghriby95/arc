@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.active_users') }}</span><span class="value">{{ $data['total_users'] }}</span></td>
            <td><span class="label">{{ __('reports.total_actions') }}</span><span class="value">{{ $data['total_actions'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.user_activity_log') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('reports.user') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('reports.created') }}</th>
                    <th>{{ __('reports.transitions') }}</th>
                    <th>{{ __('reports.uploads') }}</th>
                    <th>{{ __('reports.sum') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['user'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['created'] }}</td>
                        <td>{{ $row['transitions'] }}</td>
                        <td>{{ $row['uploads'] }}</td>
                        <td>{{ $row['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
