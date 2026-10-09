<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Support\ColumnasComunes;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->weight('semibold')
                    ->description(fn (User $record) => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('rol')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => User::ETIQUETAS_ROL[$state] ?? ucfirst($state))
                    ->color(fn (string $state) => $state === 'admin' ? 'secondary' : 'primary')
                    ->icon(fn (string $state) => $state === 'admin' ? Heroicon::OutlinedShieldCheck : Heroicon::OutlinedUser)
                    ->sortable(),
                TextColumn::make('cargo')
                    ->placeholder('—')
                    ->searchable(),
                ColumnasComunes::recuento('areas', 'Áreas', Heroicon::OutlinedSquares2x2),
                TextColumn::make('fecha_ingreso')
                    ->label('Ingreso')
                    ->date('d/m/Y')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->placeholder('—')
                    ->sortable(),
                ColumnasComunes::estado('activo', 'Activo', 'Inactivo'),
                TextColumn::make('email_verified_at')
                    ->label('Correo verificado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin verificar')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ...ColumnasComunes::fechas(),
            ])
            ->filters([
                SelectFilter::make('rol')
                    ->label('Rol')
                    ->options(User::ETIQUETAS_ROL),
                ColumnasComunes::filtroEstado('activo', 'Estado', 'Activos', 'Inactivos'),
                SelectFilter::make('area')
                    ->label('Área asignada')
                    ->relationship('areas', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('name')
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
