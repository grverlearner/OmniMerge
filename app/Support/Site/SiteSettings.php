<?php

namespace App\Support\Site;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| La configuración del sitio entero
|--------------------------------------------------------------------------
|
| Lo que antes estaba escrito a mano en cada plantilla —el nombre, el
| icono de la pestaña, repetido en diez layouts— y lo que no existía —un
| anuncio para todos, el modo mantenimiento, cerrar el registro—.
|
| Todo tiene un valor por defecto, así que el sitio funciona igual que
| antes mientras nadie toque nada. Se lee una vez por petición desde
| caché; guardar la vacía.
|
*/

class SiteSettings
{
    private const CACHE_KEY = 'omnimerge.site_settings';

    public const DEFAULTS = [
        'site_name' => 'OmniMerge',
        'tagline' => 'Crea · Organiza · Compite',
        'favicon' => null,
        'logo' => null,
        'accent' => '#8b5cf6',

        'announcement_active' => false,
        'announcement_text' => '',
        'announcement_tone' => 'info',
        'announcement_link' => '',

        'maintenance_active' => false,
        'maintenance_message' => 'Estamos haciendo mejoras. Vuelve en un rato.',

        'registration_open' => true,
        'community_open' => true,
    ];

    public const TONES = [
        'info' => ['label' => 'Informativo', 'color' => '#38bdf8'],
        'success' => ['label' => 'Buena noticia', 'color' => '#34d399'],
        'warning' => ['label' => 'Atención', 'color' => '#fbbf24'],
        'danger' => ['label' => 'Importante', 'color' => '#fb7185'],
    ];

    private ?array $cargados = null;

    public function all(): array
    {
        if ($this->cargados !== null) {
            return $this->cargados;
        }

        $guardados = Cache::rememberForever(self::CACHE_KEY, function () {
            /* Antes de migrar no hay tabla: el sitio sigue con sus valores de siempre */
            if (! Schema::hasTable('site_settings')) {
                return [];
            }

            return SiteSetting::query()->pluck('value', 'key')->all();
        });

        return $this->cargados = array_merge(self::DEFAULTS, array_intersect_key($guardados, self::DEFAULTS));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(array $valores): void
    {
        foreach ($valores as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->cargados = null;
    }

    public function name(): string
    {
        return (string) ($this->get('site_name') ?: self::DEFAULTS['site_name']);
    }

    public function faviconUrl(): string
    {
        return $this->publicUrl($this->get('favicon')) ?? asset('images/joganboruto.jpg');
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrl($this->get('logo'));
    }

    public function announcement(): ?array
    {
        if (! $this->get('announcement_active') || trim((string) $this->get('announcement_text')) === '') {
            return null;
        }

        $tono = self::TONES[$this->get('announcement_tone')] ?? self::TONES['info'];

        return [
            'text' => (string) $this->get('announcement_text'),
            'link' => (string) $this->get('announcement_link'),
            'color' => $tono['color'],
        ];
    }

    public function inMaintenance(): bool
    {
        return (bool) $this->get('maintenance_active');
    }

    private function publicUrl(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }

        $disco = Storage::disk('public');

        return $disco->exists($ruta) ? $disco->url($ruta) : null;
    }
}
