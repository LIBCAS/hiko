@push('styles')
    <link rel="stylesheet" href="{{ asset('dist/images.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('dist/images.js') }}"></script>
@endpush
<x-app-layout :title="$title">
    <livewire:image-form :letter="$letter" />
    <livewire:image-metadata-form :letter="$letter" />
</x-app-layout>
