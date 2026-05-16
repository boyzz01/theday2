<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StagingBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('staging')) {
            $user = config('staging.basic_auth.user');
            $password = config('staging.basic_auth.password');

            if ($request->getUser() !== $user || $request->getPassword() !== $password) {
                return response('Unauthorized', 401, [
                    'WWW-Authenticate' => 'Basic realm="Staging"',
                ]);
            }
        }

        return $next($request);
    }
}
