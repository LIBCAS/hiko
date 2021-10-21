<x-guest-layout>
    <x-auth-card title="{{ __('Přihlášení') }}">
        <x-auth-session-status class="mb-4" :status="session('status')" />
        <x-auth-validation-errors class="mb-4" :errors="$errors" />
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <x-label for="email" :value="__('E-mail')" />
            <x-input id="email" class="block w-full mt-1" type="email" name="email" :value="old('email')" required
                autofocus />
            <x-label for="password" :value="__('Heslo')" class="mt-4" />
            <x-input id="password" class="block w-full mt-1" type="password" name="password" required
                autocomplete="current-password" />
            <div class="block mt-4">
                <x-checkbox name="remember_me" label="{{ __('Zapamatovat') }}" checked="false" />
            </div>
            <div class="flex flex-col items-center mt-4 space-y-2">
                <x-button-simple class="w-full">
                    {{ __('Přihlásit se') }}
                </x-button-simple>
                <a class="text-sm text-gray-600 underline hover:text-gray-900" href="{{ route('password.request') }}">
                    {{ __('Zapomněli jste heslo?') }}
                </a>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
