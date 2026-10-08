<x-app-layout :title="$title">
    <x-page-lock scope="global" resource-type="nationalities_admin" :redirect-url="route('nationalities.index')" :read-only-on-deny="true" />
    <x-success-alert />
    @if($errors->any()) <div class="mb-4 text-red-600">{{ $errors->first() }}</div> @endif
    <section>
        <h1 class="mb-2 text-xl font-bold">{{ __('hiko.nationality_expansions') }}</h1>
        <p class="mb-4 text-sm">{{ __('hiko.nationality_expansions_help') }}</p>
        <form method="POST" action="{{ route('nationalities.expansions.store') }}" class="mb-4 flex flex-wrap items-end gap-3">
            @csrf
            @foreach(['source' => 'nationality_expansion_source', 'target' => 'nationality_expansion_target'] as $side => $label)
                <label class="block text-sm">{{ __('hiko.' . $label) }}
                    <x-select name="{{ $side }}_nationality_id" required class="block w-full">
                        <option value="">—</option>
                        @foreach($nationalities as $item)
                            <option value="{{ $item->id }}" @selected(old($side . '_nationality_id') == $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </x-select>
                </label>
            @endforeach
            <button class="rounded bg-gray-800 px-4 py-2 text-white" type="submit">{{ __('hiko.create') }}</button>
        </form>
        @php($catalogue = $nationalities->keyBy('id'))
        <ul class="divide-y rounded border bg-white">
            @foreach($expansions as $expansion)
                <li class="flex items-center justify-between gap-4 px-4 py-2">
                    <span>{{ $catalogue[$expansion->source_nationality_id]->name }} → {{ $catalogue[$expansion->target_nationality_id]->name }}</span>
                    <form method="POST" action="{{ route('nationalities.expansions.destroy', [$expansion->source_nationality_id, $expansion->target_nationality_id]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                            title="{{ __('hiko.remove') }}" aria-label="{{ __('hiko.remove') }}">
                            <x-icons.remove class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    </section>
    <div class="mt-6 flex justify-end">
        <x-loading-link href="{{ route('nationalities.index') }}">
            {{ __('hiko.nationalities') }}
        </x-loading-link>
    </div>
</x-app-layout>
