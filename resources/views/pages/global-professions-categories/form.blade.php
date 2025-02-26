<x-app-layout :title="$title">
    <x-success-alert />

    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">
        <!-- Form Section -->
        <div class="max-w-sm">
            <form 
                x-data="similarItems({ 
                    similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'GlobalProfessionCategory']) }}', 
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

                <!-- CS Field -->
                <div>
                    <x-label for="cs" value="{{ __('CS') }}" />
                    <x-input 
                        id="cs" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="cs" 
                        :value="old('cs', $professionCategory->getTranslation('name', 'cs') ?? null)"
                        x-on:change="search = $el.value"
                        required
                    />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- EN Field -->
                <div>
                    <x-label for="en" value="{{ __('EN') }}" />
                    <x-input 
                        id="en" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="en" 
                        :value="old('en', $professionCategory->getTranslation('name', 'en') ?? null)"
                        x-on:change="search = $el.value"
                    />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Alert for Similar Names -->
                <x-alert-similar-names />

                <!-- Submit Buttons -->
                <x-button-simple class="w-full" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>
                <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>

            <!-- Delete Button -->
            @if ($professionCategory->id)
                @can('delete-metadata')
                    <form 
                        x-data="{ form: $el }" 
                        action="{{ route('global.professions.category.destroy', $professionCategory->id) }}" 
                        method="POST" 
                        class="w-full mt-8"
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

        <!-- Related Identities Section -->
        @if ($professionCategory->id)
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if ($professionCategory->identities?->count() > 0)
                    <h2 class="text-l font-semibold">
                        {{ __('hiko.attached_persons_count') }}: {{ $professionCategory->identities->count() }}
                    </h2>
                    <ul class="list-disc px-3 py-3">
                        @foreach ($professionCategory->identities->sortBy('name') as $identity)
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

            <!-- Related Professions Section -->
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                <h2 class="text-l font-semibold">
                    {{ __('hiko.professions') }}: {{ $professionCategory->professions?->count() ?? 0 }}
                </h2>
                <ul class="list-disc p-3">
                    @foreach ($professionCategory->professions->sortBy('name') as $profession)
                        @if ($profession->identities->count() > 0)
                            <li>
                                <a href="{{ route('global.professions.edit', $profession->id) }}" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                    {{ $profession->name }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
