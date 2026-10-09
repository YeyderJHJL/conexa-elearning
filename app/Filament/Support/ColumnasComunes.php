<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;

/**
 * Columnas y filtros que se repiten en las tablas del panel, para que se vean igual en todas.
 */
class ColumnasComunes
{
    /**
     * Estado sí/no como insignia: verde con check si es verdadero, gris si es falso.
     */
    public static function estado(string $campo, string $verdadero, string $falso, string $etiqueta = 'Estado'): TextColumn
    {
        return TextColumn::make($campo)
            ->label($etiqueta)
            ->badge()
            ->formatStateUsing(fn ($state) => $state ? $verdadero : $falso)
            ->color(fn ($state) => $state ? 'success' : 'gray')
            ->icon(fn ($state) => $state ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedMinusCircle)
            ->sortable();
    }

    /**
     * Cantidad de registros de una relación, como insignia con ícono.
     */
    public static function recuento(string $relacion, string $etiqueta, BackedEnum $icono, string $color = 'primary'): TextColumn
    {
        return TextColumn::make("{$relacion}_count")
            ->label($etiqueta)
            ->counts($relacion)
            ->badge()
            ->color($color)
            ->icon($icono)
            ->alignCenter()
            ->sortable();
    }

    /**
     * Ícono que indica si el campo tiene un valor (video, PDF, etc.).
     */
    public static function tieneValor(string $campo, string $etiqueta, BackedEnum $icono): IconColumn
    {
        return IconColumn::make($campo)
            ->label($etiqueta)
            ->boolean()
            ->trueIcon($icono)
            ->falseIcon(Heroicon::OutlinedMinus)
            ->trueColor('success')
            ->falseColor('gray')
            ->alignCenter();
    }

    /**
     * Insignia con el nombre de un registro relacionado (área, módulo, quiz).
     */
    public static function relacionado(string $campo, string $etiqueta, BackedEnum $icono, string $color = 'primary'): TextColumn
    {
        return TextColumn::make($campo)
            ->label($etiqueta)
            ->badge()
            ->color($color)
            ->icon($icono)
            ->searchable()
            ->sortable();
    }

    /**
     * Fechas de creación y edición, ocultas por defecto.
     *
     * @return array<int, TextColumn>
     */
    public static function fechas(): array
    {
        return [
            TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')->label('Editado')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Filtro de tres posiciones (todos / sí / no) para un campo booleano.
     */
    public static function filtroEstado(string $campo, string $etiqueta, string $si, string $no): TernaryFilter
    {
        return TernaryFilter::make($campo)
            ->label($etiqueta)
            ->placeholder('Todos')
            ->trueLabel($si)
            ->falseLabel($no);
    }
}
