<?php

namespace App\Filament\Resources\Leccions\Tables;

use App\Filament\Support\ColumnasComunes;
use App\Models\Area;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeccionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnasComunes::relacionado('modulo.titulo', 'Módulo', Heroicon::OutlinedRectangleStack),
                TextColumn::make('titulo')
                    ->label('Lección')
                    ->weight('semibold')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                ColumnasComunes::tieneValor('url_video', 'Video', Heroicon::OutlinedVideoCamera),
                ColumnasComunes::tieneValor('archivo_pdf', 'PDF', Heroicon::OutlinedDocumentText),
                TextColumn::make('duracion_min')
                    ->label('Duración')
                    ->suffix(' min')
                    ->icon(Heroicon::OutlinedClock)
                    ->placeholder('—')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                ColumnasComunes::estado('activa', 'Activa', 'Inactiva'),
                ...ColumnasComunes::fechas(),
            ])
            ->filters([
                SelectFilter::make('area')
                    ->label('Área')
                    ->options(fn () => Area::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $area) => $query->whereHas('modulo', fn (Builder $modulo) => $modulo->where('area_id', $area))
                    )),
                SelectFilter::make('modulo_id')
                    ->label('Módulo')
                    ->relationship('modulo', 'titulo')
                    ->searchable()
                    ->preload(),
                ColumnasComunes::filtroEstado('activa', 'Estado', 'Activas', 'Inactivas'),
            ])
            ->defaultSort('orden')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
