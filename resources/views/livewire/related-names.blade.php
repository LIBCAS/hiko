<div>
    <fieldset id="a-related-name" class="p-4 space-y-3 bg-white rounded-lg shadow-md border border-gray-200" wire:loading.attr="disabled">
        <p class="text-lg font-semibold">
            {{ __('hiko.related_names') }}
        </p>
        @foreach ($related_names as $related_name)
            <div class="space-y-3" wire:key="{{ $loop->index }}">
                <div>
                    <x-label for="related_name_surname-{{ $loop->index }}" :value="__('hiko.surname')" />
                    <x-input wire:model.defer="related_names.{{ $loop->index }}.surname"
                             name="related_names[{{ $loop->index }}][surname]" id="related_name_surname-{{ $loop->index }}"
                             :value="$related_name['surname'] ?? ''" class="block w-full mt-1" type="text" />
                </div>
                <div>
                    <x-label for="related_name_forename-{{ $loop->index }}" :value="__('hiko.forename')" />
                    <x-input wire:model.defer="related_names.{{ $loop->index }}.forename"
                             name="related_names[{{ $loop->index }}][forename]" id="related_name_forename-{{ $loop->index }}"
                             :value="$related_name['forename'] ?? ''" class="block w-full mt-1" type="text" />
                </div>
                <div>
                    <x-label for="related_name_general_name_modifier-{{ $loop->index }}" :value="__('hiko.general_name_modifier')" />
                    <x-input wire:model.defer="related_names.{{ $loop->index }}.general_name_modifier"
                             name="related_names[{{ $loop->index }}][general_name_modifier]" id="related_name_general_name_modifier-{{ $loop->index }}"
                             :value="$related_name['general_name_modifier'] ?? ''" class="block w-full mt-1" type="text" />
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
            </svg>
            {{ __('hiko.add_new_item') }}
        </button>
    </fieldset>
</div>
