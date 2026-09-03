@props([
    'name' => 'punto',
    'size' => 'h-5 w-5',
])

@php
    /*
     * El juego de iconos de OmniMerge.
     *
     * Antes cada enlace del sidebar llevaba un carácter suelto —▦, ✦, ⌘, ⚗,
     * y algún emoji—. Puestos en columna no se leían como una familia sino
     * como una colección de símbolos encontrados: distinto peso, distinto
     * tamaño óptico, y los emojis además cambian de forma según el sistema
     * operativo y no heredan el color del texto.
     *
     * Aquí son todos lo mismo: caja de 24, trazo de 1.75, extremos
     * redondeados, sin relleno, en `currentColor`. Eso es lo que hace que
     * combinen —y lo que permite que el sidebar plegado, donde el icono es
     * lo único que queda, siga siendo legible—.
     *
     * Añadir uno nuevo es añadirlo AQUÍ, nunca pegar un carácter en una
     * vista: un juego de iconos disperso deja de ser un juego.
     */

    $trazos = match ($name) {

        /* ---------------------------------------------- estructura */

        'panel' => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M9.5 4v16"/>',

        'cuadricula' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.8"/>' .
            '<rect x="13.5" y="3" width="7.5" height="7.5" rx="1.8"/>' .
            '<rect x="3" y="13.5" width="7.5" height="7.5" rx="1.8"/>' .
            '<rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.8"/>',

        'capas' => '<path d="M12 3 3 7.5 12 12l9-4.5L12 3Z"/><path d="m3 12.5 9 4.5 9-4.5"/><path d="m3 16.75 9 4.5 9-4.5"/>',

        /* Un marco con su paisaje: el icono de «solo imágenes» */
        'galeria' => '<rect x="3" y="4" width="18" height="16" rx="2.5"/>' .
            '<circle cx="8.5" cy="9.5" r="1.6"/>' .
            '<path d="m3.5 17.5 4.6-4.6a2 2 0 0 1 2.8 0l3.1 3.1"/>' .
            '<path d="m12.8 14.3 2.1-2.1a2 2 0 0 1 2.8 0l2.8 2.8"/>',

        'grafo' => '<circle cx="6" cy="6" r="2.5"/><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="12" r="2.5"/>' .
            '<path d="M6 8.5v7"/><path d="M8.4 6.9A6.2 6.2 0 0 1 15.5 11"/><path d="M8.4 17.1a6.2 6.2 0 0 0 7.1-4.1"/>',

        /* ---------------------------------------------- biblioteca */

        'chispa' => '<path d="M12 3.2 13.9 9 19.8 10.8 13.9 12.6 12 18.4 10.1 12.6 4.2 10.8 10.1 9 12 3.2Z"/>' .
            '<path d="m18.6 15.4.65 1.75 1.75.65-1.75.65-.65 1.75-.65-1.75-1.75-.65 1.75-.65.65-1.75Z"/>',

        'controles' => '<path d="M4 7h9"/><path d="M17.5 7H20"/><path d="M4 12h2.5"/><path d="M11 12h9"/>' .
            '<path d="M4 17h9"/><path d="M17.5 17H20"/>' .
            '<circle cx="15.25" cy="7" r="2.25"/><circle cx="8.75" cy="12" r="2.25"/><circle cx="15.25" cy="17" r="2.25"/>',

        'libro' => '<path d="M20 3H7.5A3.5 3.5 0 0 0 4 6.5v11A3.5 3.5 0 0 1 7.5 14H20V3Z"/>' .
            '<path d="M4 17.5A3.5 3.5 0 0 0 7.5 21H20v-3.5"/>',

        /* ---------------------------------------------- torneos */

        'trofeo' => '<path d="M7 4h10v6a5 5 0 0 1-10 0V4Z"/>' .
            '<path d="M7 6H4.6a2.4 2.4 0 0 0 2.6 4.8"/><path d="M17 6h2.4a2.4 2.4 0 0 1-2.6 4.8"/>' .
            '<path d="M12 15v4"/><path d="M8.5 21h7"/>',

        'espadas' => '<path d="M3.5 3.5h3l11 11"/><path d="M20.5 3.5h-3l-11 11"/>' .
            '<path d="m14.9 15.9 3.4 3.4-1.4 1.4-3.4-3.4"/><path d="m9.1 15.9-3.4 3.4 1.4 1.4 3.4-3.4"/>',

        'matraz' => '<path d="M9.5 3h5"/><path d="M10.5 3v6.6L5.2 18.2A2 2 0 0 0 6.9 21.2h10.2a2 2 0 0 0 1.7-3L13.5 9.6V3"/>' .
            '<path d="M7.6 15.5h8.8"/>',

        'medalla' => '<circle cx="12" cy="15" r="5.2"/>' .
            '<path d="M8.6 10.6 6 3h4l2 3.8L14 3h4l-2.6 7.6"/>' .
            '<path d="m12 12.9.95 1.9 2.1.3-1.52 1.45.36 2.05L12 17.65l-1.89.95.36-2.05L8.95 15.1l2.1-.3.95-1.9Z"/>',

        /* ---------------------------------------------- universos */

        'orbita' => '<circle cx="12" cy="12" r="3.2"/>' .
            '<ellipse cx="12" cy="12" rx="9.5" ry="4.2" transform="rotate(-28 12 12)"/>' .
            '<circle cx="19.6" cy="8.4" r="1.4"/>',

        'brujula' => '<circle cx="12" cy="12" r="9"/><path d="m15.6 8.4-2.1 5.1-5.1 2.1 2.1-5.1 5.1-2.1Z"/>',

        'dado' => '<rect x="3" y="3" width="18" height="18" rx="4.5"/>' .
            '<circle cx="8.6" cy="8.6" r="1.15"/><circle cx="12" cy="12" r="1.15"/><circle cx="15.4" cy="15.4" r="1.15"/>',

        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18"/>' .
            '<path d="M8 3v4"/><path d="M16 3v4"/>',

        'historial' => '<path d="M3.5 12a8.5 8.5 0 1 0 2.5-6"/><path d="M3 3.5V9h5.5"/><path d="M12 8v4.4l3.2 1.9"/>',

        'barras' => '<path d="M3 21h18"/><path d="M6 21V10.5"/><path d="M12 21V4"/><path d="M18 21v-7"/>',

        'globo' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><ellipse cx="12" cy="12" rx="4" ry="9"/>',

        /* ---------------------------------------------- acciones */

        'engranaje' => '<circle cx="12" cy="12" r="3.1"/>' .
            '<path d="M12 2.6v2.3"/><path d="M12 19.1v2.3"/><path d="M21.4 12h-2.3"/><path d="M4.9 12H2.6"/>' .
            '<path d="m18.6 5.4-1.6 1.6"/><path d="m7 17-1.6 1.6"/><path d="m18.6 18.6-1.6-1.6"/><path d="M7 7 5.4 5.4"/>',

        'mas' => '<path d="M12 5v14"/><path d="M5 12h14"/>',

        'casa' => '<path d="m3 10.4 9-7.2 9 7.2V19a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 19v-8.6Z"/>' .
            '<path d="M9.5 21.5V14h5v7.5"/>',

        'usuario' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',

        'flecha-izquierda' => '<path d="M19.5 12H4.5"/><path d="m10.5 6-6 6 6 6"/>',

        'flecha-derecha' => '<path d="M4.5 12h15"/><path d="m13.5 6 6 6-6 6"/>',

        'chevron-izquierda' => '<path d="m14.5 5.5-6.5 6.5 6.5 6.5"/>',

        'chevron-derecha' => '<path d="m9.5 5.5 6.5 6.5-6.5 6.5"/>',

        'menu' => '<path d="M4 6.5h16"/><path d="M4 12h16"/><path d="M4 17.5h16"/>',

        'cerrar' => '<path d="m6 6 12 12"/><path d="M18 6 6 18"/>',

        default => '<circle cx="12" cy="12" r="3.5"/>',
    };
@endphp

<svg {{ $attributes->merge(['class' => $size . ' shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {!! $trazos !!}
</svg>
