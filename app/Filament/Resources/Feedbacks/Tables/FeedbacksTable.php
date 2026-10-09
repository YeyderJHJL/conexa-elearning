<?php

namespace App\Filament\Resources\Feedbacks\Tables;

use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeedbacksTable
{
    public static function configure(Table $table): Table
    {
        $color = fn (int $state): string => match (true) {
            $state >= 4 => 'success',
            $state === 3 => 'warning',
            default => 'danger',
        };

        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Trabajador')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('area.nombre')
                    ->label('Área')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('claridad')
                    ->label('Claridad')
                    ->badge()
                    ->color($color)
                    ->alignCenter()
                    ->sortable()
                    ->summarize(Average::make()->label('Promedio')->numeric(decimalPlaces: 1)),
                TextColumn::make('utilidad')
                    ->label('Utilidad')
                    ->badge()
                    ->color($color)
                    ->alignCenter()
                    ->sortable()
                    ->summarize(Average::make()->label('Promedio')->numeric(decimalPlaces: 1)),
                TextColumn::make('ritmo')
                    ->label('Ritmo')
                    ->badge()
                    ->color($color)
                    ->alignCenter()
                    ->sortable()
                    ->summarize(Average::make()->label('Promedio')->numeric(decimalPlaces: 1)),
                TextColumn::make('comentario')
                    ->label('Comentario')
                    ->wrap()
                    ->placeholder('Sin comentario'),
                TextColumn::make('created_at')
                    ->label('Respondida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('Área')
                    ->relationship('area', 'nombre'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Todavía no hay respuestas')
            ->emptyStateDescription('Las respuestas aparecerán cuando los trabajadores terminen un área y contesten la encuesta.');
    }
}
