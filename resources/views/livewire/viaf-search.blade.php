<div>
    <div class="relative" x-data="{ isVisible: true }" @click.away="isVisible = false">
        <x-label for="search_viaf" :value="__('hiko.query')" />
        <div class="relative">
            <x-input wire:model.live.debounce.1000ms="search" id="search_viaf" class="block w-full mt-1" type="search" x-ref="search"
                @focus="isVisible = true" @keydown.escape.window="isVisible = false" @keydown="isVisible = true"
                @keydown.shift.tab="isVisible = false" />
            <x-icons.refresh wire:loading
                class="absolute top-0 right-0 h-5 mt-3 mr-4 text-primary-light motion-safe:animate-spin" />
        </div>
        @if (strlen($search) >= 2)
            <div class="absolute z-50 w-full mt-1 text-sm bg-purple-100 rounded-md"
                x-show.transition.opacity.duration.200="isVisible">
                <ul class="mb-8">
                    @foreach ($searchResults as $identity)
                        <li class="border border-primary-dark">
                            <button type="button" class="w-full p-2 text-left"
                                wire:click="selectIdentity({{ $identity['recordID'] }})">
                                {{ $identity['name'] }} ({{ $identity['type'] }})
                            </button>
                        </li>
                    @endforeach
                    <li class="w-full p-1 text-xs text-center text-white bg-primary">
                        {{ __('hiko.data_source') }}: VIAF
                    </li>
                </ul>
            </div>
            @if (!empty($error))
                <div class="px-1 py-3 text-sm text-red-700">{{ $error }}</div>
            @endif
        @endif
    </div>
    @push('scripts')
        <script>
            Livewire.on('identitySelected', data => {
                document.getElementById('viaf_id').value = data.id;
            })
        </script>
    @endpush
</div>
