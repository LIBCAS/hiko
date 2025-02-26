<div>
    <fieldset id="a-related-identity-resource" class="p-4 space-y-3 bg-white rounded-lg shadow-md border border-gray-200" wire:loading.attr="disabled">
        <p class="text-lg font-semibold">
            {{ __('hiko.related_resources') }}
        </p>
        @foreach ($resources as $resource)
            <div class="space-y-3" wire:key="{{ $loop->index }}">
                <div class="required">
                    <x-label for="resource_title-{{ $loop->index }}" :value="__('hiko.name_2')" />
                    <x-input wire:model.live="resources.{{ $loop->index }}.title"
                        name="related_identity_resources[{{ $loop->index }}][title]" id="resource_title-{{ $loop->index }}"
                        :value="$resource['title']" class="block w-full mt-1" type="text" required />
                </div>
                <div>
                    <x-label for="resource_link-{{ $loop->index }}" value="URL" />
                    <x-input wire:model.live="resources.{{ $loop->index }}.link"
                        name="related_identity_resources[{{ $loop->index }}][link]" id="resource_link-{{ $loop->index }}"
                        :value="$resource['link']" class="block w-full mt-1" type="text" type="url" />
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            {{ __('hiko.add_new_item') }}
        </button>
    </fieldset>
</div>