<?php

// app/Http/Middleware/CheckInvitationAccess.php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Invitation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInvitationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug') ?? $request->route('invitationSlug');

        if ($slug) {
            $invitation = Invitation::where('slug', $slug)->first();

            if ($invitation && $invitation->status->value === 'archived') {
                return inertia('Invitation/Inactive', [
                    'slug'    => $invitation->slug,
                    'message' => 'Undangan ini sudah tidak aktif.',
                ])->toResponse($request);
            }
        }

        return $next($request);
    }
}
