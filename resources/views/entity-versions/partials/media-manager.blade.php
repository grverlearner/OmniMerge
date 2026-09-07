@php
    /*
     * La multimedia de una versión.
     *
     * Dos cosas distintas que antes se mezclaban:
     *
     *   la PORTADA   el campo `image` de la versión. Es su cara, siempre hay
     *                una, y no se borra: se sustituye desde el formulario.
     *   la GALERÍA   filas de `entity_version_images`. Puede haber cero o
     *                veinte, cada una con su tipo, su pie y su texto
     *                alternativo, y cualquiera puede ascender a portada.
     *
     * Se dice cuál es cuál, porque las acciones que admiten no son las mismas.
     */

    $mediaPayload = $entityVersion->images
        ->map(
            fn($image) => [
                'id' => (string) $image->id,
                'image_url' => $image->image_url,
                'caption' => $image->caption ?? '',
                'alt_text' => $image->alt_text ?? '',
                'media_type' => $image->media_type,
                'type_label' => $image->media_type_label,
                'update_url' => route('entity-versions.images.update', [$entity, $entityVersion, $image]),
                'primary_url' => route('entity-versions.images.primary', [$entity, $entityVersion, $image]),
                'delete_url' => route('entity-versions.images.destroy', [$entity, $entityVersion, $image]),
            ],
        )
        ->values()
        ->all();

    $tiposDeMedio = [
        'PORTRAIT' => 'Retrato',
        'FULL_BODY' => 'Cuerpo completo',
        'COMBAT' => 'Combate',
        'OUTFIT' => 'Apariencia',
        'REFERENCE' => 'Referencia',
        'ALTERNATIVE' => 'Alternativa',
        'OTHER' => 'Otra',
    ];

    $tonoDeMedio = [
        'PORTRAIT' => 'border-fuchsia-500/30 bg-fuchsia-500/10 text-fuchsia-300',
        'FULL_BODY' => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300',
        'COMBAT' => 'border-rose-500/30 bg-rose-500/10 text-rose-300',
        'OUTFIT' => 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300',
        'REFERENCE' => 'border-slate-700 bg-slate-800/60 text-slate-300',
        'ALTERNATIVE' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'OTHER' => 'border-slate-700 bg-slate-800/60 text-slate-400',
    ];
@endphp

