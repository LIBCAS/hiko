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
</ul>
