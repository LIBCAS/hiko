<div>
    <div class="relative" x-data="{ isVisible: true }" @click.away="isVisible = false">
        <x-input wire:model.debounce.50ms="search" class="block w-full mt-1" type="search" x-ref="search"
            @focus="isVisible = true" @keydown.escape.window="isVisible = false" @keydown="isVisible = true"
            @keydown.shift.tab="isVisible = false" />
        <x-heroicon-o-refresh wire:loading
            class="absolute top-0 right-0 h-5 mt-3 mr-4 text-primary-light motion-safe:animate-spin" />
        @if (strlen($search) >= 2)
            <div class="absolute z-50 w-full mt-1 text-sm bg-purple-100 rounded-md"
                x-show.transition.opacity.duration.200="isVisible">
                @if (count($searchResults) > 0)
                    <ul>
                        @foreach ($searchResults as $city)
                            <li class="border border-primary-dark">
                                <button type="button" class="w-full p-2 text-left"
                                    wire:click="selectCity({{ $city->id }}, {{ $city->latitude }}, {{ $city->longitude }})">
                                    {{ $city->name }} ({{ $city->adminName }} – {{ $city->country }})
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-3 py-3">{{ __('Nebyly nalezeny žádné výsledky')}}</div>
                @endif
            </div>
        @endif
    </div>
    @push('scripts')
        <script>
            Livewire.on('citySelected', data => {
                document.getElementById('latitude').value = data.latitude;
                document.getElementById('longitude').value = data.longitude;
                document.getElementById('geoname_id').value = data.id;
            })
        </script>
    @endpush
</div>
