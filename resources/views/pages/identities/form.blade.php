<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="{ form: $el }" @submit.prevent action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <x-button-simple type="button" @click="form.submit()" class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($place->id)
        <form action="{{ route('identities.destroy', $place->id) }}" method="post" class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full" x-data="" x-on:click="return confirm('Odstraní osobu / instituci! Pokračovat?')">
                {{ __('Odstranit osobu / instituci?') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
