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
                        {{ __('Autor') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-recipient">
                        {{ __('Příjemce') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-origin">
                        {{ __('Místo odeslání') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-destination">
                        {{ __('Místo určení') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-content">
                        {{ __('Popis obsahu') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 list-group-item hover:bg-gray-100" href="#a-related-resource">
                        {{ __('Související zdroje') }}
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
                class="max-w-sm -mt-3 space-y-6" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset
                <fieldset id="a-dates" class="space-y-6">
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
                        <x-checkbox name="date_inferred" label="{{ __('Datum je odvozené') }}"
                            :checked="boolval(old('date_inferred', $letter->date_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('Datum není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů') }}
                        </small>
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
                        <x-textarea name="date_note" id="date_note" class="block w-full mt-1">
                            {{ old('date_note', $letter->date_note) }}
                        </x-textarea>
                        @error('date_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div>
                    <hr class="my-6">
                </div>
                <fieldset id="a-author" class="space-y-6"
                    x-data="{ authors: JSON.parse(document.getElementById('selectedAuthors').innerHTML) }">
                    <legend class="text-lg font-semibold">
                        {{ __('Autor') }}
                    </legend>
                    <template x-for="author, index in authors" :key="author.key ? author.key : author.id">
                        <div class="p-3 space-y-6 border border-primary-light">
                            <div class="required">
                                <x-label x-bind:for="'name' + index" :value="__('Jméno autora')" />
                                <x-select name="author[]" class="block w-full mt-1" x-bind:id="'name' + index"
                                    x-data="ajaxSelect({url: '{{ route('ajax.identities') }}', element: $el, options: { id: author.id, name: author.name } })"
                                    x-init="initSelect()" required>
                                </x-select>
                            </div>
                            <div>
                                <x-label x-bind:for="'marked' + index" :value="__('Jméno použité v dopise')" />
                                <x-input x-bind:id="'marked' + index" x-bind:value="author.marked"
                                    class="block w-full mt-1" type="text" name="author_marked[]" />
                            </div>
                            <button type="button" class="inline-flex items-center mt-6 text-red-600"
                                x-on:click="authors = authors.filter((item, authorIndex) => { return authorIndex !== index })">
                                <x-heroicon-o-trash class="h-5" />
                                {{ __('Odstranit autora') }}
                            </button>
                        </div>
                    </template>
                    <div>
                        <button type="button" class="mb-3 text-sm font-bold text-primary hover:underline"
                            x-on:click="authors.push({id: null, name: '', key: Math.random().toString(36).substring(7) })">
                            {{ __('Přidat další') }}
                        </button>
                    </div>
                    <div>
                        <x-checkbox name="author_inferred" label="{{ __('Datum je odvozené') }}"
                            :checked="boolval(old('author_inferred', $letter->author_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('Jméno není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="author_uncertain" label="{{ __('Autor je nejistý') }}"
                            :checked="boolval(old('author_uncertain', $letter->author_uncertain))" />
                    </div>
                    <div>
                        <x-label for="author_note" :value="__('Poznámka k autorům')" />
                        <x-textarea name="author_note" id="author_note" class="block w-full mt-1">
                            {{ old('author_note', $letter->author_note) }}
                        </x-textarea>
                        @error('author_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div>
                    <hr class="my-6">
                </div>
                <fieldset id="a-recipient" class="space-y-6"
                    x-data="{ recipients: JSON.parse(document.getElementById('selectedRecipients').innerHTML) }">
                    <legend class="text-lg font-semibold">
                        {{ __('Příjemce') }}
                    </legend>
                    <template x-for="recipient, index in recipients"
                        :key="recipient.key ? recipient.key : recipient.id">
                        <div class="p-3 space-y-6 border border-primary-light">
                            <div class="required">
                                <x-label x-bind:for="'name' + index" :value="__('Jméno příjemce')" />
                                <x-select name="recipient[]" class="block w-full mt-1" x-bind:id="'name' + index"
                                    x-data="ajaxSelect({url: '{{ route('ajax.identities') }}', element: $el, options: { id: recipient.id, name: recipient.name } })"
                                    x-init="initSelect()" required>
                                </x-select>
                            </div>
                            <div>
                                <x-label x-bind:for="'marked' + index" :value="__('Jméno použité v dopise')" />
                                <x-input x-bind:id="'marked' + index" x-bind:value="recipient.marked"
                                    class="block w-full mt-1" type="text" name="recipient_marked[]" />
                            </div>
                            <div>
                                <x-label x-bind:for="'salutation' + index" :value="__('Oslovení')" />
                                <x-input x-bind:id="'salutation' + index" x-bind:value="recipient.salutation"
                                    class="block w-full mt-1" type="text" name="recipient_salutation[]" />
                            </div>
                            <button type="button" class="inline-flex items-center mt-6 text-red-600"
                                x-on:click="recipients = recipients.filter((item, recipientIndex) => { return recipientIndex !== index })">
                                <x-heroicon-o-trash class="h-5" />
                                {{ __('Odstranit příjemce') }}
                            </button>
                        </div>
                    </template>
                    <div>
                        <button type="button" class="mb-3 text-sm font-bold text-primary hover:underline"
                            x-on:click="recipients.push({id: null, name: '', key: Math.random().toString(36).substring(7) })">
                            {{ __('Přidat další') }}
                        </button>
                    </div>
                    <div>
                        <x-checkbox name="recipient_inferred" label="{{ __('Datum je odvozené') }}"
                            :checked="boolval(old('recipient_inferred', $letter->recipient_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('Jméno není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="recipient_uncertain" label="{{ __('Příjemce je nejistý') }}"
                            :checked="boolval(old('recipient_uncertain', $letter->recipient_uncertain))" />
                    </div>
                    <div>
                        <x-label for="recipient_note" :value="__('Poznámka k příjemcům')" />
                        <x-textarea name="recipient_note" id="recipient_note" class="block w-full mt-1">
                            {{ old('recipient_note', $letter->recipient_note) }}
                        </x-textarea>
                        @error('recipient_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div>
                    <hr class="my-6">
                </div>
                <fieldset id="a-origin" class="space-y-6"
                    x-data="{ origins: JSON.parse(document.getElementById('selectedOrigins').innerHTML) }">
                    <legend class="text-lg font-semibold">
                        {{ __('Místo odeslání') }}
                    </legend>
                    <template x-for="origin, index in origins" :key="origin.key ? origin.key : origin.id">
                        <div class="p-3 space-y-6 border border-primary-light">
                            <div class="required">
                                <x-label x-bind:for="'name' + index" :value="__('Jméno')" />
                                <x-select name="origin[]" class="block w-full mt-1" x-bind:id="'name' + index"
                                    x-data="ajaxSelect({url: '{{ route('ajax.places') }}', element: $el, options: { id: origin.id, name: origin.name } })"
                                    x-init="initSelect()" required>
                                </x-select>
                            </div>
                            <div>
                                <x-label x-bind:for="'marked' + index" :value="__('Jméno použité v dopise')" />
                                <x-input x-bind:id="'marked' + index" x-bind:value="origin.marked"
                                    class="block w-full mt-1" type="text" name="origin_marked[]" />
                            </div>
                            <button type="button" class="inline-flex items-center mt-6 text-red-600"
                                x-on:click="origins = origins.filter((item, originIndex) => { return originIndex !== index })">
                                <x-heroicon-o-trash class="h-5" />
                                {{ __('Odstranit místo odeslání') }}
                            </button>
                        </div>
                    </template>
                    <div>
                        <button type="button" class="mb-3 text-sm font-bold text-primary hover:underline"
                            x-on:click="origins.push({id: null, name: '', key: Math.random().toString(36).substring(7) })">
                            {{ __('Přidat další') }}
                        </button>
                    </div>
                    <div>
                        <x-checkbox name="origin_inferred" label="{{ __('Místo odeslání je odvozené') }}"
                            :checked="boolval(old('origin_inferred', $letter->origin_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('Jméno není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="origin_uncertain" label="{{ __('Místo odeslání je nejisté') }}"
                            :checked="boolval(old('origin_uncertain', $letter->origin_uncertain))" />
                    </div>
                    <div>
                        <x-label for="origin_note" :value="__('Poznámka k místu odeslání')" />
                        <x-textarea name="origin_note" id="origin_note" class="block w-full mt-1">
                            {{ old('origin_note', $letter->origin_note) }}
                        </x-textarea>
                        @error('origin_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div>
                    <hr class="my-6">
                </div>
                <fieldset id="a-destination" class="space-y-6"
                    x-data="{ destinations: JSON.parse(document.getElementById('selectedDestinations').innerHTML) }">
                    <legend class="text-lg font-semibold">
                        {{ __('Místo určení') }}
                    </legend>
                    <template x-for="destination, index in destinations"
                        :key="destination.key ? destination.key : destination.id">
                        <div class="p-3 space-y-6 border border-primary-light">
                            <div class="required">
                                <x-label x-bind:for="'name' + index" :value="__('Jméno')" />
                                <x-select name="destination[]" class="block w-full mt-1" x-bind:id="'name' + index"
                                    x-data="ajaxSelect({url: '{{ route('ajax.places') }}', element: $el, options: { id: destination.id, name: destination.name } })"
                                    x-init="initSelect()" required>
                                </x-select>
                            </div>
                            <div>
                                <x-label x-bind:for="'marked' + index" :value="__('Jméno použité v dopise')" />
                                <x-input x-bind:id="'marked' + index" x-bind:value="destination.marked"
                                    class="block w-full mt-1" type="text" name="destination_marked[]" />
                            </div>
                            <button type="button" class="inline-flex items-center mt-6 text-red-600"
                                x-on:click="destinations = destinations.filter((item, destinationIndex) => { return destinationIndex !== index })">
                                <x-heroicon-o-trash class="h-5" />
                                {{ __('Odstranit místo určení') }}
                            </button>
                        </div>
                    </template>
                    <div>
                        <button type="button" class="mb-3 text-sm font-bold text-primary hover:underline"
                            x-on:click="destinations.push({id: null, name: '', key: Math.random().toString(36).substring(7) })">
                            {{ __('Přidat další') }}
                        </button>
                    </div>
                    <div>
                        <x-checkbox name="destination_inferred" label="{{ __('Místo určení je odvozené') }}"
                            :checked="boolval(old('destination_inferred', $letter->destination_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('Jméno není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="destination_uncertain" label="{{ __('Místo určení je nejisté') }}"
                            :checked="boolval(old('destination_uncertain', $letter->destination_uncertain))" />
                    </div>
                    <div>
                        <x-label for="destination_note" :value="__('Poznámka k místu určení')" />
                        <x-textarea name="destination_note" id="destination_note" class="block w-full mt-1">
                            {{ old('destination_note', $letter->destination_note) }}
                        </x-textarea>
                        @error('destination_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div>
                    <hr class="my-6">
                </div>
                <fieldset id="a-content" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('Popis obsahu') }}
                    </legend>
                    <div>
                        <x-label for="languages" :value="__('Jazyk')" />
                        <x-select x-data="select({element: $el })" x-init="initSelect()" id="languages"
                            class="block w-full mt-1" name="languages[]" multiple>
                            @foreach ($languages as $language)
                                <option value="{{ $language->name }}" @if (in_array($language->name, $selectedLanguages)) selected @endif>
                                    {{ $language->name }}
                                </option>
                            @endforeach
                        </x-select>
                        @error('languages')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="keyword" :value="__('Klíčová slova')" />
                        <x-select name="keyword[]" class="block w-full mt-1" id="keyword"
                            x-data="ajaxSelect({url: '{{ route('ajax.keywords') }}', element: $el, options: JSON.parse(document.getElementById('selectedKeywords').innerHTML) })"
                            x-init="initSelect()" multiple>
                        </x-select>
                        @error('keyword')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="abstract_cs" :value="__('Abstrakt CS')" />
                        <x-textarea name="abstract_cs" id="abstract_cs" class="block w-full mt-1">
                            {{ old('abstract_cs', $letter->translations['abstract']['cs'] ?? '') }}
                        </x-textarea>
                        @error('abstract_cs')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="abstract_en" :value="__('Abstrakt EN')" />
                        <x-textarea name="abstract_en" id="abstract_en" class="block w-full mt-1">
                            {{ old('abstract_en', $letter->translations['abstract']['en'] ?? '') }}
                        </x-textarea>
                        @error('abstract_en')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="incipit" :value="__('Incipit')" />
                        <x-textarea name="incipit" id="incipit" class="block w-full mt-1">
                            {{ old('incipit', $letter->incipit) }}
                        </x-textarea>
                        @error('incipit')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="explicit" :value="__('Explicit')" />
                        <x-textarea name="explicit" id="explicit" class="block w-full mt-1">
                            {{ old('explicit', $letter->explicit) }}
                        </x-textarea>
                        @error('explicit')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="mentioned" :value="__('Zmíněné osoby')" />
                        <x-select name="mentioned[]" class="block w-full mt-1" id="mentioned"
                            x-data="ajaxSelect({url: '{{ route('ajax.identities') }}', element: $el, options: JSON.parse(document.getElementById('selectedMentioned').innerHTML) })"
                            x-init="initSelect()" multiple>
                        </x-select>
                        @error('mentioned')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="people_mentioned_note" :value="__('Poznámka ke zmíněným osobám')" />
                        <x-textarea name="people_mentioned_note" id="people_mentioned_note" class="block w-full mt-1">
                            {{ old('people_mentioned_note', $letter->people_mentioned_note) }}
                        </x-textarea>
                        @error('people_mentioned_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="notes_private" :value="__('Poznámka pro zpracovatele')" />
                        <x-textarea name="notes_private" id="notes_private" class="block w-full mt-1">
                            {{ old('notes_private', $letter->notes_private) }}
                        </x-textarea>
                        @error('notes_private')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="notes_public" :value="__('Veřejná poznámka')" />
                        <x-textarea name="notes_public" id="notes_public" class="block w-full mt-1">
                            {{ old('notes_public', $letter->notes_public) }}
                        </x-textarea>
                        @error('notes_public')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div>
                    <hr class="my-6">
                </div>
                <fieldset id="a-related-resource" class="space-y-6"
                    x-data="{ relatedResources: JSON.parse(document.getElementById('relatedResources').innerHTML)}">
                    <legend class="text-lg font-semibold">
                        {{ __('Související zdroje') }}
                    </legend>
                    <template x-for="resource, index in relatedResources" :key="resource.key ? resource.key : index">
                        <div class="p-3 space-y-6 border border-primary-light">
                            <div class="required">
                                <x-label x-bind:for="'resource_title' + index" :value="__('Název')" />
                                <x-input x-bind:id="'resource_title' + index" x-bind:value="resource.title"
                                    class="block w-full mt-1" type="text" name="resource_title[]" required />
                            </div>
                            <div>
                                <x-label x-bind:for="'resource_link' + index" :value="__('URL')" />
                                <x-input x-bind:id="'resource_link' + index" x-bind:value="resource.link"
                                    class="block w-full mt-1" type="text" name="resource_link[]" type="url" />
                            </div>
                            <button type="button" class="inline-flex items-center mt-6 text-red-600"
                                x-on:click="relatedResources = relatedResources.filter((item, resourceIndex) => { return resourceIndex !== index })">
                                <x-heroicon-o-trash class="h-5" />
                                {{ __('Odstranit zdroj') }}
                            </button>
                        </div>
                    </template>
                    <div>
                        <button type="button" class="mb-3 text-sm font-bold text-primary hover:underline"
                            x-on:click="relatedResources.push({title: '', link: '', key: Math.random().toString(36).substring(7) })">
                            {{ __('Přidat další') }}
                        </button>
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
    @push('scripts')
        <script id="selectedAuthors" type="application/json">
            @json($selectedAuthors)
        </script>
        <script id="selectedRecipients" type="application/json">
            @json($selectedRecipients)
        </script>
        <script id="selectedOrigins" type="application/json">
            @json($selectedOrigins)
        </script>
        <script id="selectedDestinations" type="application/json">
            @json($selectedDestinations)
        </script>
        <script id="selectedKeywords" type="application/json">
            @json($selectedKeywords)
        </script>
        <script id="selectedKeywords" type="application/json">
            @json($selectedKeywords)
        </script>
        <script id="selectedMentioned" type="application/json">
            @json($selectedMentioned)
        </script>
        <script id="relatedResources" type="application/json">
            @json($selectedRelatedResources)
        </script>
    @endpush
</x-app-layout>
