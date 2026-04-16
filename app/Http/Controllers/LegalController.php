<?php

// app/Http/Controllers/LegalController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacyPolicy(): View
    {
        return view('legal.privacy');
    }

    public function termsOfService(): View
    {
        return view('legal.terms');
    }

    public function cookiePolicy(): View
    {
        return view('legal.cookie');
    }
}
