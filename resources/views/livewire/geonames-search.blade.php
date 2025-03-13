<div>
    <div class="relative">
        <x-input wire:model.live="search" class="block w-full mt-1" type="search" id="search-input" />
        <x-icons.refresh wire:loading
            class="absolute top-0 right-0 h-5 mt-3 mr-4 text-primary-light motion-safe:animate-spin" />
        @if (strlen($search) >= 2)
            <div class="absolute z-50 w-full mt-1 text-sm bg-purple-100 rounded-md">
                <ul class="mb-8">
                    @foreach ($searchResults as $city)
                        <li class="border border-primary-dark">
                            <button type="button" class="w-full p-2 text-left"
                                wire:click="selectCity({{ $city['id'] }}, {{ $city['latitude'] }}, {{ $city['longitude'] }}, '{{$city['name']}}')">
                                {{ $city['name'] }} ({{ $city['adminName'] }} – {{ $city['country'] }})
                            </button>
                        </li>
                    @endforeach
                    <li class="w-full p-1 text-xs text-center text-white bg-primary">
                        {{ __('hiko.data_source') }}: GeoNames
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
            Livewire.on('citySelected', data => {
               Livewire.dispatch('updateCoordinates', data);
            });
        </script>
    @endpush
</div>
