@push('scripts')
    <script src="{{ asset('dist/editor.js') }}"></script>
@endpush
<x-app-layout :title="$title">
    <ul class="flex flex-wrap mb-6 space-x-6 text-sm">
        <li>
            <a href="{{ route('letters.edit', $letter->id) }}" class="hover:underline">
                {{ __('Upravit dopis') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.show', $letter->id) }}" class="hover:underline">
                {{ __('Prohlédnout si dopis') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.images', $letter->id) }}" class="hover:underline">
                {{ __('Upravit přílohy') }}
            </a>
        </li>
    </ul>
    <div class="w-full md:flex md:space-x-6">
        <livewire:editor :letter="$letter" />
        <div class="flex-1">
            <div class="top-0 space-y-6 overflow-y-scroll border h-96 md:sticky">
                @foreach ($images as $image)
                    <div x-data="{open: false}" x-on:keydown.escape="open = false">
                        <button x-on:click="open = true" class="block border"
                            aria-label="{{ __('Zobrazit přílohu') }}">
                            <img src="{{ $image->getUrl() }}" alt="{{ __('Příloha') }}" loading="lazy"
                                class="w-full">
                        </button>
                        <div x-show="open" x-on:click="open = false" style="display:none"
                            class="fixed inset-0 z-50 p-4 bg-black bg-opacity-75">
                            <div class="flex justify-center w-full" x-on:click.away="open = false">
                                <img src="{{ $image->getUrl() }}" alt="{{ __('Příloha') }}" class="block border"
                                    loading="lazy">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>