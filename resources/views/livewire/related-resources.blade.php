<div>
    <fieldset id="a-related-resource" class="space-y-6" wire:loading.attr="disabled">
        <legend class="text-lg font-semibold">
            {{ __('hiko.related_resources') }}
        </legend>
        @foreach ($resources as $resource)
            <div wire:key="{{ $loop->index }}" class="p-3 space-y-6 border border-primary-light">
                <div class="required">
                    <x-label for="resource_title-{{ $loop->index }}" :value="__('hiko.name')" />
                    <x-input wire:model.lazy="resources.{{ $loop->index }}.title"
                        id="resource_title-{{ $loop->index }}" :value="$resource['title']" class="block w-full mt-1"
                        type="text" required />
                </div>
                <div>
                    <x-label for="resource_link-{{ $loop->index }}" value="URL" />
                    <x-input wire:model.lazy="resources.{{ $loop->index }}.link"
                        id="resource_link-{{ $loop->index }}" :value="$resource['link']" class="block w-full mt-1"
                        type="text" type="url" />
                </div>
                <button wire:click="removeItem({{ $loop->index }})" type="button"
                    class="inline-flex items-center mt-6 text-red-600">
                    <x-icons.trash class="h-5" />
                    {{ __('hiko.remove_item') }}
                </button>
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
        @if (!empty($resources[0]['title']))
            <input type="hidden" name="related_resources" value="{!! htmlspecialchars(json_encode($resources), ENT_QUOTES, 'UTF-8') !!}">
        @endif
    </fieldset>
</div>
