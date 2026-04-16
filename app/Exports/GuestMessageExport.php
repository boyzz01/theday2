<?php

// app/Exports/GuestMessageExport.php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuestMessageExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly Collection $messages) {}

    public function collection(): Collection
    {
        return $this->messages->map(function ($m) {
            $status = [];
            if ($m->is_pinned) $status[] = 'Dipinned';
            if ($m->is_hidden) $status[] = 'Disembunyikan';
            if (empty($status)) $status[] = 'Tampil';

            return [
                $m->displayName(),
                $m->message,
                $m->created_at->format('d/m/Y H:i'),
                implode(', ', $status),
            ];
        });
    }

    public function headings(): array
    {
        return ['Nama', 'Ucapan', 'Waktu Kirim', 'Status'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
