<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.maintenance_mode') && $request->path() !== 'maintenance') {
            return redirect()->route('maintenance');
        }

        return $next($request);
    }
}
