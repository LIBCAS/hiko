@push('styles')
    <link rel="stylesheet" href="{{ asset('dist/images.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('dist/images.js') }}"></script>
@endpush
<x-app-layout :title="$title">
    <ul class="flex flex-wrap mb-6 space-x-6 text-sm">
        <li>
            <a href="{{ route('letters.edit', $letter->id) }}" class="hover:underline">
                {{ __('Upravit dopis') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.text', $letter->id) }}" class="hover:underline">
                {{ __('Upravit plný text') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.show', $letter->id) }}" class="hover:underline">
                {{ __('Prohlédnout si dopis') }}
            </a>
        </li>
    </ul>
    <livewire:image-form :letter="$letter" />
    <livewire:image-metadata-form :letter="$letter" />
</x-app-layout>
