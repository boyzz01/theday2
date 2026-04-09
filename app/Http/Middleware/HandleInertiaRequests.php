<?php

// app/Http/Middleware/HandleInertiaRequests.php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'avatar_url' => $user->avatar_url,
                ] : null,
                'subscription' => $user ? (function () use ($user) {
                    $sub = $user->activeSubscription;
                    if (! $sub) return null;
                    return [
                        'plan_name'        => $sub->plan->name,
                        'status'           => $sub->status,
                        'remove_watermark' => $sub->plan->remove_watermark,
                        'analytics_access' => $sub->plan->analytics_access,
                        'custom_music'     => $sub->plan->custom_music,
                    ];
                })() : null,
                'isGuest' => ! $user,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'translations' => [
                'id' => require lang_path('id/auth.php'),
                'en' => require lang_path('en/auth.php'),
            ],
        ];
    }
}
