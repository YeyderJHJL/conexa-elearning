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
            ->columns(1)
            ->components([
                Section::make('Datos del quiz')
                    ->description('Cada módulo tiene un quiz que se aprueba con la nota mínima. Las preguntas se agregan en la pestaña «Preguntas».')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columns(3)
                    ->schema([
                        Select::make('modulo_id')
                            ->label('Módulo')
                            ->relationship('modulo', 'titulo')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => 'Este módulo ya tiene un quiz.'])
                            ->columnSpan(2),
                        TextInput::make('nota_minima')
                            ->label('Nota mínima')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->placeholder('70')
                            ->helperText('Vacío = 70%.'),
                        TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
