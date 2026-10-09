<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Leccion extends Model
{
    protected $fillable = ['modulo_id', 'titulo', 'contenido', 'url_video', 'archivo_video', 'archivo_pdf', 'duracion_min', 'orden', 'activa'];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    /**
     * URL embebible del video (YouTube, Vimeo o Google Drive), o null si no se reconoce.
     * Acepta direcciones sin "https://", "music.youtube.com", "youtube-nocookie.com" y listas de reproducción.
     *
     * @return Attribute<string|null, never>
     */
    protected function videoEmbedUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $partes = $this->partesDelVideo();

            if ($partes === null) {
                return null;
            }

            $host = strtolower(preg_replace('/^(www|m|music|gaming)\./i', '', $partes['host'] ?? ''));
            $ruta = $partes['path'] ?? '';
            parse_str($partes['query'] ?? '', $query);

            if ($host === 'youtu.be') {
                return $this->embedYoutube(ltrim($ruta, '/'));
            }

            if (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
                if (preg_match('#^/(?:embed|shorts|live|v)/([^/?]+)#', $ruta, $coincidencia) && $coincidencia[1] !== 'videoseries') {
                    return $this->embedYoutube($coincidencia[1]);
                }

                if (! empty($query['v'])) {
                    return $this->embedYoutube((string) $query['v']);
                }

                return $this->embedListaYoutube((string) ($query['list'] ?? ''));
            }

            if ($host === 'vimeo.com' || $host === 'player.vimeo.com') {
                return $this->embedVimeo($ruta, $query);
            }

            if ($host === 'drive.google.com') {
                $id = preg_match('#^/file/d/([^/]+)#', $ruta, $coincidencia) ? $coincidencia[1] : ($query['id'] ?? '');

                return preg_match('/^[A-Za-z0-9_-]+$/', (string) $id)
                    ? "https://drive.google.com/file/d/{$id}/preview"
                    : null;
            }

            return null;
        });
    }

    /**
     * URL de un archivo de video directo (.mp4, .webm, .ogg) para reproducirlo con <video>, o null.
     *
     * @return Attribute<string|null, never>
     */
    protected function videoDirectoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $partes = $this->partesDelVideo();

            if ($partes === null || ! preg_match('/\.(mp4|webm|ogg|ogv)$/i', $partes['path'] ?? '')) {
                return null;
            }

            return $partes['scheme'].'://'.$partes['host']
                .(isset($partes['port']) ? ':'.$partes['port'] : '')
                .$partes['path']
                .(isset($partes['query']) ? '?'.$partes['query'] : '');
        });
    }

    /**
     * Partes de url_video tolerando que falte el esquema ("youtube.com/watch?v=..."); solo http/https.
     *
     * @return array<string, mixed>|null
     */
    private function partesDelVideo(): ?array
    {
        $url = trim((string) $this->url_video);

        if ($url !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) && preg_match('#^[\w-]+(\.[\w-]+)+(/|$)#', $url)) {
            $url = 'https://'.$url;
        }

        $partes = parse_url($url);

        if (! is_array($partes) || ! in_array($partes['scheme'] ?? '', ['http', 'https'], true) || blank($partes['host'] ?? '')) {
            return null;
        }

        return $partes;
    }

    private function embedListaYoutube(string $lista): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]{10,60}$/', $lista)
            ? "https://www.youtube-nocookie.com/embed/videoseries?list={$lista}"
            : null;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function embedVimeo(string $ruta, array $query): ?string
    {
        if (! preg_match('#^/(?:video/)?(\d{5,12})(?:/([A-Za-z0-9]+))?#', $ruta, $coincidencia)) {
            return null;
        }

        $clave = $coincidencia[2] ?? ($query['h'] ?? null);

        return "https://player.vimeo.com/video/{$coincidencia[1]}".($clave && preg_match('/^[A-Za-z0-9]+$/', (string) $clave) ? "?h={$clave}" : '');
    }

    private function embedYoutube(string $id): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)
            ? "https://www.youtube-nocookie.com/embed/{$id}"
            : null;
    }
}
