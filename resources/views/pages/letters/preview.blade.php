<x-app-layout :title="$title">
    @foreach ($letters as $letter)
        <article x-data="{ show: true }" class="pb-8 mb-8 border-b border-primary-light">
            <h2 class="mb-4 font-semibold text-primary-dark hover:underline">
                <button type="button" x-on:click="show = !show" class="px-2 py-1 mr-1 border border-primary-dark">
                    <x-icons.minus x-show="show" class="h-3" x-cloak />
                    <x-icons.plus x-show="!show" class="h-3" x-cloak />
                </button>
                <a href="{{ route('letters.edit', $letter->id) }}" target="_blank">
                    {{ $letter->name }} (ID:{{ $letter->id }})
                </a>
            </h2>
            <div x-show="show" class="prose-sm prose">
                <h3 class="text-base">
                    Date
                </h3>
                <ul>
                    <li>
                        {{ $letter->pretty_date }}
                    </li>
                    @if ($letter->date_is_range)
                        <li>
                            Date is range: {{ $letter->pretty_range_date }}
                        </li>
                    @endif
                    @if ($letter->date_uncertain)
                        <li>Uncertain date</li>
                    @endif
                    @if ($letter->date_inferred)
                        <li>Inferred date</li>
                    @endif
                    @if ($letter->date_approximate)
                        <li>Approximate date</li>
                    @endif
                    @if ($letter->date_marked)
                        <li>
                            Date as marked: {{ $letter->date_marked }}
                        </li>
                    @endif
                    @if ($letter->date_note)
                        <li>
                            Notes on date: {{ $letter->date_note }}
                        </li>
                    @endif
                </ul>
                <h3 class="text-base">
                    Authors
                </h3>
                @if (isset($letter->identities_grouped['author']))
                    <ul>
                        @foreach ($letter->identities_grouped['author'] as $author)
                            <li>
                                {{ $author['name'] }}@if ($author['pivot']['marked']), marked as: {{ $author['pivot']['marked'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                <ul>
                    @if ($letter->author_note)
                        <li>
                            Note on authors: {{ $letter->author_note }}
                        </li>
                    @endif
                    @if ($letter->author_uncertain)
                        <li>Uncertain author</li>
                    @endif
                    @if ($letter->author_inferred)
                        <li>Inferred author</li>
                    @endif
                </ul>
                <h3 class="text-base">
                    Recipients
                </h3>
                @if (isset($letter->identities_grouped['recipient']))
                    <ul>
                        @foreach ($letter->identities_grouped['recipient'] as $recipient)
                            <li>
                                {{ $recipient['name'] }}@if ($recipient['pivot']['marked']) , marked as: {{ $recipient['pivot']['marked'] }}
                                    @endif @if ($recipient['pivot']['salutation']), salutation: {{ $recipient['pivot']['salutation'] }}
                                    @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                <ul>
                    @if ($letter->recipient_note)
                        <li>
                            Note on recipients: {{ $letter->recipient_note }}
                        </li>
                    @endif
                    @if ($letter->recipient_uncertain)
                        <li>Uncertain recipient</li>
                    @endif
                    @if ($letter->recipient_inferred)
                        <li>Inferred recipient</li>
                    @endif
                </ul>
                <h3 class="text-base">
                    Origin
                </h3>
                @if (isset($letter->places_grouped['origin']))
                    <ul>
                        @foreach ($letter->places_grouped['origin'] as $origin)
                            <li>
                                {{ $origin['name'] }}@if ($origin['pivot']['marked']), marked as: {{ $origin['pivot']['marked'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <ul>
                        @if ($letter->origin_note)
                            <li>
                                Notes on origin: {{ $letter->origin_note }}
                            </li>
                        @endif
                        @if ($letter->origin_uncertain)
                            <li>Uncertain origin</li>
                        @endif
                        @if ($letter->origin_inferred)
                            <li>Inferred origin</li>
                        @endif
                    </ul>
                @endif
                <h3 class="text-base">
                    Destination
                </h3>
                @if (isset($letter->places_grouped['destination']))
                    <ul>
                        @foreach ($letter->places_grouped['destination'] as $destination)
                            <li>
                                {{ $destination['name'] }}@if ($destination['pivot']['marked']), marked as: {{ $destination['pivot']['marked'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <ul>
                        @if ($letter->destination_note)
                            <li>
                                Notes on destination: {{ $letter->destination_note }}
                            </li>
                        @endif
                        @if ($letter->destination_uncertain)
                            <li>Uncertain destination</li>
                        @endif
                        @if ($letter->destination_inferred)
                            <li>Inferred destination</li>
                        @endif
                    </ul>
                @endif
                <h3 class="text-base">
                    Manifestations and repositories
                </h3>
                @foreach ((array) $letter->copies as $c)
                    <ul @if(!$loop->last) class="border-b" @endif>
                        @if ($c['l_number'])
                            <li>
                                Letter number: {{ $c['l_number'] }}
                            </li>
                        @endif
                        @if ($c['repository'])
                            <li>
                                Repository: {{ $c['repository'] }}
                            </li>
                        @endif
                        @if ($c['archive'])
                            <li>
                                Archive: {{ $c['archive'] }}
                            </li>
                        @endif
                        @if ($c['collection'])
                            <li>
                                Collection: {{ $c['collection'] }}
                            </li>
                        @endif
                        @if ($c['signature'])
                            <li>
                                Signature: {{ $c['signature'] }}
                            </li>
                        @endif
                        @if ($c['location_note'])
                            <li>
                                Notes on location: {{ $c['location_note'] }}
                            </li>
                        @endif
                        @if ($c['type'])
                            <li>
                                Document type: {{ $c['type'] }}
                            </li>
                        @endif
                        @if ($c['preservation'])
                            <li>
                                Preservation: {{ $c['preservation'] }}
                            </li>
                        @endif
                        @if ($c['copy'])
                            <li>
                                Type of copy: {{ $c['copy'] }}
                            </li>
                        @endif
                        @if ($c['manifestation_notes'])
                            <li>
                                Notes on manifestation: {{ $c['manifestation_notes'] }}
                            </li>
                        @endif
                    </ul>
                @endforeach
                @if ($letter->copyright)
                    <h3 class="text-base">
                        Copyright
                    </h3>
                    <ul>
                        <li>
                            {{ $letter->copyright }}
                        </li>
                    </ul>
                @endif
                @if ($letter->abstract)
                    <h3 class="text-base">
                        Abstract
                    </h3>
                    <ul>
                        <li>
                            {{ $letter->getTranslation('abstract', 'cs', false) }}
                        </li>
                        <li>
                            {{ $letter->getTranslation('abstract', 'en', false) }}
                        </li>
                    </ul>
                @endif
                @if ($letter->incipit)
                    <h3 class="text-base">
                        Incipit
                    </h3>
                    <ul>
                        <li>
                            {{ $letter->incipit }}
                        </li>
                    </ul>
                @endif
                @if ($letter->explicit)
                    <h3 class="text-base">
                        Explicit
                    </h3>
                    <ul>
                        <li>
                            {{ $letter->explicit }}
                        </li>
                    </ul>
                @endif
                @if ($letter->people_mentioned_note || isset($letter->identities_grouped['mentioned']))
                    <h3 class="text-base">
                        People mentioned
                    </h3>
                    @if (isset($letter->identities_grouped['mentioned']))
                        <ul>
                            @foreach ($letter->identities_grouped['mentioned'] as $mentioned)
                                <li>
                                    {{ $mentioned['name'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($letter->people_mentioned_note)
                        <ul>
                            <li>
                                Notes on people mentioned: {{ $letter->people_mentioned_note }}
                            </li>
                        </ul>
                    @endif
                @endif
                @if ($letter->languages)
                    <h3 class="text-base">
                        Languages
                    </h3>
                    <ul>
                        @foreach (explode(';', $letter->languages) as $lang)
                            <li>
                                {{ $lang }}
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($letter->keywords)
                    <h3 class="text-base">
                        Keywords
                    </h3>
                    <ul>
                        @foreach ($letter->keywords as $kw)
                            <li>
                                {{ implode(' | ', array_values($kw->getTranslations('name'))) }}
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($letter->related_resources)
                    <h3 class="text-base">
                        Related resources
                    </h3>
                    <ul>
                        @foreach ($letter->related_resources as $resource)
                            <li>
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
                @endif
                @if ($letter->notes_public || $letter->notes_private)
                    <h3 class="text-base">
                        Notes
                    </h3>
                    <ul>
                        @if ($letter->notes_public)
                            <li>
                                {{ $letter->notes_public }}
                            </li>
                        @endif

                        @if ($letter->notes_private)
                            <li>
                                {{ $letter->notes_private }}
                            </li>
                        @endif
                    </ul>
                @endif
                <h3 class="text-base">
                    Status: {{ $letter->status }}
                </h3>
            </div>
        </article>
    @endforeach
</x-app-layout>
