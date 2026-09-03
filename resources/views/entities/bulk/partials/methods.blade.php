@php
    /*
     * Las cuatro formas de llenar un lote, dibujadas.
     *
     * Antes había cuatro botones sueltos —«+1», «+5», «CSV», «Imágenes en
     * masa»— y no había manera de saber qué hacía cada uno sin pulsarlo. Un
     * diagrama de cuatro trazos explica en un vistazo lo que un párrafo
     * explica en diez segundos, y el límite va debajo en letra pequeña
     * porque el límite es lo que arruina el intento número dos.
     *
     * Los dibujos son SVG en línea y del mismo juego que el resto: caja de
     * 24, trazo fino, `currentColor`. No son ilustraciones, son esquemas.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
            <x-omni-icon name="capas" size="h-4 w-4" />
        </span>

        <div>
            <h2 class="text-sm font-black text-white">Cómo llenar el lote</h2>
            <p class="text-[10px] text-slate-500">
                Cuatro maneras, y se pueden mezclar: pega los nombres, añade alguna a mano y
                suelta las imágenes al final.
            </p>
        </div>
    </header>

    <div class="grid gap-2 p-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- ============ 1 · A MANO ============ --}}

        <div class="group flex flex-col rounded-xl border border-slate-800 bg-slate-950/60 p-3 transition hover:border-indigo-500/40">

            <span class="flex items-center gap-2">
                <span class="rounded-md bg-indigo-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-indigo-300">
                    01
                </span>
                <span class="text-[11px] font-black text-white">A mano</span>
            </span>

            {{-- Filas que se van apilando --}}
            <svg viewBox="0 0 120 44" class="mt-2.5 h-11 w-full text-indigo-400" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                <rect x="4" y="5" width="80" height="9" rx="2.5" />
                <rect x="4" y="18" width="80" height="9" rx="2.5" />
                <rect x="4" y="31" width="80" height="9" rx="2.5" stroke-dasharray="3 3" opacity=".55" />
                <path d="M99 22.5h13M105.5 16v13" />
            </svg>

            <p class="mt-2 flex-1 text-[10px] leading-4 text-slate-500">
                Añade filas vacías de una en una, de cinco en cinco o de diez en diez y
                escribe encima.
            </p>

            <div class="mt-2.5 flex flex-wrap gap-1">
                @foreach ([1, 5, 10] as $cuantas)
                    <button type="button" @click="addRows({{ $cuantas }})"
                        class="rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-[10px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                        +{{ $cuantas }}
                    </button>
                @endforeach
            </div>

            <p class="mt-2 border-t border-slate-800 pt-1.5 text-[9px] text-slate-600">
                Límite · 200 filas por lote
            </p>
        </div>


        {{-- ============ 2 · PEGAR ============ --}}

        <div class="group flex flex-col rounded-xl border border-slate-800 bg-slate-950/60 p-3 transition hover:border-emerald-500/40">

            <span class="flex items-center gap-2">
                <span class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-emerald-300">
                    02
                </span>
                <span class="text-[11px] font-black text-white">Pegar de una hoja</span>
            </span>

            {{-- Una cuadrícula de celdas que cae en filas --}}
            <svg viewBox="0 0 120 44" class="mt-2.5 h-11 w-full text-emerald-400" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                <rect x="4" y="6" width="42" height="32" rx="2.5" />
                <path d="M4 16h42M4 27h42M18 6v32M32 6v32" opacity=".6" />
                <path d="M54 22h18M66 16l6 6-6 6" />
                <rect x="80" y="9" width="36" height="8" rx="2.5" />
                <rect x="80" y="20" width="36" height="8" rx="2.5" />
                <rect x="80" y="31" width="36" height="8" rx="2.5" />
            </svg>

            <p class="mt-2 flex-1 text-[10px] leading-4 text-slate-500">
                Copia de Excel, Google Sheets o un CSV y pega. Cada línea es una entidad y
                cada columna, un dato.
            </p>

            <button type="button" @click="pasteOpen = true"
                class="mt-2.5 rounded-lg bg-emerald-500/15 px-2.5 py-1.5 text-[10px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-slate-950">
                Pegar filas
            </button>

            <p class="mt-2 border-t border-slate-800 pt-1.5 text-[9px] text-slate-600">
                Separadores · tabulador, coma o punto y coma
            </p>
        </div>


        {{-- ============ 3 · PLANTILLA ============ --}}

        <div class="group flex flex-col rounded-xl border border-slate-800 bg-slate-950/60 p-3 transition hover:border-violet-500/40">

            <span class="flex items-center gap-2">
                <span class="rounded-md bg-violet-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-violet-300">
                    03
                </span>
                <span class="text-[11px] font-black text-white">Copiar de una que ya tienes</span>
            </span>

            {{-- Una ficha que reparte su forma a las demás --}}
            <svg viewBox="0 0 120 44" class="mt-2.5 h-11 w-full text-violet-400" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                <rect x="4" y="9" width="26" height="26" rx="3" />
                <path d="M9 16h16M9 22h12M9 28h16" opacity=".6" />
                <path d="M36 22h14" />
                <path d="m44 17 6 5-6 5" />
                <rect x="58" y="4" width="26" height="16" rx="3" opacity=".8" />
                <rect x="58" y="24" width="26" height="16" rx="3" opacity=".55" />
                <rect x="90" y="14" width="26" height="16" rx="3" opacity=".35" />
            </svg>

            <p class="mt-2 flex-1 text-[10px] leading-4 text-slate-500">
                Toma el tipo, las características y las colecciones de una entidad existente
                y las usa como molde del lote.
            </p>

            <div class="mt-2.5">
                <select @change="loadTemplate($event.target.value)"
                    class="w-full rounded-lg border-slate-800 bg-slate-900 py-1.5 text-[10px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Elegir un molde…</option>

                    @foreach ($templateEntities as $plantilla)
                        <option value="{{ $plantilla->id }}">{{ $plantilla->name }}</option>
                    @endforeach
                </select>
            </div>

            <p class="mt-2 border-t border-slate-800 pt-1.5 text-[9px] text-slate-600">
                Copia la forma, no los valores de cada fila
            </p>
        </div>


        {{-- ============ 4 · IMÁGENES ============ --}}

        <div class="group flex flex-col rounded-xl border border-slate-800 bg-slate-950/60 p-3 transition hover:border-amber-500/40">

            <span class="flex items-center gap-2">
                <span class="rounded-md bg-amber-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-amber-300">
                    04
                </span>
                <span class="text-[11px] font-black text-white">Imágenes de golpe</span>
            </span>

            {{-- Archivos que encuentran su fila por el nombre --}}
            <svg viewBox="0 0 120 44" class="mt-2.5 h-11 w-full text-amber-400" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                <rect x="4" y="6" width="22" height="16" rx="2.5" />
                <circle cx="10.5" cy="12" r="1.6" />
                <path d="m5.5 20 5-5 4 4 3-3 7 6" />
                <rect x="4" y="26" width="22" height="14" rx="2.5" opacity=".5" />
                <path d="M34 22h14" stroke-dasharray="3 3" />
                <path d="m42 17 6 5-6 5" />
                <rect x="56" y="9" width="60" height="10" rx="2.5" />
                <path d="M61 14h16" opacity=".6" />
                <rect x="56" y="25" width="60" height="10" rx="2.5" opacity=".5" />
                <path d="M61 30h12" opacity=".4" />
            </svg>

            <p class="mt-2 flex-1 text-[10px] leading-4 text-slate-500">
                Suelta muchas a la vez: cada archivo busca su fila comparando el nombre del
                archivo con el nombre escrito.
            </p>

            <label
                class="mt-2.5 block cursor-pointer rounded-lg bg-amber-500/15 px-2.5 py-1.5 text-center text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-slate-950">
                Elegir imágenes
                <input type="file" name="bulk_images[]" multiple
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="hidden"
                    @change="matchBulkImages($event)">
            </label>

            <p class="mt-2 border-t border-slate-800 pt-1.5 text-[9px] text-slate-600">
                JPG, PNG o WEBP · hasta 4 MB cada una
            </p>
        </div>

    </div>

</section>
