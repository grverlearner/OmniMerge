@php
    /*
     * La Base activa de una entidad.
     *
     * «Cambiar base» era un botón sin explicación al lado de un nombre. La
     * pregunta que nadie podía responder mirándolo era la única que importa:
     * ¿qué le pasa a lo demás si la cambio?
     *
     * Respuesta, y ahora está dibujada: nada. La base activa es solo la CARA
     * que el resto de la aplicación enseña de esta entidad. No borra, no
     * sustituye y no toca ninguna versión: cambia a cuál apunta.
     *
     * El modal elige por la cara, no por un desplegable de nombres, y deja
     * ver de dónde vienes y a dónde vas antes de confirmar.
     */

    $currentBase = $activeBaseEntityVersion ?? $entity->baseVersionSetting?->entityVersion;

    $availableBaseVersions = $entity->entityVersions->where('status', 'ACTIVE');
@endphp

<section x-data="{
    open: false,
    elegida: {{ $currentBase?->id ?? 'null' }},
    elegidaNombre: @js($currentBase?->name ?? $entity->name),
    elegidaImagen: @js($currentBase?->image_url ?? $entity->image_url),
    elegidaMolde: @js($currentBase?->version?->name ?? 'La entidad original'),
}" @keydown.escape.window="open = false"
    class="overflow-hidden rounded-2xl border border-amber-500/25 bg-slate-900/50">

    {{-- ===================================================== --}}
    {{-- LA CARA DE AHORA --}}
    {{-- ===================================================== --}}

    <div class="flex flex-wrap items-center gap-3 p-4">

        <span class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-amber-500/40 bg-slate-950">
            @if ($currentBase?->image_url)
                <img src="{{ $currentBase->image_url }}" alt="" class="h-full w-full object-cover">
            @elseif ($entity->image_url)
                <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
            @endif
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="rounded bg-amber-400 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-950">
                    ★ Base activa
                </span>

                <span class="text-[10px] text-slate-500">
                    la cara que el resto de la aplicación enseña de {{ $entity->name }}
                </span>
            </div>

            <p class="mt-0.5 truncate text-[14px] font-black text-white">
                {{ $currentBase?->name ?? $entity->name }}
            </p>

            <p class="truncate text-[10px] text-slate-500">
                @if ($currentBase)
                    Molde: <span class="text-violet-300">{{ $currentBase->version?->name }}</span>
                @else
                    Sin ninguna versión encima: se enseña la entidad tal como la creaste.
                @endif
            </p>
        </div>

        @can('update', $entity)
            <button type="button" @click="open = true"
                class="shrink-0 rounded-xl bg-amber-500/15 px-3 py-2 text-[11px] font-black text-amber-300 transition hover:bg-amber-400 hover:text-amber-950">
                ⇄ Cambiar la base
            </button>
        @endcan
    </div>


    {{-- ===================================================== --}}
    {{-- QUÉ SIGNIFICA, DIBUJADO --}}
    {{-- ===================================================== --}}

    <div x-data="{ abierto: false }" class="border-t border-slate-800">

        <button type="button" @click="abierto = !abierto"
            class="flex w-full items-center gap-2 px-4 py-2 text-left transition hover:bg-slate-950/50">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                ¿Qué pasa si la cambio?
            </span>
            <span class="ml-auto text-slate-600 transition" :class="abierto ? 'rotate-90' : ''">
                <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
            </span>
        </button>

        <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800 bg-slate-950/40 p-4">
            <div class="grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">

                <svg viewBox="0 0 250 108" class="h-auto w-full text-amber-400" fill="none"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                    {{-- Las versiones, todas intactas --}}
                    <rect x="6" y="8" width="52" height="26" rx="4" opacity=".45" />
                    <circle cx="20" cy="21" r="6" opacity=".4" />
                    <path d="M32 17h18M32 25h12" opacity=".25" />

                    <rect x="6" y="41" width="52" height="26" rx="4" />
                    <circle cx="20" cy="54" r="6" opacity=".8" />
                    <path d="M32 50h18M32 58h12" opacity=".45" />
                    <path d="M50 39l2.4 4.8 5.3.8-3.8 3.7.9 5.3-4.8-2.5-4.8 2.5.9-5.3-3.8-3.7 5.3-.8z"
                        fill="currentColor" stroke="none" />

                    <rect x="6" y="74" width="52" height="26" rx="4" opacity=".45" />
                    <circle cx="20" cy="87" r="6" opacity=".4" />
                    <path d="M32 83h18M32 91h12" opacity=".25" />

                    {{-- La flecha, que es lo único que se mueve --}}
                    <path d="M66 54h26M92 54l-7-4M92 54l-7 4" opacity=".9" />
                    <path d="M66 54 66 21M66 21h20" stroke-dasharray="3 3" opacity=".3" />
                    <path d="M66 54 66 87M66 87h20" stroke-dasharray="3 3" opacity=".3" />

                    {{-- Lo que ve el resto de la aplicación --}}
                    <rect x="100" y="30" width="66" height="48" rx="5" />
                    <circle cx="122" cy="54" r="10" opacity=".7" />
                    <path d="M140 46h18M140 60h12" opacity=".4" />
                    <text x="133" y="24" text-anchor="middle" fill="currentColor" stroke="none"
                        font-size="8" font-weight="700">El resto la ve así</text>

                    <path d="M174 54h20M194 54l-6-4M194 54l-6 4" opacity=".5" />
                    <rect x="200" y="34" width="20" height="16" rx="2" opacity=".55" />
                    <rect x="226" y="34" width="20" height="16" rx="2" opacity=".55" />
                    <rect x="200" y="58" width="20" height="16" rx="2" opacity=".55" />
                    <rect x="226" y="58" width="20" height="16" rx="2" opacity=".55" />
                </svg>

                <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                    <p>
                        <strong class="text-white">No pasa nada malo.</strong> Cambiar la base activa no
                        borra ni sustituye ninguna versión: las tres del dibujo siguen exactamente donde
                        estaban. Lo único que se mueve es <strong class="text-amber-300">la flecha</strong>.
                    </p>

                    <p>
                        La base activa es la cara que verán los listados, las fichas y todo lo que enseñe
                        a <strong class="text-slate-200">{{ $entity->name }}</strong> sin pedir una versión
                        concreta.
                    </p>

                    <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                        Puedes volver a la <strong class="text-slate-300">entidad original</strong> cuando
                        quieras: es la opción de arriba del todo en el selector, y también deja intactas
                        todas las versiones.
                    </p>
                </div>

            </div>
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- EL SELECTOR --}}
    {{-- ===================================================== --}}

    @can('update', $entity)
        <div x-show="open" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
            @click.self="open = false">

            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">

                {{-- Cabecera --}}
                <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                        <x-omni-icon name="medalla" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-black text-white">
                            ¿Qué cara enseña {{ $entity->name }}?
                        </p>
                        <p class="text-[10px] text-slate-500">
                            Elige una. Ninguna versión se borra ni se modifica.
                        </p>
                    </div>

                    <button type="button" @click="open = false"
                        class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-white">
                        <x-omni-icon name="cerrar" size="h-4 w-4" />
                    </button>
                </div>


                {{-- De dónde vienes, a dónde vas --}}
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 bg-slate-950/50 px-4 py-3">

                    <span class="flex items-center gap-2">
                        <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($currentBase?->image_url)
                                <img src="{{ $currentBase->image_url }}" alt="" class="h-full w-full object-cover">
                            @elseif ($entity->image_url)
                                <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Ahora</span>
                            <span class="block truncate text-[11px] font-black text-slate-300">
                                {{ $currentBase?->name ?? $entity->name }}
                            </span>
                        </span>
                    </span>

                    <span class="text-slate-700">→</span>

                    <span class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-amber-500/40 bg-slate-950">
                            <template x-if="elegidaImagen">
                                <img :src="elegidaImagen" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="! elegidaImagen">
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                            </template>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[9px] font-black uppercase tracking-wider text-amber-400/70">Quedaría</span>
                            <span class="block truncate text-[11px] font-black text-white" x-text="elegidaNombre"></span>
                            <span class="block truncate text-[9px] text-violet-300" x-text="elegidaMolde"></span>
                        </span>
                    </span>
                </div>


                {{-- Las opciones --}}
                <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4">

                    {{-- La entidad original --}}
                    <button type="button"
                        @click="elegida = null;
                                elegidaNombre = @js($entity->name);
                                elegidaImagen = @js($entity->image_url);
                                elegidaMolde = 'La entidad original'"
                        :class="elegida === null
                            ? 'border-amber-500 ring-1 ring-amber-500'
                            : 'border-slate-800 hover:border-slate-600'"
                        class="group overflow-hidden rounded-xl border bg-slate-950 text-left transition">

                        <span class="relative block aspect-square overflow-hidden bg-slate-900">
                            @if ($entity->image_url)
                                <img src="{{ $entity->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                            @endif

                            <span class="absolute left-1 top-1 rounded bg-slate-950/85 px-1 text-[8px] font-black uppercase tracking-wider text-slate-400">
                                Original
                            </span>

                            @unless ($currentBase)
                                <span class="absolute right-1 top-1 rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★ AHORA</span>
                            @endunless

                            <span x-show="elegida === null" x-cloak
                                class="absolute inset-0 flex items-center justify-center bg-amber-500/30 text-lg font-black text-white">✓</span>
                        </span>

                        <span class="block px-1.5 py-1">
                            <span class="block truncate text-[10px] font-black text-white">{{ $entity->name }}</span>
                            <span class="block truncate text-[9px] text-slate-600">Sin versión encima</span>
                        </span>
                    </button>

                    {{-- Sus versiones --}}
                    @foreach ($availableBaseVersions as $opcion)
                        <button type="button"
                            @click="elegida = {{ $opcion->id }};
                                    elegidaNombre = @js($opcion->name);
                                    elegidaImagen = @js($opcion->image_url);
                                    elegidaMolde = @js('Molde: ' . ($opcion->version?->name ?? '—'))"
                            :class="elegida === {{ $opcion->id }}
                                ? 'border-amber-500 ring-1 ring-amber-500'
                                : 'border-slate-800 hover:border-slate-600'"
                            class="group overflow-hidden rounded-xl border bg-slate-950 text-left transition">

                            <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                @if ($opcion->image_url)
                                    <img src="{{ $opcion->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◈</span>
                                @endif

                                <span class="absolute left-1 top-1 rounded bg-slate-950/85 px-1 text-[8px] font-black uppercase tracking-wider text-violet-300">
                                    {{ $opcion->version?->name }}
                                </span>

                                @if ($currentBase && $currentBase->id === $opcion->id)
                                    <span class="absolute right-1 top-1 rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★ AHORA</span>
                                @endif

                                <span x-show="elegida === {{ $opcion->id }}" x-cloak
                                    class="absolute inset-0 flex items-center justify-center bg-amber-500/30 text-lg font-black text-white">✓</span>
                            </span>

                            <span class="block px-1.5 py-1">
                                <span class="block truncate text-[10px] font-black text-white">{{ $opcion->name }}</span>
                                <span class="block truncate text-[9px] text-slate-600">
                                    {{ $opcion->version_attributes_count ?? 0 }} cambios ·
                                    {{ $opcion->images_count ?? 0 }} imágenes
                                </span>
                            </span>
                        </button>
                    @endforeach

                </div>

                @if ($availableBaseVersions->isEmpty())
                    <p class="px-4 pb-4 text-[11px] text-slate-500">
                        {{ $entity->name }} no tiene ninguna versión activa todavía, así que la única cara
                        posible es la de la entidad original.
                    </p>
                @endif


                {{-- Confirmar --}}
                <div class="flex flex-wrap items-center gap-2 border-t border-slate-800 px-4 py-3">

                    <p class="min-w-0 flex-1 text-[10px] leading-relaxed text-slate-500">
                        Se cambia solo a qué apunta la entidad.
                        <strong class="text-slate-300">Ninguna versión se borra ni se modifica.</strong>
                    </p>

                    <button type="button" @click="open = false"
                        class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:text-white">
                        Cancelar
                    </button>

                    {{-- Volver a la entidad original --}}
                    <form method="POST" action="{{ route('entities.base-version.destroy', $entity) }}"
                        x-show="elegida === null" x-cloak>
                        @csrf
                        @method('DELETE')
                        <button type="submit" @disabled(! $currentBase)
                            class="rounded-xl bg-amber-500 px-4 py-2 text-[11px] font-black text-amber-950 transition hover:bg-amber-400 disabled:opacity-40">
                            {{ $currentBase ? 'Volver a la entidad original' : 'Ya es la que está puesta' }}
                        </button>
                    </form>

                    {{-- Poner una versión --}}
                    <form method="POST" action="{{ route('entities.base-version.update', $entity) }}"
                        x-show="elegida !== null" x-cloak>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="entity_version_id" :value="elegida">

                        <button type="submit"
                            :disabled="elegida === {{ $currentBase?->id ?? 'null' }}"
                            class="rounded-xl bg-amber-500 px-4 py-2 text-[11px] font-black text-amber-950 transition hover:bg-amber-400 disabled:opacity-40">
                            <span x-show="elegida !== {{ $currentBase?->id ?? 'null' }}">Poner esta como base</span>
                            <span x-show="elegida === {{ $currentBase?->id ?? 'null' }}" x-cloak>Ya es la que está puesta</span>
                        </button>
                    </form>

                </div>

            </div>

        </div>
    @endcan

</section>
