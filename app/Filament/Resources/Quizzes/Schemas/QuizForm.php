<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del quiz')
                    ->description('Cada módulo tiene un quiz que se aprueba con la nota mínima.')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columns(2)
                    ->schema([
                        Select::make('modulo_id')
                            ->label('Módulo')
                            ->relationship('modulo', 'titulo')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nota_minima')
                            ->label('Nota mínima para aprobar')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->placeholder('70')
                            ->helperText('Si lo dejas vacío se usa 70%.'),
                    ]),
            ]);
    }
}
