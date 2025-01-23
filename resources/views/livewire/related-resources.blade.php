<div>
    <fieldset id="a-related-resource" class="space-y-6" wire:loading.attr="disabled">
        <legend class="text-lg font-semibold">
            {{ __('hiko.related_resources') }}
        </legend>

        @if (is_iterable($resources) && count($resources) > 0)
            @foreach ($resources as $index => $resource)
                <div wire:key="resource-{{ $index }}" class="p-3 space-y-6 bg-gray-200 shadow">
                    <div class="required">
                        <x-label for="resource_title-{{ $index }}" :value="__('hiko.name')" />
                        <x-input wire:model.live="resources.{{ $index }}.title"
                            name="related_resources[{{ $index }}][title]" id="resource_title-{{ $index }}"
                            :value="$resource['title'] ?? ''" class="block w-full mt-1" type="text" required />
                    </div>
                    <div>
                        <x-label for="resource_link-{{ $index }}" value="URL" />
                        <x-input wire:model.live="resources.{{ $index }}.link"
                            name="related_resources[{{ $index }}][link]" id="resource_link-{{ $index }}"
                            :value="$resource['link'] ?? ''" class="block w-full mt-1" type="url" />
                    </div>
                    <x-button-trash wire:click="removeItem({{ $index }})" />
                </div>
            @endforeach
        @else
            <p class="text-gray-500">{{ __('hiko.no_related_resources') }}</p>
        @endif

        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
    </fieldset>
</div>
