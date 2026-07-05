<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <h2 class="page-title">{{ __('departments.title') }}</h2>
            @permission('departments.create')
                <a href="{{ route('departments.create') }}" class="btn btn-primary">{{ __('departments.add_button') }}</a>
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
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('departments.code') }}</th>
                                <th>{{ __('departments.documents_count') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($departments as $department)
                                <tr>
                                    <td>{{ $department->name }}</td>
                                    <td>{{ $department->code }}</td>
                                    <td>{{ $department->documents_count }}</td>
                                    <td>{{ $department->is_active ? __('common.active') : __('common.inactive') }}</td>
                                    <td>
                                        @permission('departments.edit')
                                            <a href="{{ route('departments.edit', $department) }}">{{ __('common.edit') }}</a>
                                        @endpermission
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">{{ __('departments.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $departments->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
