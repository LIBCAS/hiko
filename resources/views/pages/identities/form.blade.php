<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="{ type: '{{ $identity->type ? $identity->type : 'person' }}' }" action="{{ $action }}"
        method="post" onkeydown="return event.key != 'Enter';" class="max-w-sm space-y-3" autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="type" :value="__('hiko.type')" />
            <x-select x-model="type" id="type" class="block w-full mt-1" name="type" required>
                @foreach ($types as $type)
                    <option value="{{ $type }}" {{ old('type', $identity->type) === $type ? 'selected' : '' }}>
                        {{ __("hiko.{$type}") }}
                    </option>
                @endforeach
            </x-select>
            @error('type')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <template x-if="type === 'person'">
            <div class="space-y-3">
                <div class="required">
                    <x-label for="surname" :value="__('hiko.surname')" />
                    <x-input id="surname" class="block w-full mt-1" type="text" name="surname"
                        :value="old('surname', $identity->surname)" required />
                    @error('surname')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="forename" :value="__('hiko.forename')" />
                    <x-input id="forename" class="block w-full mt-1" type="text" name="forename"
                        :value="old('forename', $identity->forename)" />
                    @error('forename')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex space-x-6">
                    <div>
                        <x-label for="birth_year" :value="__('hiko.birth_year')" />
                        <x-input id="birth_year" class="block w-full mt-1" type="text" name="birth_year"
                            :value="old('birth_year', $identity->birth_year)" />
                        @error('birth_year')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="death_year" :value="__('hiko.death_year')" />
                        <x-input id="death_year" class="block w-full mt-1" type="text" name="death_year"
                            :value="old('death_year', $identity->death_year)" />
                        @error('death_year')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div>
                    <x-label for="nationality" :value="__('hiko.nationality')" />
                    <x-input id="nationality" class="block w-full mt-1" type="text" name="nationality"
                        :value="old('nationality', $identity->nationality)" />
                    @error('nationality')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="gender" :value="__('hiko.gender')" />
                    <x-input id="gender" class="block w-full mt-1" type="text" name="gender"
                        :value="old('gender', $identity->gender)" />
                    @error('gender')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div x-data="{ professions: JSON.parse(document.getElementById('selectedProfessions').innerHTML) }"
                    class="p-2 space-y-3 border rounded-md border-primary-light">
                    <p>
                        {{ __('hiko.profession') }}
                    </p>
                    @error('profession')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                    <template x-for="profession, index in professions"
                        :key="profession.key ? profession.key : profession.id">
                        <div class="flex">
                            <x-select name="profession[]" class="block w-full mt-1" aria-label="{{ __('hiko.profession') }}"
                                x-data="ajaxSelect({url: '{{ route('ajax.professions') }}', element: $el, options: { id: profession.id, name: profession.name } })"
                                x-init="initSelect()">
                            </x-select>
                            <button type="button" class="ml-6 text-red-600"
                                aria-label="{{ __('hiko.remove_item') }}" title="{{ __('hiko.remove_item') }}"
                                x-on:click="
                            professions = professions.filter((item, professionIndex) => {
                                return professionIndex !== index
                            })
                            ">
                                <x-icons.trash class="h-5" />
                            </button>
                        </div>
                    </template>
                    <button type="button" class="text-sm font-bold text-primary hover:underline"
                        x-on:click="professions.push({id: null, name: '', key: Math.random().toString(36).substring(7) })">
                        {{ __('hiko.add_new_item') }}
                    </button>
                </div>
                <div x-data="{ categories: JSON.parse(document.getElementById('selectedCategories').innerHTML) }"
                    class="p-2 space-y-3 border rounded-md border-primary-light">
                    <p>
                        {{ __('hiko.professions_category') }}
                    </p>
                    @error('category')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                    <template x-for="category, index in categories" :key="category.key ? category.key : category.id">
                        <div class="flex">
                            <x-select name="category[]" class="block w-full mt-1" aria-label="{{ __('hiko.professions_category') }}"
                                x-data="ajaxSelect({url: '{{ route('ajax.professions.category') }}', element: $el, options: { id: category.id, name: category.name } })"
                                x-init="initSelect()">
                            </x-select>
                            <button type="button" class="ml-6 text-red-600"
                                aria-label="{{ __('hiko.remove_item') }}"
                                title="{{ __('hiko.remove_item') }}" x-on:click="
                            categories = categories.filter((item, categoryIndex) => {
                            return categoryIndex !== index
                        })
                        ">
                                <x-icons.trash class="h-5" />
                            </button>
                        </div>
                    </template>
                    <button type="button" class="text-sm font-bold text-primary hover:underline"
                        x-on:click="categories.push({id: null, name: '', key: Math.random().toString(36).substring(7) })">
                        {{ __('hiko.add_new_item') }}
                    </button>
                </div>
            </div>
        </template>
        <template x-if="type === 'institution'">
            <div class="required">
                <x-label for="name" :value="__('Název')" />
                <x-input id="name" class="block w-full mt-1" type="text" name="name"
                    :value="old('name', $identity->name)" required />
                @error('name')
                    <div class="text-red-600">{{ $message }}</div>
                @enderror
            </div>
        </template>
        <div>
            <x-label for="viaf_id" value="VIAF ID" />
            <x-input id="viaf_id" class="block w-full mt-1" type="text" name="viaf_id"
                :value="old('viaf_id', $identity->viaf_id)" />
            @error('viaf_id')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="note" :value="__('hiko.note')" />
            <x-textarea name="note" id="note" class="block w-full mt-1">{{ old('note', $identity->note) }}
            </x-textarea>
            @error('note')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($identity->id)
        <form x-data="{ form: $el }" action="{{ route('identities.destroy', $identity->id) }}" method="post"
            class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full"
                x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                {{ __('hiko.remove') }}
            </x-button-danger>
        </form>
    @endif
    @push('scripts')
        <script id="selectedProfessions" type="application/json">
            @json($selectedProfessions)
        </script>
        <script id="selectedCategories" type="application/json">
            @json($selectedCategories)
        </script>
    @endpush
</x-app-layout>
