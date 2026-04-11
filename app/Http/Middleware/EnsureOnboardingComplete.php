<?php

// app/Http/Middleware/EnsureOnboardingComplete.php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasCompletedOnboarding()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Onboarding not completed.'], 403);
            }

            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
