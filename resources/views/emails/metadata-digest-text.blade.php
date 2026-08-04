{{ __('hiko.metadata_digest_heading', [
    'from' => $digest['period_start']->format('d.m.Y H:i:s'),
    'to' => $digest['period_end']->format('d.m.Y H:i:s'),
]) }}

@if($digest['total'] === 0)
{{ __('hiko.metadata_digest_empty') }}
@else
@foreach($digest['sections'] as $section)
## {{ $section['label'] }}
@forelse($section['groups'] as $group)
{{ $group['scope'] === 'global' ? __('hiko.global') : __('hiko.local') }} | {{ $group['tenant'] ?? '-' }} | {{ count($group['records']) }} | @foreach($group['records'] as $record){{ $record['id'] }} ({{ $record['href'] }})@if(!$loop->last), @endif @endforeach
@empty
{{ __('hiko.metadata_digest_section_empty') }}
@endforelse

@endforeach
@endif
{{ __('hiko.metadata_digest_automatic_notice') }}
