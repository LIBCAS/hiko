<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #1f2937; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }
        table { border-collapse: collapse; margin: 12px 0 24px; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 7px 9px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .ids a { white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ __('hiko.metadata_digest_heading', [
        'from' => $digest['period_start']->format('d.m.Y H:i:s'),
        'to' => $digest['period_end']->format('d.m.Y H:i:s'),
    ]) }}</h1>

    @if($digest['total'] === 0)
        <p>{{ __('hiko.metadata_digest_empty') }}</p>
    @else
        @foreach($digest['sections'] as $section)
            <h2>{{ $section['label'] }}</h2>
            @if($section['total'] === 0)
                <p>{{ __('hiko.metadata_digest_section_empty') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('hiko.metadata_digest_scope') }}</th>
                            <th>{{ __('hiko.metadata_digest_tenant') }}</th>
                            <th>{{ __('hiko.metadata_digest_count') }}</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['groups'] as $group)
                            <tr>
                                <td>{{ $group['scope'] === 'global' ? __('hiko.global') : __('hiko.local') }}</td>
                                <td>{{ $group['tenant'] ?? '-' }}</td>
                                <td>{{ count($group['records']) }}</td>
                                <td class="ids">
                                    @foreach($group['records'] as $record)
                                        <a href="{{ $record['href'] }}">{{ $record['id'] }}</a>@if(!$loop->last), @endif
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif

    <p>{{ __('hiko.metadata_digest_automatic_notice') }}</p>
</body>
</html>
