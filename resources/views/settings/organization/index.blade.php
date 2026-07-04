<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">← العودة للإعدادات</a>
                <h2 class="page-title">الهيكل التنظيمي</h2>
                <p class="page-subtitle">شجرة مرنة للقطاعات والإدارات والأقسام — أضف أي مسمى وعيّن مديراً لكل وحدة</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <datalist id="org-unit-labels">
            @foreach ($unitLabelSuggestions as $label)
                <option value="{{ $label }}"></option>
            @endforeach
        </datalist>

        <div class="org-stats">
            <div class="org-stat">
                <span class="org-stat-value">{{ $totalDepartments }}</span>
                <span class="org-stat-label">إجمالي الوحدات</span>
            </div>
            <div class="org-stat">
                <span class="org-stat-value">{{ $totalUsers }}</span>
                <span class="org-stat-label">إجمالي الموظفين</span>
            </div>
        </div>

        @permission('departments.create')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">إضافة وحدة تنظيمية رئيسية</h3>
                </div>
                <div class="card-body">
                    @include('settings.partials.org-add-form', [
                        'parentId' => null,
                        'defaultUnitLabel' => 'قطاع',
                        'users' => $users,
                        'unitLabelSuggestions' => $unitLabelSuggestions,
                    ])
                </div>
            </div>
        @endpermission

        <div class="card org-tree-card-wrapper">
            <div class="card-header">
                <h3 class="card-title">شجرة الهيكل التنظيمي</h3>
                <button type="button" class="btn btn-secondary" data-org-expand-all>توسيع الكل</button>
            </div>
            <div class="card-body">
                @if ($departments->isEmpty())
                    <div class="settings-empty">
                        <div class="settings-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                            </svg>
                        </div>
                        <p>لا توجد وحدات تنظيمية بعد. ابدأ بإضافة قطاع أو إدارة رئيسية.</p>
                    </div>
                @else
                    <ul class="org-tree" data-org-tree>
                        @foreach ($departments as $department)
                            @include('settings.partials.org-tree-node', [
                                'department' => $department,
                                'depth' => 0,
                                'users' => $users,
                                'unitLabelSuggestions' => $unitLabelSuggestions,
                            ])
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
