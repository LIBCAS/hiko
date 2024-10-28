<x-guest-layout>
    <x-auth-card title="{{ __('Zapomenuté heslo') }}">
        <div class="mb-4 text-sm text-gray-600">
            {{ __('Zapomněli jste heslo? Zadejte svou e-mailovou adresu, na kterou vám přijde e-mail s odkazem na resetování hesla.') }}
        </div>
        <x-auth-session-status class="mb-4" :status="session('status')" />
        <x-auth-validation-errors class="mb-4" :errors="$errors" />
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <x-label for="email" :value="__('E-mail')" />
            <x-input id="email" class="block w-full mt-1" type="email" name="email" :value="old('email')" required
                autofocus />
            <x-button-simple class="w-full mt-4">
                {{ __('Odeslat žádost o změnu hesla') }}
            </x-button-simple>
        </form>
    </x-auth-card>
</x-guest-layout>
