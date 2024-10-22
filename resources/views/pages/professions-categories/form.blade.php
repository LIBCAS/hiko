<x-app-layout :title="$title">
    <x-success-alert />

    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">
        <div class="max-w-sm">
            <form 
                x-data="similarItems({ 
                    similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'ProfessionCategory']) }}', 
                    id: '{{ $professionCategory->id }}' 
                })" 
                x-init="$watch('search', () => findSimilarNames($data))" 
                action="{{ $action }}" 
                method="POST" 
                class="space-y-3" 
                autocomplete="off"
            >
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <div>
                    <x-label for="cs" value="CS" />
                    <x-input 
                        id="cs" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="cs" 
                        :value="old('cs', $professionCategory->translations['name']['cs'] ?? null)"
                        x-on:change="search = $el.value"
                    />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-label for="en" value="EN" />
                    <x-input 
                        id="en" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="en" 
                        :value="old('en', $professionCategory->translations['name']['en'] ?? null)"
                        x-on:change="search = $el.value"
                    />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <x-alert-similar-names />

                <x-button-simple class="w-full" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>

                <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>

            @if ($professionCategory->id)
                @can('delete-metadata')
                    <form 
                        x-data="{ form: $el }" 
                        action="{{ route('professions.category.destroy', $professionCategory->id) }}" 
                        method="POST" 
                        class="max-w-sm mt-8"
                    >
                        @csrf
                        @method('DELETE')

                        <x-button-danger class="w-full" 
                            x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                            {{ __('hiko.remove') }}
                        </x-button-danger>
                    </form>
                @endcan
            @endif
        </div>

        @if ($professionCategory->id)
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if ($professionCategory->identities->count() > 0)
                    <h2 class="text-l font-semibold">
                        {{ __('hiko.attached_persons_count') }}: {{ $professionCategory->identities->count() }}
                    </h2>
                    <ul class="list-disc px-3 py-3">
                        @foreach ($professionCategory->identities as $identity)
                            <li>
                                <a href="{{ route('identities.edit', $identity->id) }}" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                    {{ $identity->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_persons') }}</h2>
                @endif
            </div>

            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                <h2 class="text-l font-semibold">
                    {{ __('hiko.professions') }}: {{ $professionCategory->professions->count() }}
                </h2>
                <ul class="list-disc p-3">
                    @foreach ($professions as $profession)
                        @if ($profession->identities->count() > 0)
                            <li>
                                <a href="{{ route('professions.edit', $profession->id) }}" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                    {{ $profession->name }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>

                <x-attach-profession-modal 
                    :professionCategory="$professionCategory" 
                    :availableProfessions="$availableProfessions" 
                />
            </div>
        @endif
    </div>
</x-app-layout>
