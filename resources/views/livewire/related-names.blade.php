<div>
    <fieldset id="a-related-name" class="p-3 space-y-3 rounded-md shadow" wire:loading.attr="disabled">
        <p class="text-lg font-semibold">
            {{ __('hiko.related_names') }}
        </p>
        @foreach ($related_names as $related_name)
            <div wire:key="{{ $loop->index }}" class="p-3 space-y-6 bg-gray-200 shadow">
                <div>
                    <x-label for="related_name_surname-{{ $loop->index }}" :value="__('hiko.surname')" />
                    <x-input wire:model.live="related_names.{{ $loop->index }}.surname"
                             name="related_names[{{ $loop->index }}][surname]" id="related_name_surname-{{ $loop->index }}"
                             :value="$related_name['surname'] ?? ''" class="block w-full mt-1" type="text" />
                </div>
                <div>
                    <x-label for="related_name_forename-{{ $loop->index }}" :value="__('hiko.forename')" />
                    <x-input wire:model.live="related_names.{{ $loop->index }}.forename"
                             name="related_names[{{ $loop->index }}][forename]" id="related_name_forename-{{ $loop->index }}"
                             :value="$related_name['forename'] ?? ''" class="block w-full mt-1" type="text" />
                </div>
                <div>
                    <x-label for="related_name_general_name_modifier-{{ $loop->index }}" :value="__('hiko.general_name_modifier')" />
                    <x-input wire:model.live="related_names.{{ $loop->index }}.general_name_modifier"
                             name="related_names[{{ $loop->index }}][general_name_modifier]" id="related_name_general_name_modifier-{{ $loop->index }}"
                             :value="$related_name['general_name_modifier'] ?? ''" class="block w-full mt-1" type="text" />
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
    </fieldset>
</div>
