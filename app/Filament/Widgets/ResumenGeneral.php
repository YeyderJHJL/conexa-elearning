<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenGeneral extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|array|null $columns = ['md' => 2, 'xl' => 4];

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $datos = app(DashboardService::class)->datos();
        $avance = $datos['avancePromedio'];
        $aprobacion = $datos['aprobacion'];

        return [
            Stat::make('Trabajadores activos', $datos['trabajadores'])
                ->description($datos['nuevosSemana'] > 0 ? "+{$datos['nuevosSemana']} nuevos en 7 días" : 'Sin altas en los últimos 7 días')
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->descriptionColor($datos['nuevosSemana'] > 0 ? 'success' : 'gray')
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary')
                ->chart($datos['tendencias']['trabajadores'])
                ->chartColor('primary'),

            Stat::make('Áreas activas', $datos['areasActivas'])
                ->description("{$datos['modulosActivos']} módulos · {$datos['leccionesActivas']} lecciones activas")
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('secondary'),

            Stat::make('Avance promedio global', $avance === null ? '—' : "{$avance}%")
                ->description("{$datos['leccionesSemana']} lecciones completadas esta semana")
                ->icon(Heroicon::OutlinedChartBar)
                ->color('info')
                ->chart($datos['tendencias']['lecciones'])
                ->chartColor('info'),

            Stat::make('Aprobación de quizzes', $aprobacion['tasa'] === null ? '—' : "{$aprobacion['tasa']}%")
                ->description($aprobacion['rendidos'] === 0 ? 'Aún no hay quizzes rendidos' : "{$aprobacion['aprobados']} de {$aprobacion['rendidos']} quizzes rendidos aprobados")
                ->icon(Heroicon::OutlinedAcademicCap)
                ->color('success')
                ->chart($datos['tendencias']['aprobados'])
                ->chartColor('success'),
        ];
    }
}
