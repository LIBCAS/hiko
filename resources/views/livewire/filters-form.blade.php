<form wire:submit="search" class="space-y-4">
    <div class="space-y-2">
        <h3 class="font-semibold text-gray-700">Date Range</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.from') }}</span>
                <x-input wire:model.live="filters.after" class="w-full px-2 text-sm" type="date" placeholder="From date" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.to') }}</span>
                <x-input wire:model.live="filters.before" class="w-full px-2 text-sm" type="date" placeholder="To date" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-gray-700">Metadata</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">ID</span>
                <x-input wire:model.live="filters.id" class="w-full px-2 text-sm" type="text" placeholder="Letter ID" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.signature') }}</span>
                <x-input wire:model.live="filters.signature" class="w-full px-2 text-sm" type="text" placeholder="Signature" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-gray-700">People</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.author') }}</span>
                <x-input wire:model.live="filters.author" class="w-full px-2 text-sm" type="text" placeholder="Author name" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.recipient') }}</span>
                <x-input wire:model.live="filters.recipient" class="w-full px-2 text-sm" type="text" placeholder="Recipient name" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-gray-700">Places</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.origin') }}</span>
                <x-input wire:model.live="filters.origin" class="w-full px-2 text-sm" type="text" placeholder="Origin place" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.destination') }}</span>
                <x-input wire:model.live="filters.destination" class="w-full px-2 text-sm" type="text" placeholder="Destination place" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-gray-700">Repository</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.repository') }}</span>
                <x-input wire:model.live="filters.repository" class="w-full px-2 text-sm" type="text" placeholder="Repository" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.archive') }}</span>
                <x-input wire:model.live="filters.archive" class="w-full px-2 text-sm" type="text" placeholder="Archive" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.collection') }}</span>
                <x-input wire:model.live="filters.collection" class="w-full px-2 text-sm" type="text" placeholder="Collection" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-gray-700">Keywords & Text</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.keywords') }}</span>
                <x-input wire:model.live="filters.keyword" class="w-full px-2 text-sm" type="text" placeholder="Keywords" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.mentioned') }}</span>
                <x-input wire:model.live="filters.mentioned" class="w-full px-2 text-sm" type="text" placeholder="Mentioned" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.full_text') }}</span>
                <x-input wire:model.live="filters.fulltext" class="w-full px-2 text-sm" type="text" placeholder="Full text" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.abstract') }}</span>
                <x-input wire:model.live="filters.abstract" class="w-full px-2 text-sm" type="text" placeholder="Abstract" />
            </label>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="font-semibold text-gray-700">Other</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.language') }} {{ __('hiko.in_english') }}</span>
                <x-input wire:model.live="filters.languages" class="w-full px-2 text-sm" type="text" placeholder="Languages" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">{{ __('hiko.note') }}</span>
                <x-input wire:model.live="filters.note" class="w-full px-2 text-sm" type="text" placeholder="Note" />
            </label>
            <label class="block text-sm">
                <span class="block text-gray-700">
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
                <span class="block text-gray-700">
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
                <span class="block text-gray-700">
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
                    <span class="block text-gray-700">{{ __('hiko.editors') }}</span>
                    <x-input wire:model.live="filters.editor" class="w-full px-2 text-sm" type="text" placeholder="Editor name" />
                </label>
            @elsecan('manage-metadata')
                <label class="block text-sm">
                    <span class="block text-gray-700">
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
        <h3 class="font-semibold text-gray-700">Ordering</h3>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="block text-gray-700">
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
                <span class="block text-gray-700">
                    {{ __('hiko.order_direction') }}
                </span>
                <div class="flex items-center space-x-2">
                    <label class="inline-flex items-center">
                        <x-radio name="direction" wire:model.live="filters.direction" value="asc" />
                        <span class="ml-2 text-gray-700">{{ __('hiko.ascending') }}</span>
                    </label>
                    <label class="inline-flex items-center">
                        <x-radio name="direction" wire:model.live="filters.direction" value="desc" />
                        <span class="ml-2 text-gray-700">{{ __('hiko.descending') }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-between">
        <button type="button" wire:click="resetFilters" class="px-4 py-2 border rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest disabled:opacity-25 transition ease-in-out duration-150 w-full border-red-700 hover:bg-red-700 hover:text-white w-full">
            Reset
        </button>
    </div>
</form>
