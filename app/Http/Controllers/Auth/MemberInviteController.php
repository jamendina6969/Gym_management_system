<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MemberInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class MemberInviteController extends Controller
{
    public function show(string $token): View
    {
        $invitation = MemberInvitation::with('user')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isAccepted() || $invitation->isExpired()) {
            abort(410, 'This invitation link has expired or was already used.');
        }

        return view('auth.accept-invite', [
            'invitation' => $invitation,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = MemberInvitation::with('user')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isAccepted() || $invitation->isExpired()) {
            abort(410, 'This invitation link has expired or was already used.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
            'accept_waiver' => ['accepted'],
        ]);

        $user = $invitation->user;
        $user->password = Hash::make($validated['password']);
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }
        $user->save();

        if ($user->member && $user->member->waiver_accepted_at === null) {
            $user->member->update(['waiver_accepted_at' => now()]);
        }

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);

        return redirect()->route('portal.home')->with('success', 'Welcome! Your password is set and your portal is ready.');
    }
}
