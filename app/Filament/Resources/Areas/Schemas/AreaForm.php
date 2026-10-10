<?php

namespace App\Filament\Resources\Areas\Schemas;

use App\Models\Area;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del área')
                    ->description('El nombre y la descripción que verán los colaboradores.')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(2)
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                if (blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Identificador (URL)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Se genera solo a partir del nombre.'),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Section::make('Apariencia')
                    ->description('Cómo se ve el área en las tarjetas y en su portada.')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->columns(2)
                    ->schema([
                        TextInput::make('icono')
                            ->label('Ícono')
                            ->prefixIcon(Heroicon::OutlinedSquares2x2)
                            ->placeholder('bi-cart')
                            ->helperText('Clase de Bootstrap Icons, por ejemplo bi-cart. Se muestra cuando no hay imagen.'),
                        ColorPicker::make('color')
                            ->label('Color de acento')
                            ->hex()
                            ->helperText('Opcional. Si lo dejas vacío se usa el azul de marca.')
                            ->hintActions(collect([
                                'Azul de marca' => Area::COLOR_MARCA,
                                'Dorado' => Area::COLOR_DORADO,
                                'Azul complementario' => Area::COLOR_COMPLEMENTARIO,
                            ])->map(fn (string $hex, string $nombre) => Action::make('color_'.str($nombre)->slug('_'))
                                ->label($nombre)
                                ->link()
                                ->action(fn (Set $set) => $set('color', $hex))
                            )->values()->all()),
                        FileUpload::make('imagen')
                            ->label('Imagen de portada')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('areas')
                            ->helperText('Opcional. Se muestra como portada de la tarjeta del área.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Material y publicación')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columns(2)
                    ->schema([
                        FileUpload::make('resumen_pdf')
                            ->label('Resumen del área (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('resumenes')
                            ->helperText('Opcional. Lo descargan quienes terminan toda el área.')
                            ->columnSpanFull(),
                        TextInput::make('orden')
                            ->label('Orden')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('El número menor aparece primero.'),
                        Toggle::make('activa')
                            ->label('Visible para los colaboradores')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
