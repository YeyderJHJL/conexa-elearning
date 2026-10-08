<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Leccion extends Model
{
    protected $fillable = ['modulo_id', 'titulo', 'contenido', 'url_video', 'archivo_pdf', 'duracion_min', 'orden', 'activa'];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    /**
     * URL embebible del video (YouTube o Google Drive), o null si no se reconoce.
     *
     * @return Attribute<string|null, never>
     */
    protected function videoEmbedUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $partes = parse_url(trim((string) $this->url_video));

            if (! is_array($partes) || ! in_array($partes['scheme'] ?? '', ['http', 'https'], true)) {
                return null;
            }

            $host = strtolower(preg_replace('/^(www|m)\./i', '', $partes['host'] ?? ''));
            $ruta = $partes['path'] ?? '';
            parse_str($partes['query'] ?? '', $query);

            if ($host === 'youtu.be') {
                return $this->embedYoutube(ltrim($ruta, '/'));
            }

            if ($host === 'youtube.com') {
                if (preg_match('#^/(?:embed|shorts|live)/([^/]+)#', $ruta, $coincidencia)) {
                    return $this->embedYoutube($coincidencia[1]);
                }

                return $this->embedYoutube($query['v'] ?? '');
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

    private function embedYoutube(string $id): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)
            ? "https://www.youtube-nocookie.com/embed/{$id}"
            : null;
    }
}
