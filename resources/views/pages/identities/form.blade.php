<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="identityForm({ type: '{{ $identity->type ? $identity->type : 'person' }}', similarNamesUrl: '{{ route('ajax.identities.similar') }}', id: '{{ $identity->id }}', surname: '{{ $identity->surname }}', name: '{{ $identity->name }}', forename: '{{ $identity->forename }}' })" x-init="$watch('fullName', () => findSimilarNames($data))" action="{{ $action }}" method="post"
        onkeydown="return event.key != 'Enter';" class="max-w-sm space-y-6">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <livewire:identity-form-switcher :types="$types" :identityType="$selectedType" :identity="$identity" :selectedProfessions="$selectedProfessions"
            :selectedCategories="$selectedCategories" />
        <div x-data="{ open: false }" class="p-3 bg-gray-200 border rounded-md shadow">
            <button type="button" @click="open = !open"
                class="inline-flex items-center text-sm font-semibold text-primary hover:underline">
                <x-icons.user-group class="h-4 mr-2" /> <span>{{ __('hiko.search_viaf') }}</span>
            </button>
            <span x-show="open" x-transition.duration.500ms>
                <livewire:viaf-search />
            </span>
        </div>
        <div>
            <x-label for="viaf_id" value="VIAF ID" />
            <x-input id="viaf_id" class="block w-full mt-1" type="text" name="viaf_id" :value="old('viaf_id', $identity->viaf_id)" />
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
</x-app-layout>
