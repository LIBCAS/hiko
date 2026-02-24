<x-app-layout :title="$title">
    <x-success-alert />
    <x-form-errors />
    @if (!empty($identity->id))
        <x-page-lock
            scope="global"
            resource-type="global_identity_edit"
            :resource-id="$identity->id"
            :redirect-url="route('identities')"
            :read-only-on-deny="true" />
    @endif

    <form x-data="identityForm({
        type: '{{ $identity->type ? $identity->type : 'person' }}',
        similarNamesUrl: '{{ route('ajax.identities.similar') }}',
        id: '{{ $identity->id }}',
        surname: '{{ $identity->surname }}',
        name: '{{ $identity->name }}',
        forename: '{{ $identity->forename }}'
    })" x-init="$watch('fullName', () => findSimilarNames($data))" action="{{ $action }}" method="post"
        class="max-w-lg space-y-6">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <livewire:identity-form-switcher
            :types="$types"
            :identityType="$selectedType"
            :identity="$identity"
            :selectedProfessions="$selectedProfessions"
            :selectedCategories="[]"
            :selectedReligions="$selectedReligions"
            :globalMode="true"
            professionFieldKey="professions"
            professionRoute="ajax.professions"
            :professionRouteParams="['scope' => 'global']"
            :professionLabel="__('hiko.global_profession')"
            :showCreateProfessionModal="false" />

        <livewire:related-identity-resources :resources="$identity->related_identity_resources" />

        <div>
            <x-label for="note" :value="__('hiko.note')" />
            <x-textarea name="note" id="note" rows="3" class="block w-full mt-1" style="min-height: 90px;">{{ old('note', $identity->note) }}</x-textarea>
        </div>

        <div class="space-y-2">
            <x-button-simple class="w-full" name="action" value="edit">
                {{ $label }}
            </x-button-simple>
            <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                {{ $label }} {{ __('hiko.and_create_new') }}
            </x-button-inverted>
        </div>
    </form>

    @if ($identity->id)
        @can('manage-users')
            <form x-data="{ form: $el }" action="{{ route('global.identities.destroy', $identity->id) }}" method="post" class="max-w-lg space-y-6">
                @csrf
                @method('DELETE')
                <x-button-danger class="w-full" x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                    {{ __('hiko.remove') }}
                </x-button-danger>
            </form>
        @endcan
    @endif
</x-app-layout>
