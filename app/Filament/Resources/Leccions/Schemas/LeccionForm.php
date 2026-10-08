<?php

namespace App\Filament\Resources\Leccions\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeccionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('modulo_id')
                    ->relationship('modulo', 'titulo')
                    ->required(),
                TextInput::make('titulo')
                    ->required(),
                RichEditor::make('contenido')
                    ->label('Texto / resumen')
                    ->columnSpanFull(),
                TextInput::make('url_video')
                    ->label('URL del video (YouTube/Drive)')
                    ->url(),
                FileUpload::make('archivo_pdf')
                    ->label('PDF adjunto')
                    ->acceptedFileTypes(['application/pdf'])
                    ->directory('lecciones'),
                TextInput::make('duracion_min')
                    ->numeric(),
                TextInput::make('orden')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('activa')
                    ->default(true),
            ]);
    }
}
