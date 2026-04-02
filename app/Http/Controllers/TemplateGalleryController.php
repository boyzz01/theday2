<?php

// app/Http/Controllers/TemplateGalleryController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateGalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = TemplateCategory::active()
            ->ordered()
            ->get(['id', 'name', 'slug']);

        $templates = Template::active()
            ->with('category:id,name,slug')
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
                'slug'           => $t->slug,
                'thumbnail_url'  => $t->thumbnail_url,
                'description'    => $t->description,
                'tier'           => $t->tier->value,
                'default_config' => $t->default_config,
                'category'       => [
                    'name' => $t->category->name,
                    'slug' => $t->category->slug,
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
}
