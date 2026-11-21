<x-guest-layout>
    @if (isset($branches) && $branches->isEmpty() && \App\Models\Branch::count() > 0)
        <div class="text-center py-6 space-y-3">
            <h2 class="text-lg font-semibold text-gray-900">Online Registration Unavailable</h2>
            <p class="text-sm text-gray-600">Online self-registration is currently disabled. Please visit any branch front desk to register.</p>
            <a href="{{ route('login') }}" class="inline-block mt-2 text-sm text-emerald-600 hover:underline font-medium">Back to Login</a>
        </div>
    @else
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Full Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div class="mt-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Phone -->
            <div class="mt-4">
                <x-input-label for="phone" :value="__('Mobile Number (optional)')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" placeholder="+63 917 123 4567" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <!-- Home Branch -->
            @if (isset($branches) && $branches->isNotEmpty())
                <div class="mt-4">
                    <x-input-label for="home_branch_id" :value="__('Home Branch')" />
                    <select id="home_branch_id" name="home_branch_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('home_branch_id') == $branch->id)>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('home_branch_id')" class="mt-2" />
                </div>
            @endif

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <!-- Waiver -->
            <div class="mt-4">
                <label class="inline-flex items-start gap-2 text-xs text-gray-600">
                    <input type="checkbox" name="accept_waiver" value="1" @checked(old('accept_waiver')) required class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span>I agree to the gym rules, health &amp; safety waiver, and membership terms. My account will be activated upon payment or front-desk approval.</span>
                </label>
                <x-input-error :messages="$errors->get('accept_waiver')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button class="ms-4">
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </form>
    @endif
</x-guest-layout>
