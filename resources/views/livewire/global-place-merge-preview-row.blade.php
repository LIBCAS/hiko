<tr class="hover:bg-gray-50" wire:key="place-row-{{ $local->id }}"
    x-data="mergeRowData(@js($local), @js($global), '{{ $strategy }}', @entangle("mergeAttrs.{$local->id}"))">

    {{-- Checkbox Column --}}
    <td class="px-3 py-4 whitespace-nowrap align-top">
        <input
            type="checkbox"
            wire:model.live="selectedIds"
            value="{{ $local->id }}"
            id="place-{{ $local->id }}"
            class="rounded border-gray-300 text-primary focus:ring-primary">
    </td>

    {{-- Local Place Column with Radio Buttons --}}
    <td class="px-3 py-4 align-top">
        <div class="text-sm space-y-2">
            {{-- Name --}}
            <div class="flex items-center gap-2">
                @if($strategy === 'merge' && $global)
                    <input type="radio"
                           name="attr_name_{{ $local->id }}"
                           value="local"
                           wire:model.live="mergeAttrs.{{ $local->id }}.name"
                           class="rounded-full">
                @endif
                <div class="flex-1">
                    <strong>{{ __('hiko.name_2') }}:</strong>
                    <a href="{{ route('places.edit', $local->id) }}"
                       target="_blank"
                       class="text-blue-600 hover:text-blue-800 underline">
                        {{ $local->name }}
                    </a>
                </div>
            </div>

            {{-- Country --}}
            @if($local->country || ($strategy === 'merge' && $global && $global->country))
            <div class="flex items-center gap-2">
                @if($strategy === 'merge' && $global)
                    <input type="radio"
                           name="attr_country_{{ $local->id }}"
                           value="local"
                           wire:model.live="mergeAttrs.{{ $local->id }}.country"
                           class="rounded-full">
                @endif
                <div class="flex-1">
                    <strong>{{ __('hiko.country') }}:</strong>
                    {{ $local->country ?? '—' }}
                </div>
            </div>
            @endif

            {{-- Division --}}
            @if($local->division || ($strategy === 'merge' && $global && $global->division))
            <div class="flex items-center gap-2">
                @if($strategy === 'merge' && $global)
                    <input type="radio"
                           name="attr_division_{{ $local->id }}"
                           value="local"
                           wire:model.live="mergeAttrs.{{ $local->id }}.division"
                           class="rounded-full">
                @endif
                <div class="flex-1">
                    <strong>{{ __('hiko.division_abbr') }}:</strong>
                    {{ $local->division ?? '—' }}
                </div>
            </div>
            @endif

            {{-- Latitude --}}
            @if($local->latitude || ($strategy === 'merge' && $global && $global->latitude))
            <div class="flex items-center gap-2">
                @if($strategy === 'merge' && $global)
                    <input type="radio"
                           name="attr_latitude_{{ $local->id }}"
                           value="local"
                           wire:model.live="mergeAttrs.{{ $local->id }}.latitude"
                           class="rounded-full">
                @endif
                <div class="flex-1">
                    <strong>{{ __('hiko.latitude_abbr') }}:</strong>
                    {{ $local->latitude ?? '—' }}
                </div>
            </div>
            @endif

            {{-- Longitude --}}
            @if($local->longitude || ($strategy === 'merge' && $global && $global->longitude))
            <div class="flex items-center gap-2">
                @if($strategy === 'merge' && $global)
                    <input type="radio"
                           name="attr_longitude_{{ $local->id }}"
                           value="local"
                           wire:model.live="mergeAttrs.{{ $local->id }}.longitude"
                           class="rounded-full">
                @endif
                <div class="flex-1">
                    <strong>{{ __('hiko.longitude_abbr') }}:</strong>
                    {{ $local->longitude ?? '—' }}
                </div>
            </div>
            @endif

            {{-- Geoname ID --}}
            @if($local->geoname_id || ($strategy === 'merge' && $global && $global->geoname_id))
            <div class="flex items-center gap-2">
                @if($strategy === 'merge' && $global)
                    <input type="radio"
                           name="attr_geoname_id_{{ $local->id }}"
                           value="local"
                           wire:model.live="mergeAttrs.{{ $local->id }}.geoname_id"
                           class="rounded-full">
                @endif
                <div class="flex-1">
                    <strong>{{ __('hiko.geoname_id') }}:</strong>
                    {{ $local->geoname_id ?? '—' }}
                </div>
            </div>
            @endif
        </div>
    </td>

    {{-- Method/Strategy Column --}}
    <td class="px-3 py-4 whitespace-nowrap align-top">
        @if($strategy === 'merge')
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                {{ __('hiko.merge') }}
            </span>
            @if($reason)
                <span class="block mt-4 text-sm">
                    {{ __('hiko.merge_reason_' . $reason) }}
                </span>
            @endif
        @else
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                {{ __('hiko.move') }}
            </span>
        @endif
    </td>

    {{-- Global Place Column with Radio Buttons --}}
    <td class="px-3 py-4 align-top">
        @if($global && $strategy === 'merge')
            <div class="text-sm space-y-2">
                {{-- Name --}}
                <div class="flex items-center gap-2">
                    <input type="radio"
                           name="attr_name_{{ $local->id }}"
                           value="global"
                           wire:model.live="mergeAttrs.{{ $local->id }}.name"
                           class="rounded-full">
                    <div class="flex-1">
                        <strong>{{ __('hiko.name_2') }}:</strong>
                        <a href="{{ route('global.places.edit', $global->id) }}"
                           target="_blank"
                           class="text-blue-600 hover:text-blue-800 underline">
                            {{ $global->name }}
                        </a>
                    </div>
                </div>

                {{-- Country --}}
                <div class="flex items-center gap-2">
                    <input type="radio"
                           name="attr_country_{{ $local->id }}"
                           value="global"
                           wire:model.live="mergeAttrs.{{ $local->id }}.country"
                           class="rounded-full">
                    <div class="flex-1">
                        <strong>{{ __('hiko.country') }}:</strong>
                        {{ $global->country ?? '—' }}
                    </div>
                </div>

                {{-- Division --}}
                <div class="flex items-center gap-2">
                    <input type="radio"
                           name="attr_division_{{ $local->id }}"
                           value="global"
                           wire:model.live="mergeAttrs.{{ $local->id }}.division"
                           class="rounded-full">
                    <div class="flex-1">
                        <strong>{{ __('hiko.division_abbr') }}:</strong>
                        {{ $global->division ?? '—' }}
                    </div>
                </div>

                {{-- Latitude --}}
                <div class="flex items-center gap-2">
                    <input type="radio"
                           name="attr_latitude_{{ $local->id }}"
                           value="global"
                           wire:model.live="mergeAttrs.{{ $local->id }}.latitude"
                           class="rounded-full">
                    <div class="flex-1">
                        <strong>{{ __('hiko.latitude_abbr') }}:</strong>
                        {{ $global->latitude ?? '—' }}
                    </div>
                </div>

                {{-- Longitude --}}
                <div class="flex items-center gap-2">
                    <input type="radio"
                           name="attr_longitude_{{ $local->id }}"
                           value="global"
                           wire:model.live="mergeAttrs.{{ $local->id }}.longitude"
                           class="rounded-full">
                    <div class="flex-1">
                        <strong>{{ __('hiko.longitude_abbr') }}:</strong>
                        {{ $global->longitude ?? '—' }}
                    </div>
                </div>

                {{-- Geoname ID --}}
                <div class="flex items-center gap-2">
                    <input type="radio"
                           name="attr_geoname_id_{{ $local->id }}"
                           value="global"
                           wire:model.live="mergeAttrs.{{ $local->id }}.geoname_id"
                           class="rounded-full">
                    <div class="flex-1">
                        <strong>{{ __('hiko.geoname_id') }}:</strong>
                        {{ $global->geoname_id ?? '—' }}
                    </div>
                </div>
            </div>
        @else
            <span class="text-gray-400">—</span>
        @endif
    </td>

    {{-- Merged Result Column --}}
    <td class="px-3 py-4 align-top">
        @if($strategy === 'merge' && $global)
            <div class="text-sm space-y-1 bg-gray-50 p-2 rounded">
                <div>
                    <strong>{{ __('hiko.name_2') }}:</strong>
                    <span x-text="getMergedValue('name')"></span>
                </div>
                <div>
                    <strong>{{ __('hiko.country') }}:</strong>
                    <span x-text="getMergedValue('country')"></span>
                </div>
                <div>
                    <strong>{{ __('hiko.division_abbr') }}:</strong>
                    <span x-text="getMergedValue('division')"></span>
                </div>
                <div>
                    <strong>{{ __('hiko.latitude_abbr') }}:</strong>
                    <span x-text="getMergedValue('latitude')"></span>
                </div>
                <div>
                    <strong>{{ __('hiko.longitude_abbr') }}:</strong>
                    <span x-text="getMergedValue('longitude')"></span>
                </div>
                <div>
                    <strong>{{ __('hiko.geoname_id') }}:</strong>
                    <span x-text="getMergedValue('geoname_id')"></span>
                </div>

                {{-- Hidden inputs for form submission --}}
                <template x-for="(value, key) in attrs" :key="key">
                    <input type="hidden"
                           :name="'merge_attrs[' + placeId + '][' + key + ']'"
                           :value="value">
                </template>
            </div>
        @else
            <span class="text-gray-400">—</span>
        @endif
    </td>
</tr>
