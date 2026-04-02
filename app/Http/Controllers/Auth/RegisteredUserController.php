<?php

// app/Http/Controllers/Auth/RegisteredUserController.php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\AssignFreeSubscriptionAction;
use App\Actions\ConvertGuestDraftAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(
        Request $request,
        AssignFreeSubscriptionAction $assignFreeSubscription,
        ConvertGuestDraftAction $convertGuestDraft,
    ): RedirectResponse {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => UserRole::User,
        ]);

        $assignFreeSubscription->execute($user);

        event(new Registered($user));

        Auth::login($user);

        $sessionId = $request->cookie('guest_session_id');

        if ($sessionId) {
            $invitation = $convertGuestDraft->execute($user, $sessionId);

            if ($invitation) {
                return redirect()->route('dashboard')->with(
                    'flash',
                    ['type' => 'success', 'message' => 'Akun dibuat! Undanganmu berhasil disimpan. Lanjutkan mengedit.']
                );
            }
        }

        return redirect()->route('dashboard')->with(
            'flash',
            ['type' => 'success', 'message' => 'Selamat datang di TheDay! 🎉']
        );
    }
}
