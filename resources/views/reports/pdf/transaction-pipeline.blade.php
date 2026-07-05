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
                <span class="label">الإجمالي</span>
                <span class="value">{{ $data['total'] }}</span>
            </td>
        </tr>
    </table>

    @if (! empty($data['by_department']))
        <div class="pdf-section">
            <h2>توزيع حسب القسم</h2>
            <table>
                <thead>
                    <tr>
                        <th>القسم</th>
                        @foreach ($data['statuses'] as $status)
                            <th>{{ $status->name }}</th>
                        @endforeach
                        <th>المجموع</th>
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
        <h2>التفاصيل ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>الرقم المرجعي</th>
                    <th>العنوان</th>
                    <th>القسم</th>
                    <th>النوع</th>
                    <th>الحالة</th>
                    <th>المنشئ</th>
                    <th>التاريخ</th>
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
