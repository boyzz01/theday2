<?php

// app/Http/Controllers/Dashboard/DashboardRsvpController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Rsvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardRsvpController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();

        $invitations = Invitation::where('user_id', $userId)
            ->with('template:id,name,default_config')
            ->withCount([
                'rsvps as total_rsvps',
                'rsvps as hadir_count'       => fn ($q) => $q->where('attendance', 'hadir'),
                'rsvps as tidak_hadir_count' => fn ($q) => $q->where('attendance', 'tidak_hadir'),
                'rsvps as ragu_count'        => fn ($q) => $q->where('attendance', 'ragu'),
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($inv) => [
                'id'               => $inv->id,
                'title'            => $inv->title,
                'status'           => $inv->status->value,
                'total_rsvps'      => $inv->total_rsvps,
                'hadir_count'      => $inv->hadir_count,
                'tidak_hadir_count'=> $inv->tidak_hadir_count,
                'ragu_count'       => $inv->ragu_count,
                'total_tamu'       => Rsvp::where('invitation_id', $inv->id)
                                        ->where('attendance', 'hadir')
                                        ->sum('guest_count'),
                'template_color'   => $inv->template->default_config['primary_color'] ?? '#D4A373',
            ]);

        return Inertia::render('Dashboard/Rsvp/Index', [
            'invitations' => $invitations,
        ]);
    }

    public function show(Request $request, Invitation $invitation): Response
    {
        abort_if($invitation->user_id !== Auth::id(), 403);

        $filter = $request->query('filter', 'semua');

        $query = Rsvp::where('invitation_id', $invitation->id)
            ->orderBy('created_at', 'desc');

        if ($filter === 'hadir')        $query->where('attendance', 'hadir');
        elseif ($filter === 'tidak_hadir') $query->where('attendance', 'tidak_hadir');
        elseif ($filter === 'ragu')     $query->where('attendance', 'ragu');

        $rsvps = $query->get()->map(fn ($r) => [
            'id'          => $r->id,
            'guest_name'  => $r->guest_name,
            'phone'       => $r->phone,
            'attendance'  => $r->attendance->value,
            'guest_count' => $r->guest_count,
            'notes'       => $r->notes,
            'created_at'  => $r->created_at->format('d M Y, H:i'),
        ]);

        $base = Rsvp::where('invitation_id', $invitation->id);

        $summary = [
            'total'        => (clone $base)->count(),
            'hadir'        => (clone $base)->where('attendance', 'hadir')->count(),
            'tidak_hadir'  => (clone $base)->where('attendance', 'tidak_hadir')->count(),
            'ragu'         => (clone $base)->where('attendance', 'ragu')->count(),
            'total_tamu'   => (clone $base)->where('attendance', 'hadir')->sum('guest_count'),
        ];

        return Inertia::render('Dashboard/Rsvp/Show', [
            'invitation' => [
                'id'             => $invitation->id,
                'title'          => $invitation->title,
                'status'         => $invitation->status->value,
                'template_color' => $invitation->template?->default_config['primary_color'] ?? '#D4A373',
            ],
            'rsvps'   => $rsvps,
            'summary' => $summary,
            'filter'  => $filter,
        ]);
    }

    public function export(Invitation $invitation): StreamedResponse
    {
        abort_if($invitation->user_id !== Auth::id(), 403);

        $rsvps = Rsvp::where('invitation_id', $invitation->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'rsvp-' . $invitation->slug . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rsvps) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Nama', 'No. HP', 'Kehadiran', 'Jumlah Tamu', 'Catatan', 'Waktu Daftar']);

            $label = [
                'hadir'       => 'Hadir',
                'tidak_hadir' => 'Tidak Hadir',
                'ragu'        => 'Masih Ragu',
            ];

            foreach ($rsvps as $r) {
                fputcsv($out, [
                    $r->guest_name,
                    $r->phone ?? '-',
                    $label[$r->attendance->value] ?? $r->attendance->value,
                    $r->guest_count,
                    $r->notes ?? '-',
                    $r->created_at->format('d/m/Y H:i'),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
