<ul class="list-disc">
    @can('manage-metadata')
        <li>
            <a href="{{ route('letters.edit', $id) }}" class='font-semibold text-primary hover:underline'>
                {{ __('Upravit') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.text', $id) }}" class='font-semibold text-primary hover:underline'>
                {{ __('Plný text') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.images', $id) }}" class='font-semibold text-primary hover:underline'>
                {{ __('Obrazové přílohy') }}
            </a>
        </li>
    @endcan
    @can('view-metadata')
        <li>
            <a href="{{ route('letters.show', $id) }}" class='font-semibold text-primary hover:underline'>
                {{ __('Náhled') }}
            </a>
        </li>
    @endcan
    @can('manage-metadata')
    <li>
        <div x-data="{open: false}" x-on:keydown.escape="open = false">
            <button x-on:click="open = true" class="font-semibold text-primary hover:underline">
                {{ __('Historie') }}
            </button>
            <div x-show="open" x-on:click="open = false" style="display:none"
                class="fixed inset-0 z-50 p-4 bg-black bg-opacity-75">
                <div class="flex items-center justify-center w-full h-full" x-on:click.away="open = false">
                    <div class="p-3 overflow-y-auto bg-white h-96 w-96">
                        {!! nl2br($history) !!}
                    </div>
                </div>
            </div>
        </div>
    </li>
@endcan
</ul>
