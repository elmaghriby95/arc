@extends('reports.pdf.layout')

@section('content')
    <table class="pdf-stats">
        <tr>
            <td><span class="label">إجمالي الحركات</span><span class="value">{{ $data['total'] }}</span></td>
        </tr>
    </table>

    <div class="pdf-section">
        <h2>سجل الحركات ({{ $data['details']->count() }})</h2>
        <table>
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الرقم المرجعي</th>
                    <th>العنوان</th>
                    <th>القسم</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>الإجراء</th>
                    <th>بواسطة</th>
                    <th>ملاحظات</th>
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
