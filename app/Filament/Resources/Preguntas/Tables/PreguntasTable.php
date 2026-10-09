<?php

namespace App\Filament\Resources\Preguntas\Tables;

use App\Filament\Support\ColumnasComunes;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PreguntasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnasComunes::relacionado('quiz.titulo', 'Quiz', Heroicon::OutlinedClipboardDocumentCheck),
                TextColumn::make('enunciado')
                    ->label('Pregunta')
                    ->weight('semibold')
                    ->limit(90)
                    ->wrap()
                    ->tooltip(fn (TextColumn $column) => strlen((string) $column->getState()) > 90 ? $column->getState() : null)
                    ->searchable(),
                ColumnasComunes::recuento('opciones', 'Opciones', Heroicon::OutlinedListBullet, 'secondary'),
                TextColumn::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                ...ColumnasComunes::fechas(),
            ])
            ->filters([
                SelectFilter::make('quiz_id')
                    ->label('Quiz')
                    ->relationship('quiz', 'titulo')
                    ->searchable()
                    ->preload(),
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
