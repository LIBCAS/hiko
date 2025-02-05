<form wire:submit="search" class="space-y-4">
    <div class="space-y-2">
        <h3 class="font-semibold text-black">{{ __('hiko.date_is_range') }}</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.from') }}</span>
                <x-input wire:model.live="filters.after" class="w-full px-2 text-sm" type="date" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.to') }}</span>
                <x-input wire:model.live="filters.before" class="w-full px-2 text-sm" type="date" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-black">{{ __('hiko.metadata') }}</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">ID</span>
                <x-input wire:model.live="filters.id" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.signature') }}</span>
                <x-input wire:model.live="filters.signature" class="w-full px-2 text-sm" type="text" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-black">{{ __('hiko.people') }}</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.author') }}</span>
                <x-input wire:model.live="filters.author" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.recipient') }}</span>
                <x-input wire:model.live="filters.recipient" class="w-full px-2 text-sm" type="text" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-black">{{ __('hiko.places') }}</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.origin') }}</span>
                <x-input wire:model.live="filters.origin" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.destination') }}</span>
                <x-input wire:model.live="filters.destination" class="w-full px-2 text-sm" type="text" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-black">{{ __('hiko.repository') }}</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.repository') }}</span>
                <x-input wire:model.live="filters.repository" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.archive') }}</span>
                <x-input wire:model.live="filters.archive" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.collection') }}</span>
                <x-input wire:model.live="filters.collection" class="w-full px-2 text-sm" type="text" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-black">{{ __('hiko.keywords') }} & text</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.keywords') }}</span>
                <x-input wire:model.live="filters.keyword" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.mentioned') }}</span>
                <x-input wire:model.live="filters.mentioned" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.full_text') }}</span>
                <x-input wire:model.live="filters.fulltext" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.abstract') }}</span>
                <x-input wire:model.live="filters.abstract" class="w-full px-2 text-sm" type="text" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-black">{{ __('hiko.mode_other') }}</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.language') }} {{ __('hiko.in_english') }}</span>
                <x-input wire:model.live="filters.languages" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.note') }}</span>
                <x-input wire:model.live="filters.note" class="w-full px-2 text-sm" type="text" />
            </label>
            <label class="block text-sm">
                <span class="block text-black">
                    {{ __('hiko.media') }}
                </span>
                <x-select wire:model.live="filters.media" class="w-full px-2 text-sm">
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
            <label class="block text-sm">
                <span class="block text-black">
                    {{ __('hiko.status') }}
                </span>
                <x-select wire:model.live="filters.status" class="w-full px-2 text-sm">
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
            <label class="block text-sm">
                <span class="block text-black">
                    {{ __('hiko.approval') }}
                </span>
                <x-select wire:model.live="filters.approval" class="w-full px-2 text-sm">
                    <option value="">
                        ---
                    </option>
                    <option value="{{ \App\Models\Letter::APPROVED }}">
                        {{ __('hiko.approved') }}
                    </option>
                    <option value="{{ \App\Models\Letter::NOT_APPROVED }}">
                        {{ __('hiko.not_approved') }}
                    </option>
                </x-select>
            </label>
            @can('manage-users')
            <label class="block text-sm">
                <span class="block text-black">{{ __('hiko.editors') }}</span>
                <x-input wire:model.live="filters.editor" class="w-full px-2 text-sm" type="text" />
            </label>
            @elsecan('manage-metadata')
            <label class="block text-sm">
                <span class="block text-black">
                    {{ __('hiko.only_my_records') }}
                </span>
                <x-select wire:model.live="filters.editor" class="w-full px-2 text-sm">
                    <option value="">
                        {{ __('hiko.no') }}
                    </option>
                    <option value='my'>
                        {{ __('hiko.yes') }}
                    </option>
                </x-select>
            </label>
            @endcan
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-black">Ordering</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-black">
                    {{ __('hiko.order_by') }}
                </span>
                <x-select wire:model.live="filters.order" class="w-full px-2 text-sm">
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
            <div class="block text-sm">
                <span class="block text-black">
                    {{ __('hiko.order_direction') }}
                </span>
                <div class="flex items-center space-x-2">
                    <label class="inline-flex items-center">
                        <x-radio name="direction" wire:model.live="filters.direction" value="asc" />
                        <span class="ml-2 text-black">{{ __('hiko.ascending') }}</span>
                    </label>
                    <label class="inline-flex items-center">
                        <x-radio name="direction" wire:model.live="filters.direction" value="desc" />
                        <span class="ml-2 text-black">{{ __('hiko.descending') }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-between">
        <button type="button" wire:click="resetFilters" class="px-4 py-2 border rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest disabled:opacity-25 transition ease-in-out duration-150 w-full border-red-700 hover:bg-red-700 hover:text-white w-full">
            {{ __('hiko.reset') }}
        </button>
    </div>
</form>