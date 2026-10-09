<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class AvancePorAreaChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Avance promedio por área';

    protected ?string $description = 'Promedio entre los trabajadores que tienen el área asignada.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $areas = app(DashboardService::class)->datos()['avancePorArea'];

        return [
            'datasets' => [[
                'label' => 'Avance promedio (%)',
                'data' => $areas->pluck('promedio')->all(),
                'backgroundColor' => config('marca.colores.dorado'),
                'borderColor' => config('marca.colores.azul'),
                'borderWidth' => 1,
                'borderRadius' => 6,
            ]],
            'labels' => $areas->map(fn (array $fila) => $fila['area']->nombre)->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: (valor) => valor + '%' } },
            },
        }
        JS);
    }
}
