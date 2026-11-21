<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Set Up Your Member Account</h2>
        <p class="text-sm text-gray-600 mt-1">
            Welcome, <span class="font-medium text-gray-900">{{ $invitation->user->name }}</span> ({{ $invitation->user->email }}). Choose a password to activate your Member Portal access.
        </p>
    </div>

    <form method="POST" action="{{ route('invite.accept', $invitation->token) }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4">
            <label class="inline-flex items-start gap-2 text-xs text-gray-600">
                <input type="checkbox" name="accept_waiver" value="1" required class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                <span>I accept the gym rules, health &amp; safety waiver, and membership terms.</span>
            </label>
            <x-input-error :messages="$errors->get('accept_waiver')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-5">
            <x-primary-button>
                {{ __('Activate My Portal') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
