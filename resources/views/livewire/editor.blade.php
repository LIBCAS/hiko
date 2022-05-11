<div class="flex-1">
    <form x-data="editor()" x-init="initEditor(); window.livewire.on('saved', () => { initEditor() })" class="bg-white">
        <div id="editor" class="w-full max-w-full font-sans text-base prose">
            {!! $letter->content !!}
        </div>
        <x-button-simple x-on:click="$wire.save(getContent(), getPlainText())" type="button" class="w-full"
            wire:loading.attr="disabled">
            {{ __('hiko.save') }}
        </x-button-simple>
    </form>
    <div wire:loading>
        {{ __('hiko.saving') }}...
    </div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('dist/editor.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('dist/editor.js') }}"></script>
        @production
            <script>
                window.onbeforeunload = function() {
                    return '{{ __('hiko.confirm_leave') }}'
                }
            </script>
        @endproduction
    @endpush
</div>
