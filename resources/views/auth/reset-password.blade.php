<x-guest-layout>
    <x-auth-card title="{{ __('Nastavení nového hesla') }}">
        <x-auth-validation-errors class="mb-4" :errors="$errors" />
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-label for="email" :value="__('E-mail')" />
            <x-input id="email" class="block w-full mt-1" type="email" name="email"
                :value="old('email', $request->email)" required autofocus />
            <x-label for="password" :value="__('Nové heslo')" class="mt-4" />
            <x-input id="password" class="block w-full mt-1" type="password" name="password" required />
            <x-label for="password_confirmation" :value="__('Zopakujte nové heslo')" class="mt-4" />
            <x-input id="password_confirmation" class="block w-full mt-1" type="password" name="password_confirmation"
                required />
            <x-button-simple class="w-full mt-4">
                {{ __('Nastavit nové heslo') }}
            </x-button-simple>
        </form>
    </x-auth-card>
</x-guest-layout>
