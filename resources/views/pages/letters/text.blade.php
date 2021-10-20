@push('scripts')
    <script src="{{ asset('dist/editor.js') }}"></script>
@endpush
<x-app-layout :title="$title">
    <livewire:editor :letter="$letter" />
</x-app-layout>
