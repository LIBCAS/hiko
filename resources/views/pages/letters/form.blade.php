<x-app-layout :title="$title">
    @if ($letter->id)
        <ul class="flex flex-wrap mb-6 space-x-6 text-sm">
            <li>
                <a href="{{ route('letters.images', $letter->id) }}" class="text-primary hover:underline">
                    {{ __('hiko.edit_attachments') }}
                </a>
            </li>
            <li>
                <a href="{{ route('letters.text', $letter->id) }}" class="text-primary hover:underline">
                    {{ __('hiko.edit_full_text') }}
                </a>
            </li>
            <li>
                <a href="{{ route('letters.show', $letter->id) }}" class="text-primary hover:underline">
                    {{ __('hiko.preview_letter') }}
                </a>
            </li>
        </ul>
    @endif
    <div class="md:flex md:space-x-6">
        <div class="hidden md:block">
            <ul class="sticky text-gray-700 bg-white rounded-md shadow-lg top-16">
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-dates">
                        {{ __('hiko.date') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-author">
                        {{ __('hiko.author') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-recipient">
                        {{ __('hiko.recipient') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-origin">
                        {{ __('hiko.origin') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-destination">
                        {{ __('hiko.destination') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-content">
                        {{ __('hiko.content_description') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-related-resource">
                        {{ __('hiko.related_resources') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-copies">
                        {{ __('hiko.manifestation_location') }}
                    </a>
                </li>
                <li class="border-b border-primary-light">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-copyright">
                        {{ __('hiko.copyright') }}
                    </a>
                </li>
                <li class="">
                    <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-status">
                        {{ __('hiko.status') }}
                    </a>
                </li>
                @can('delete-metadata')
                    <li class="border-t border-primary-light">
                        <a class="block w-full px-3 py-1 hover:bg-gray-200" href="#a-approval">
                            {{ __('hiko.approval') }}
                        </a>
                    </li>
                @endcan
            </ul>
        </div>
        <div class="p-6 bg-white rounded-lg shadow-lg md:w-1/2">
            @if (session()->has('success') || $errors->any())
                <div class="pb-3">
                    <x-success-alert />
                    <x-form-errors />
                </div>
            @endif
            <form action="{{ $action }}" method="post" class="space-y-6 md:-mt-6" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset
                <fieldset id="a-dates" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.date') }}
                    </legend>
                    <div class="flex space-x-6">
                        <div>
                            <x-label for="date_year" :value="__('hiko.year')" />
                            <x-input id="date_year" class="block w-full mt-1" type="text" name="date_year"
                                :value="old('date_year', $letter->date_year)" max="{{ date('Y') }}" type="number" />
                            @error('date_year')
                                <div class="text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-label for="date_month" :value="__('hiko.month')" />
                            <x-input id="date_month" class="block w-full mt-1" type="text" name="date_month"
                                :value="old('date_month', $letter->date_month)" min="0" max="12" type="number" />
                            @error('date_month')
                                <div class="text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-label for="date_day" :value="__('hiko.day')" />
                            <x-input id="date_day" class="block w-full mt-1" type="text" name="date_day"
                                :value="old('date_day', $letter->date_day)" min="0" max="31" type="number" />
                            @error('date_day')
                                <div class="text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <x-label for="date_marked" :value="__('hiko.date_marked')" />
                        <x-input id="date_marked" class="block w-full mt-1" type="text" name="date_marked"
                            :value="old('date_day', $letter->date_marked)" />
                        @error('date_marked')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-checkbox name="date_uncertain" label="{{ __('hiko.date_uncertain') }}" :checked="boolval(old('date_uncertain', $letter->date_uncertain))" />
                    </div>
                    <div>
                        <x-checkbox name="date_approximate" label="{{ __('hiko.date_approximate') }}"
                            :checked="boolval(old('date_approximate', $letter->date_approximate))" />
                    </div>
                    <div>
                        <x-checkbox name="date_inferred" label="{{ __('hiko.date_inferred') }}" :checked="boolval(old('date_inferred', $letter->date_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('hiko.date_inferred_help') }}
                        </small>
                    </div>
                    <div x-data="{ isRange: {{ var_export(boolval(old('date_day', $letter->date_is_range)), true) }} }">
                        <x-checkbox name="date_is_range" label="{{ __('hiko.date_is_range') }}" x-model="isRange"
                            :checked="boolval(old('date_day', $letter->date_is_range))" />
                        <div class="flex space-x-6">
                            <div x-show="isRange">
                                <x-label for="range_year" :value="__('hiko.year') . ' 2'" />
                                <x-input id="range_year" class="block w-full mt-1" type="text" name="range_year"
                                    :value="old('range_year', $letter->range_year)" max="{{ date('Y') }}" type="number" />
                                @error('range_year')
                                    <div class="text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div x-show="isRange">
                                <x-label for="range_month" :value="__('hiko.month') . ' 2'" />
                                <x-input id="range_month" class="block w-full mt-1" type="text"
                                    name="range_month" :value="old('range_month', $letter->range_month)" min="0" max="12"
                                    type="number" />
                                @error('range_month')
                                    <div class="text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div x-show="isRange">
                                <x-label for="range_day" :value="__('hiko.day') . ' 2'" />
                                <x-input id="range_day" class="block w-full mt-1" type="text" name="range_day"
                                    :value="old('range_day', $letter->range_day)" min="0" max="31" type="number" />
                                @error('range_day')
                                    <div class="text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div>
                        <x-label for="date_note" :value="__('hiko.date_note')" />
                        <x-textarea name="date_note" id="date_note" class="block w-full mt-1">
                            {{ old('date_note', $letter->date_note) }}
                        </x-textarea>
                        @error('date_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div class="h-1"></div>
                <fieldset id="a-author" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.author') }}
                    </legend>
                    <livewire:letter-meta-field :items="$selectedAuthors" fieldKey="authors" route="ajax.identities"
                        :label="__('hiko.author_name')" :fields="[['label' => __('hiko.name_marked'), 'key' => 'marked']]" />
                    <div>
                        <x-checkbox name="author_inferred" label="{{ __('hiko.author_inferred') }}"
                            :checked="boolval(old('author_inferred', $letter->author_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('hiko.name_inferred_help') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="author_uncertain" label="{{ __('hiko.author_uncertain') }}"
                            :checked="boolval(old('author_uncertain', $letter->author_uncertain))" />
                    </div>
                    <div>
                        <x-label for="author_note" :value="__('hiko.author_note')" />
                        <x-textarea name="author_note" id="author_note" class="block w-full mt-1">
                            {{ old('author_note', $letter->author_note) }}
                        </x-textarea>
                        @error('author_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div class="h-1"></div>
                <fieldset id="a-recipient" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.recipient') }}
                    </legend>
                    <livewire:letter-meta-field :items="$selectedRecipients" fieldKey="recipients" route="ajax.identities"
                        :label="__('hiko.recipient_name')" :fields="[
                            ['label' => __('hiko.name_marked'), 'key' => 'marked'],
                            ['label' => __('hiko.salutation'), 'key' => 'salutation'],
                        ]" />
                    <div>
                        <x-checkbox name="recipient_inferred" label="{{ __('hiko.recipient_inferred') }}"
                            :checked="boolval(old('recipient_inferred', $letter->recipient_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('hiko.name_inferred_help') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="recipient_uncertain" label="{{ __('hiko.recipient_uncertain') }}"
                            :checked="boolval(old('recipient_uncertain', $letter->recipient_uncertain))" />
                    </div>
                    <div>
                        <x-label for="recipient_note" :value="__('hiko.recipient_note')" />
                        <x-textarea name="recipient_note" id="recipient_note" class="block w-full mt-1">
                            {{ old('recipient_note', $letter->recipient_note) }}
                        </x-textarea>
                        @error('recipient_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div class="h-1"></div>
                <fieldset id="a-origin" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.origin') }}
                    </legend>
                    <livewire:letter-meta-field :items="$selectedOrigins" fieldKey="origins" route="ajax.places"
                        :label="__('hiko.name')" :fields="[['label' => __('hiko.name_marked'), 'key' => 'marked']]" />
                    <livewire:create-new-item-modal :route="route('places.create')" :text="__('hiko.modal_new_place')" />
                    <div>
                        <x-checkbox name="origin_inferred" label="{{ __('hiko.origin_inferred') }}"
                            :checked="boolval(old('origin_inferred', $letter->origin_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('hiko.name_inferred_help') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="origin_uncertain" label="{{ __('hiko.origin_uncertain') }}"
                            :checked="boolval(old('origin_uncertain', $letter->origin_uncertain))" />
                    </div>
                    <div>
                        <x-label for="origin_note" :value="__('hiko.origin_note')" />
                        <x-textarea name="origin_note" id="origin_note" class="block w-full mt-1">
                            {{ old('origin_note', $letter->origin_note) }}
                        </x-textarea>
                        @error('origin_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div class="h-1"></div>
                <fieldset id="a-destination" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.destination') }}
                    </legend>
                    <livewire:letter-meta-field :items="$selectedDestinations" fieldKey="destinations" route="ajax.places"
                        :label="__('hiko.name')" :fields="[['label' => __('hiko.name_marked'), 'key' => 'marked']]" />
                    <livewire:create-new-item-modal :route="route('places.create')" :text="__('hiko.modal_new_place')" />
                    <div>
                        <x-checkbox name="destination_inferred" label="{{ __('hiko.destination_inferred') }}"
                            :checked="boolval(old('destination_inferred', $letter->destination_inferred))" />
                        <small class="block text-gray-600">
                            {{ __('hiko.name_inferred_help') }}
                        </small>
                    </div>
                    <div>
                        <x-checkbox name="destination_uncertain" label="{{ __('hiko.destination_uncertain') }}"
                            :checked="boolval(old('destination_uncertain', $letter->destination_uncertain))" />
                    </div>
                    <div>
                        <x-label for="destination_note" :value="__('hiko.destination_note')" />
                        <x-textarea name="destination_note" id="destination_note" class="block w-full mt-1">
                            {{ old('destination_note', $letter->destination_note) }}
                        </x-textarea>
                        @error('destination_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div class="h-1"></div>
                <fieldset id="a-content" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.content_description') }}
                    </legend>
                    <div>
                        <x-label for="languages" :value="__('hiko.language')" />
                        <x-select x-data="choices({ element: $el })" x-init="initSelect()" id="languages"
                            class="block w-full mt-1" name="languages[]" multiple>
                            @foreach ($languages as $language)
                                <option value="{{ $language }}" @if (in_array($language, $selectedLanguages)) selected @endif>
                                    {{ $language }}
                                </option>
                            @endforeach
                        </x-select>
                        @error('languages')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="keywords" :value="__('hiko.keywords')" />
                        <x-select name="keywords[]" class="block w-full mt-1" id="keywords" x-data="ajaxChoices({ url: '{{ route('ajax.keywords') }}', element: $el })"
                            x-init="initSelect()" multiple>
                            @foreach ($selectedKeywords as $kw)
                                <option value="{{ $kw['value'] }}" selected>{{ $kw['label'] }}</option>
                            @endforeach
                        </x-select>
                        @error('keywords')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                        <livewire:create-new-item-modal :route="route('global.keywords.create')" :text="__('hiko.modal_new_keyword')" />
                    </div>
                    <div>
                        <x-label for="abstract_cs" :value="__('hiko.abstract') . ' CS'" />
                        <x-textarea name="abstract_cs" id="abstract_cs" class="block w-full mt-1">
                            {{ old('abstract_cs', $letter->translations['abstract']['cs'] ?? '') }}
                        </x-textarea>
                        @error('abstract_cs')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="abstract_en" :value="__('hiko.abstract') . ' EN'" />
                        <x-textarea name="abstract_en" id="abstract_en" class="block w-full mt-1">
                            {{ old('abstract_en', $letter->translations['abstract']['en'] ?? '') }}
                        </x-textarea>
                        @error('abstract_en')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="incipit" :value="__('hiko.incipit')" />
                        <x-textarea name="incipit" id="incipit" class="block w-full mt-1">
                            {{ old('incipit', $letter->incipit) }}
                        </x-textarea>
                        @error('incipit')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="explicit" :value="__('hiko.explicit')" />
                        <x-textarea name="explicit" id="explicit" class="block w-full mt-1">
                            {{ old('explicit', $letter->explicit) }}
                        </x-textarea>
                        @error('explicit')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="mentioned" :value="__('hiko.mentioned')" />
                        <x-select name="mentioned[]" class="block w-full mt-1" id="mentioned"
                            x-data="ajaxChoices({ url: '{{ route('ajax.identities') }}', element: $el })" x-init="initSelect()" multiple @input.debounce.500ms="search">
                            @foreach ($selectedMentioned as $mention)
                                <option value="{{ $mention['value'] }}" selected>{{ $mention['label'] }}</option>
                            @endforeach
                        </x-select>
                        @error('mentioned')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                        <livewire:create-new-item-modal :route="route('identities.create')" :text="__('hiko.modal_new_identity')" />
                    </div>
                    <div>
                        <x-label for="people_mentioned_note" :value="__('hiko.people_mentioned_note')" />
                        <x-textarea name="people_mentioned_note" id="people_mentioned_note"
                            class="block w-full mt-1">
                            {{ old('people_mentioned_note', $letter->people_mentioned_note) }}
                        </x-textarea>
                        @error('people_mentioned_note')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="notes_private" :value="__('hiko.notes_private')" />
                        <x-textarea name="notes_private" id="notes_private" class="block w-full mt-1">
                            {{ old('notes_private', $letter->notes_private) }}
                        </x-textarea>
                        @error('notes_private')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-label for="notes_public" :value="__('hiko.notes_public')" />
                        <x-textarea name="notes_public" id="notes_public" class="block w-full mt-1">
                            {{ old('notes_public', $letter->notes_public) }}
                        </x-textarea>
                        @error('notes_public')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div class="h-1"></div>
                <livewire:related-resources :letter="$letter" />
                <div class="h-1"></div>
                <livewire:letter-copies :letter="$letter" />
                <div class="h-1"></div>
                <fieldset id="a-copyright" class="space-y-6">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.copyright') }}
                    </legend>
                    <div>
                        <x-label for="copyright" :value="__('hiko.copyright')" />
                        <x-textarea name="copyright" id="copyright" class="block w-full mt-1">
                            {{ old('copyright', $letter->copyright) }}
                        </x-textarea>
                        @error('copyright')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>
                <div class="h-1"></div>
                <fieldset id="a-status" class="p-3 shadow">
                    <legend class="text-lg font-semibold">
                        {{ __('hiko.status') }}
                    </legend>
                    <div>
                        <x-radio name="status" label="{{ __('hiko.draft_letter') }}" value="draft"
                            :checked="old('status', $letter->status) === 'draft'" name="status" required />
                    </div>
                    <div>
                        <x-radio name="status" label="{{ __('hiko.published_letter') }}" value="publish"
                            :checked="old('status', $letter->status) === 'publish'" name="status" required />
                    </div>
                    @error('status')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </fieldset>
                @can('delete-metadata')
                    <fieldset id="a-approval" class="p-3 shadow mt-4">
                        <legend class="text-lg font-semibold">
                            Schválení
                        </legend>
                        <div>
                            <label class="inline-flex items-center">
                                <input type="radio" name="approval" value="1"
                                    {{ old('approval', $letter->approval) == 1 ? 'checked' : '' }}
                                    class="border-gray-300 shadow-sm text-primary focus:border-primary-light focus:ring focus:ring-primary-light focus:ring-opacity-50"
                                    required>
                                <span class="ml-2 text-sm text-gray-600">Schváleno</span>
                            </label>
                        </div>
                        <div>
                            <label class="inline-flex items-center">
                                <input type="radio" name="approval" value="0"
                                    {{ old('approval', $letter->approval) == 0 ? 'checked' : '' }}
                                    class="border-gray-300 shadow-sm text-primary focus:border-primary-light focus:ring focus:ring-primary-light focus:ring-opacity-50"
                                    required>
                                <span class="ml-2 text-sm text-gray-600">Neschváleno</span>
                            </label>
                        </div>
                        @error('approval')
                            <div class="text-red-600">{{ $message }}</div>
                        @enderror
                    </fieldset>
                @endcan
                <div class="h-1"></div>
                <x-button-simple class="w-full" onclick="preventLeaving = false" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>
                <x-button-inverted class="w-full text-black bg-white" onclick="preventLeaving = false" name="action"
                    value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>
            @if ($letter->id)
                @can('delete-metadata')
                    <form x-data="{ form: $el }" action="{{ route('letters.destroy', $letter->id) }}" method="post"
                        class="w-full mt-8">
                        @csrf
                        @method('DELETE')
                        <x-button-danger class="w-full" onclick="preventLeaving = false"
                            x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                            {{ __('hiko.remove') }}
                        </x-button-danger>
                    </form>
                @endcan
            @endif
        </div>
        <div class="md:w-1/2"> <livewire:ocr-upload /></div>
    </div>
    @push('scripts')
        <script>
            // Form changes tracker
            document.addEventListener('DOMContentLoaded', function() {
                // Define variables
                var formChanged = false;
                var confirmLeaveMessage = '{{ __('hiko.confirm_leave') }}';

                // Function to mark form as changed
                function markFormAsChanged() {
                    formChanged = true;
                }

                // Function to mark form as saved/unchanged
                function markFormAsUnchanged() {
                    formChanged = false;
                }

                // Get all forms on the page
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    // Standard inputs, textareas, selects
                    const formElements = form.querySelectorAll('input, textarea, select');
                    formElements.forEach(element => {
                        // Different event listeners based on element type
                        if (element.type === 'text' || element.type === 'password' || element.type ===
                            'email' ||
                            element.type === 'number' || element.type === 'search' || element.type ===
                            'tel' ||
                            element.type === 'url' || element.tagName === 'TEXTAREA') {
                            element.addEventListener('input', markFormAsChanged);
                            element.addEventListener('change', markFormAsChanged);
                        } else if (element.type === 'checkbox' || element.type === 'radio') {
                            element.addEventListener('change', markFormAsChanged);
                        } else if (element.tagName === 'SELECT') {
                            element.addEventListener('change', markFormAsChanged);

                            // For select elements that might be using Choices.js
                            if (element.classList.contains('choices__input') ||
                                element.parentElement?.classList.contains('choices')) {
                                // Try to find related Choices container and listen for mutations
                                const choicesContainer = element.closest('.choices');
                                if (choicesContainer) {
                                    const observer = new MutationObserver(markFormAsChanged);
                                    observer.observe(choicesContainer, {
                                        childList: true,
                                        subtree: true
                                    });
                                }
                            }
                        }
                    });

                    // Add listener for form submission
                    form.addEventListener('submit', markFormAsUnchanged);
                });

                // Handle buttons that should bypass the warning
                // Save buttons
                const saveButtons = document.querySelectorAll(
                    'button[type="submit"], input[type="submit"], [name="action"]');
                saveButtons.forEach(button => {
                    button.addEventListener('click', markFormAsUnchanged);
                });

                // Delete/cancel buttons
                const specialButtons = document.querySelectorAll(
                    '.btn-danger, [x-on\\:click*="confirm"], [data-no-confirm]');
                specialButtons.forEach(button => {
                    button.addEventListener('click', markFormAsUnchanged);
                });

                // Alpine.js driven elements
                document.addEventListener('change', function(e) {
                    // Target is within a form and has x-model attribute
                    if (e.target.closest('form') && (e.target.hasAttribute('x-model') ||
                            e.target.hasAttribute(':value') ||
                            e.target.hasAttribute('x-bind:value'))) {
                        markFormAsChanged();
                    }
                });

                // Listen for Alpine.js initialization
                document.addEventListener('alpine:initialized', function() {
                    // Find all Alpine-powered elements within forms
                    const alpineElements = document.querySelectorAll(
                        'form [x-data], form [x-model], form [x-bind], form [x-on\\:input]');
                    alpineElements.forEach(el => {
                        // For elements with x-model
                        if (el.hasAttribute('x-model')) {
                            el.addEventListener('input', markFormAsChanged);
                            el.addEventListener('change', markFormAsChanged);
                        }

                        // For elements with x-data (might contain reactive elements)
                        if (el.hasAttribute('x-data')) {
                            const observer = new MutationObserver(function(mutations) {
                                mutations.forEach(function(mutation) {
                                    if (mutation.type === 'attributes' || mutation
                                        .type === 'childList') {
                                        markFormAsChanged();
                                    }
                                });
                            });

                            observer.observe(el, {
                                attributes: true,
                                childList: true,
                                subtree: true
                            });
                        }
                    });
                });

                // Set up the beforeunload event
                window.onbeforeunload = function(e) {
                    if (formChanged) {
                        return confirmLeaveMessage;
                    }
                };

                // Expose functions globally for programmatic control
                window.formTracker = {
                    markChanged: markFormAsChanged,
                    markUnchanged: markFormAsUnchanged,
                    isChanged: () => formChanged
                };

                // For iframes in modals (keeping this functionality)
                var iframes = document.querySelectorAll('iframe');
                iframes.forEach(function(iframe) {
                    iframe.addEventListener('load', function() {
                        try {
                            var iframeContent = iframe.contentDocument || iframe.contentWindow.document;
                            var header = iframeContent.querySelector('header');
                            var footer = iframeContent.querySelector('footer');
                            if (header) header.style.display = 'none';
                            if (footer) footer.style.display = 'none';
                        } catch (error) {
                            console.warn('Unable to access iframe content:', error);
                        }
                    });
                });
            });

            // Keep the debounce function
            function debounce(func, wait, immediate) {
                var timeout;
                return function() {
                    var context = this,
                        args = arguments;
                    var later = function() {
                        timeout = null;
                        if (!immediate) func.apply(context, args);
                    };
                    var callNow = immediate && !timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                    if (callNow) func.apply(context, args);
                };
            }
            // Use debounce with the AJAX request
            var searchInput = document.getElementById('mentioned');
            searchInput.addEventListener('input', debounce(function(e) {
                // Make AJAX request here
            }, 500));

            // Hide header and footer in modals
            function hideHeaderFooterInIframe() {
                var modals = document.querySelectorAll('.modal-window');
                modals.forEach(function(modal) {
                    var iframe = modal.querySelector('iframe');
                    if (iframe) {
                        var iframeContent = iframe.contentDocument || iframe.contentWindow.document;
                        var header = iframeContent.querySelector('header');
                        var footer = iframeContent.querySelector('footer');

                        if (header) {
                            header.style.display = 'none';
                        }

                        if (footer) {
                            footer.style.display = 'none';
                        }
                    }
                });
            }

            // Function to handle iframe content load
            function handleIframeLoad() {
                hideHeaderFooterInIframe();
            }

            // Listen for iframe load event and handle it
            document.addEventListener('DOMContentLoaded', function() {
                var iframes = document.querySelectorAll('iframe');
                iframes.forEach(function(iframe) {
                    iframe.addEventListener('load', handleIframeLoad);
                });
            });
        </script>
    @endpush
</x-app-layout>
