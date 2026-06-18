<x-app-layout :title="$title">
    <x-success-alert />

    <h2 class="mb-3 text-lg font-semibold">{{ __('hiko.incoming_transfers') }}</h2>
    @include('pages.inter-tenant-transfers.partials.request-table', ['requests' => $incoming, 'direction' => 'incoming'])

    <h2 class="mb-3 mt-8 text-lg font-semibold">{{ __('hiko.outgoing_transfers') }}</h2>
    @include('pages.inter-tenant-transfers.partials.request-table', ['requests' => $outgoing, 'direction' => 'outgoing'])
</x-app-layout>
