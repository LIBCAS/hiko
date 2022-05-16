<x-app-layout :title="$title">
    <ul class="flex flex-wrap mb-6 space-x-6 text-sm">
        <li>
            <a href="{{ route('letters.edit', $letter->id) }}" class="text-primary hover:underline">
                {{ __('hiko.edit_letter') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.show', $letter->id) }}" class="text-primary hover:underline">
                {{ __('hiko.preview_letter') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.images', $letter->id) }}" class="text-primary hover:underline">
                {{ __('hiko.edit_attachments') }}
            </a>
        </li>
    </ul>
    <div class="w-full md:flex md:space-x-6">
        <livewire:editor :letter="$letter" />
        <div class="z-50 flex-1">
            <div class="top-0 space-y-6 overflow-y-scroll border h-96 md:sticky">
                @foreach ($images as $image)
                    <a href="{{ $image->getUrl() }}" target="_blank">
                        <img src="{{ $image->getUrl() }}" alt="{{ __('Příloha') }}" class="block border"
                            loading="lazy">
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
