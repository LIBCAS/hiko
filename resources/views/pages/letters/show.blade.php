<x-app-layout :title="$title">
    <ul class="flex flex-wrap mb-6 space-x-6 text-sm">
        <li>
            <a href="{{ route('letters.edit', $letter->id) }}" class="hover:underline">
                {{ __('Upravit dopis') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.text', $letter->id) }}" class="hover:underline">
                {{ __('Upravit plný text') }}
            </a>
        </li>
        <li>
            <a href="{{ route('letters.images', $letter->id) }}" class="hover:underline">
                {{ __('Upravit přílohy') }}
            </a>
        </li>
    </ul>
    <h2 class="text-lg font-bold">
        Dates
    </h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            <tr class="align-baseline border-t border-b border-gray-200">
                <td class="w-1/5 py-2">
                    Letter date
                </td>
                <td class="py-2">
                    {{ $letter->formattedDate }}@if ($letter->date_is_range)–{{ $letter->formattedRangeDate }} @endif
                    @if ($letter->date_uncertain)
                        <small class="block pl-3"><em>Uncertain date</em></small>
                    @endif
                    @if ($letter->date_inferred)
                        <small class="block pl-3"><em>Inferred date</em></small>
                    @endif

                    @if ($letter->date_approximate)
                        <small class="block pl-3"><em>Approximate date</em></small>
                    @endif
                </td>
            </tr>
            @if ($letter->date_marked)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        Date as marked
                    </td>
                    <td class="py-2">
                        {{ $letter->date_marked }}
                    </td>
                </tr>
            @endif
            @if ($letter->date_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        Notes on date
                    </td>
                    <td class="py-2">
                        {{ $letter->date_note }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <h2 class="text-lg font-bold">
        {{ __('Osoby a instituce') }}
    </h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            @if ($letter->authors())
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Author</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($letter->authors()->get() as $author)
                                <li class="mb-1">
                                    {{ $author->name }}
                                    @if ($author->pivot->marked)
                                        <span class="block pl-3 text-gray-500">
                                            Marked as: {{ $author->pivot->marked }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->author_uncertain)
                            <small class="block pl-3"><em>Uncertain author</em></small>
                        @endif
                        @if ($letter->author_inferred)
                            <small class="block pl-3"><em>Inferred author</em></small>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($letter->author_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        Notes on authors
                    </td>
                    <td class="py-2">
                        {{ $letter->author_note }}
                    </td>
                </tr>
            @endif
            @if ($letter->recipients())
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Recipients</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($letter->recipients()->get() as $recipient)
                                <li class="mb-1">
                                    {{ $recipient->name }}
                                    @if ($recipient->pivot->marked)
                                        <span class="block pl-3 text-gray-500">
                                            Marked as: {{ $recipient->pivot->marked }}
                                        </span>
                                    @endif
                                    @if ($recipient->pivot->salutation)
                                        <span class="block pl-3 text-gray-500">
                                            Salutation: {{ $recipient->pivot->salutation }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->recipient_uncertain)
                            <small class="block pl-3"><em>Uncertain recipient</em></small>
                        @endif
                        @if ($letter->recipient_inferred)
                            <small class="block pl-3"><em>Inferred recipient</em></small>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($letter->recipient_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        Notes on recipients
                    </td>
                    <td class="py-2">
                        {{ $letter->recipient_note }}
                    </td>
                </tr>
            @endif
            @if ($letter->mentioned())
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">Mentioned people</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($letter->mentioned()->get() as $mentioned)
                                <li class="mb-1">
                                    {{ $mentioned->name }}
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
    <h2 class="text-lg font-bold">Places</h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            @if ($letter->origins())
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Origin</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($letter->origins()->get() as $origin)
                                <li class="mb-1">
                                    {{ $origin->name }}
                                    @if ($origin->pivot->marked)
                                        <span class="block pl-3 text-gray-500">
                                            Marked as: {{ $origin->pivot->marked }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->origin_uncertain)
                            <small class="block pl-3"><em>Uncertain origin</em></small>
                        @endif
                        @if ($letter->origin_inferred)
                            <small class="block pl-3"><em>Inferred origin</em></small>
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
            @if ($letter->destinations())
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Destination</td>
                    <td class="py-2">
                        <ul class="list-disc list-inside">
                            @foreach ($letter->destinations()->get() as $destination)
                                <li class="mb-1">
                                    {{ $destination->name }}
                                    @if ($destination->pivot->marked)
                                        <span class="block pl-3 text-gray-500">
                                            Marked as: {{ $destination->pivot->marked }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($letter->destination_uncertain)
                            <small class="block pl-3"><em>Uncertain destination</em></small>
                        @endif
                        @if ($letter->destination_inferred)
                            <small class="block pl-3"><em>Inferred destination</em></small>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($letter->destination_note)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">
                        Notes on destination
                    </td>
                    <td class="py-2">
                        {{ $letter->destination_note }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <h2 class="text-lg font-bold">Content</h2>
    <table class="w-full mb-10 text-sm">
        <tbody>
            @if ($letter->getTranslation('abstract', 'cs'))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Abstract CS</td>
                    <td class="py-2">
                        {{ $letter->getTranslation('abstract', 'cs') }}
                    </td>
                </tr>
            @endif
            @if ($letter->getTranslation('abstract', 'en'))
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Abstract EN</td>
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
                    <td class="w-1/5 py-2">Languages</td>
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
            @if ($letter->keywords())
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="py-2">Keywords</td>
                    <td class="py-2">
                        @foreach ($letter->keywords()->get() as $kw)
                            <li class="mb-1">
                                {{ implode(' | ', array_values($kw->getTranslations('name'))) }}
                            </li>
                        @endforeach
                    </td>
                </tr>
            @endif
            @if ($letter->notes_public)
                <tr class="align-baseline border-t border-b border-gray-200">
                    <td class="w-1/5 py-2">Notes on letter</td>
                    <td class="py-2">
                        {{ $letter->notes_public }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <h2 class="text-lg font-bold">Repositories and versions</h2>
    @foreach ($letter->copies as $c) :
        <table class="w-full mb-10 text-sm">
            <tbody>
                @if ($c['l_number'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Letter number</td>
                        <td class="py-2">
                            {{ $c['l_number'] }}
                        </td>
                    </tr>
                @endif

                @if ($c['repository'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Repository</td>
                        <td class="py-2">
                            {{ $c['repository'] }}
                        </td>
                    </tr>
                @endif

                @if ($c['repository'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Repository</td>
                        <td class="py-2">
                            <x?= $c['repository'] ?>
                        </td>
                    </tr>
                @endif
                @if ($c['archive'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Archive</td>
                        <td class="py-2">
                            {{ $c['archive'] }}
                        </td>
                    </tr>
                @endif
                @if ($c['collection'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Collection</td>
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
                        <td class="w-1/5 py-2">Note on location</td>
                        <td class="py-2">
                            {{ $c['location_note'] }}
                        </td>
                    </tr>
                @endif
                @if ($c['type'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Document type</td>
                        <td class="py-2">
                            {{ $c['type'] }}
                        </td>
                    </tr>
                @endif
                @if ($c['preservation'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Preservation</td>
                        <td class="py-2">
                            {{ $c['preservation'] }}
                        </td>
                    </tr>
                @endif
                @if ($c['copy'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">Type of copy</td>
                        <td class="py-2">
                            {{ $c['copy'] }}
                        </td>
                    </tr>
                @endif
                @if ($c['manifestation_notes'])
                    <tr class="align-baseline border-t border-b border-gray-200">
                        <td class="w-1/5 py-2">
                            Notes on manifestation</td>
                        <td class="py-2">
                            {{ $c['manifestation_notes'] }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
    @if ($letter->related_resources)
        <h2 class="text-lg font-bold">Related resources</h2>
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
    <div class="flex flex-wrap mb-6 -m-x-1 gallery">
        @foreach ($letter->getMedia() as $image)
            <div x-data="{open: false}" x-on:keydown.escape="open = false">
                <button x-on:click="open = true" class="block border" aria-label="{{ __('Zobrazit přílohu') }}">
                    <img src="{{ $image->getUrl('thumb') }}" alt="{{ __('Příloha') }}" loading="lazy"
                        class="w-full">
                </button>
                <div x-show="open" x-on:click="open = false" style="display:none"
                    class="fixed inset-0 z-50 p-4 bg-black bg-opacity-75">
                    <div class="flex justify-center w-full" x-on:click.away="open = false">
                        <img src="{{ $image->getUrl() }}" alt="{{ __('Příloha') }}" class="block border"
                            loading="lazy">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
