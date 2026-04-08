<?php

// app/Http/Controllers/TemplateGalleryController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateGalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = TemplateCategory::active()
            ->ordered()
            ->get(['id', 'name', 'name_en', 'slug']);

        $templates = Template::active()
            ->with('category:id,name,name_en,slug')
            ->when($request->category, fn ($q) => $q->whereHas(
                'category',
                fn ($q) => $q->where('slug', $request->category)
            ))
            ->when($request->tier && $request->tier !== 'all', fn ($q) => $q->where('tier', $request->tier))
            ->ordered()
            ->get()
            ->map(fn ($t) => [
                'id'             => $t->id,
                'name'           => $t->name,
                'name_en'        => $t->name_en ?? $t->name,
                'slug'           => $t->slug,
                'thumbnail_url'  => $t->thumbnail_url,
                'description'    => $t->description,
                'description_en' => $t->description_en ?? $t->description,
                'tier'           => $t->tier->value,
                'default_config' => $t->default_config,
                'demo_data'      => $t->demo_data,
                'category'       => [
                    'name'    => $t->category->name,
                    'name_en' => $t->category->name_en ?? $t->category->name,
                    'slug'    => $t->category->slug,
                ],
            ]);

        return Inertia::render('Templates/Gallery', [
            'categories' => $categories,
            'templates'  => $templates,
            'filters'    => [
                'category' => $request->category ?? 'all',
                'tier'     => $request->tier ?? 'all',
            ],
            'isGuest'    => ! $request->user(),
        ]);
    }

    public function demo(Request $request, Template $template): Response
    {
        $demo   = $template->demo_data ?? [];
        $config = array_merge(
            $template->default_config        ?? [],
            $demo['custom_config']            ?? []
        );

        $isWedding = isset($demo['details']['groom_name']);
        $eventType = $isWedding ? 'pernikahan' : 'ulang_tahun';

        // Format events to match invitation shape expected by section components
        $events = collect($demo['events'] ?? [])->map(function ($e, $i) {
            $date = isset($e['event_date'])
                ? Carbon::parse($e['event_date'])
                : null;

            return [
                'id'                   => $i + 1,
                'event_name'           => $e['event_name']    ?? '',
                'event_date'           => $e['event_date']    ?? null,
                'event_date_formatted' => $date
                    ? $date->locale('id')->translatedFormat('l, d F Y')
                    : null,
                'start_time'    => $e['start_time']    ?? null,
                'end_time'      => $e['end_time']      ?? null,
                'venue_name'    => $e['venue_name']    ?? null,
                'venue_address' => $e['venue_address'] ?? null,
                'maps_url'      => $e['maps_url']      ?? null,
            ];
        })->values()->all();

        // Format gallery: demo stores plain URL strings
        $galleries = collect($demo['gallery'] ?? [])->map(fn ($url, $i) => [
            'id'        => $i + 1,
            'image_url' => $url,
            'caption'   => null,
        ])->values()->all();

        $demoMessages = [
            ['id' => 1, 'name' => 'Budi & Ani',          'message' => 'Selamat menempuh hidup baru! Semoga menjadi keluarga yang sakinah mawaddah warahmah 🤲',    'created_at' => '2 jam lalu'],
            ['id' => 2, 'name' => 'Keluarga Pak Harto',  'message' => 'Barakallah! Semoga pernikahannya diberkahi Allah SWT 🙏',                                   'created_at' => '5 jam lalu'],
            ['id' => 3, 'name' => 'Rina',                 'message' => 'Happy wedding! Udah ga sabar mau dateng nih 🎉',                                           'created_at' => '1 hari lalu'],
            ['id' => 4, 'name' => 'Teman SMA',            'message' => 'Akhirnya nikah juga! Selamat ya bro & sis! 💍',                                            'created_at' => '2 hari lalu'],
        ];

        return Inertia::render('Templates/Demo', [
            'template' => [
                'id'   => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'tier' => $template->tier->value,
            ],
            'invitation' => [
                'id'         => 'demo',
                'title'      => $isWedding
                    ? (($demo['details']['groom_name'] ?? '') . ' & ' . ($demo['details']['bride_name'] ?? ''))
                    : ('Ulang Tahun ' . ($demo['details']['birthday_person_name'] ?? '')),
                'slug'       => 'demo',
                'event_type' => $eventType,
                'details'    => $demo['details'] ?? null,
                'events'     => $events,
                'galleries'  => $galleries,
                'music'      => null,
                'config'        => $config,
                'template_slug' => $template->slug,
                'expires_at'    => null,
            ],
            'messages' => $demoMessages,
            'isGuest'  => ! $request->user(),
        ]);
    }
}
