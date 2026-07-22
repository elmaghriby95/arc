@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.confidential_documents.label') }}</span><span class="value">{{ $data['total_documents'] }}</span></td>
            <td><span class="label">{{ __('reports.linked_attachments') }}</span><span class="value">{{ $data['total_linked_attachments'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.confidential_list') }} ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('reports.source') }}</th>
                    <th>{{ __('common.title') }}</th>
                    <th>{{ __('common.reference_number') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('reports.uploader') }}</th>
                    <th>{{ __('common.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['source'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['reference_number'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['uploader'] }}</td>
                        <td>{{ $row['date'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
