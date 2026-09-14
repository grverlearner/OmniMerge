@php
    /*
     * Nuevo valor de catálogo.
     *
     * La pantalla tiene tres estados, y el que más importa es el segundo:
     *
     *   · No hay ni un catálogo donde meterlo → se explica qué falta y dónde.
     *   · Hay catálogos pero no se ha elegido → se elige por su cara, viendo
     *     cuántos valores tiene ya cada uno y cuál está vacío.
     *   · Ya hay catálogo → el formulario.
     *
     * El segundo estado era antes un buscador con tarjetas planas. Ahora enseña
     * lo que hace falta para decidir: los vacíos primero, porque son los que
     * están pidiendo valores a gritos.
     */
@endphp

<x-app-layout title="Nuevo valor" surface="dark">

    <x-slot name="header">Catálogos</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('attribute-options.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Catálogos
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Nuevo valor
                    @if ($selectedAttribute)
                        <span class="text-slate-600">en</span>
                        <span style="color: {{ $selectedAttribute->color ?: '#a78bfa' }}">{{ $selectedAttribute->name }}</span>
                    @endif
                </h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Una de las opciones que se podrán elegir al rellenar un atributo. Con su nombre y su
                    cara.
                </p>
            </div>

            <div class="shrink-0 rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-right">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Su código será</p>
                <p class="font-mono text-[13px] font-black text-violet-300">{{ $previewCode }}</p>
            </div>
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-rose-200">Falta algo antes de poder guardar:</p>
                <ul class="mt-1 space-y-0.5 text-[11px] leading-relaxed text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- QUÉ ES ESTO --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: {{ $selectedAttribute ? 'false' : 'true' }} }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="capas" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Qué estás creando exactamente
                    <span class="font-bold text-slate-500">— catálogo, valor y entidad, en un dibujo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                <div class="grid gap-4 lg:grid-cols-[340px_minmax(0,1fr)]">

                    <svg viewBox="0 0 280 150" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- El catálogo, que ya existe --}}
                        <rect x="6" y="46" width="64" height="46" rx="5" stroke-dasharray="5 4" />
                        <path d="M16 58h44M16 69h44M16 80h28" opacity=".4" />
                        <text x="38" y="40" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">1 · El catálogo</text>
                        <text x="38" y="104" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">ya existe: «Clan»</text>

                        {{-- El valor, que es lo que se crea aquí --}}
                        <path d="M76 69h20M96 69l-6-4M96 69l-6 4" opacity=".7" />
                        <rect x="102" y="46" width="60" height="46" rx="5" stroke-width="2" />
                        <circle cx="120" cy="63" r="8" opacity=".85" />
                        <path d="M133 60h22M133 68h14" opacity=".45" />
                        <path d="M112 82h40" opacity=".3" />
                        <text x="132" y="40" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">2 · El valor</text>
                        <text x="132" y="104" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700">esto es lo que creas</text>
                        <text x="132" y="114" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">«Uzumaki» + su imagen</text>

                        {{-- La entidad, que se lo queda --}}
                        <path d="M168 69h20M188 69l-6-4M188 69l-6 4" opacity=".7" />
                        <rect x="194" y="42" width="80" height="54" rx="6" />
                        <circle cx="214" cy="60" r="9" opacity=".7" />
                        <path d="M230 54h34" opacity=".4" />
                        <circle cx="210" cy="80" r="4" opacity=".85" />
                        <path d="M219 80h26" opacity=".5" />
                        <text x="234" y="34" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">3 · La entidad</text>
                        <text x="234" y="108" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">Naruto · Clan = Uzumaki</text>

                        <path d="M6 128h268" opacity=".15" />
                        <text x="140" y="140" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".5">crear el valor no se lo pone a nadie: eso se hace desde cada entidad</text>
                    </svg>

                    <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            Estás creando una <strong class="text-white">opción elegible</strong> dentro de un
                            catálogo que ya existe. No estás creando el catálogo —eso es un atributo— ni se lo
                            estás poniendo a ninguna entidad.
                        </p>

                        <p>
                            Lo único imprescindible es el <strong class="text-violet-300">nombre</strong>. Todo
                            lo demás se puede rellenar luego… menos una cosa:
                        </p>

                        <p class="rounded-xl border border-rose-500/25 bg-rose-500/5 px-3 py-2 text-[10px] text-rose-200/80">
                            <strong class="text-rose-200">Ponle imagen ahora.</strong> Todas las pantallas
                            donde se elige un valor lo enseñan por su cara. Sin imagen aparece como un cuadro
                            vacío y no hay forma de reconocerlo entre cincuenta.
                        </p>

                        <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            Si este valor está <strong class="text-slate-300">dentro</strong> de otro —Konoha
                            dentro del País del Fuego— se dice en el paso 3. Si no, se deja suelto, que es lo
                            normal.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        @if ($attributes->isEmpty())

            {{-- ================================================= --}}
            {{-- NO HAY DÓNDE METERLO --}}
            {{-- ================================================= --}}

            <section class="rounded-2xl border border-dashed border-amber-500/30 bg-amber-500/5 p-10 text-center">

                <span class="inline-flex text-amber-400/60"><x-omni-icon name="capas" size="h-10 w-10" /></span>

                <p class="mt-3 text-[14px] font-black text-white">Todavía no tienes ningún catálogo</p>

                <p class="mx-auto mt-2 max-w-lg text-[11px] leading-relaxed text-slate-400">
                    Un valor tiene que pertenecer a un catálogo, y un catálogo no es más que un atributo de
                    tipo <strong class="text-violet-300">catálogo</strong>. Crea primero el atributo —«Clan»,
                    «Aldea», «Elemento»— y luego vuelve aquí a llenarlo.
                </p>

                <a href="{{ route('attributes.create') }}"
                    class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
                    Crear el atributo primero →
                </a>
            </section>

        @elseif (! $selectedAttribute)

            {{-- ================================================= --}}
            {{-- ELEGIR DÓNDE --}}
            {{-- ================================================= --}}

            @php
                /* Los vacíos primero: son los que están pidiendo valores. */
                $ordenados = $attributes->sortBy(fn($catalogo) => $catalogo->options_count === 0 ? 0 : 1)->values();

                $vacios = $attributes->where('options_count', 0)->count();
            @endphp

            <section x-data="{ buscar: '' }"
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">

                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <x-omni-icon name="capas" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">¿A qué catálogo va?</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            @if ($vacios > 0)
                                <strong class="text-amber-300">{{ $vacios }}</strong>
                                {{ $vacios === 1 ? 'está vacío' : 'están vacíos' }} y salen primero: un
                                catálogo sin valores no se puede asignar a nadie.
                            @else
                                Elige dónde vivirá este valor. Después no se puede cambiar.
                            @endif
                        </p>
                    </div>

                    <input type="search" x-model="buscar" placeholder="Buscar catálogo…"
                        class="w-full min-w-0 rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500 sm:w-56">
                </div>

                <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($ordenados as $catalogo)
                        @php $tono = $catalogo->color ?: '#6366f1'; @endphp

                        <a href="{{ route('attribute-options.create', ['attribute' => $catalogo->id]) }}"
                            x-show="! buscar || @js(mb_strtolower($catalogo->name)).includes(buscar.toLowerCase())"
                            class="group flex items-center gap-2.5 rounded-xl border bg-slate-950 p-2.5 transition hover:-translate-y-0.5"
                            style="border-color: {{ $catalogo->options_count === 0 ? '#f59e0b40' : $tono . '40' }}">

                            @include('attributes.partials.cara', [
                                'cosa' => $catalogo,
                                'tamano' => 'h-10 w-10',
                                'respaldo' => '◫',
                                'tono' => $tono,
                            ])

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12px] font-black text-white">{{ $catalogo->name }}</span>
                                <span class="block truncate font-mono text-[9px] text-slate-600">{{ $catalogo->code }}</span>
                            </span>

                            <span class="shrink-0 text-right">
                                <span class="block font-mono text-[14px] font-black"
                                    style="color: {{ $catalogo->options_count > 0 ? $tono : '#fbbf24' }}">
                                    {{ $catalogo->options_count }}
                                </span>
                                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                    {{ $catalogo->options_count === 0 ? 'vacío' : 'valores' }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

        @else

            {{-- ================================================= --}}
            {{-- EL FORMULARIO --}}
            {{-- ================================================= --}}

            <form method="POST" action="{{ route('attributes.options.store', $selectedAttribute) }}"
                enctype="multipart/form-data">
                @csrf

                @include('attribute-options.partials.form')
            </form>

        @endif

    </div>

</x-app-layout>
