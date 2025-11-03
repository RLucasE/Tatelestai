<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpiringOffersSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $expiringOffers;

    public function __construct($expiringOffers)
    {
        $this->expiringOffers = $expiringOffers;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->expiringOffers as $offer) {
            $rows[] = [
                $offer['day'] ?? $offer['label'] ?? '',
                $offer['count'] ?? $offer['value'] ?? 0
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Día', 'Cantidad de Ofertas'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FDE68A']
            ]],
        ];
    }

    public function title(): string
    {
        return 'Ofertas por Expirar';
    }
}
