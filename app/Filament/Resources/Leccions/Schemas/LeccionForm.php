<?php

namespace App\Filament\Resources\Leccions\Schemas;

use App\Filament\Support\VideoUnico;
use App\Models\Leccion;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LeccionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos de la lección')
                    ->description('Dónde aparece y en qué orden se muestra.')
                    ->icon(Heroicon::OutlinedAcademicCap)
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
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('duracion_min')
                            ->label('Duración')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('min')
                            ->placeholder('Ej. 10'),
                        TextInput::make('orden')
                            ->label('Orden')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('El número menor aparece primero.'),
                        Toggle::make('activa')
                            ->label('Visible para los colaboradores')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Contenido')
                    ->description('Texto, imágenes y apuntes de la lección.')
                    ->icon(Heroicon::OutlinedBars3BottomLeft)
                    ->schema([
                        RichEditor::make('contenido')
                            ->label('Texto / resumen')
                            ->columnSpanFull(),
                    ]),

                self::seccionVideo(),

                Section::make('Material de apoyo')
                    ->description('Un PDF opcional que los colaboradores pueden ver o descargar.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->collapsible()
                    ->schema([
                        self::campoPdf(),
                    ]),
            ]);
    }

    /**
     * Campos de una lección para usarla dentro del formulario de un módulo (sin el selector de módulo ni el orden,
     * que lo da el arrastre de la lista).
     *
     * @return array<int, mixed>
     */
    public static function camposAnidados(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                TextInput::make('duracion_min')
                    ->label('Duración')
                    ->numeric()
                    ->minValue(1)
                    ->suffix('min')
                    ->placeholder('Ej. 10'),
            ]),
            RichEditor::make('contenido')
                ->label('Texto / resumen'),
            self::seccionVideo(),
            self::campoPdf(),
            Toggle::make('activa')
                ->label('Visible para los colaboradores')
                ->default(true),
        ];
    }

    /**
     * Elige UNA fuente de video: archivo subido o enlace (ver {@see VideoUnico}).
     */
    public static function seccionVideo(): Section
    {
        return Section::make('Video')
            ->description('Elige una sola fuente: un archivo de tu computador o un enlace. Se verá dentro de la plataforma.')
            ->icon(Heroicon::OutlinedVideoCamera)
            ->schema([
                ToggleButtons::make('tipo_video')
                    ->label('Fuente del video')
                    ->options([
                        'ninguno' => 'Sin video',
                        'archivo' => 'Subir archivo',
                        'enlace' => 'Enlace',
                    ])
                    ->icons([
                        'ninguno' => Heroicon::OutlinedNoSymbol,
                        'archivo' => Heroicon::OutlinedArrowUpTray,
                        'enlace' => Heroicon::OutlinedLink,
                    ])
                    ->colors([
                        'ninguno' => 'gray',
                        'archivo' => 'primary',
                        'enlace' => 'primary',
                    ])
                    ->inline()
                    ->grouped()
                    ->default('ninguno')
                    ->live()
                    ->afterStateHydrated(function (ToggleButtons $component, Get $get): void {
                        $component->state(match (true) {
                            filled($get('archivo_video')) => 'archivo',
                            filled($get('url_video')) => 'enlace',
                            default => 'ninguno',
                        });
                    }),
                FileUpload::make('archivo_video')
                    ->label('Video desde tu computador')
                    ->helperText(fn (): string => 'MP4, WebM, OGG o MOV, hasta '.self::limiteDeSubidaEnMb().' MB (límite actual del servidor).')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                    ->maxSize(fn (): int => self::limiteDeSubidaEnMb() * 1024)
                    ->directory('lecciones/videos')
                    ->visible(fn (Get $get): bool => $get('tipo_video') === 'archivo'),
                TextInput::make('url_video')
                    ->label('Enlace del video')
                    ->placeholder('https://www.youtube.com/watch?v=…')
                    ->prefixIcon(Heroicon::OutlinedLink)
                    ->helperText('YouTube, Vimeo, Google Drive o un archivo .mp4/.webm.')
                    ->url()
                    ->required(fn (Get $get): bool => $get('tipo_video') === 'enlace')
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $video = new Leccion(['url_video' => (string) $value]);

                        if (blank($video->video_embed_url) && blank($video->video_directo_url)) {
                            $fail('Este enlace no se puede reproducir dentro de la plataforma. Usa YouTube, Vimeo, Google Drive o un archivo .mp4/.webm.');
                        }
                    })
                    ->visible(fn (Get $get): bool => $get('tipo_video') === 'enlace'),
            ]);
    }

    public static function campoPdf(): FileUpload
    {
        return FileUpload::make('archivo_pdf')
            ->label('PDF adjunto')
            ->acceptedFileTypes(['application/pdf'])
            ->directory('lecciones');
    }

    /**
     * Tamaño máximo de video (MB): el menor entre 200 MB y lo que PHP deja pasar (upload_max_filesize y post_max_size).
     */
    public static function limiteDeSubidaEnMb(): int
    {
        $aMb = function (string $valor): int {
            $numero = (int) $valor;

            return match (strtoupper(substr(trim($valor), -1))) {
                'G' => $numero * 1024,
                'M' => $numero,
                'K' => intdiv($numero, 1024),
                default => intdiv($numero, 1024 * 1024),
            };
        };

        $archivo = $aMb((string) ini_get('upload_max_filesize'));
        $peticion = $aMb((string) ini_get('post_max_size'));

        return max(1, min(200, $archivo ?: 200, $peticion ?: 200));
    }
}
