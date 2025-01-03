<div>
    <fieldset id="a-copies" class="p-3 space-y-6 shadow" wire:loading.attr="disabled">
        <legend class="text-lg font-semibold">
            {{ __('hiko.manifestation_location') }}
        </legend>

        @if (is_iterable($copies) && count($copies) > 0)
            @foreach ($copies as $index => $item)
                <div class="p-3 space-y-6 bg-gray-200 shadow">
                    <div>
                        <x-label for="ms_manifestation_{{ $index }}" :value="__('hiko.ms_manifestation')" />
                        <x-select wire:model="copies.{{ $index }}.ms_manifestation"
                            name="copies[{{ $index }}][ms_manifestation]" id="ms_manifestation_{{ $index }}"
                            class="block w-full mt-1">
                            <option value="">---</option>
                            @foreach ($copyValues['ms_manifestation'] ?? [] as $cv)
                                <option value="{{ $cv }}">
                                    {{ __("hiko.ms_manifestation_{$cv}") }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-label for="type_{{ $index }}" :value="__('hiko.doc_type')" />
                        <x-select wire:model="copies.{{ $index }}.type" id="type_{{ $index }}"
                            name="copies[{{ $index }}][type]" class="block w-full mt-1">
                            <option value="">---</option>
                            @foreach ($copyValues['type'] ?? [] as $cv)
                                <option value="{{ $cv }}">
                                    {{ __('hiko.' . str_replace(' ', '_', $cv)) }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-label for="preservation_{{ $index }}" :value="__('hiko.preservation')" />
                        <x-select wire:model="copies.{{ $index }}.preservation"
                            name="copies[{{ $index }}][preservation]" id="preservation_{{ $index }}"
                            class="block w-full mt-1">
                            <option value="">---</option>
                            @foreach ($copyValues['preservation'] ?? [] as $cv)
                                <option value="{{ $cv }}">
                                    {{ __('hiko.preservation_' . str_replace(' ', '_', $cv)) }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <x-button-trash wire:click="removeItem({{ $index }})" />
                </div>
            @endforeach
        @else
            <p>{{ __('hiko.no_copies') }}</p>
        @endif

        <livewire:create-new-item-modal :route="route('locations.create')" :text="__('hiko.modal_new_location')" />

        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
    </fieldset>
</div>
