<div>
    @can('manage-metadata')
        <a href="{{ $args['link'] }}" class="font-semibold text-primary-dark hover:underline">
            {{ $args['label'] }}
        </a>
    @endcan
    @cannot('manage-metadata')
        {{ $args['label'] }}
    @endcan
</div>
