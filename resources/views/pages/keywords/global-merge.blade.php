<x-app-layout :title="$title">
    <x-success-alert />
    <x-page-lock
        scope="global"
        resource-type="keyword_global_merge"
        :redirect-url="route('keywords')"
        :read-only-on-deny="true" />

    <div x-data="{ showGuide: false }">
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('keywords') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
                <span>←</span> {{ __('hiko.back_to_keywords') }}
            </a>
            <button @click="showGuide = !showGuide" type="button" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ __('hiko.guide') }}
            </button>
        </div>

        <div x-show="showGuide" x-transition class="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4 relative" style="display: none;">
            <button @click="showGuide = false" class="absolute top-2 right-9 text-blue-400 hover:text-blue-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-sm font-bold text-blue-800">{{ __('hiko.how_to_merge') }}</h3>
            <ul class="list-disc list-inside text-sm text-blue-700 mt-2">
                <li>{{ __('hiko.global_keyword_merge_step_1') }}</li>
                <li>{{ __('hiko.global_keyword_merge_step_2') }}</li>
                <li>{{ __('hiko.global_keyword_merge_step_3') }}</li>
            </ul>
        </div>

        <livewire:global-keyword-merge />
    </div>
</x-app-layout>
