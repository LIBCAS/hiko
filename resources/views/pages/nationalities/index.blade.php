<x-app-layout :title="$title">
    <x-page-lock scope="global" resource-type="nationalities_admin" :redirect-url="route('letters')" :read-only-on-deny="true" />
    <x-success-alert />
    @if($errors->any()) <div class="mb-4 text-red-600">{{ $errors->first() }}</div> @endif
    <div class="mb-4 flex flex-wrap align-middle justify-between">
        <h1 class="mb-4 text-xl font-bold">{{ $title }}</h1>
        <div class="flex justify-end">
            <x-loading-link href="{{ route('nationalities.expansions.index') }}">
                {{ __('hiko.nationality_expansions') }}
            </x-loading-link>
        </div>
    </div>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm text-left">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-700">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('hiko.nationality_cs') }}</th>
                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('hiko.nationality_en') }}</th>
                    <th scope="col" class="w-28 px-4 py-3 text-center font-semibold">{{ __('hiko.action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="bg-gray-50">
                    <td class="px-4 py-3 align-middle">
                        <x-input form="nationality-create" name="cs" required maxlength="255" :value="old('cs')"
                            :aria-label="__('hiko.nationality_cs')" :placeholder="__('hiko.nationality_cs')" class="block w-full min-w-48" />
                    </td>
                    <td class="px-4 py-3 align-middle">
                        <x-input form="nationality-create" name="en" required maxlength="255" :value="old('en')"
                            :aria-label="__('hiko.nationality_en')" :placeholder="__('hiko.nationality_en')" class="block w-full min-w-48" />
                    </td>
                    <td class="px-4 py-3 align-middle">
                        <form id="nationality-create" method="POST" action="{{ route('nationalities.store') }}" class="flex justify-center">
                            @csrf
                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-gray-800 text-white hover:bg-gray-700 focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                title="{{ __('hiko.create') }}" aria-label="{{ __('hiko.create') }}">
                                <x-icons.plus class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </form>
                    </td>
                </tr>
                @foreach($nationalities as $nationality)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 align-middle">
                            <x-input form="nationality-save-{{ $nationality->id }}" name="cs" required maxlength="255"
                                :value="$nationality->getTranslation('name', 'cs', false)" :aria-label="__('hiko.nationality_cs')" class="block w-full min-w-48" />
                        </td>
                        <td class="px-4 py-2 align-middle">
                            <x-input form="nationality-save-{{ $nationality->id }}" name="en" required maxlength="255"
                                :value="$nationality->getTranslation('name', 'en', false)" :aria-label="__('hiko.nationality_en')" class="block w-full min-w-48" />
                        </td>
                        <td class="px-4 py-2 align-middle">
                            <div class="flex items-center justify-center gap-2">
                                <form id="nationality-save-{{ $nationality->id }}" method="POST" action="{{ route('nationalities.update', $nationality) }}">
                                    @csrf @method('PUT')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-green-700 hover:bg-green-100 focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                        title="{{ __('hiko.save') }}" aria-label="{{ __('hiko.save') }}">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('nationalities.destroy', $nationality) }}" onsubmit="return confirm(@js(__('hiko.confirm_delete_nationality')))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                        title="{{ __('hiko.remove') }}" aria-label="{{ __('hiko.remove') }}">
                                        <x-icons.remove class="h-5 w-5" aria-hidden="true" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
