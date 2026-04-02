<?php

// app/Http/Middleware/TrackGuestSession.php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackGuestSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $sessionId = $request->cookie('guest_session_id');

        if (empty($sessionId)) {
            $sessionId = (string) Str::uuid();
        }

        $request->merge(['guest_session_id' => $sessionId]);

        $response = $next($request);

        if (! $request->cookie('guest_session_id')) {
            $response->withCookie(cookie(
                name:     'guest_session_id',
                value:    $sessionId,
                minutes:  60 * 24 * 30,
                httpOnly: true,
                secure:   $request->isSecure(),
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}
