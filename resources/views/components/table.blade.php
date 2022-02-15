<div class="flex flex-col">
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <div class="inline-block min-w-full align-middle">
            <div class="overflow-hidden ">
                <table class="min-w-full divide-y divide-gray-200 table-fixed">
                    <thead class="bg-gray-200">
                        <tr>
                            @foreach ($tableData['header'] as $header)
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                    {{ $header }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($tableData['rows'] as $row)
                            <tr class="text-sm text-gray-900 hover:bg-gray-100">
                                @foreach ($row as $item)
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if (isset($item['link']) && isset($item['external']) && $item['external'])
                                            <a href="{{ $item['link'] }}" target="_blank" class="hover:underline">
                                                {{ $item['label'] }} &#10697
                                            </a>
                                        @elseif (isset($item['link']))
                                            <a href="{{ $item['link'] }}"
                                                class="font-semibold text-primary-dark hover:underline">
                                                {{ $item['label'] }}
                                            </a>
                                        @elseif (is_array($item['label']))
                                            <ul>
                                                @foreach ($item['label'] as $label)
                                                    <li>
                                                        {{ $label }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {{ $item['label'] }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        @empty($tableData['rows'])
                            <tr class="text-sm text-gray-900 hover:bg-gray-100">
                                <td colspan="{{ count($tableData['header']) }}" class="px-6 py-4 whitespace-nowrap">
                                    {{ __('hiko.items_not_found') }}
                                </td>
                            </tr>
                        @endempty
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
