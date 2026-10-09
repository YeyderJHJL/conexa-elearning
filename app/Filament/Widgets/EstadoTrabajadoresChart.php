<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class EstadoTrabajadoresChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Trabajadores por estado';

    protected ?string $description = 'Completado: 100 % de avance. Sin iniciar: sin lecciones ni quizzes.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $estados = app(DashboardService::class)->datos()['estados'];

        return [
            'datasets' => [[
                'label' => 'Trabajadores',
                'data' => [$estados['completado'], $estados['en_progreso'], $estados['sin_iniciar']],
                'backgroundColor' => [config('marca.colores.dorado'), config('marca.colores.complementario'), config('marca.colores.gris_claro')],
                'borderColor' => '#FFFFFF',
                'borderWidth' => 2,
            ]],
            'labels' => ['Completado', 'En progreso', 'Sin iniciar'],
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            cutout: '62%',
            plugins: { legend: { position: 'bottom' } },
            scales: { x: { display: false }, y: { display: false } },
        }
        JS);
    }
}
