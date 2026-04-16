<?php

// app/Exports/RsvpExport.php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Invitation;
use App\Models\Rsvp;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class RsvpExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    private static array $label = [
        'hadir'       => 'Hadir',
        'tidak_hadir' => 'Tidak Hadir',
        'ragu'        => 'Masih Ragu',
    ];

    public function __construct(private readonly Invitation $invitation) {}

    public function collection(): Collection
    {
        return Rsvp::where('invitation_id', $this->invitation->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($r) => [
                $r->guest_name,
                $r->phone ?? '-',
                self::$label[$r->attendance->value] ?? $r->attendance->value,
                $r->attendance->value === 'hadir' ? $r->guest_count : '-',
                $r->notes ?? '-',
                $r->created_at->format('d/m/Y H:i'),
            ]);
    }

    public function headings(): array
    {
        return ['Nama', 'No. HP', 'Kehadiran', 'Jumlah Tamu', 'Catatan', 'Waktu Daftar'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
