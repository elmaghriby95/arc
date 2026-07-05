@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.attachment_refs') }}</span><span class="value">{{ $data['total_attachment_refs'] }}</span></td>
            <td><span class="label">{{ __('reports.duplicate_refs') }}</span><span class="value">{{ $data['duplicate_count'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.details') }} ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('reports.reference_number') }}</th>
                    <th>{{ __('reports.year') }}</th>
                    <th>{{ __('common.title') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('reports.uploader') }}</th>
                    <th>{{ __('common.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['reference_number'] }}</td>
                        <td>{{ $row['year'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['uploader'] }}</td>
                        <td>{{ $row['date'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
