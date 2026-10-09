<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\ReporteAdminService;
use App\Services\ReporteService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ReporteAvance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Reporte de avance';

    protected static ?string $title = 'Reporte de avance';

    /**
     * Resumen ya calculado por id de trabajador, para no repetir consultas entre columnas.
     *
     * @var array<int, array{global: int, rendidos: int, aprobados: int, promedio: int|null}>
     */
    protected array $resumenes = [];

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('rol', 'trabajador')->with('areas'))
            ->columns([
                TextColumn::make('name')
                    ->label('Trabajador')
                    ->description(fn (User $record) => $record->cargo)
                    ->searchable(['name', 'email', 'cargo'])
                    ->sortable(),
                TextColumn::make('areas_count')
                    ->label('Áreas')
                    ->counts('areas')
                    ->sortable(),
                TextColumn::make('avance')
                    ->label('Avance global')
                    ->state(fn (User $record) => $this->resumen($record)['global'])
                    ->suffix('%')
                    ->badge()
                    ->color(fn (User $record) => $this->resumen($record)['global'] >= 100 ? 'success' : 'primary'),
                TextColumn::make('quizzes')
                    ->label('Quizzes aprobados')
                    ->state(function (User $record) {
                        $resumen = $this->resumen($record);

                        return $resumen['rendidos'] === 0 ? 'Sin rendir' : "{$resumen['aprobados']} de {$resumen['rendidos']}";
                    }),
                TextColumn::make('promedio')
                    ->label('Promedio de notas')
                    ->state(fn (User $record) => $this->resumen($record)['promedio'])
                    ->suffix('%')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('area')
                    ->label('Área')
                    ->relationship('areas', 'nombre'),
            ])
            ->recordActions([
                Action::make('detalle')
                    ->label('Ver detalle')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (User $record) => "Avance de {$record->name}")
                    ->modalContent(fn (User $record) => view('filament.reporte-trabajador', app(ReporteService::class)->datosPara($record)))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->slideOver(),
                Action::make('descargar')
                    ->label('Descargar PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (User $record) => route('reportes.trabajador', $record)),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Aún no hay trabajadores')
            ->emptyStateDescription('Crea trabajadores desde la sección Usuarios para ver su avance aquí.');
    }

    /**
     * @return array{global: int, rendidos: int, aprobados: int, promedio: int|null}
     */
    private function resumen(User $usuario): array
    {
        return $this->resumenes[$usuario->id] ??= app(ReporteAdminService::class)
            ->resumenTrabajadores(collect([$usuario]))
            ->get($usuario->id);
    }
}
