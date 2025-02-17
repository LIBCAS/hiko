<div class="relative inline-block">
    <div class="flex items-center gap-4">
        <div class="space-y-2">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="block text-black">
                        {{ __('hiko.order_by') }}
                    </span>
                    <x-select wire:model.live="sorting.order" class="w-full px-2 text-sm">
                        @foreach ($sortingOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                </label>
                <div class="block text-sm">
                    <span class="block text-black">
                        {{ __('hiko.order_direction') }}
                    </span>
                    <div class="flex flex-col">
                        <label class="inline-flex items-center">
                            <x-radio name="direction" wire:model.live="sorting.direction" value="asc" />
                            <span class="ml-2 text-black">{{ __('hiko.ascending') }}</span>
                        </label>
                        <label class="inline-flex items-center">
                            <x-radio name="direction" wire:model.live="sorting.direction" value="desc" />
                            <span class="ml-2 text-black">{{ __('hiko.descending') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
