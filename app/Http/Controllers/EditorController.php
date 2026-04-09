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
            ['id' => 'canon-d',           'title' => 'Canon in D — Pachelbel',             'file_url' => '/music/Canon-in-D-Pachelbels-Canon-Cell.mp3'],
            ['id' => 'thousand-years',    'title' => 'A Thousand Years — Christina Perri', 'file_url' => '/music/Brooklyn-Duo-A-Thousand-Years-WE.mp3'],
            ['id' => 'perfect',           'title' => 'Perfect — Ed Sheeran',               'file_url' => '/music/Perfect-Ed-Sheeran-Wedding-Versi.mp3'],
            ['id' => 'cant-help',         'title' => "Can't Help Falling in Love — Elvis", 'file_url' => '/music/Elvis-Presley-Cant-Help-Falling.mp3'],
            ['id' => 'marry-you',         'title' => 'Marry You — Bruno Mars',             'file_url' => '/music/Bruno-Mars-Marry-You-Official-Ly.mp3'],
            ['id' => 'beautiful-in-white','title' => 'Beautiful In White — Westlife',      'file_url' => '/music/Westlife-Beautiful-in-white-Lyri.mp3'],
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