<section x-data="mediaManager(@js($mediaPayload))" class="space-y-4">

    {{-- ===================================================== --}}
    {{-- LA PORTADA --}}
    {{-- ===================================================== --}}

    <div class="grid gap-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-4 lg:grid-cols-[minmax(0,240px)_minmax(0,1fr)]">

        <div class="overflow-hidden rounded-xl border border-amber-500/40 bg-slate-950">
            <div class="relative aspect-square overflow-hidden">
                @if ($entityVersion->image_url)
                    <button type="button" @click="lightbox = @js($entityVersion->image_url)"
                        class="block h-full w-full">
                        <img src="{{ $entityVersion->image_url }}" alt="{{ $entityVersion->name }}"
                            class="h-full w-full object-cover transition duration-500 hover:scale-105">
                    </button>
                @else
                    <span class="flex h-full w-full items-center justify-center text-4xl text-slate-800">◈</span>
                @endif

                <span class="absolute left-2 top-2 rounded-lg bg-amber-400 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-950">
                    ★ Portada
                </span>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <h3 class="text-[13px] font-black text-white">La portada</h3>
                <p class="mt-0.5 text-[11px] leading-relaxed text-slate-500">
                    Es <strong class="text-slate-300">la cara</strong> de esta versión: la que sale en los
                    listados y la que se copia cuando alguien la usa. Siempre hay una, así que no se borra —se
                    sustituye—, y puede venir de dos sitios: subiéndola desde el formulario, o
                    <strong class="text-slate-300">ascendiendo una de la galería</strong>.
                </p>
            </div>

            @can('update', $entityVersion)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('entity-versions.edit', [$entity, $entityVersion]) }}"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                        ✎ Cambiar la portada
                    </a>
                </div>
            @endcan

            <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">Cuántas hay</p>
                <p class="mt-0.5 text-[12px] text-slate-400">
                    <strong class="font-mono text-lg {{ $entityVersion->images->count() > 0 ? 'text-fuchsia-300' : 'text-slate-700' }}">{{ $entityVersion->images->count() }}</strong>
                    {{ $entityVersion->images->count() === 1 ? 'imagen en la galería' : 'imágenes en la galería' }}
                    @if ($entityVersion->images->isNotEmpty())
                        <span class="text-slate-700">·</span>
                        {{ $entityVersion->images->pluck('media_type')->unique()->count() }} tipos distintos
                    @endif
                </p>
            </div>
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- SUBIR --}}
    {{-- ===================================================== --}}

    @can('update', $entityVersion)
        <form method="POST" enctype="multipart/form-data"
            action="{{ route('entity-versions.images.store', [$entity, $entityVersion]) }}"
            x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
            @csrf

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-950/50">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
                    <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-[13px] font-black text-white">Añadir imágenes a la galería</span>
                    <span class="block text-[10px] text-slate-500">
                        Hasta 20 de una vez · JPG, PNG o WEBP · máximo 2 MB cada una
                    </span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800 p-4">

                <label class="block">
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Qué muestran las que vas a subir
                    </span>

                    <select name="media_type"
                        class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-fuchsia-500 focus:ring-fuchsia-500 sm:w-72">
                        @foreach ($tiposDeMedio as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($valor === 'ALTERNATIVE')>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <span class="mt-1 block text-[10px] text-slate-600">
                        Se aplica a todas las de esta tanda; luego se puede corregir una a una.
                        El tipo no es decorativo: es lo que permite filtrar la galería cuando crece.
                    </span>
                </label>

                <div class="mt-3">
                    <x-omni-multi-image-upload name="gallery_images[]" label="Elegir las imágenes" :max-mb="2"
                        surface="dark" />
                </div>

                <button type="submit"
                    class="mt-3 rounded-xl bg-fuchsia-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-fuchsia-400">
                    Subirlas
                </button>
            </div>
        </form>
    @endcan


    {{-- ===================================================== --}}
    {{-- LA GALERÍA --}}
    {{-- ===================================================== --}}

    @if ($entityVersion->images->isEmpty())

        <div class="rounded-2xl border border-dashed border-slate-800 py-12 text-center">
            <span class="inline-flex text-slate-700"><x-omni-icon name="galeria" size="h-9 w-9" /></span>

            <p class="mt-2 text-[13px] font-black text-white">La galería está vacía</p>

            <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                {{ $entityVersion->name }} tiene su portada, pero ninguna imagen más. La galería sirve para
                guardar otras caras del mismo estado —de combate, de cuerpo entero, de referencia— sin crear
                otra versión.
            </p>
        </div>

    @else

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
                    <x-omni-icon name="galeria" size="h-3.5 w-3.5" />
                </span>

                <div class="min-w-0 flex-1">
                    <h3 class="text-[13px] font-black text-white">La galería</h3>
                    <p class="text-[10px] text-slate-500">
                        Pulsa una imagen para verla grande, o el lápiz para corregirla.
                    </p>
                </div>

                @can('update', $entityVersion)
                    <form method="POST" action="{{ route('entity-versions.images.reorder', [$entity, $entityVersion]) }}">
                        @csrf
                        @method('PATCH')

                        <template x-for="item in items" :key="item.id">
                            <input type="hidden" name="ordered_ids[]" :value="item.id">
                        </template>

                        <button type="submit" x-show="ordenCambiado" x-cloak
                            class="rounded-xl bg-fuchsia-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-fuchsia-400">
                            Guardar el orden
                        </button>
                    </form>
                @endcan
            </div>

            <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-4">

                <template x-for="(item, indice) in items" :key="item.id">
                    <article class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950"
                        draggable="true"
                        @dragstart="dragIndex = indice"
                        @dragover.prevent
                        @drop.prevent="move(dragIndex, indice)">

                        <div class="relative aspect-square overflow-hidden bg-slate-900">
                            <button type="button" @click="lightbox = item.image_url" class="block h-full w-full">
                                <img :src="item.image_url" :alt="item.alt_text"
                                    class="h-full w-full object-cover transition duration-500 hover:scale-105">
                            </button>

                            <span class="absolute left-1.5 top-1.5 rounded-lg border border-slate-700 bg-slate-950/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-300"
                                x-text="item.type_label"></span>

                            <span class="absolute right-1.5 top-1.5 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-slate-500"
                                x-text="indice + 1" title="Orden en la galería"></span>
                        </div>

                        <div class="p-2">
                            <p class="truncate text-[11px] font-black text-white"
                                x-text="item.caption || 'Sin pie de foto'"
                                :class="item.caption ? '' : 'text-slate-600'"></p>

                            <p class="truncate text-[9px] text-slate-600"
                                x-text="item.alt_text || 'Sin texto alternativo'"></p>

                            @can('update', $entityVersion)
                                <div class="mt-1.5 flex items-center gap-1">
                                    <button type="button" @click="editando = editando === item.id ? null : item.id"
                                        class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">✎</button>

                                    <form method="POST" :action="item.primary_url">
                                        @csrf
                                        <button type="submit" title="Hacerla la portada de esta versión"
                                            class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">★</button>
                                    </form>

                                    <form method="POST" :action="item.delete_url" class="ml-auto"
                                        onsubmit="return confirm('Se borra la imagen para siempre. ¿Seguro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-600 transition hover:text-rose-300">✕</button>
                                    </form>
                                </div>

                                {{-- Corregir --}}
                                <form method="POST" :action="item.update_url"
                                    x-show="editando === item.id" x-cloak x-collapse
                                    class="mt-1.5 space-y-1.5 border-t border-slate-800 pt-2">
                                    @csrf
                                    @method('PATCH')

                                    <select name="media_type" x-model="item.media_type"
                                        class="w-full rounded-lg border-slate-800 bg-slate-900 py-1 text-[10px] text-slate-200 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                                        @foreach ($tiposDeMedio as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>

                                    <input type="text" name="caption" x-model="item.caption" maxlength="200"
                                        placeholder="Pie de foto"
                                        class="w-full rounded-lg border-slate-800 bg-slate-900 py-1 text-[10px] text-slate-200 placeholder:text-slate-600 focus:border-fuchsia-500 focus:ring-fuchsia-500">

                                    <input type="text" name="alt_text" x-model="item.alt_text" maxlength="200"
                                        placeholder="Texto alternativo"
                                        class="w-full rounded-lg border-slate-800 bg-slate-900 py-1 text-[10px] text-slate-200 placeholder:text-slate-600 focus:border-fuchsia-500 focus:ring-fuchsia-500">

                                    <button type="submit"
                                        class="w-full rounded-lg bg-fuchsia-500/15 py-1.5 text-[10px] font-black text-fuchsia-300 transition hover:bg-fuchsia-500 hover:text-white">
                                        Guardar
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </article>
                </template>

            </div>

            <p class="border-t border-slate-800 px-4 py-2 text-[10px] text-slate-600">
                Se pueden arrastrar para reordenarlas; el botón de guardar aparece cuando cambias el orden.
                La estrella asciende una imagen a portada: la que era portada baja a la galería.
            </p>

        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- VER GRANDE --}}
    {{-- ===================================================== --}}

    <div x-show="lightbox" x-cloak @keydown.escape.window="lightbox = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-5 backdrop-blur-sm"
        @click.self="lightbox = null">

        <button type="button" @click="lightbox = null"
            class="absolute right-5 top-5 flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-slate-300 transition hover:bg-slate-800 hover:text-white">
            <x-omni-icon name="cerrar" size="h-4 w-4" />
        </button>

        <img :src="lightbox" alt="" class="max-h-[90vh] max-w-[95vw] rounded-2xl object-contain shadow-2xl">
    </div>


    <script>
        /*
         * El motor de la galería. Es el mismo de antes —lista, arrastrar y
         * soltar, y la imagen grande— más dos cosas que el marcado nuevo
         * necesita: cuál se está corrigiendo, y si el orden cambió, para no
         * enseñar el botón de guardar cuando no hay nada que guardar.
         */
        function mediaManager(initialItems) {

            return {

                items: initialItems ?? [],

                ordenInicial: (initialItems ?? []).map((item) => item.id).join(','),

                dragIndex: null,

                editando: null,

                lightbox: null,


                get ordenCambiado() {
                    return this.items.map((item) => item.id).join(',') !== this.ordenInicial;
                },


                move(from, to) {

                    if (from === null || from === to) {
                        return;
                    }

                    const item = this.items.splice(from, 1)[0];

                    this.items.splice(to, 0, item);

                    this.dragIndex = null;
                },
            };
        }
    </script>

</section>
