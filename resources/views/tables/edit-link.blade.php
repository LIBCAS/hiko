@can('manage-metadata')
    <a href="{{ $route }}" class='font-semibold text-primary'>
    @endcan
    {{ $label }}
    @can('manage-metadata')
    </a>
@endcan
