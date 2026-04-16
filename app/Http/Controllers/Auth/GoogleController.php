<?php

// app/Http/Controllers/Auth/GoogleController.php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\AssignFreeSubscriptionAction;
use App\Actions\CreateInvitationFromTemplateAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(
        AssignFreeSubscriptionAction $assignFreeSubscription,
        CreateInvitationFromTemplateAction $createInvitation,
    ): RedirectResponse {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link google_id if not already set
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = User::create([
                'name'       => $googleUser->getName(),
                'email'      => $googleUser->getEmail(),
                'google_id'  => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role'       => UserRole::User,
            ]);

            $assignFreeSubscription->execute($user);
            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        if ($user->role->isAdmin()) {
            return redirect()->route('admin.articles.index');
        }

        if (! $user->hasCompletedOnboarding()) {
            return redirect()->route('onboarding');
        }

        $pendingTemplate = session()->pull('pending_template');

        if ($pendingTemplate) {
            $invitation = $createInvitation->execute($user, $pendingTemplate);
            if ($invitation) {
                return redirect()->route('dashboard.invitations.edit', $invitation)
                    ->with('flash', ['type' => 'success', 'message' => 'Selamat datang! Template sudah dipilih.']);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
