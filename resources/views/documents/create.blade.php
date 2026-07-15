<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">{{ __('documents.create_title') }}</h2>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <x-input-label for="title" :value="__('documents.title_label')" />
                        <x-text-input id="title" name="title" type="text" :value="old('title')" required />
                    </div>

                    <div class="form-group">
                        <x-input-label for="description" :value="__('common.description')" />
                        <textarea id="description" name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="department_id" :value="__('common.org_unit')" />
                            @include('settings.partials.org-unit-select', [
                                'orgUnits' => $orgUnits,
                                'selected' => old('department_id', $defaultDepartmentId ?? null),
                            ])
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="document_date" :value="__('documents.date')" />
                            <x-text-input id="document_date" name="document_date" type="date" :value="old('document_date')" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="status" :value="__('common.status')" />
                            <select id="status" name="status" class="form-select" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', 'active') == $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <x-input-label for="tags" :value="__('documents.tags')" />
                        <x-text-input id="tags" name="tags" type="text" :value="old('tags')" />
                    </div>

                    <div class="form-check form-group">
                        <input id="is_confidential" name="is_confidential" type="checkbox" value="1" @checked(old('is_confidential'))>
                        <x-input-label for="is_confidential" :value="__('documents.confidential')" />
                    </div>

                    <div class="form-group">
                        <x-input-label for="file" :value="__('documents.file')" />
                        <input id="file" name="file" type="file" class="form-control" required>
                    </div>

                    <div class="form-actions">
                        <x-primary-button>{{ __('common.save') }}</x-primary-button>
                        <a href="{{ route('documents.index') }}" class="btn btn-link">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
