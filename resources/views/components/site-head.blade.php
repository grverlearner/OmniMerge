{{--
    Lo que decide un admin en Configuración y va en el <head> de todas las
    páginas: el icono de la pestaña, el color de la barra del navegador en
    el móvil y el color de marca como variable, para que la portada y las
    páginas de entrada lo usen (clases omni-accent-*).
--}}

<link rel="icon" href="{{ $sitio->faviconUrl() }}">
<meta name="theme-color" content="{{ $sitio->get('accent') }}">
<style>
    :root { --omni-accent: {{ $sitio->get('accent') }}; }
    .omni-accent-bg { background-color: var(--omni-accent); }
    .omni-accent-bg:hover { filter: brightness(1.12); }
    .omni-accent-text { color: var(--omni-accent); }
    .omni-accent-ring { box-shadow: 0 0 0 2px var(--omni-accent); }
</style>
