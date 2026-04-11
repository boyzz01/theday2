<?php

// app/Http/Controllers/UseTemplateController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInvitationFromTemplateAction;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UseTemplateController extends Controller
{
    public function __invoke(
        Request $request,
        Template $template,
        CreateInvitationFromTemplateAction $createInvitation,
    ): RedirectResponse {
        if (! $request->user()) {
            session(['pending_template' => $template->id]);
            return redirect()->route('register');
        }

        $invitation = $createInvitation->execute($request->user(), $template->id);

        if (! $invitation) {
            return redirect()->route('dashboard.templates')
                ->with('flash', ['type' => 'error', 'message' => 'Template tidak ditemukan.']);
        }

        return redirect()->route('dashboard.invitations.edit', $invitation)
            ->with('flash', ['type' => 'success', 'message' => 'Template dipilih. Mulai buat undanganmu!']);
    }
}
