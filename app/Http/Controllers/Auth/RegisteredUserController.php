<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $branches = Branch::allowingSelfRegistration()->orderBy('name')->get();

        return view('auth.register', [
            'branches' => $branches,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $selfRegBranches = Branch::allowingSelfRegistration()->pluck('id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:30'],
            'home_branch_id' => array_filter([
                count($selfRegBranches) > 0 ? 'nullable' : 'nullable',
                count($selfRegBranches) > 0 ? Rule::in($selfRegBranches) : null,
            ]),
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'accept_waiver' => ['accepted'],
        ]);

        $homeBranchId = $validated['home_branch_id'] ?? ($selfRegBranches[0] ?? null);

        $user = DB::transaction(function () use ($validated, $homeBranchId) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
            ]);

            Role::findOrCreate('member', 'web');
            $user->assignRole('member');

            Member::create([
                'user_id' => $user->id,
                'home_branch_id' => $homeBranchId,
                'waiver_accepted_at' => now(),
                'status' => 'pending',
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
