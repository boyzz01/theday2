<?php

// app/Http/Controllers/OnboardingController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /** Reserved slugs that cannot be used as invitation URLs */
    private const RESERVED_SLUGS = [
        'login', 'register', 'logout', 'dashboard', 'admin', 'templates',
        'editor', 'use-template', 'onboarding', 'profile', 'up',
        'verify-email', 'confirm-password', 'forgot-password',
        'reset-password', 'email', 'sitemap', 'api',
    ];

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Setup', [
            'user' => [
                'name'  => $request->user()->name,
                'phone' => $request->user()->phone,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $noDate   = $request->boolean('no_date');
        $skipSlug = $request->boolean('skip_slug');

        $data = $request->validate([
            'groom_name'     => ['required', 'string', 'max:255'],
            'groom_nickname' => ['nullable', 'string', 'max:10'],
            'bride_name'     => ['required', 'string', 'max:255'],
            'bride_nickname' => ['nullable', 'string', 'max:10'],
            'phone'          => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'no_date'        => ['boolean'],
            'wedding_date'   => [$noDate ? 'nullable' : 'required', 'nullable', 'date_format:Y-m-d'],
            'start_time'     => ['nullable', 'date_format:H:i'],
            'venue_name'     => ['nullable', 'string', 'max:255'],
            'venue_address'  => ['nullable', 'string', 'max:1000'],
            'skip_slug'      => ['boolean'],
            'slug'           => [
                $skipSlug ? 'nullable' : 'required',
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('invitations', 'slug'),
                Rule::notIn(self::RESERVED_SLUGS),
            ],
        ], [
            'groom_name.required'   => 'Nama mempelai pria wajib diisi.',
            'bride_name.required'   => 'Nama mempelai wanita wajib diisi.',
            'groom_nickname.max'    => 'Nama panggilan pria maksimal 10 karakter.',
            'bride_nickname.max'    => 'Nama panggilan wanita maksimal 10 karakter.',
            'wedding_date.required' => 'Tanggal pernikahan wajib diisi, atau centang "Belum menentukan tanggal".',
            'slug.required'         => 'Slug undangan wajib diisi, atau centang "Lewati dan isi slug nanti".',
            'slug.unique'           => 'Slug ini sudah dipakai. Coba slug lain.',
            'slug.regex'            => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung (-).',
            'slug.not_in'           => 'Slug ini tidak bisa digunakan. Pilih slug lain.',
        ]);

        $user = $request->user();

        // Update user phone if provided and changed
        if (! empty($data['phone']) && $data['phone'] !== $user->phone) {
            $user->update(['phone' => $data['phone']]);
        }

        // Resolve template: use pending session or fall back to first active free template
        $pendingTemplateId = session()->pull('pending_template');
        $template = $pendingTemplateId
            ? Template::find($pendingTemplateId)
            : Template::active()->where('tier', 'free')->ordered()->first();

        // Build title and slug
        $title = trim("{$data['groom_name']} & {$data['bride_name']}");
        $slug  = ($skipSlug || empty($data['slug']))
            ? $this->generateUniqueSlug($title)
            : $data['slug'];

        // Create the first invitation draft
        $invitation = Invitation::create([
            'user_id'    => $user->id,
            'template_id' => $template->id,
            'title'       => $title,
            'event_type'  => 'pernikahan',
            'slug'        => $slug,
            'status'      => 'draft',
        ]);

        // Create invitation details with couple data
        $invitation->details()->create([
            'invitation_id'  => $invitation->id,
            'groom_name'     => $data['groom_name'],
            'groom_nickname' => $data['groom_nickname'] ?? null,
            'bride_name'     => $data['bride_name'],
            'bride_nickname' => $data['bride_nickname'] ?? null,
        ]);

        // Create event if date is provided
        if (! $noDate && ! empty($data['wedding_date'])) {
            $invitation->events()->create([
                'event_name'    => 'Akad & Resepsi',
                'event_date'    => $data['wedding_date'],
                'start_time'    => $data['start_time'] ?? null,
                'venue_name'    => $data['venue_name'] ?? '',
                'venue_address' => $data['venue_address'] ?? null,
                'sort_order'    => 0,
            ]);
        }

        // Mark onboarding as complete
        $user->update(['onboarding_completed_at' => now()]);

        return redirect()
            ->route('dashboard.invitations.edit', $invitation)
            ->with('flash', [
                'type'    => 'success',
                'message' => 'Selamat! Setup selesai. Undanganmu siap dikustomisasi.',
            ]);
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'undangan';
        $slug = $base;
        $i    = 1;

        while (
            Invitation::withTrashed()->where('slug', $slug)->exists()
            || in_array($slug, self::RESERVED_SLUGS, true)
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
