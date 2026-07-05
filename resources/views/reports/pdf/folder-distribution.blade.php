@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">{{ __('reports.total_transactions') }}</span><span class="value">{{ $data['total_transactions'] }}</span></td>
            <td><span class="label">{{ __('reports.used_folders') }}</span><span class="value">{{ $data['total_folders'] }}</span></td>
            <td><span class="label">{{ __('reports.without_folder') }}</span><span class="value">{{ $data['without_folder'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>{{ __('reports.by_folder') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('common.folder') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('reports.transactions') }}</th>
                    <th>{{ __('reports.attachments') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['by_folder'] as $row)
                    <tr>
                        <td>{{ $row['folder'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['transactions'] }}</td>
                        <td>{{ $row['attachments'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
