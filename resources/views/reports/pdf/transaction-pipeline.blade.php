@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            @foreach ($data['summary'] as $item)
                <td>
                    <span class="label">{{ $item['name'] }}</span>
                    <span class="value">{{ $item['count'] }}</span>
                </td>
            @endforeach
            <td>
                <span class="label">{{ __('common.total') }}</span>
                <span class="value">{{ $data['total'] }}</span>
            </td>
        </tr>
    </table>

    @if (! empty($data['by_department']))
        <div class="pdf-section">
            <h2>{{ __('reports.by_department') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('common.department') }}</th>
                        @foreach ($data['statuses'] as $status)
                            <th>{{ $status->name }}</th>
                        @endforeach
                        <th>{{ __('reports.sum') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['by_department'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            @foreach ($data['statuses'] as $status)
                                <td>{{ $row['cells'][$status->name]['count'] ?? 0 }}</td>
                            @endforeach
                            <td><strong>{{ $row['total'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="pdf-section">
        <h2>{{ __('reports.details') }} ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('common.reference_number') }}</th>
                    <th>{{ __('common.title') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('common.transaction_type') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('reports.creator') }}</th>
                    <th>{{ __('common.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['reference_number'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['creator'] }}</td>
                        <td>{{ $row['date'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
