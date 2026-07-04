<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <h2 class="page-title">الأقسام</h2>
            @permission('departments.create')
                <a href="{{ route('departments.create') }}" class="btn btn-primary">إضافة قسم</a>
            @endpermission
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الرمز</th>
                                <th>عدد الوثائق</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($departments as $department)
                                <tr>
                                    <td>{{ $department->name }}</td>
                                    <td>{{ $department->code }}</td>
                                    <td>{{ $department->documents_count }}</td>
                                    <td>{{ $department->is_active ? 'نشط' : 'غير نشط' }}</td>
                                    <td>
                                        @permission('departments.edit')
                                            <a href="{{ route('departments.edit', $department) }}">تعديل</a>
                                        @endpermission
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">لا توجد أقسام.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $departments->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
