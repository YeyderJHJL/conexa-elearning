<?php

namespace App\Filament\Resources\Opcions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class OpcionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Opción de respuesta')
                    ->description('Marca como correcta la respuesta que el colaborador debe elegir.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->schema([
                        Select::make('pregunta_id')
                            ->label('Pregunta')
                            ->relationship('pregunta', 'enunciado')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('texto')
                            ->label('Texto de la opción')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('es_correcta')
                            ->label('Es la respuesta correcta')
                            ->inline(false),
                    ]),
            ]);
    }
}
