<?php

// app/Http/Controllers/Auth/AuthenticatedSessionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\ConvertGuestDraftAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }

    public function store(
        LoginRequest $request,
        ConvertGuestDraftAction $convertGuestDraft,
    ): RedirectResponse {
        $request->authenticate();

        $request->session()->regenerate();

        $sessionId = $request->cookie('guest_session_id');

        if ($sessionId) {
            $invitation = $convertGuestDraft->execute($request->user(), $sessionId);

            if ($invitation) {
                return redirect()->intended(route('dashboard', absolute: false))->with(
                    'flash',
                    ['type' => 'success', 'message' => 'Selamat datang kembali! Undanganmu berhasil dipulihkan.']
                );
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
