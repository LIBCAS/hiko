<x-app-layout :title="$title">
    <ul class="flex flex-wrap mb-6 space-x-6 text-sm">
        <li>
            <a href="{{ route('letters.edit', $letter->id) }}" class="text-primary hover:underline">
                {{ __('hiko.edit_letter') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.text', $letter->id) }}" class="text-primary hover:underline">
                {{ __('hiko.edit_full_text') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.images', $letter->id) }}" class="text-primary hover:underline">
                {{ __('hiko.edit_attachments') }}
            </a>
        </li>
    </ul>
    <h2 class="text-lg font-bold">
        {{ __('hiko.dates') }}
    </h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            <tr class="align-baseline border-t border-b border-gray-200">
                <td class="w-1/5 py-2">
                    {{ __('hiko.letter_date') }}
                </td>
                <td class="py-2">
                    {{ $letter->pretty_date }}@if ($letter->date_is_range)
                        –{{ $letter->pretty_range_date }}
                    @endif
                    @if ($letter->date_uncertain)
                        <small class="block pl-3"><em>{{ __('hiko.uncertain_date') }}</em></small>
                    @endif
                    @if ($letter->date_inferred)
                        <small class="block pl-3"><em>{{ __('hiko.inferred_date') }}</em></small>
                    @endif

                    @if ($letter->date_approximate)
                        <small class="block pl-3"><em>{{ __('hiko.approximate_date') }}</em></small>
                    @endif
                </td>
            </tr>
            @if ($letter->date_marked)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                         {{ __('hiko.date_marked') }}
                    </td>
                    <td class="py-2">
                        {{ $letter->date_marked }}
                    </td>
                </tr>
            @endif
            @if ($letter->date_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        {{ __('hiko.date_note') }}
                    </td>
                    <td class="py-2">
                        {{ $letter->date_note }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <h2 class="text-lg font-bold">
        {{ __('hiko.identities') }}
    </h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            @if (isset($identities['author']))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.author') }}</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($identities['author'] as $author)
                                <li class="mb-1">
                                    <a href="{{ route('identities.edit', $author['id']) }}" target="_blank" class="underline">{{ $author['name'] }}</a>
                                    @if ($author['pivot']['marked'])
                                        <span class="block pl-3 text-gray-500">
                                            {{ __('hiko.marked_as') }}: {{ $author['pivot']['marked'] }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->author_uncertain)
                            <small class="block pl-3"><em>{{ __('hiko.uncertain_author') }}</em></small>
                        @endif
                        @if ($letter->author_inferred)
                            <small class="block pl-3"><em>{{ __('hiko.inferred_author') }}</em></small>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($letter->author_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        {{ __('hiko.author_note') }}
                    </td>
                    <td class="py-2">
                        {{ $letter->author_note }}
                    </td>
                </tr>
            @endif
            @if (isset($identities['recipient']))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.recipient') }}</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($identities['recipient'] as $recipient)
                                <li class="mb-1">
                                    <a href="{{ route('identities.edit', $recipient['id']) }}" target="_blank" class="underline">{{ $recipient['name'] }}</a>
                                    @if ($recipient['pivot']['marked'])
                                        <span class="block pl-3 text-gray-500">
                                            {{ __('hiko.marked_as') }}: {{ $recipient['pivot']['marked'] }}
                                        </span>
                                    @endif
                                    @if ($recipient['pivot']['salutation'])
                                        <span class="block pl-3 text-gray-500">
                                            {{ __('hiko.salutation') }}: {{ $recipient['pivot']['salutation'] }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->recipient_uncertain)
                            <small class="block pl-3"><em>{{ __('hiko.uncertain_recipient') }}</em></small>
                        @endif
                        @if ($letter->recipient_inferred)
                            <small class="block pl-3"><em>{{ __('hiko.inferred_recipient') }}</em></small>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($letter->recipient_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        {{ __('hiko.recipient_note') }}
                    </td>
                    <td class="py-2">
                        {{ $letter->recipient_note }}
                    </td>
                </tr>
            @endif
            @if (isset($identities['mentioned']))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">Mentioned people</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($identities['mentioned'] as $mentioned)
                                <li class="mb-1">
                                    <a href="{{ route('identities.edit', $mentioned['id']) }}" target="_blank" class="underline">{{ $mentioned['name'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
            @if ($letter->people_mentioned_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        Notes on mentioned people
                    </td>
                    <td class="py-2">
                        {{ $letter->people_mentioned_note }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <h2 class="text-lg font-bold">{{ __('hiko.places') }}</h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            @if (isset($places['origin']))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.origin') }}</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($places['origin'] as $origin)
                                <li class="mb-1">
                                    <a href="{{ route('places.edit', $origin['id']) }}" target="_blank" class="underline">{{ $origin['name'] }}</a>
                                    @if ($origin['pivot']['marked'])
                                        <span class="block pl-3 text-gray-500">
                                            {{ __('hiko.marked_as_f') }}: {{ $origin['pivot']['marked'] }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->origin_uncertain)
                            <small class="block pl-3"><em>{{ __('hiko.uncertain_origin') }}</em></small>
                        @endif
                        @if ($letter->origin_inferred)
                            <small class="block pl-3"><em>{{ __('hiko.inferred_origin') }}</em></small>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($letter->origin_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        Notes on origin
                    </td>
                    <td class="py-2">
                        {{ $letter->origin_note }}
                    </td>
                </tr>
            @endif
            @if (isset($places['destination']))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.destination') }}</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($places['destination'] as $destination)
                                <li class="mb-1">
                                    <a href="{{ route('places.edit', $destination['id']) }}" target="_blank" class="underline">{{ $destination['name'] }}</a>
                                    @if ($destination['pivot']['marked'])
                                        <span class="block pl-3 text-gray-500">
                                            {{ __('hiko.marked_as') }}: {{ $destination['pivot']['marked'] }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->destination_uncertain)
                            <small class="block pl-3"><em>{{ __('hiko.uncertain_destination') }}<</em></small>
                        @endif
                        @if ($letter->destination_inferred)
                            <small class="block pl-3"><em>{{ __('hiko.inferred_destination') }}<</em></small>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($letter->destination_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        {{ __('hiko.destination_note') }}
                    </td>
                    <td class="py-2">
                        {{ $letter->destination_note }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <h2 class="text-lg font-bold">{{ __('hiko.content') }}</h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            @if ($letter->getTranslation('abstract', 'cs', false))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.abstract_cs') }}</td>
                    <td class="py-2">
                        {{ $letter->getTranslation('abstract', 'cs') }}
                    </td>
                </tr>
            @endif
            @if ($letter->getTranslation('abstract', 'en', false))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.abstract_en') }}</td>
                    <td class="py-2">
                        {{ $letter->getTranslation('abstract', 'en') }}
                    </td>
                </tr>
            @endif
            @if ($letter->incipit)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Incipit</td>
                    <td class="py-2">
                        {{ $letter->incipit }}
                    </td>
                </tr>
            @endif
            @if ($letter->explicit)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Explicit</td>
                    <td class="py-2">
                        {{ $letter->explicit }}
                    </td>
                </tr>
            @endif
            @if ($letter->languages)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.languages') }}</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach (explode(';', $letter->languages) as $lang)
                                <li class="mb-1">
                                    {{ $lang }}
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
            @if ($letter->keywords)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">{{ __('hiko.keywords') }}</td>
                    <td class="py-2">
                        @foreach ($letter->keywords as $kw)
                            <li class="mb-1">
                                <a href="{{ route('keywords.edit', $kw['id']) }}" target="_blank" class="underline">{{ implode(' | ', array_values($kw->getTranslations('name'))) }}</a>
                            </li>
                        @endforeach
                    </td>
                </tr>
            @endif
            @if ($letter->notes_public)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.notes_letter') }}</td>
                    <td class="py-2">
                        {{ $letter->notes_public }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    @if (!empty($letter->copies) && is_array($letter->copies))
        <h2 class="text-lg font-bold">{{ __('hiko.repositories_and_versions') }}</h2>
        @foreach ($letter->copies as $c)
            <table class="w-full mb-10 text-sm">
                <tbody>
                    @if ($c['l_number'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.l_number')}}</td>
                            <td class="py-2">
                                {{ $c['l_number'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['repository'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.repository') }}</td>
                            <td class="py-2">
                                {{ $c['repository'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['archive'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.archive') }}</td>
                            <td class="py-2">
                                {{ $c['archive'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['collection'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.collection') }}</td>
                            <td class="py-2">
                                {{ $c['collection'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['signature'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">Signature</td>
                            <td class="py-2">
                                {{ $c['signature'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['location_note'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.note_location') }}</td>
                            <td class="py-2">
                                {{ $c['location_note'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['type'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.doc_type') }}</td>
                            <td class="py-2">
                                {{ $c['type'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['preservation'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.preservation') }}</td>
                            <td class="py-2">
                                {{ $c['preservation'] }}
                            </td>
                        </tr>
                    @endif
                    @if ($c['copy'])
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.copy_type') }}</td>
                            <td class="py-2">
                                {{ $c['copy'] }}
                            </td>
                        </tr>
                    @endif
                    @if (!empty($c['manifestation_notes']))
                        <tr class="align-baseline border-t border-b border-gray-200">
                            <td class="w-1/5 py-2">{{ __('hiko.manifestation_notes') }}</td>
                            <td class="py-2">{{ $c['manifestation_notes'] }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
    @endif
    @if ($letter->copyright)
        <h2 class="text-lg font-bold">Copyright</h2>
        <table class="w-full mb-10 text-sm">
            <tbody>
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">{{ __('hiko.copyright') }}</td>
                    <td class="py-2">
                        {{ $letter->copyright }}
                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    @if (!empty($letter->related_resources) && is_iterable($letter->related_resources))
        <h2 class="text-lg font-bold">{{ __('hiko.related_resources') }} </h2>
        <table class="w-full mb-10 text-sm">
            <tbody>
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        <ul>
                            @foreach ($letter->related_resources as $resource)
                                <li class="mb-1">
                                    @if (!empty($resource['link']))
                                        <a href="{{ $resource['link'] }}" target="_blank" class="underline">
                                            {{ $resource['title'] }}
                                        </a>
                                    @else
                                        {{ $resource['title'] }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    @if ($letter->content)
        <h2 class="text-lg font-bold">
            Full text
        </h2>
        <div class="p-3 mb-10 prose-sm prose bg-gray-200 shadow-sm">
            {!! $letter->content !!}
        </div>
    @endif
</x-app-layout>
