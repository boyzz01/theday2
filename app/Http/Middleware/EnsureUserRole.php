<?php

// app/Http/Middleware/EnsureUserRole.php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        foreach ($roles as $role) {
            $expected = UserRole::from($role);

            if ($userRole === $expected) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak.');
    }
}
