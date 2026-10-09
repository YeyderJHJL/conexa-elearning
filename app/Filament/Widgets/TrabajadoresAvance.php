<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\DashboardService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

class TrabajadoresAvance extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Trabajadores y su avance';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Avance ya calculado por trabajador, para no repetir consultas entre columnas.
     *
     * @var array<int, array{global: int, estado: string, ultima: Carbon|null}>
     */
    protected array $resumenes = [];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => User::query()->where('rol', 'trabajador')->where('activo', true)->with('areas'))
            ->defaultSort('name')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('name')
                    ->label('Trabajador')
                    ->description(fn (User $record) => $record->cargo)
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('areas_count')
                    ->label('Áreas')
                    ->counts('areas')
                    ->badge()
                    ->color('primary')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('avance')
                    ->label('Avance global')
                    ->state(fn (User $record) => $this->resumen($record)['global'])
                    ->suffix('%')
                    ->badge()
                    ->color(fn (User $record) => $this->resumen($record)['global'] >= 100 ? 'success' : 'primary')
                    ->alignCenter(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->state(fn (User $record) => $this->resumen($record)['estado'])
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        DashboardService::COMPLETADO => 'Completado',
                        DashboardService::EN_PROGRESO => 'En progreso',
                        default => 'Sin iniciar',
                    })
                    ->color(fn (string $state) => match ($state) {
                        DashboardService::COMPLETADO => 'success',
                        DashboardService::EN_PROGRESO => 'secondary',
                        default => 'gray',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        DashboardService::COMPLETADO => Heroicon::OutlinedCheckCircle,
                        DashboardService::EN_PROGRESO => Heroicon::OutlinedArrowPath,
                        default => Heroicon::OutlinedMinusCircle,
                    }),
                TextColumn::make('ultimo_avance')
                    ->label('Último avance')
                    ->state(fn (User $record) => $this->resumen($record)['ultima'])
                    ->dateTime('d/m/Y H:i')
                    ->icon(Heroicon::OutlinedClock)
                    ->placeholder('Sin actividad'),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (User $record) => route('reportes.trabajador', $record)),
            ])
            ->emptyStateHeading('Aún no hay trabajadores activos')
            ->emptyStateDescription('Crea trabajadores desde Personas > Usuarios para ver su avance aquí.')
            ->emptyStateIcon(Heroicon::OutlinedUsers);
    }

    /**
     * @return array{global: int, estado: string, ultima: Carbon|null}
     */
    private function resumen(User $usuario): array
    {
        return $this->resumenes[$usuario->id] ??= app(DashboardService::class)->paraTrabajador($usuario);
    }
}
