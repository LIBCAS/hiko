<ul>
    <li>
        <a href="{{ route('letters.edit', $id) }}" class='font-semibold text-primary'>{{ __('Upravit') }}</a>
    </li>
    <li>
        <a href="#" class='font-semibold text-primary'>{{ __('Plný text') }}</a>
    </li>
    <li>
        <a href="{{ route('letters.images', $id) }}" class='font-semibold text-primary'>{{ __('Obrazové přílohy') }}</a>
    </li>
    <li>
        <a href="#" class='font-semibold text-primary'>{{ __('Náhled') }}</a>
    </li>
</ul>
