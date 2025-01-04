<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">
                ID
            </span>
            <x-input wire:model.live="filters.id" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.from') }}</span>
            <x-input wire:model.live="filters.after" class="block w-full px-2 text-sm lg:w-32" type="date" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.to') }}</span>
            <x-input wire:model.live="filters.before" class="block w-full px-2 text-sm lg:w-32" type="date" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.signature') }}</span>
            <x-input wire:model.live="filters.signature" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.author') }}</span>
            <x-input wire:model.live="filters.author" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.recipient') }}</span>
            <x-input wire:model.live="filters.recipient" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.origin') }}</span>
            <x-input wire:model.live="filters.origin" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.destination') }}</span>
            <x-input wire:model.live="filters.destination" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.repository') }}</span>
            <x-input wire:model.live="filters.repository" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.archive') }}</span>
            <x-input wire:model.live="filters.archive" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.collection') }}</span>
            <x-input wire:model.live="filters.collection" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.keywords') }}</span>
            <x-input wire:model.live="filters.keyword" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.mentioned') }}</span>
            <x-input wire:model.live="filters.mentioned" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.full_text') }}</span>
            <x-input wire:model.live="filters.fulltext" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.abstract') }}</span>
            <x-input wire:model.live="filters.abstract" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.language') }} {{ __('hiko.in_english') }}</span>
            <x-input wire:model.live="filters.languages" class="block w-full px-2 text-sm lg:w-32" type="text" />
        </label>
        <label>
            <span class="block text-sm">{{ __('hiko.note') }}</span>
            <x-input wire:model.live="filters.note" class="block w-full px-2 text-sm lg:w-32" type="text"/>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.media') }}
            </span>
            <x-select wire:model.live="filters.media" class="w-full px-2 text-sm lg:w-32">
                <option value="">
                    ---
                </option>
                <option value="1">
                    {{ __('hiko.with_media') }}
                </option>
                <option value='0'>
                    {{ __('hiko.without_media') }}
                </option>
            </x-select>
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.status') }}
            </span>
            <x-select wire:model.live="filters.status" class="w-full px-2 text-sm lg:w-32">
                <option value="">
                    ---
                </option>
                <option value="publish">
                    {{ __('hiko.publish') }}
                </option>
                <option value='draft'>
                    {{ __('hiko.draft') }}
                </option>
            </x-select>
        </label>
        @can('manage-users')
            <label>
                <span class="block text-sm">{{ __('hiko.editors') }}</span>
                <x-input wire:model.live="filters.editor" class="block w-full px-2 text-sm lg:w-32" type="text" />
            </label>
        @elsecan('manage-metadata')
            <label>
                <span class="block text-sm">
                    {{ __('hiko.only_my_records') }}
                </span>
                <x-select wire:model.live="filters.editor" class="w-full px-2 text-sm lg:w-32">
                    <option value="">
                        {{ __('hiko.no') }}
                    </option>
                    <option value='my'>
                        {{ __('hiko.yes') }}
                    </option>
                </x-select>
            </label>
        @endcan
        <label>
            <span class="block text-sm">
                {{ __('hiko.order_by') }}
            </span>
            <x-select wire:model.live="filters.order" class="w-full px-2 text-sm lg:w-40">
                <option value='updated_at'>
                    {{ __('hiko.by_update') }}
                </option>
                <option value='date_computed'>
                    {{ __('hiko.by_letter_date') }}
                </option>
                <option value='id'>
                    {{ __('hiko.by_letter_id') }}
                </option>
            </x-select>
        </label>
        <div>
            <span class="block text-sm">
                {{ __('hiko.order_direction') }}
            </span>
            <div class="flex flex-col">
                <x-radio name="direction" wire:model.live="filters.direction"
                    aria-label="{{ __('hiko.ascending') }}" title="{{ __('hiko.ascending') }}" label="ASC"
                    value="asc" />
                <x-radio name="direction" wire:model.live="filters.direction"
                    aria-label="{{ __('hiko.descending') }}" title="{{ __('hiko.descending') }}" label="DESC"
                    value="desc" />
            </div>
        </div>
    </x-filter-form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>

@push('scripts')
    <script>
    Livewire.on('filtersChanged', filters => {
        updateExportUrl(filters, document.getElementById('export-url'));
    })
    </script>
@endpush
