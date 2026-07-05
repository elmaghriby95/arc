@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">الأقسام</span><span class="value">{{ $data['totals']['departments'] }}</span></td>
            <td><span class="label">منشأة</span><span class="value">{{ $data['totals']['created'] }}</span></td>
            <td><span class="label">مؤرشفة</span><span class="value">{{ $data['totals']['archived'] }}</span></td>
            <td><span class="label">قيد الإجراء</span><span class="value">{{ $data['totals']['in_progress'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>مقارنة الأقسام</h2>
        <table>
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>منشأة</th>
                    <th>مسودة</th>
                    <th>قيد الإجراء</th>
                    <th>مؤرشفة</th>
                    <th>مكتملة بالفترة</th>
                    <th>نسبة الإنجاز</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['details'] as $row)
                    <tr>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['created'] }}</td>
                        <td>{{ $row['draft'] }}</td>
                        <td>{{ $row['in_progress'] }}</td>
                        <td>{{ $row['archived'] }}</td>
                        <td>{{ $row['completed_in_period'] }}</td>
                        <td>{{ $row['completion_rate'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
