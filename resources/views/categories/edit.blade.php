<x-app-layout>
    <x-slot name="header"><h2 class="page-title">{{ __('categories.edit_title') }}</h2></x-slot>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('categories.update', $category) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <x-input-label for="name" :value="__('categories.name')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $category->name)" required />
                    </div>
                    <div class="form-group">
                        <x-input-label for="parent_id" :value="__('categories.parent')" />
                        <select id="parent_id" name="parent_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" :value="__('common.description')" />
                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $category->description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <x-input-label for="sort_order" :value="__('categories.sort_order')" />
                        <x-text-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', $category->sort_order)" />
                    </div>
                    <div class="form-check form-group">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->is_active))>
                        <x-input-label for="is_active" :value="__('common.active')" />
                    </div>
                    <x-primary-button>{{ __('common.update') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
