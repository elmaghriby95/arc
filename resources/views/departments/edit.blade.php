<x-app-layout>
    <x-slot name="header"><h2 class="page-title">{{ __('departments.edit_title') }}</h2></x-slot>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('departments.update', $department) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <x-input-label for="name" :value="__('departments.name')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $department->name)" required />
                    </div>
                    <div class="form-group">
                        <x-input-label for="code" :value="__('departments.code')" />
                        <x-text-input id="code" name="code" type="text" :value="old('code', $department->code)" required />
                    </div>
                    <div class="form-group">
                        <x-input-label for="parent_id" :value="__('departments.parent')" />
                        <select id="parent_id" name="parent_id" class="form-select">
                            <option value="">{{ __('departments.no_parent') }}</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id', $department->parent_id) == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" :value="__('common.description')" />
                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $department->description) }}</textarea>
                    </div>
                    <div class="form-check form-group">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $department->is_active))>
                        <x-input-label for="is_active" :value="__('common.active')" />
                    </div>
                    <x-primary-button>{{ __('common.update') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
