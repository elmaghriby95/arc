<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.hanel_storage.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.hanel_storage.subtitle') }}</p>
            </div>
            @permission('settings.hanel-storage.view')
            <a href="{{ route('settings.hanel-storage.items.index') }}" class="btn btn-primary">{{ __('settings.hanel_storage.items.open') }}</a>
            <a href="{{ route('settings.hanel-storage.occupancy.index') }}" class="btn btn-secondary">{{ __('settings.hanel_storage.occupancy.open') }}</a>
            @endpermission
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="ref-settings-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p>{{ __('settings.hanel_storage.info') }}</p>
        </div>

        <div class="card" id="hanel-test-card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.hanel_storage.test_title') }}</h3>
            </div>
            <div class="card-body">
                @if ($settings->last_tested_at)
                    <p class="form-hint">
                        {{ __('settings.hanel_storage.last_test') }}:
                        {{ $settings->last_tested_at->format('Y-m-d H:i') }}
                        — {{ $settings->last_test_message }}
                    </p>
                @endif

                <div id="hanel-test-result" class="hanel-test-result" hidden></div>

                @permission('settings.hanel-storage.edit')
                <button type="button" class="btn btn-secondary" id="hanel-test-btn" data-url="{{ route('settings.hanel-storage.test') }}">
                    {{ __('settings.hanel_storage.test_button') }}
                </button>
                @endpermission
            </div>
        </div>

        @permission('settings.hanel-storage.edit')
        <div class="card hanel-shelf-move-card" id="hanel-shelf-move-card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.hanel_storage.shelf_move_title') }}</h3>
            </div>
            <div class="card-body">
                <p class="form-hint">{{ __('settings.hanel_storage.shelf_move_subtitle') }}</p>
                <p class="form-hint">{{ __('settings.hanel_storage.shelf_move_hint') }}</p>

                <div class="hanel-shelf-move-row">
                    <div class="form-group hanel-shelf-move-field">
                        <x-input-label for="hanel-move-shelf-number" :value="__('settings.hanel_storage.shelf_number')" />
                        <x-text-input id="hanel-move-shelf-number" type="number" min="1" max="999" class="form-control hanel-shelf-move-input" value="4" required />
                    </div>
                    <div class="form-group hanel-shelf-move-field">
                        <x-input-label for="hanel-move-article-number" :value="__('settings.hanel_storage.shelf_move_article')" />
                        <x-text-input id="hanel-move-article-number" type="text" class="form-control hanel-shelf-move-input" placeholder="12001" />
                    </div>
                    <button type="button" class="btn btn-primary hanel-shelf-move-btn" id="hanel-move-shelf-btn" data-url="{{ route('settings.hanel-storage.move-shelf') }}">
                        {{ __('settings.hanel_storage.shelf_move_run') }}
                    </button>
                </div>

                <details class="hanel-shelf-move-advanced">
                    <summary>{{ __('settings.hanel_storage.compartment_number') }} / {{ __('settings.hanel_storage.compartment_depth') }}</summary>
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="hanel-move-compartment-number" :value="__('settings.hanel_storage.compartment_number')" />
                            <x-text-input id="hanel-move-compartment-number" type="number" min="1" max="255" class="form-control" value="1" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="hanel-move-compartment-depth" :value="__('settings.hanel_storage.compartment_depth')" />
                            <x-text-input id="hanel-move-compartment-depth" type="number" min="1" max="99" class="form-control" value="1" />
                        </div>
                    </div>
                </details>

                <div id="hanel-shelf-move-result" class="hanel-test-result" hidden></div>
            </div>
        </div>

        <div class="card" id="hanel-command-card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.hanel_storage.commands_title') }}</h3>
            </div>
            <div class="card-body">
                <p class="form-hint">{{ __('settings.hanel_storage.commands_info') }}</p>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <x-input-label for="hanel-command" :value="__('settings.hanel_storage.command')" />
                        <select id="hanel-command" class="form-control">
                            <option value="read_articles">{{ __('settings.hanel_storage.commands.read_articles') }}</option>
                            <option value="read_status">{{ __('settings.hanel_storage.commands.read_status') }}</option>
                            <option value="get_shelf">{{ __('settings.hanel_storage.commands.get_shelf') }}</option>
                            <option value="send_pick_job">{{ __('settings.hanel_storage.commands.send_pick_job') }}</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid form-grid--2" data-command-fields="get_shelf">
                    <div class="form-group">
                        <x-input-label for="hanel-shelf-number" :value="__('settings.hanel_storage.shelf_number')" />
                        <x-text-input id="hanel-shelf-number" type="number" min="1" max="999" class="form-control" value="4" />
                    </div>
                    <div class="form-group">
                        <x-input-label for="hanel-compartment-number" :value="__('settings.hanel_storage.compartment_number')" />
                        <x-text-input id="hanel-compartment-number" type="number" min="1" max="255" class="form-control" value="1" />
                    </div>
                    <div class="form-group">
                        <x-input-label for="hanel-compartment-depth" :value="__('settings.hanel_storage.compartment_depth')" />
                        <x-text-input id="hanel-compartment-depth" type="number" min="1" max="99" class="form-control" value="1" />
                    </div>
                </div>

                <div class="form-grid form-grid--2" data-command-fields="send_pick_job" hidden>
                    <div class="form-group">
                        <x-input-label for="hanel-article-number" :value="__('settings.hanel_storage.article_number')" />
                        <x-text-input id="hanel-article-number" type="text" class="form-control" value="12001" />
                    </div>
                    <div class="form-group">
                        <x-input-label for="hanel-operation" :value="__('settings.hanel_storage.operation')" />
                        <x-text-input id="hanel-operation" type="text" class="form-control" value="+" maxlength="4" />
                    </div>
                    <div class="form-group">
                        <x-input-label for="hanel-quantity" :value="__('settings.hanel_storage.quantity')" />
                        <x-text-input id="hanel-quantity" type="text" class="form-control" value="1" maxlength="16" />
                    </div>
                    <div class="form-group">
                        <x-input-label for="hanel-job-number" :value="__('settings.hanel_storage.job_number')" />
                        <x-text-input id="hanel-job-number" type="number" min="1" max="999" class="form-control" maxlength="3" placeholder="1–999" />
                    </div>
                </div>

                <div id="hanel-command-result" class="hanel-test-result" hidden></div>
                <pre id="hanel-command-details" class="hanel-command-details" hidden></pre>

                <button type="button" class="btn btn-secondary" id="hanel-command-btn" data-url="{{ route('settings.hanel-storage.command') }}">
                    {{ __('settings.hanel_storage.command_run') }}
                </button>
            </div>
        </div>
        @endpermission

        @permission('settings.hanel-storage.edit')
        <form method="POST" action="{{ route('settings.hanel-storage.update') }}" class="ref-form" id="hanel-settings-form">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.hanel_storage.connection_title') }}</h3>
                </div>
                <div class="card-body">
                    <label class="form-check">
                        <input type="checkbox" name="is_enabled" value="1" class="form-check-input" {{ old('is_enabled', $settings->is_enabled) ? 'checked' : '' }}>
                        <span>{{ __('settings.hanel_storage.is_enabled') }}</span>
                    </label>

                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="protocol" :value="__('settings.hanel_storage.protocol')" />
                            <select id="protocol" name="protocol" class="form-control">
                                @foreach ($protocols as $protocol)
                                    <option value="{{ $protocol }}" @selected(old('protocol', $settings->protocol) === $protocol)>
                                        {{ __('settings.hanel_storage.protocols.'.$protocol) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('protocol')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="host" :value="__('settings.hanel_storage.host')" />
                            <x-text-input id="host" name="host" type="text" class="form-control" :value="old('host', $settings->host)" required />
                            <p class="form-hint">{{ __('settings.hanel_storage.host_desc') }}</p>
                            <x-input-error :messages="$errors->get('host')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="http_port" :value="__('settings.hanel_storage.http_port')" />
                            <x-text-input id="http_port" name="http_port" type="number" min="1" max="65535" class="form-control" :value="old('http_port', $settings->http_port)" required />
                            <x-input-error :messages="$errors->get('http_port')" />
                        </div>

                        <div class="form-group" data-protocol-field="host_com">
                            <x-input-label for="tcp_port" :value="__('settings.hanel_storage.tcp_port')" />
                            <x-text-input id="tcp_port" name="tcp_port" type="number" min="1" max="65535" class="form-control" :value="old('tcp_port', $settings->tcp_port)" required />
                            <p class="form-hint">{{ __('settings.hanel_storage.tcp_port_desc') }}</p>
                            <x-input-error :messages="$errors->get('tcp_port')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="timeout_seconds" :value="__('settings.hanel_storage.timeout_seconds')" />
                            <x-text-input id="timeout_seconds" name="timeout_seconds" type="number" min="10" max="120" class="form-control" :value="old('timeout_seconds', $settings->timeout_seconds)" required />
                            <p class="form-hint">{{ __('settings.hanel_storage.timeout_seconds_desc') }}</p>
                            <x-input-error :messages="$errors->get('timeout_seconds')" />
                        </div>

                        <div class="form-group">
                            <label class="form-check">
                                <input type="checkbox" name="use_https" value="1" class="form-check-input" {{ old('use_https', $settings->use_https) ? 'checked' : '' }}>
                                <span>{{ __('settings.hanel_storage.use_https') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.hanel_storage.lift_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <x-input-label for="lift_number" :value="__('settings.hanel_storage.lift_number')" />
                            <x-text-input id="lift_number" name="lift_number" type="number" min="1" max="999" class="form-control" :value="old('lift_number', $settings->lift_number)" required />
                            <x-input-error :messages="$errors->get('lift_number')" />
                        </div>

                        <div class="form-group">
                            <x-input-label for="access_point" :value="__('settings.hanel_storage.access_point')" />
                            <x-text-input id="access_point" name="access_point" type="number" min="1" max="9" class="form-control" :value="old('access_point', $settings->access_point)" required />
                            <x-input-error :messages="$errors->get('access_point')" />
                        </div>

                        <div class="form-group">
                            <x-input-label :value="__('settings.hanel_storage.shelf_layout')" />
                            <p class="form-hint">{{ __('settings.hanel_storage.shelf_layout_desc') }}</p>
                            @php
                                $layout = $settings->shelf_layout;
                                $layoutShelves = is_array($layout['shelves'] ?? null) ? $layout['shelves'] : [];
                            @endphp
                            <div id="hanel-layout-summary" class="hanel-layout-summary">
                                @if ($layoutShelves !== [])
                                    <p>{{ __('settings.hanel_storage.shelf_layout_current', ['count' => count($layoutShelves)]) }}</p>
                                    <ul class="hanel-layout-shelf-list">
                                        @foreach ($layoutShelves as $shelfNumber => $shelfConfig)
                                            <li>{{ __('settings.hanel_storage.shelf_layout_item', [
                                                'shelf' => $shelfNumber,
                                                'compartments' => $shelfConfig['compartments'] ?? '?',
                                                'depths' => $shelfConfig['depths'] ?? '?',
                                            ]) }}</li>
                                        @endforeach
                                    </ul>
                                    @if ($settings->layout_synced_at)
                                        <p class="text-muted text-sm">{{ __('settings.hanel_storage.shelf_layout_synced_at', ['time' => $settings->layout_synced_at->format('Y-m-d H:i')]) }}</p>
                                    @endif
                                @else
                                    <p class="text-muted">{{ __('settings.hanel_storage.shelf_layout_empty') }}</p>
                                @endif
                            </div>
                            <button type="button" class="btn btn-secondary" id="hanel-sync-layout-btn" data-url="{{ route('settings.hanel-storage.sync-layout') }}">{{ __('settings.hanel_storage.sync_layout') }}</button>
                            <p id="hanel-layout-result" class="form-hint" hidden></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
            </div>
        </form>
        @else
        <div class="card">
            <div class="card-body">
                <dl class="hanel-settings-readonly">
                    <dt>{{ __('settings.hanel_storage.protocol') }}</dt>
                    <dd>{{ __('settings.hanel_storage.protocols.'.$settings->protocol) }}</dd>
                    <dt>{{ __('settings.hanel_storage.host') }}</dt>
                    <dd>{{ $settings->baseUrl() }}</dd>
                    <dt>{{ __('settings.hanel_storage.tcp_port') }}</dt>
                    <dd>{{ $settings->tcp_port }}</dd>
                </dl>
            </div>
        </div>
        @endpermission
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const protocolSelect = document.getElementById('protocol');
                const testBtn = document.getElementById('hanel-test-btn');
                const testResult = document.getElementById('hanel-test-result');

                const syncProtocolFields = () => {
                    if (!protocolSelect) return;
                    const protocol = protocolSelect.value;
                    document.querySelectorAll('[data-protocol-field]').forEach((el) => {
                        el.hidden = el.dataset.protocolField !== protocol;
                    });
                };

                syncProtocolFields();
                protocolSelect?.addEventListener('change', syncProtocolFields);

                testBtn?.addEventListener('click', async () => {
                    testBtn.disabled = true;
                    testResult.hidden = false;
                    testResult.className = 'hanel-test-result hanel-test-result--pending';
                    testResult.textContent = '{{ __('settings.hanel_storage.testing') }}';

                    try {
                        const response = await fetch(testBtn.dataset.url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();
                        testResult.className = 'hanel-test-result ' + (data.ok ? 'hanel-test-result--ok' : 'hanel-test-result--warn');
                        testResult.textContent = data.message;
                    } catch (error) {
                        testResult.className = 'hanel-test-result hanel-test-result--error';
                        testResult.textContent = error.message;
                    } finally {
                        testBtn.disabled = false;
                    }
                });

                const commandSelect = document.getElementById('hanel-command');
                const commandBtn = document.getElementById('hanel-command-btn');
                const commandResult = document.getElementById('hanel-command-result');
                const commandDetails = document.getElementById('hanel-command-details');

                const syncCommandFields = () => {
                    if (!commandSelect) return;
                    const command = commandSelect.value;
                    document.querySelectorAll('[data-command-fields]').forEach((el) => {
                        el.hidden = el.dataset.commandFields !== command;
                    });
                };

                syncCommandFields();
                commandSelect?.addEventListener('change', syncCommandFields);

                commandBtn?.addEventListener('click', async () => {
                    const command = commandSelect?.value;
                    if (!command) return;

                    const payload = { command };

                    if (command === 'get_shelf') {
                        payload.shelf_number = Number(document.getElementById('hanel-shelf-number')?.value || 1);
                        payload.compartment_number = Number(document.getElementById('hanel-compartment-number')?.value || 1);
                        payload.compartment_depth = Number(document.getElementById('hanel-compartment-depth')?.value || 1);
                    }

                    if (command === 'send_pick_job') {
                        payload.article_number = document.getElementById('hanel-article-number')?.value ?? '';
                        payload.operation = document.getElementById('hanel-operation')?.value ?? '+';
                        payload.quantity = document.getElementById('hanel-quantity')?.value ?? '1';
                        const jobNumber = document.getElementById('hanel-job-number')?.value?.trim();
                        if (jobNumber) payload.job_number = jobNumber;
                    }

                    commandBtn.disabled = true;
                    commandResult.hidden = false;
                    commandDetails.hidden = true;
                    commandResult.className = 'hanel-test-result hanel-test-result--pending';
                    commandResult.textContent = '{{ __('settings.hanel_storage.command_running') }}';

                    try {
                        const response = await fetch(commandBtn.dataset.url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json();
                        commandResult.className = 'hanel-test-result ' + (data.ok ? 'hanel-test-result--ok' : 'hanel-test-result--warn');
                        commandResult.textContent = data.message;

                        if (data.details) {
                            commandDetails.hidden = false;
                            commandDetails.textContent = JSON.stringify(data.details, null, 2);
                        }
                    } catch (error) {
                        commandResult.className = 'hanel-test-result hanel-test-result--error';
                        commandResult.textContent = error.message;
                    } finally {
                        commandBtn.disabled = false;
                    }
                });

                const moveShelfBtn = document.getElementById('hanel-move-shelf-btn');
                const moveShelfResult = document.getElementById('hanel-shelf-move-result');

                moveShelfBtn?.addEventListener('click', async () => {
                    const shelfNumber = Number(document.getElementById('hanel-move-shelf-number')?.value || 0);
                    if (!shelfNumber || shelfNumber < 1) return;

                    const payload = { shelf_number: shelfNumber };
                    const article = document.getElementById('hanel-move-article-number')?.value?.trim();
                    const compartment = document.getElementById('hanel-move-compartment-number')?.value;
                    const depth = document.getElementById('hanel-move-compartment-depth')?.value;
                    if (article) payload.article_number = article;
                    if (compartment) payload.compartment_number = Number(compartment);
                    if (depth) payload.compartment_depth = Number(depth);

                    moveShelfBtn.disabled = true;
                    moveShelfResult.hidden = false;
                    moveShelfResult.className = 'hanel-test-result hanel-test-result--pending';
                    moveShelfResult.textContent = '{{ __('settings.hanel_storage.shelf_move_running') }}';

                    try {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 15000);

                        const response = await fetch(moveShelfBtn.dataset.url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                            signal: controller.signal,
                        });

                        clearTimeout(timeoutId);

                        const data = await response.json();
                        moveShelfResult.className = 'hanel-test-result ' + (data.ok ? 'hanel-test-result--ok' : 'hanel-test-result--warn');
                        let text = data.message;
                        if (data.details && Object.keys(data.details).length) {
                            text += '\n\n' + JSON.stringify(data.details, null, 2);
                        }
                        moveShelfResult.textContent = text;
                    } catch (error) {
                        moveShelfResult.className = 'hanel-test-result hanel-test-result--error';
                        moveShelfResult.textContent = error.name === 'AbortError'
                            ? '{{ __('settings.hanel_storage.shelf_move_timeout') }}'
                            : error.message;
                    } finally {
                        moveShelfBtn.disabled = false;
                    }
                });

                const syncLayoutBtn = document.getElementById('hanel-sync-layout-btn');
                const layoutResult = document.getElementById('hanel-layout-result');

                syncLayoutBtn?.addEventListener('click', async () => {
                    syncLayoutBtn.disabled = true;
                    layoutResult.hidden = false;
                    layoutResult.className = 'form-hint';
                    layoutResult.textContent = '{{ __('settings.hanel_storage.sync_layout_running') }}';

                    try {
                        const response = await fetch(syncLayoutBtn.dataset.url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();
                        layoutResult.textContent = data.message;

                        if (data.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        layoutResult.textContent = error.message;
                    } finally {
                        syncLayoutBtn.disabled = false;
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
