@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.total_attachments') }}</span><span class="value">{{ $data['total_count'] }}</span></td>
            <td><span class="label">{{ __('reports.storage_size') }}</span><span class="value">{{ $data['total_size_formatted'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.by_file_kind') }}</h2>
        <table>
            <thead><tr><th>{{ __('reports.kind') }}</th><th>{{ __('reports.count') }}</th><th>{{ __('reports.size') }}</th></tr></thead>
            <tbody>
                @foreach ($data['by_kind'] as $row)
                    <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['size_formatted'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pdf-section">
        <h2>{{ __('reports.details') }} ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('common.title') }}</th>
                    <th>{{ __('documents.transaction') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('documents.file_kind') }}</th>
                    <th>{{ __('reports.size') }}</th>
                    <th>{{ __('documents.uploaded_by') }}</th>
                    <th>{{ __('common.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['transaction'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['kind'] }}</td>
                        <td>{{ $row['size'] }}</td>
                        <td>{{ $row['uploader'] }}</td>
                        <td>{{ $row['date'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
