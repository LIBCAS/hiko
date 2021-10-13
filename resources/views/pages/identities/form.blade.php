<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="{ form: $el, type: '{{ $identity->type ? $identity->type : 'person' }}' }" @submit.prevent
        action="{{ $action }}" method="post" class="max-w-sm space-y-3" autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="type" :value="__('Typ')" />
            <x-select x-model="type" id="type" class="block w-full mt-1" name="type" required>
                @foreach ($types as $key => $type)
                    <option value="{{ $key }}" {{ old('type', $identity->type) == $key ? 'selected' : '' }}>
                        {{ $type }}
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
                    <x-label for="surname" :value="__('Příjmení')" />
                    <x-input id="surname" class="block w-full mt-1" type="text" name="surname"
                        :value="old('surname', $identity->surname)" required />
                    @error('surname')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="forename" :value="__('Rodné jméno')" />
                    <x-input id="forename" class="block w-full mt-1" type="text" name="forename"
                        :value="old('forename', $identity->forename)" />
                    @error('forename')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex space-x-6">
                    <div>
                        <x-label for="birth_year" :value="__('Rok narození')" />
                        <x-input id="birth_year" class="block w-full mt-1" type="text" name="birth_year"
                            :value="old('birth_year', $identity->birth_year)" />
                        @error('birth_year')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="death_year" :value="__('Rok úmrtí')" />
                        <x-input id="death_year" class="block w-full mt-1" type="text" name="death_year"
                            :value="old('death_year', $identity->death_year)" />
                        @error('death_year')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div>
                    <x-label for="nationality" :value="__('Národnost')" />
                    <x-input id="nationality" class="block w-full mt-1" type="text" name="nationality"
                        :value="old('nationality', $identity->nationality)" />
                    @error('nationality')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="gender" :value="__('Pohlaví')" />
                    <x-input id="gender" class="block w-full mt-1" type="text" name="gender"
                        :value="old('gender', $identity->gender)" />
                    @error('gender')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="profession" :value="__('Profese')" />
                    <x-input id="profession" class="block w-full mt-1" type="text" name="profession"
                        :value="old('profession', $identity->profession)" />
                    @error('profession')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="profession_category" :value="__('Profese – kategorie')" />
                    <x-input id="profession_category" class="block w-full mt-1" type="text" name="profession_category"
                        :value="old('profession_category', $identity->_category)" />
                    @error('profession_category')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
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
            <x-label for="viaf_id" :value="__('VIAF ID')" />
            <x-input id="viaf_id" class="block w-full mt-1" type="text" name="viaf_id"
                :value="old('viaf_id', $identity->viaf_id)" required />
            @error('viaf_id')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="note" :value="__('Poznámka')" />
            <x-textarea name="note" id="note" class="block w-full mt-1">{{ old('note', $identity->note) }}</x-textarea>
            @error('note')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple type="button" @click="form.submit()" class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($identity->id)
        <form action="{{ route('identities.destroy', $identity->id) }}" method="post" class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full" x-data=""
                x-on:click="return confirm('Odstraní osobu / instituci! Pokračovat?')">
                {{ __('Odstranit osobu / instituci?') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
