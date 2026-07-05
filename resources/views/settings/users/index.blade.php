<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.users.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.users.subtitle') }}</p>
            </div>
            @permission('settings.users.create')
                <a href="{{ route('settings.users.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    {{ __('settings.users.create_button') }}
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.users.list_title') }}</h3>
                <span class="settings-badge">{{ __('settings.users.list_count', ['count' => $users->total()]) }}</span>
            </div>
            <div class="card-body">
                @if ($users->isEmpty())
                    <div class="settings-empty">
                        <div class="settings-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <p>{{ __('settings.users.empty') }}</p>
                        @permission('settings.users.create')
                            <a href="{{ route('settings.users.create') }}" class="btn btn-primary">{{ __('settings.users.create_first') }}</a>
                        @endpermission
                    </div>
                @else
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('settings.users.user') }}</th>
                                    <th>{{ __('common.email') }}</th>
                                    <th>{{ __('common.role') }}</th>
                                    <th>{{ __('settings.users.org_location') }}</th>
                                    <th>{{ __('settings.users.registered_at') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <span class="user-avatar user-avatar--{{ $user->role?->slug ?? 'user' }}">{{ mb_substr($user->name, 0, 1) }}</span>
                                                <div>
                                                    <strong>{{ $user->name }}</strong>
                                                    @if ($user->id === auth()->id())
                                                        <span class="user-tag">{{ __('settings.users.you') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="role-pill role-pill--{{ $user->role?->slug ?? 'user' }}">{{ $user->role?->name ?? '—' }}</span></td>
                                        <td>
                                            @if ($user->department_id && isset($breadcrumbs[$user->department_id]))
                                                <span class="org-path org-path--compact">{{ $breadcrumbs[$user->department_id] }}</span>
                                            @else
                                                <span class="text-muted">{{ __('settings.users.not_assigned') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $user->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            @permission('settings.users.edit')
                                                <a href="{{ route('settings.users.edit', $user) }}" class="btn btn-secondary btn-sm">{{ __('common.edit') }}</a>
                                            @endpermission
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $users->links() }}
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
