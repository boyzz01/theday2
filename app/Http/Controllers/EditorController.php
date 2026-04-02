<?php

// app/Http/Controllers/EditorController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GuestDraft;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditorController extends Controller
{
    public function create(Request $request): Response
    {
        $template = Template::active()
            ->with('category:id,name,slug')
            ->findOrFail($request->get('template'));

        $defaultMusic = [
            ['id' => 'canon-d',          'title' => 'Canon in D — Pachelbel',             'file_url' => null],
            ['id' => 'thousand-years',   'title' => 'A Thousand Years — Christina Perri', 'file_url' => null],
            ['id' => 'perfect',          'title' => 'Perfect — Ed Sheeran',               'file_url' => null],
            ['id' => 'cant-help',        'title' => "Can't Help Falling in Love — Elvis", 'file_url' => null],
            ['id' => 'all-of-me',        'title' => 'All of Me — John Legend',            'file_url' => null],
            ['id' => 'marry-you',        'title' => 'Marry You — Bruno Mars',             'file_url' => null],
            ['id' => 'thinking-out-loud','title' => 'Thinking Out Loud — Ed Sheeran',     'file_url' => null],
        ];

        $fonts = [
            ['value' => 'Playfair Display',   'label' => 'Playfair Display',   'category' => 'Serif'],
            ['value' => 'Cormorant Garamond', 'label' => 'Cormorant Garamond', 'category' => 'Serif'],
            ['value' => 'Great Vibes',        'label' => 'Great Vibes',        'category' => 'Script'],
            ['value' => 'Dancing Script',     'label' => 'Dancing Script',     'category' => 'Script'],
            ['value' => 'Parisienne',         'label' => 'Parisienne',         'category' => 'Script'],
            ['value' => 'Cinzel',             'label' => 'Cinzel',             'category' => 'Display'],
            ['value' => 'Lato',               'label' => 'Lato',               'category' => 'Sans-serif'],
            ['value' => 'Montserrat',         'label' => 'Montserrat',         'category' => 'Sans-serif'],
        ];

        // Load existing guest draft data if any
        $guestDraft = null;
        $sessionId  = $request->cookie('guest_session_id') ?? $request->input('guest_session_id');

        if ($sessionId && ! $request->user()) {
            $guestDraft = GuestDraft::bySession($sessionId)->notExpired()->first();
        }

        return Inertia::render('Editor/Create', [
            'template' => [
                'id'             => $template->id,
                'name'           => $template->name,
                'slug'           => $template->slug,
                'thumbnail_url'  => $template->thumbnail_url,
                'tier'           => $template->tier->value,
                'default_config' => $template->default_config ?? [],
                'category'       => [
                    'name' => $template->category->name,
                    'slug' => $template->category->slug,
                ],
            ],
            'defaultMusic' => $defaultMusic,
            'fonts'        => $fonts,
            'isGuest'      => ! $request->user(),
            'guestDraft'   => $guestDraft ? [
                'id'   => $guestDraft->id,
                'step' => $guestDraft->step,
                'data' => $guestDraft->data,
            ] : null,
        ]);
    }
}
