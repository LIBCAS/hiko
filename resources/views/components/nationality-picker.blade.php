@props(['identity'])
@php
    $options = \App\Models\Nationality::orderBy('name->'.app()->getLocale())->get()->map(fn($n) => ['id' => (string)$n->id, 'label' => $n->name]);
    $rawSelected = old('nationalities', $identity->exists ? $identity->nationalities->pluck('id')->all() : []);
    $selected = collect(is_array($rawSelected) ? $rawSelected : [])->filter(fn($id) => is_int($id) || (is_string($id) && ctype_digit($id)))->map(fn($id) => (string)$id)->unique()->values()->all();
@endphp
<div class="p-4 space-y-4 bg-white rounded-lg shadow-md border border-gray-200"
    x-data="{
        options: @js($options),
        rows: [],
        nextKey: 0,
        init() {
            this.rows = @js($selected).map(id => this.makeRow(id));
            if (!this.rows.length) this.add();
        },
        makeRow(id = '') {
            return { key: this.nextKey++, id, query: this.options.find(option => option.id === id)?.label || id, open: false, highlighted: 0 };
        },
        add() { this.rows.push(this.makeRow()); },
        normalize(value) { return value.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
        matches(row) {
            const label = this.options.find(option => option.id === row.id)?.label;
            const query = row.query === label ? '' : this.normalize(row.query);
            return this.options.filter(option =>
                !this.rows.some(other => other.key !== row.key && other.id === option.id)
                &amp;&amp; this.normalize(option.label).includes(query)
            );
        },
        choose(row, option) {
            if (!option) return;
            row.id = option.id;
            row.query = option.label;
            row.open = false;
        },
        close(row) {
            row.open = false;
            row.query = this.options.find(option => option.id === row.id)?.label || row.id;
        },
        highlight(row, delta) {
            row.open = true;
            row.highlighted = Math.max(0, Math.min(this.matches(row).length - 1, row.highlighted + delta));
            this.$nextTick(() => document.getElementById('nationality-option-' + row.key + '-' + row.highlighted)?.scrollIntoView({ block: 'nearest' }));
        },
        move(index, delta) {
            const target = index + delta;
            if (target &lt; 0 || target >= this.rows.length) return;
            const row = this.rows.splice(index, 1)[0];
            this.rows.splice(target, 0, row);
        }
    }">
    <h3 class="text-lg font-semibold">{{ __('hiko.nationalities') }}</h3>
    <input type="hidden" name="nationalities_present" value="1">

    @error('nationalities') <div class="text-red-600 text-sm rounded-md bg-red-50 p-2">{{ $message }}</div> @enderror
    @error('nationalities.*') <div class="text-red-600 text-sm rounded-md bg-red-50 p-2">{{ $message }}</div> @enderror

    <div class="space-y-2">
        <template x-for="(row, index) in rows" :key="row.key">
            <div class="flex items-center gap-2">
                <input type="hidden" name="nationalities[]" :value="row.id" :disabled="!row.id">
                <div class="relative min-w-0 flex-1" @click.outside="close(row)" @focusout="if (!$el.contains($event.relatedTarget)) close(row)">
                    <input type="text" x-model="row.query"
                        @focus="row.open = true; row.highlighted = 0"
                        @input="row.open = true; row.highlighted = 0"
                        @keydown.arrow-down.prevent="highlight(row, 1)"
                        @keydown.arrow-up.prevent="highlight(row, -1)"
                        @keydown.enter.prevent="if (row.open) choose(row, matches(row)[row.highlighted])"
                        @keydown.escape.prevent.stop="close(row)"
                        class="block w-full rounded-md border-gray-300 py-2 px-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary"
                        :class="{ 'border-primary': row.open }"
                        placeholder="{{ __('hiko.search') }} ..."
                        aria-label="{{ __('hiko.select_nationality') }}"
                        role="combobox" aria-autocomplete="list" :aria-expanded="row.open"
                        :aria-controls="'nationality-options-' + row.key"
                        :aria-activedescendant="row.open &amp;&amp; matches(row).length ? 'nationality-option-' + row.key + '-' + row.highlighted : null"
                        autocomplete="off">
                    <div x-show="row.open" x-cloak :id="'nationality-options-' + row.key" role="listbox"
                        aria-label="{{ __('hiko.nationalities') }}"
                        class="absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg max-h-60 overflow-y-auto py-1 text-sm ring-1 ring-black ring-opacity-5">
                        <div x-show="matches(row).length === 0" class="p-2 text-center text-sm text-gray-500">{{ __('hiko.no_results') }}</div>
                        <template x-for="(option, optionIndex) in matches(row)" :key="option.id">
                            <div role="option" :id="'nationality-option-' + row.key + '-' + optionIndex"
                                :aria-selected="option.id === row.id"
                                @mousedown.prevent @click="choose(row, option)" @mouseenter="row.highlighted = optionIndex"
                                :class="row.highlighted === optionIndex ? 'bg-primary text-white' : 'text-gray-900'"
                                class="cursor-pointer select-none py-2 px-3 hover:bg-primary hover:text-white"
                                x-text="option.label"></div>
                        </template>
                    </div>
                </div>
                <div x-show="rows.length > 1" class="flex items-center gap-1">
                    <button type="button" @click="move(index, -1)" :disabled="index === 0"
                        class="p-1 text-gray-500 hover:text-primary disabled:opacity-25 disabled:cursor-not-allowed"
                        aria-label="{{ __('hiko.move_up') }}" title="{{ __('hiko.move_up') }}">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 16V4m-5 5 5-5 5 5" /></svg>
                    </button>
                    <button type="button" @click="move(index, 1)" :disabled="index === rows.length - 1"
                        class="p-1 text-gray-500 hover:text-primary disabled:opacity-25 disabled:cursor-not-allowed"
                        aria-label="{{ __('hiko.move_down') }}" title="{{ __('hiko.move_down') }}">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 4v12m-5-5 5 5 5-5" /></svg>
                    </button>
                </div>
                <button type="button" @click="rows.splice(index, 1)"
                    class="flex-shrink-0 text-red-500 hover:text-red-700 transition-colors duration-150 flex items-center"
                    aria-label="{{ __('hiko.remove_item') }}" title="{{ __('hiko.remove_item') }}">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                </button>
            </div>
        </template>
    </div>
    <button type="button" @click="add(); $nextTick(() => $el.previousElementSibling.lastElementChild.querySelector('input[type=text]').focus())"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
        <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
        {{ __('hiko.add_new_item') }}
    </button>
</div>
