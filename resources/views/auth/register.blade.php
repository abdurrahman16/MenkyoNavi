<x-guest-layout>
    <div class="mb-5" data-registration-form>
        <h1 class="text-2xl font-bold tracking-tight text-navy">{{ __('auth.register_title') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-navy/80">{{ __('auth.register_lead') }}</p>
    </div>

    @if (! $registrationOpen)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-navy" data-registration-closed>
            <p class="font-semibold">{{ __('auth.registration_closed_title') }}</p>
            <p class="mt-1 leading-relaxed">{{ __('auth.registration_closed') }}</p>
            <p class="mt-3">
                <a class="font-semibold text-accent hover:text-accent-dark" href="{{ route('login') }}">
                    {{ __('auth.already_registered') }}
                </a>
            </p>
        </div>
    @else
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" :value="__('auth.field_name')" />
                <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('auth.field_email')" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('auth.field_password')" />
                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('auth.field_password_confirmation')" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <p class="text-xs leading-relaxed text-navy/70">{{ __('auth.register_privacy_note') }}</p>

            <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
                <a class="text-sm font-semibold text-accent hover:text-accent-dark" href="{{ route('login') }}">
                    {{ __('auth.already_registered') }}
                </a>
                <x-primary-button class="min-h-12 justify-center">
                    {{ __('auth.register_submit') }}
                </x-primary-button>
            </div>
        </form>
    @endif
</x-guest-layout>
