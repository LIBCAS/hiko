<div x-data="{ open: false }" class="max-w-sm mt-8 bg-gray-200 border rounded-md shadow">
    <button type="button" @click="open = !open"
        class="inline-flex items-center p-3 text-sm font-semibold text-primary hover:underline">
        <x-icons.inbox-in class="h-4 mr-2" /><span>{{ __('hiko.merge') }}</span>
    </button>
    <span x-show="open" x-transition.duration.500ms>
        <form x-data="{ form: $el }" action="{{ route('merge') }}" method="post" class="px-3 pb-3 space-y-6">
            @csrf
            @method('POST')
            <div class="required">
                <x-label for="merge" value="{{ __('hiko.merge_field') }}" />
                <x-select x-data="ajaxChoices({ url: '{{ $route }}', element: $el })" x-init="initSelect();" name="newId" id="merge"
                    class="block w-full mt-1" required>
                </x-select>
            </div>
            <input type="hidden" name="oldId" value="{{ $oldId }}">
            <input type="hidden" name="model" value="{{ $model }}">
            <x-button-danger type="button" class="w-full" x-on:click="handleMergeForm(form)">
                {{ __('hiko.merge') }}
            </x-button-danger>
        </form>
    </span>
</div>

@push('scripts')
    <script>
        function handleMergeForm(form) {
            if (!form.checkValidity()) {
                return alert('{{ __('hiko.merge_field_required') }}');
            };

            if (confirm('{{ __('hiko.confirm_merge') }}')) {
                form.submit();
            }
        }
    </script>
@endpush
