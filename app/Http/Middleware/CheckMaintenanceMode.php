<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (env('MAINTENANCE_MODE', false) && $request->path() !== 'maintenance') {
            return redirect()->route('maintenance');
        }

        return $next($request);
    }
}
