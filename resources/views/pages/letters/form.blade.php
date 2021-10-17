<x-app-layout :title="$title">
    <x-success-alert />
    <form action="{{ $action }}" method="post" onkeydown="return event.key != 'Enter';" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <x-button-simple class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($identity->id)
        <form x-data="{ form: $el }" action="{{ route('letters.destroy', $letter->id) }}" method="post"
            class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full"
                x-on:click.prevent="if (confirm('Odstraní dopis! Pokračovat?')) form.submit()">
                {{ __('Odstranit dopis?') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
