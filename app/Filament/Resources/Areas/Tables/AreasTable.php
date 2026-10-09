<?php

namespace App\Filament\Resources\Areas\Tables;

use App\Filament\Support\ColumnasComunes;
use App\Models\Area;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('imagen')
                    ->label('Portada')
                    ->disk('public')
                    ->imageSize(48)
                    ->square(),
                TextColumn::make('nombre')
                    ->label('Área')
                    ->weight('semibold')
                    ->description(fn (Area $record) => filled($record->descripcion) ? Str::limit($record->descripcion, 60) : null)
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('color')
                    ->label('Color'),
                ColumnasComunes::recuento('modulos', 'Módulos', Heroicon::OutlinedRectangleStack),
                ColumnasComunes::recuento('usuarios', 'Trabajadores', Heroicon::OutlinedUsers, 'secondary'),
                ColumnasComunes::tieneValor('resumen_pdf', 'Resumen PDF', Heroicon::OutlinedDocumentText),
                TextColumn::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                ColumnasComunes::estado('activa', 'Activa', 'Inactiva'),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('icono')
                    ->label('Ícono')
                    ->toggleable(isToggledHiddenByDefault: true),
                ...ColumnasComunes::fechas(),
            ])
            ->filters([
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
