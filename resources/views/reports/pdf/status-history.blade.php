@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.total_movements') }}</span><span class="value">{{ $data['total'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.movement_log') }} ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('common.date') }}</th>
                    <th>{{ __('common.reference_number') }}</th>
                    <th>{{ __('common.title') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('transactions.from_status') }}</th>
                    <th>{{ __('transactions.to_status') }}</th>
                    <th>{{ __('transactions.action') }}</th>
                    <th>{{ __('transactions.changed_by') }}</th>
                    <th>{{ __('common.notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['reference_number'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['from_status'] }}</td>
                        <td>{{ $row['to_status'] }}</td>
                        <td>{{ $row['action'] }}</td>
                        <td>{{ $row['changed_by'] }}</td>
                        <td>{{ $row['notes'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
