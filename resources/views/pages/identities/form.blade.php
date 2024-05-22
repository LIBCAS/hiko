<x-app-layout :title="$title">
    <x-success-alert />
    <x-form-errors />
    <form x-data="identityForm({ type: '{{ $identity->type ? $identity->type : 'person' }}', similarNamesUrl: '{{ route('ajax.identities.similar') }}', id: '{{ $identity->id }}', surname: '{{ $identity->surname }}', name: '{{ $identity->name }}', forename: '{{ $identity->forename }}' })" x-init="$watch('fullName', () => findSimilarNames($data))" action="{{ $action }}" method="post"
        class="max-w-sm space-y-6">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <livewire:identity-form-switcher :types="$types" :identityType="$selectedType" :identity="$identity" :selectedProfessions="$selectedProfessions"
            :selectedCategories="$selectedCategories" />
        <livewire:related-identity-resources :resources="$identity->related_identity_resources" />
        <div>
            <x-label for="note" :value="__('hiko.note')" />
            <x-textarea name="note" id="note" class="block w-full mt-1">{{ old('note', $identity->note) }}
            </x-textarea>
            @error('note')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple class="w-full" name="action" value="edit">
            {{ $label }}
        </x-button-simple>
        <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
            {{ $label }} {{ __('hiko.and_create_new') }}
        </x-button-inverted>
    </form>
    @if ($canMerge)
        <x-merge-form :oldId="$identity->id" model="identity" route="{{ route('ajax.identities') }}" />
    @endif
    @can('delete-metadata')
        @if ($canRemove)
            <form x-data="{ form: $el }" action="{{ route('identities.destroy', $identity->id) }}" method="post"
                class="max-w-sm mt-8">
                @csrf
                @method('DELETE')
                <x-button-danger type="button" class="w-full"
                    x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                    {{ __('hiko.remove') }}
                </x-button-danger>
            </form>
        @endif
    @endcan
</x-app-layout>
