<?php

namespace App\Filament\Resources\Modulos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ModuloForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del módulo')
                    ->description('A qué área pertenece y de qué trata.')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->schema([
                        Select::make('area_id')
                            ->label('Área')
                            ->relationship('area', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Section::make('Imagen y material')
                    ->description('Opcionales: la imagen se ve en la tarjeta del módulo y el PDF lo descargan quienes lo completan.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columns(2)
                    ->schema([
                        FileUpload::make('imagen')
                            ->label('Imagen del módulo')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('modulos'),
                        FileUpload::make('resumen_pdf')
                            ->label('Resumen del módulo (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('resumenes'),
                    ]),

                Section::make('Publicación')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columns(2)
                    ->schema([
                        TextInput::make('orden')
                            ->label('Orden')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('El número menor aparece primero.'),
                        Toggle::make('activo')
                            ->label('Visible para los colaboradores')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
