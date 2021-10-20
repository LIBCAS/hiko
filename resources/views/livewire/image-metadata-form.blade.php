<div class="mt-12">
    <h2 class="font-bold uppercase">{{ __('Upravit vložené obrazové přílohy') }}</h2>
    <div x-data="{ sortlist: null }" x-init="
        sortlist = new Sortable($refs.list, {
            handle: '.handle',
            animation: 150,
            ghostClass: 'bg-gray-300',
            onUpdate: function(e) {
                $wire.reorder(this.toArray());
            }
        });
        ">
        <div class="max-w-3xl space-y-3 border border-primary-light" x-ref="list">
            @foreach ($attachedImages as $image)
                <div data-id="{{ $image->id }}" wire:key="{{ $image->id }}"
                    class="relative flex flex-wrap w-full p-3 space-x-6 border-b border-primary-light">
                    <div wire:loading>
                        <div
                            class="absolute top-0 right-0 flex items-center justify-center flex-1 w-full h-full bg-gray-100 border-b border-primary-light">
                            <svg class="h-24 animate-spin text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <button type="button" class="outline-none handle">
                        <x-icons.hand class="h-8 text-primary " />
                    </button>
                    <div x-data="{open: false}" x-on:keydown.escape="open = false">
                        <button x-on:click="open = true" class="block border"
                            aria-label="{{ __('Zobrazit přílohu') }}">
                            <img src="{{ $image->getUrl('thumb') }}" alt="{{ __('Příloha') }}" loading="lazy"
                                class="w-48">
                        </button>
                        <div x-show="open" x-on:click="open = false" style="display:none"
                            class="fixed inset-0 z-50 p-4 bg-black bg-opacity-75">
                            <div class="flex justify-center w-full" x-on:click.away="open = false">
                                <img src="{{ $image->getUrl() }}" alt="{{ __('Příloha') }}" class="block border"
                                    loading="lazy">
                            </div>
                        </div>
                    </div>
                    <div class="w-full max-w-sm">
                        <form class="w-full max-w-sm"
                            wire:submit.prevent="edit({{ $image->id }}, Object.fromEntries(new FormData($event.target)))">
                            <div>
                                <x-label for="description" :value="__('Popisek')" />
                                <x-textarea name="description" id="description" class="block w-full mt-1">
                                    {{ $image->getCustomProperty('description') }}</x-textarea>
                            </div>
                            <legend class="text-lg font-semibold">
                                {{ __('Viditelnost') }}
                            </legend>
                            <div>
                                <x-radio name="status" label="{{ __('Soukromé') }}" value="private"
                                    :checked="$image->getCustomProperty('status') === 'private'" name="status"
                                    required />
                            </div>
                            <div>
                                <x-radio name="status" label="{{ __('Veřejné') }}" value="publish"
                                    :checked="$image->getCustomProperty('status') === 'publish'" name="status"
                                    required />
                            </div>
                            <x-button-simple class="w-full">
                                {{ __('Upravit') }}
                            </x-button-simple>
                        </form>
                        <button wire:click="remove({{ $image->id }})" type="button"
                            class="inline-flex items-center mt-6 space-x-3 text-red-600">
                            <x-icons.trash /> <span class="text-sm">{{ __('Odstranit') }}</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
