@props([
    'name' => 'department_id',
    'id' => 'department_id',
    'orgUnits' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'showHint' => true,
    'selectClass' => 'form-select org-unit-select',
    'ignoreOld' => false,
    'lockWhenSingle' => false,
])

@php
    $placeholder = $placeholder ?? __('common.choose_org_unit');
    $singleUnit = ($lockWhenSingle && count($orgUnits) === 1) ? reset($orgUnits) : null;
    $locked = $singleUnit !== null;
    $current = $locked
        ? $singleUnit['id']
        : ($ignoreOld ? $selected : old($name, $selected));

    $splitUnit = function (array $unit): array {
        $parts = explode(' ← ', (string) ($unit['label'] ?? ''));
        $leaf = (string) array_pop($parts);
        $kind = '';
        $unitName = $leaf;

        if (preg_match('/^([^:]+):\s*(.+)$/u', $leaf, $matches) === 1) {
            $kind = trim($matches[1]);
            $unitName = trim($matches[2]);
        }

        return [
            'kind' => $kind,
            'name' => $unitName,
            'short' => $leaf,
            'path' => implode(' ← ', $parts),
        ];
    };

    $prepared = [];
    foreach ($orgUnits as $unit) {
        $prepared[] = array_merge($unit, $splitUnit($unit));
    }

    $selectedUnit = null;
    foreach ($prepared as $unit) {
        if ((string) $current === (string) $unit['id']) {
            $selectedUnit = $unit;
            break;
        }
    }

    $isTxnw = str_contains($selectClass, 'txnw');
    $listId = $id.'-list';
@endphp

<div
    class="org-unit-picker {{ $locked ? 'is-locked' : '' }} {{ $selectedUnit ? 'has-value' : '' }} {{ $isTxnw ? 'is-txnw' : '' }}"
    data-org-picker
    data-placeholder="{{ $placeholder }}"
    data-search-placeholder="{{ __('common.org_unit_search') }}"
>
    @if ($locked)
        <input type="hidden" name="{{ $name }}" value="{{ $current }}">
    @endif

    <select
        id="{{ $id }}"
        @unless($locked) name="{{ $name }}" @endunless
        class="{{ $selectClass }}"
        tabindex="-1"
        aria-hidden="true"
        @if($required && ! $locked) required @endif
        @if($locked) disabled data-locked="1" aria-disabled="true" title="{{ __('transactions.org_unit_locked') }}" @endif
    >
        @unless($locked)
            <option value="">{{ $placeholder }}</option>
        @endunless
        @foreach ($prepared as $unit)
            <option
                value="{{ $unit['id'] }}"
                @selected((string) $current === (string) $unit['id'])
                title="{{ $unit['label'] }}"
            >{{ $unit['short'] }}</option>
        @endforeach
    </select>

    @if ($locked)
        <div class="org-unit-field" title="{{ $selectedUnit['label'] ?? '' }}">
            <span class="org-unit-field-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 018 0v3"/></svg>
            </span>
            @if ($selectedUnit && $selectedUnit['kind'] !== '')
                <span class="org-unit-kind">{{ $selectedUnit['kind'] }}</span>
            @endif
            <span class="org-unit-static-label">{{ $selectedUnit['name'] ?? $selectedUnit['short'] ?? '' }}</span>
        </div>
        <p class="org-unit-locked-hint">{{ __('transactions.org_unit_locked') }}</p>
    @else
        <div class="org-unit-combo">
            <div class="org-unit-field" data-org-field @if($selectedUnit) title="{{ $selectedUnit['label'] }}" @endif>
                <span class="org-unit-field-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                </span>
                <div class="org-unit-field-body">
                    <input
                        type="search"
                        class="org-unit-input"
                        id="{{ $id }}_search"
                        data-org-search
                        placeholder="{{ $placeholder }}"
                        autocomplete="off"
                        spellcheck="false"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="{{ $listId }}"
                        aria-label="{{ $placeholder }}"
                    >
                    <span class="org-unit-display" data-org-display @unless($selectedUnit) hidden @endunless>
                        <span class="org-unit-kind" data-org-kind @if(! $selectedUnit || $selectedUnit['kind'] === '') hidden @endif>{{ $selectedUnit['kind'] ?? '' }}</span>
                        <span class="org-unit-display-name" data-org-name>{{ $selectedUnit['name'] ?? '' }}</span>
                    </span>
                </div>
                @unless($required)
                    <button type="button" class="org-unit-clear" data-org-clear tabindex="-1" aria-label="{{ __('common.reset') }}" @unless($selectedUnit) hidden @endunless>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                @endunless
                <button type="button" class="org-unit-caret" data-org-caret tabindex="-1" aria-label="{{ $placeholder }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>

            <div class="org-unit-menu" data-org-menu hidden>
                <div class="org-unit-list" id="{{ $listId }}" data-org-list role="listbox" aria-label="{{ $placeholder }}">
                    @foreach ($prepared as $unit)
                        <button
                            type="button"
                            class="org-unit-option"
                            id="{{ $id }}-opt-{{ $unit['id'] }}"
                            role="option"
                            data-org-option
                            data-value="{{ $unit['id'] }}"
                            data-depth="{{ (int) $unit['depth'] }}"
                            data-kind="{{ $unit['kind'] }}"
                            data-name="{{ $unit['name'] }}"
                            data-label="{{ $unit['short'] }}"
                            data-full="{{ $unit['label'] }}"
                            data-search="{{ trim($unit['kind'].' '.$unit['name'].' '.$unit['short'].' '.$unit['path']) }}"
                            style="--depth: {{ min((int) $unit['depth'], 4) }}"
                            aria-selected="{{ (string) $current === (string) $unit['id'] ? 'true' : 'false' }}"
                            title="{{ $unit['label'] }}"
                        >
                            <span class="org-unit-option-main">
                                @if ($unit['kind'] !== '')
                                    <span class="org-unit-kind">{{ $unit['kind'] }}</span>
                                @endif
                                <span class="org-unit-name">{{ $unit['name'] }}</span>
                                <svg class="org-unit-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            @if ($unit['path'] !== '')
                                <span class="org-unit-path">{{ $unit['path'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
                <p class="org-unit-empty" data-org-empty hidden>{{ __('common.org_unit_no_results') }}</p>
            </div>
        </div>
        @if ($showHint)
            <p class="org-unit-picker-hint">{{ __('settings.org.unit_hint') }}</p>
        @endif
    @endif
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/org-unit-picker.js') }}?v={{ filemtime(public_path('js/org-unit-picker.js')) }}"></script>
    @endpush
@endonce
