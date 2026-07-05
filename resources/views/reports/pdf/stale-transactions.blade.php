@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">متأخرة</span><span class="value">{{ $data['total'] }}</span></td>
            <td><span class="label">متوسط الأيام</span><span class="value">{{ $data['avg_days'] }}</span></td>
            <td><span class="label">أقصى توقف</span><span class="value">{{ $data['max_days'] }}</span></td>
            <td><span class="label">حد التأخير</span><span class="value">{{ $data['stale_days'] }}+</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>التفاصيل ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>الرقم المرجعي</th>
                    <th>العنوان</th>
                    <th>القسم</th>
                    <th>الحالة</th>
                    <th>أيام التوقف</th>
                    <th>المنشئ</th>
                    <th>آخر نشاط</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['reference_number'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['days_stale'] }}</td>
                        <td>{{ $row['creator'] }}</td>
                        <td>{{ $row['last_activity'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
