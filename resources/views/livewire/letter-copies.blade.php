<div>
    <fieldset id="a-copies" class="space-y-6" wire:loading.attr="disabled">
        <legend class="text-lg font-semibold">
            {{ __('hiko.manifestation_location') }}
        </legend>
        @foreach ($copies as $item)
            <div class="p-3 space-y-6 bg-gray-200 shadow">
                <div>
                    <x-label for="preservation_{{ $loop->index }}" :value="__('hiko.preservation')" />
                    <x-select wire:model.defer="copies.{{ $loop->index }}.preservation"
                        name="copies[{{ $loop->index }}][preservation]" id="preservation_{{ $loop->index }}"
                        class="block w-full mt-1">
                        <option value="">
                            ---
                        </option>
                        @foreach ($copyValues['preservation'] as $cv)
                            <option value="{{ $cv }}">
                                {{ __('hiko.preservation_' . str_replace(' ', '_', $cv)) }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label for="type_{{ $loop->index }}" :value="__('hiko.doc_type')" />
                    <x-select wire:model.defer="copies.{{ $loop->index }}.type" id="type_{{ $loop->index }}"
                        name="copies[{{ $loop->index }}][type]" class="block w-full mt-1">
                        <option value="">
                            ---
                        </option>
                        @foreach ($copyValues['type'] as $cv)
                            <option value="{{ $cv }}">
                                {{ __('hiko.' . str_replace(' ', '_', $cv)) }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label for="copy_{{ $loop->index }}" :value="__('hiko.doc_mode')" />
                    <x-select wire:model.defer="copies.{{ $loop->index }}.copy" id="doc_mode_{{ $loop->index }}"
                        name="copies[{{ $loop->index }}][copy]" class="block w-full mt-1">
                        <option value="">
                            ---
                        </option>
                        @foreach ($copyValues['copy'] as $cv)
                            <option value="{{ $cv }}">
                                {{ __("hiko.{$cv}") }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label for="manifestation_notes_{{ $loop->index }}" :value="__('hiko.manifestation_notes')" />
                    <x-textarea wire:model.defer="copies.{{ $loop->index }}.manifestation_notes"
                        name="copies[{{ $loop->index }}][manifestation_notes]"
                        id="manifestation_notes_{{ $loop->index }}" class="block w-full mt-1">
                    </x-textarea>
                </div>
                <div>
                    <x-label for="l_number_{{ $loop->index }}" :value="__('hiko.l_number')" />
                    <x-input wire:model.defer="copies.{{ $loop->index }}.l_number"
                        name="copies[{{ $loop->index }}][l_number]" id="l_number_{{ $loop->index }}"
                        class="block w-full mt-1" type="text" />
                </div>
                <div>
                    <x-label for="repository_{{ $loop->index }}" :value="__('hiko.repository')" />
                    <x-input wire:model.defer="copies.{{ $loop->index }}.repository"
                        name="copies[{{ $loop->index }}][repository]" id="repository_{{ $loop->index }}"
                        class="block w-full mt-1" type="text" list="repository_datalist_{{ $loop->index }}" />
                    <datalist id="repository_datalist_{{ $loop->index }}">
                        @isset($locations['repository'])
                            @foreach ($locations['repository'] as $repository)
                                <option>
                                    {{ $repository['name'] }}
                                </option>
                            @endforeach
                        @endisset
                    </datalist>
                </div>
                <div>
                    <x-label for="archive_{{ $loop->index }}" :value="__('hiko.archive')" />
                    <x-input wire:model.defer="copies.{{ $loop->index }}.archive"
                        name="copies[{{ $loop->index }}][archive]" id="archive_{{ $loop->index }}"
                        class="block w-full mt-1" type="text" list="archive_datalist_{{ $loop->index }}" />
                    <datalist id="archive_datalist_{{ $loop->index }}">
                        @isset($locations['archive'])
                            @foreach ($locations['archive'] as $archive)
                                <option>
                                    {{ $archive['name'] }}
                                </option>
                            @endforeach
                        @endisset
                    </datalist>
                </div>
                <div>
                    <x-label for="collection_{{ $loop->index }}" :value="__('hiko.collection')" />
                    <x-input wire:model.defer="copies.{{ $loop->index }}.collection"
                        name="copies[{{ $loop->index }}][collection]" id="collection_{{ $loop->index }}"
                        class="block w-full mt-1" type="text" list="collection_datalist_{{ $loop->index }}" />
                    <datalist id="collection_datalist_{{ $loop->index }}">
                        @isset($locations['collection'])
                            @foreach ($locations['collection'] as $collection)
                                <option>
                                    {{ $collection['name'] }}
                                </option>
                            @endforeach
                        @endisset
                    </datalist>
                </div>
                <div>
                    <x-label for="signature_{{ $loop->index }}" :value="__('hiko.signature')" />
                    <x-input wire:model.defer="copies.{{ $loop->index }}.signature"
                        name="copies[{{ $loop->index }}][signature]" id="signature_{{ $loop->index }}"
                        class="block w-full mt-1" type="text" />
                </div>
                <div>
                    <x-label for="location_note_{{ $loop->index }}" :value="__('hiko.location_note')" />
                    <x-textarea wire:model.defer="copies.{{ $loop->index }}.location_note"
                        name="copies[{{ $loop->index }}][location_note]" id="location_note_{{ $loop->index }}"
                        class="block w-full mt-1">
                    </x-textarea>
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
        <livewire:create-new-item-modal :route="route('locations.create')" :text="__('hiko.modal_new_location')" />
    </fieldset>
</div>
