<?php

namespace App\Filament\Resources\Leccions\Schemas;

use App\Models\Leccion;
use Closure;
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
                FileUpload::make('archivo_video')
                    ->label('Video desde tu computador')
                    ->helperText(fn (): string => 'MP4, WebM, OGG o MOV, hasta '.self::limiteDeSubidaEnMb().' MB (límite actual del servidor). Se reproduce dentro de la plataforma.')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                    ->maxSize(fn (): int => self::limiteDeSubidaEnMb() * 1024)
                    ->directory('lecciones/videos')
                    ->columnSpanFull(),
                TextInput::make('url_video')
                    ->label('O la URL de un video (YouTube/Drive)')
                    ->helperText('YouTube, Vimeo, Google Drive o un archivo .mp4/.webm. Se reproduce dentro de la plataforma.')
                    ->url()
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $video = new Leccion(['url_video' => (string) $value]);

                        if (blank($video->video_embed_url) && blank($video->video_directo_url)) {
                            $fail('Este enlace no se puede reproducir dentro de la plataforma. Usa YouTube, Vimeo, Google Drive o un archivo .mp4/.webm.');
                        }
                    }),
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
