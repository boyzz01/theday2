<?php

// app/Exports/GuestListExport.php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuestListExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly Collection $guests) {}

    public function collection(): Collection
    {
        return $this->guests->map(fn ($g) => [
            $g->name,
            $g->phone_number,
            $g->category ?? '-',
            $g->send_status->label(),
            $g->rsvp_status->label(),
            $g->last_sent_at?->format('d/m/Y H:i') ?? '-',
            $g->invitation?->title ?? '-',
        ]);
    }

    public function headings(): array
    {
        return ['Nama', 'Nomor WA', 'Kategori', 'Status Kirim', 'RSVP', 'Terakhir Kirim', 'Undangan'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
