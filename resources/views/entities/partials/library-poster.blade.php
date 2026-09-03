@php
    /*
     * Una entidad en modo galería: la cara y el nombre, nada más.
     *
     * Es el modo para MIRAR, no para trabajar. Por eso no lleva cifras, ni
     * botones, ni bordes que separen una ficha de la siguiente: lo único que
     * queda es la imagen a sangre y el nombre encima, sobre un degradado que
     * lo hace legible caiga sobre lo que caiga.
     *
     * Lo poco que sobrevive del resto —el color del tipo— aparece solo al
     * pasar por encima, como un halo. Si estuviera siempre, la pared de caras
     * dejaría de ser una pared de caras.
     */

    $tipo = $entidad->entityType;

    $colorTipo = $tipo?->color ?: '#6366f1';
@endphp

{{--
    El halo del tipo viaja como variable CSS y el hover la usa. Asi el color
    -que es un dato del usuario- no obliga a escribir JavaScript en linea ni a
    inventar clases de Tailwind que no existirian en el CSS.
--}}

<a href="{{ route('entities.show', $entidad) }}"
    style="--halo: {{ $colorTipo }}; --halo-suave: {{ $colorTipo }}55"
    class="group relative block aspect-[3/4] overflow-hidden rounded-xl bg-slate-900 ring-1 ring-slate-800 transition duration-300 hover:z-10 hover:-translate-y-1 hover:shadow-[0_14px_34px_-10px_var(--halo-suave)] hover:ring-2 hover:ring-[color:var(--halo)]">

    @if ($entidad->base_display_image_url)
        <img src="{{ $entidad->base_display_image_url }}" alt="{{ $entidad->name }}" loading="lazy"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
    @else
        {{-- Sin foto, la inicial sobre el color de su tipo: sigue siendo una cara --}}
        <span class="flex h-full w-full items-center justify-center text-5xl font-black"
            style="color: {{ $colorTipo }}44; background:
                radial-gradient(120% 90% at 50% 0%, {{ $colorTipo }}22, transparent 70%)">
            {{ $tipo?->icon ?: mb_strtoupper(mb_substr($entidad->name, 0, 1)) }}
        </span>
    @endif

    {{-- El degradado que hace legible el nombre --}}
    <span
        class="pointer-events-none absolute inset-x-0 bottom-0 h-3/5 bg-gradient-to-t from-slate-950 via-slate-950/70 to-transparent"></span>

    {{-- El nombre --}}
    <span class="absolute inset-x-0 bottom-0 p-2.5">
        <span class="block truncate text-[12px] font-black leading-tight text-white drop-shadow">
            {{ $entidad->name }}
        </span>

        {{-- Solo al pasar por encima: de qué es --}}
        @if ($tipo)
            <span
                class="mt-0.5 flex items-center gap-1 text-[9px] font-black uppercase tracking-wider opacity-0 transition duration-200 group-hover:opacity-100"
                style="color: {{ $colorTipo }}">
                <span class="h-1 w-1 rounded-full" style="background-color: {{ $colorTipo }}"></span>
                {{ $tipo->name }}
            </span>
        @endif
    </span>

    {{-- Y si no es del todo pública o está archivada, se sigue viendo --}}
    @if ($entidad->status !== 'ACTIVE')
        <span
            class="absolute right-1.5 top-1.5 rounded bg-slate-950/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400 backdrop-blur">
            {{ $entidad->status_label }}
        </span>
    @endif

</a>
