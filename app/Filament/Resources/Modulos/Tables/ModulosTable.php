<?php

namespace App\Filament\Resources\Modulos\Tables;

use App\Filament\Support\ColumnasComunes;
use App\Models\Modulo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ModulosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('imagen')
                    ->label('Imagen')
                    ->disk('public')
                    ->imageSize(48)
                    ->square(),
                ColumnasComunes::relacionado('area.nombre', 'Área', Heroicon::OutlinedSquares2x2),
                TextColumn::make('titulo')
                    ->label('Módulo')
                    ->weight('semibold')
                    ->description(fn (Modulo $record) => filled($record->descripcion) ? Str::limit($record->descripcion, 60) : null)
                    ->searchable()
                    ->sortable(),
                ColumnasComunes::recuento('lecciones', 'Lecciones', Heroicon::OutlinedBookOpen),
                TextColumn::make('quiz_count')
                    ->label('Quiz')
                    ->counts('quiz')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Con quiz' : 'Sin quiz')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->icon(fn ($state) => $state ? Heroicon::OutlinedClipboardDocumentCheck : Heroicon::OutlinedMinus)
                    ->alignCenter(),
                ColumnasComunes::tieneValor('resumen_pdf', 'Resumen PDF', Heroicon::OutlinedDocumentText),
                TextColumn::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                ColumnasComunes::estado('activo', 'Activo', 'Inactivo'),
                ...ColumnasComunes::fechas(),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('Área')
                    ->relationship('area', 'nombre')
                    ->searchable()
                    ->preload(),
                ColumnasComunes::filtroEstado('activo', 'Estado', 'Activos', 'Inactivos'),
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
