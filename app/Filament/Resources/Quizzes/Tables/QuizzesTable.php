<?php

namespace App\Filament\Resources\Quizzes\Tables;

use App\Filament\Support\ColumnasComunes;
use App\Models\Area;
use App\Models\Quiz;
use App\Services\QuizService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnasComunes::relacionado('modulo.titulo', 'Módulo', Heroicon::OutlinedRectangleStack),
                TextColumn::make('titulo')
                    ->label('Quiz')
                    ->weight('semibold')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                ColumnasComunes::recuento('preguntas', 'Preguntas', Heroicon::OutlinedQuestionMarkCircle),
                TextColumn::make('nota_minima')
                    ->label('Nota mínima')
                    ->badge()
                    ->color('secondary')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->state(fn (Quiz $record) => $record->nota_minima ?? QuizService::NOTA_MINIMA_GLOBAL)
                    ->formatStateUsing(fn ($state, Quiz $record) => $record->nota_minima === null ? "{$state}% (por defecto)" : "{$state}%")
                    ->alignCenter()
                    ->sortable(),
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
            ])
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
