<x-app-layout :title="$title">
    <div class="md:flex md:space-x-16">
        <div class="hidden md:block">
            <ul class="sticky text-gray-600 bg-white border rounded-md top-16 border-primary-light">
                <li class="pt-1 border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-dates">
                        {{ __('Datum') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-author">
                        Author
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-recipient">
                        Recipient
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-origin">
                        Origin
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-destination">
                        Destination
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-content">
                        Content
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-related-resource">
                        Related resources
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-copies">
                        Manifestations and repositories
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-description">
                        Description
                    </a>
                </li>
                <li class="pb-1 ">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-status">
                        Status
                    </a>
                </li>

            </ul>
        </div>
        <div>
            <x-success-alert />
            <form action="{{ $action }}" method="post" onkeydown="return event.key != 'Enter';"
                class="max-w-sm -mt-3 space-y-3" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset
                <fieldset id="a-dates" class="space-y-3">
                    <legend class="text-lg font-semibold">
                        {{ __('Datum') }}
                    </legend>
                    <div class="flex space-x-6">
                        <div>
                            <x-label for="date_year" :value="__('Rok')" />
                            <x-input id="date_year" class="block w-full mt-1" type="text" name="date_year"
                                :value="old('date_year', $letter->date_year)" min="0" max="{{ date('Y') }}"
                                type="number" />
                            @error('date_year')
                                <div class="text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-label for="date_month" :value="__('Měsíc')" />
                            <x-input id="date_month" class="block w-full mt-1" type="text" name="date_month"
                                :value="old('date_month', $letter->date_month)" min="1" max="12" type="number" />
                            @error('date_month')
                                <div class="text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-label for="date_day" :value="__('Den')" />
                            <x-input id="date_day" class="block w-full mt-1" type="text" name="date_day"
                                :value="old('date_day', $letter->date_day)" min="1" max="31" type="number" />
                            @error('date_day')
                                <div class="text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <x-label for="date_marked" :value="__('Datum označené v dopise')" />
                        <x-input id="date_marked" class="block w-full mt-1" type="text" name="date_marked"
                            :value="old('date_day', $letter->date_marked)" />
                        @error('date_marked')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-checkbox name="date_uncertain" label="{{ __('Datum je nejisté') }}"
                            :checked="boolval(old('date_uncertain', $letter->date_uncertain))" />
                    </div>
                    <div>
                        <x-checkbox name="date_approximate" label="{{ __('Datum je přibližné') }}"
                            :checked="boolval(old('date_approximate', $letter->date_approximate))" />
                    </div>
                    <div>
                        <x-checkbox name="date_inferred" label="{{ __('Datum je vyvozené') }}"
                            :checked="boolval(old('date_inferred', $letter->date_inferred))" />
                    </div>
                    <div
                        x-data="{ isRange: {{ var_export(boolval(old('date_day', $letter->date_is_range)), true) }} }">
                        <x-checkbox name="date_is_range" label="{{ __('Datum je uvedené v rozmezí') }}"
                            x-model="isRange" :checked="boolval(old('date_day', $letter->date_is_range))" />
                        <div class="flex space-x-6">
                            <div x-show="isRange">
                                <x-label for="range_year" :value="__('Rok 2')" />
                                <x-input id="range_year" class="block w-full mt-1" type="text" name="range_year"
                                    :value="old('range_year', $letter->range_year)" min="0" max="{{ date('Y') }}"
                                    type="number" />
                                @error('range_year')
                                    <div class="text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div x-show="isRange">
                                <x-label for="range_month" :value="__('Měsíc 2')" />
                                <x-input id="range_month" class="block w-full mt-1" type="text" name="range_month"
                                    :value="old('range_month', $letter->range_month)" min="1" max="12" type="number" />
                                @error('range_month')
                                    <div class="text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div x-show="isRange">
                                <x-label for="range_day" :value="__('Den 2')" />
                                <x-input id="range_day" class="block w-full mt-1" type="text" name="range_day"
                                    :value="old('range_day', $letter->range_day)" min="1" max="31" type="number" />
                                @error('range_day')
                                    <div class="text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div>
                        <x-label for="date_note" :value="__('Poznámka k datu')" />
                        <x-textarea name="date_note" id="date_note" class="block w-full mt-1">{{ old('date_note', $letter->date_note) }}
                        </x-textarea>
                        @error('date_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <x-button-simple class="w-full">
                    {{ $label }}
                </x-button-simple>
            </form>
            @if ($letter->id)
                <form x-data="{ form: $el }" action="{{ route('letters.destroy', $letter->id) }}" method="post"
                    class="max-w-sm mt-8">
                    @csrf
                    @method('DELETE')
                    <x-button-danger class="w-full"
                        x-on:click.prevent="if (confirm('Odstraní dopis! Pokračovat?')) form.submit()">
                        {{ __('Odstranit dopis?') }}
                    </x-button-danger>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
