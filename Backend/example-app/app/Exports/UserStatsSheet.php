<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserStatsSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $userStats;

    public function __construct($userStats)
    {
        $this->userStats = $userStats;
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['Total de Usuarios', $this->userStats['total'] ?? 0];

        $rows[] = ['', ''];


        foreach ($this->userStats['data'] as $stat) {
            $rows[] = [
                $stat['state'] ?? '',
                $stat['count'] ?? 0
            ];
        }


        return $rows;
    }

    public function headings(): array
    {
        return ['Estado', 'Cantidad'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 14], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5E9']
            ]],
        ];
    }

    public function title(): string
    {
        return 'Estadísticas de Usuarios';
    }
}

