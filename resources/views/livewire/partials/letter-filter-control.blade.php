@if($type !== 'editor' || auth()->user()?->can('manage-metadata'))
<div wire:key="letter-filter-control-{{ $field }}-{{ $revision }}" class="min-w-0 space-y-2 text-sm">
    <label for="letter-filter-input-{{ $field }}" class="block font-medium">{{ __('hiko.' . $label) }}</label>
    @if($type === 'text')
        <x-select id="letter-filter-input-{{ $field }}" wire:model.live="draft.{{ $field }}.operator" class="w-full text-sm">
            <option value="contains">{{ __('hiko.filter_contains') }}</option>
            <option value="empty">{{ __('hiko.filter_empty') }}</option>
            <option value="not_empty">{{ __('hiko.filter_not_empty') }}</option>
        </x-select>
        @if(($draft[$field]['operator'] ?? 'contains') === 'contains')
            <x-input wire:model="draft.{{ $field }}.value" type="text" class="w-full text-sm" aria-label="{{ __('hiko.' . $label) }}: {{ __('hiko.filter_contains') }}" />
        @endif
    @elseif(in_array($type, ['part', 'count']))
        <div class="flex gap-2">
            <x-select id="letter-filter-input-{{ $field }}" wire:model.live="draft.{{ $field }}.operator" class="w-1/2 text-sm">
                <option value="eq">{{ __('hiko.filter_eq') }}</option>
                @if($type === 'count')
                    <option value="gte">{{ __('hiko.filter_gte') }}</option>
                    <option value="lte">{{ __('hiko.filter_lte') }}</option>
                @else
                    <option value="empty">{{ __('hiko.filter_missing') }}</option>
                @endif
            </x-select>
            @if(($draft[$field]['operator'] ?? 'eq') !== 'empty')
                <x-input wire:model="draft.{{ $field }}.value" type="number" min="{{ $type === 'count' ? 0 : 1 }}"
                    class="w-1/2 text-sm" aria-label="{{ __('hiko.' . $label) }}" />
            @endif
        </div>
        @if(str_starts_with($field, 'range_')) <p class="text-xs text-gray-500">{{ __('hiko.filter_range_parts_help') }}</p> @endif
    @elseif(in_array($type, ['entity', 'languages']))
        <div wire:ignore>
            <x-select id="letter-filter-input-{{ $field }}" class="w-full text-sm" multiple
                x-data="{{ $type === 'languages' ? 'choices' : 'ajaxChoices' }}({
                    url: '{{ route('ajax.letter-filter-options') }}',
                    element: $el,
                    allowHTML: false,
                    extraParams: { field: '{{ $field }}' },
                    change: () => $wire.updateSelection('{{ $field }}', Array.from($el.selectedOptions).map(option => option.value))
                })" x-init="initSelect()">
                @if($type === 'languages')
                    @foreach(array_unique(array_merge($languages, $draft[$field] ?? [])) as $language)
                        <option value="{{ $language }}" @selected(in_array($language, $draft[$field] ?? []))>{{ $language }}</option>
                    @endforeach
                @else
                    @foreach($selectedOptions[$field] as $option)
                        <option value="{{ $option['value'] }}" selected>{{ $option['label'] }}</option>
                    @endforeach
                @endif
            </x-select>
        </div>
        <x-select wire:model="draft.{{ $field }}_match" class="w-full text-sm" aria-label="{{ __('hiko.' . $label) }}: {{ __('hiko.filter_match_mode') }}">
            <option value="any">{{ __('hiko.filter_any_selected') }}</option>
            <option value="all">{{ __('hiko.filter_all_selected') }}</option>
        </x-select>
    @elseif($type === 'checks')
        <fieldset id="letter-filter-input-{{ $field }}" class="space-y-2">
            <legend class="sr-only">{{ __('hiko.' . $label) }}</legend>
            @foreach(\App\Services\LetterFilterDefinitions::choices($field) as $value => $choice)
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="draft.{{ $field }}" value="{{ $value }}" class="text-primary border-gray-300 rounded focus:ring-primary" />
                    <span>{{ $choice }}</span>
                </label>
            @endforeach
        </fieldset>
    @elseif($type === 'boolean')
        <x-select id="letter-filter-input-{{ $field }}" wire:model="draft.{{ $field }}" class="w-full text-sm">
            <option value="">{{ __('hiko.filter_any') }}</option>
            <option value="1">{{ __('hiko.yes') }}</option>
            <option value="0">{{ __('hiko.no') }}</option>
        </x-select>
    @elseif($type === 'date_match')
        <x-select id="letter-filter-input-{{ $field }}" wire:model="draft.{{ $field }}" class="w-full text-sm">
            <option value="overlap">{{ __('hiko.filter_overlap') }}</option>
            <option value="within">{{ __('hiko.filter_within') }}</option>
        </x-select>
    @elseif($type === 'editor' && !auth()->user()?->can('manage-users'))
        <x-select id="letter-filter-input-{{ $field }}" wire:model="draft.editor" class="w-full text-sm" aria-label="{{ __('hiko.only_my_records') }}">
            <option value="">{{ __('hiko.filter_any') }}</option>
            <option value="{{ auth()->user()->name }}">{{ __('hiko.only_my_records') }}</option>
        </x-select>
    @else
        <x-input id="letter-filter-input-{{ $field }}" wire:model="draft.{{ $field }}" type="{{ $type === 'date' ? 'date' : 'text' }}" class="w-full text-sm"
            placeholder="{{ $type === 'id' ? __('hiko.letter_ids_placeholder') : '' }}" />
    @endif
    @if(\Illuminate\Support\Facades\Lang::has('hiko.filter_' . $field . '_help'))
        <p class="text-xs text-gray-600">{{ __('hiko.filter_' . $field . '_help') }}</p>
    @endif
    @error('draft.' . $field . '.value') <p class="text-red-700">{{ $message }}</p> @enderror
    @error('draft.' . $field) <p class="text-red-700">{{ $message }}</p> @enderror
</div>
@endif
