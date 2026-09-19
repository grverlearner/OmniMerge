<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAudit;
use App\Support\Site\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| La configuración del sitio
|--------------------------------------------------------------------------
|
| Cada campo de esta pantalla se usa en algún sitio concreto, y la
| pantalla lo dice junto al campo:
|
|   nombre y lema     título de todas las pestañas, portada, centro
|   icono             el favicon de todas las páginas
|   logo              portada, mantenimiento y el centro
|   color de marca    barra del anuncio, portada y color del navegador
|                     en el móvil (meta theme-color)
|   anuncio           una franja arriba en todos los módulos
|   mantenimiento     todo el que no es admin ve la página de aviso
|   registro          /register deja de admitir altas
|   comunidad         la comunidad deja de estar a la vista
|
*/

class AdminSettingsController extends Controller
{
    public function edit(SiteSettings $site): View
    {
        return view('admin.settings', [
            'valores' => $site->all(),
            'tonos' => SiteSettings::TONES,
        ]);
    }

    public function update(Request $request, SiteSettings $site, AdminAudit $audit): RedirectResponse
    {
        $datos = $request->validate([
            'site_name' => ['required', 'string', 'max:40'],
            'tagline' => ['nullable', 'string', 'max:80'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:1024'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048'],
            'remove_favicon' => ['nullable', 'boolean'],
            'remove_logo' => ['nullable', 'boolean'],
            'announcement_text' => ['nullable', 'string', 'max:240', 'required_if:announcement_active,1'],
            'announcement_tone' => ['required', Rule::in(array_keys(SiteSettings::TONES))],
            'announcement_link' => ['nullable', 'url', 'max:255'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ], [
            'announcement_text.required_if' => 'Para mostrar el anuncio hace falta escribirlo.',
            'accent.regex' => 'El color tiene que ir en formato #RRGGBB.',
        ], [
            'site_name' => 'nombre del sitio',
            'tagline' => 'lema',
            'accent' => 'color de marca',
            'favicon' => 'icono',
            'logo' => 'logo',
            'announcement_text' => 'texto del anuncio',
            'announcement_link' => 'enlace del anuncio',
            'maintenance_message' => 'mensaje de mantenimiento',
        ]);

        $antes = $site->all();

        $nuevos = [
            'site_name' => $datos['site_name'],
            'tagline' => $datos['tagline'] ?? '',
            'accent' => strtolower($datos['accent']),
            'announcement_active' => $request->boolean('announcement_active'),
            'announcement_text' => $datos['announcement_text'] ?? '',
            'announcement_tone' => $datos['announcement_tone'],
            'announcement_link' => $datos['announcement_link'] ?? '',
            'maintenance_active' => $request->boolean('maintenance_active'),
            'maintenance_message' => $datos['maintenance_message'] ?: SiteSettings::DEFAULTS['maintenance_message'],
            'registration_open' => $request->boolean('registration_open'),
            'community_open' => $request->boolean('community_open'),
        ];

        foreach (['favicon' => 'site/favicon', 'logo' => 'site/logo'] as $campo => $carpeta) {
            if ($request->hasFile($campo)) {
                $this->borrar($antes[$campo]);
                $nuevos[$campo] = $request->file($campo)->store($carpeta, 'public');
            } elseif ($request->boolean('remove_' . $campo)) {
                $this->borrar($antes[$campo]);
                $nuevos[$campo] = null;
            }
        }

        $site->set($nuevos);

        $cambios = collect($nuevos)
            ->filter(fn ($valor, $clave) => ($antes[$clave] ?? null) !== $valor)
            ->keys()
            ->values()
            ->all();

        if ($cambios) {
            $audit->log('settings.update', null, ['campos' => $cambios], 'Configuración del sitio');
        }

        return redirect()->route('admin.settings.edit')->with('success', $cambios
            ? 'Configuración guardada. Ya se aplica en todo el sitio.'
            : 'No había nada distinto que guardar.');
    }

    private function borrar(?string $ruta): void
    {
        if ($ruta && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
