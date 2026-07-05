<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <h2 class="page-title">{{ __('categories.title') }}</h2>
            @permission('categories.create')
                <a href="{{ route('categories.create') }}" class="btn btn-primary">{{ __('categories.add_button') }}</a>
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
                                <th>{{ __('categories.parent') }}</th>
                                <th>{{ __('categories.documents_count') }}</th>
                                <th>{{ __('categories.sort_order') }}</th>
                                <th>{{ __('common.actions') }}</th>
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
                                            <a href="{{ route('categories.edit', $category) }}">{{ __('common.edit') }}</a>
                                        @endpermission
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">{{ __('categories.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
