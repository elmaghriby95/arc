@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">المرفقات</span><span class="value">{{ $data['total_count'] }}</span></td>
            <td><span class="label">حجم التخزين</span><span class="value">{{ $data['total_size_formatted'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>حسب نوع الملف</h2>
        <table>
            <thead><tr><th>النوع</th><th>العدد</th><th>الحجم</th></tr></thead>
            <tbody>
                @foreach ($data['by_kind'] as $row)
                    <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['size_formatted'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pdf-section">
        <h2>التفاصيل ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>المعاملة</th>
                    <th>القسم</th>
                    <th>نوع الملف</th>
                    <th>الحجم</th>
                    <th>الرافع</th>
                    <th>التاريخ</th>
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
