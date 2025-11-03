<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActiveOffersSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $activeOffers;

    public function __construct($activeOffers)
    {
        $this->activeOffers = $activeOffers;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->activeOffers as $offer) {
            $rows[] = [
                $offer['establishment_type'] ?? '',
                $offer['count'] ?? 0
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Tipo de Establecimiento', 'Cantidad de Ofertas'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'A7F3D0']
            ]],
        ];
    }

    public function title(): string
    {
        return 'Ofertas Activas';
    }
}

