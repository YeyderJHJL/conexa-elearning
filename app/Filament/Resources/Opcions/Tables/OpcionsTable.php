<?php

namespace App\Filament\Resources\Opcions\Tables;

use App\Filament\Support\ColumnasComunes;
use App\Models\Quiz;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OpcionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pregunta.enunciado')
                    ->label('Pregunta')
                    ->limit(60)
                    ->wrap()
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('texto')
                    ->label('Opción')
                    ->weight('semibold')
                    ->wrap()
                    ->searchable(),
                ColumnasComunes::estado('es_correcta', 'Correcta', 'Incorrecta', 'Respuesta'),
                ...ColumnasComunes::fechas(),
            ])
            ->filters([
                ColumnasComunes::filtroEstado('es_correcta', 'Respuesta', 'Correctas', 'Incorrectas'),
                SelectFilter::make('quiz')
                    ->label('Quiz')
                    ->options(fn () => Quiz::query()->orderBy('titulo')->pluck('titulo', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $quiz) => $query->whereHas('pregunta', fn (Builder $pregunta) => $pregunta->where('quiz_id', $quiz))
                    )),
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
