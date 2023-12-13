<div>
    <fieldset id="a-related-identity-resource" class="p-3 space-y-6 shadow" wire:loading.attr="disabled">
        <legend class="text-lg font-semibold">
            {{ __('hiko.related_resources') }}
        </legend>
        @foreach ($resources as $resource)
            <div wire:key="{{ $loop->index }}" class="p-3 space-y-6 bg-gray-200 shadow">
                <div class="required">
                    <x-label for="resource_title-{{ $loop->index }}" :value="__('hiko.name_2')" />
                    <x-input wire:model.defer="resources.{{ $loop->index }}.title"
                        name="related_identity_resources[{{ $loop->index }}][title]" id="resource_title-{{ $loop->index }}"
                        :value="$resource['title']" class="block w-full mt-1" type="text" required />
                </div>
                <div>
                    <x-label for="resource_link-{{ $loop->index }}" value="URL" />
                    <x-input wire:model.defer="resources.{{ $loop->index }}.link"
                        name="related_identity_resources[{{ $loop->index }}][link]" id="resource_link-{{ $loop->index }}"
                        :value="$resource['link']" class="block w-full mt-1" type="text" type="url" />
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
    </fieldset>
</div>