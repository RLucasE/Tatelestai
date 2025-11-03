<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LastSellsSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $lastSells;

    public function __construct($lastSells)
    {
        $this->lastSells = $lastSells;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->lastSells as $interval) {
            $rows[] = [
                $interval['from'] ?? '',
                $interval['to'] ?? '',
                $interval['count'] ?? 0
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Desde', 'Hasta', 'Cantidad de Ventas'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0']
            ]],
        ];
    }

    public function title(): string
    {
        return 'Ventas Últimas 24h';
    }
}

