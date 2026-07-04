<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <h2 class="page-title">التصنيفات</h2>
            @permission('categories.create')
                <a href="{{ route('categories.create') }}" class="btn btn-primary">إضافة تصنيف</a>
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
                                <th>التصنيف الأب</th>
                                <th>عدد الوثائق</th>
                                <th>الترتيب</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->parent?->name ?? '—' }}</td>
                                    <td>{{ $category->documents_count }}</td>
                                    <td>{{ $category->sort_order }}</td>
                                    <td>
                                        @permission('categories.edit')
                                            <a href="{{ route('categories.edit', $category) }}">تعديل</a>
                                        @endpermission
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">لا توجد تصنيفات.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
