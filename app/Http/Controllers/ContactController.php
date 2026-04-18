<?php

// app/Http/Controllers/ContactController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactAutoReplyMail;
use App\Mail\ContactFormMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Kontak');
    }

    public function store(ContactRequest $request): JsonResponse
    {
        // Honeypot: silently succeed if bot filled the hidden field
        if ($request->filled('website')) {
            return response()->json(['success' => true]);
        }

        // Rate limiting: max 3 per IP per hour
        $key = 'contact:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Coba lagi dalam 1 jam.',
            ], 429);
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validated();
        $submittedAt = now()->setTimezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB';

        try {
            Mail::to('hello@theday.id')->send(new ContactFormMail(
                senderName:  $data['name'],
                senderEmail: $data['email'],
                topic:       $data['subject'],
                messageBody: $data['message'],
                submittedAt: $submittedAt,
            ));
        } catch (\Throwable $e) {
            Log::error('ContactFormMail failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
            // Still show success to user — don't expose mail errors
        }

        try {
            Mail::to($data['email'])->send(new ContactAutoReplyMail(
                senderName: $data['name'],
                topic:      $data['subject'],
            ));
        } catch (\Throwable $e) {
            Log::warning('ContactAutoReplyMail failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }
}
