@php
    /*
     * Nuevo atributo.
     *
     * La cáscara es fina: todo el trabajo vive en el formulario compartido con
     * la edición. Aquí van la cabecera y el explicador de qué es un atributo y
     * dónde encaja —que hace falta la primera vez y estorba después, por eso
     * está plegado—.
     */
@endphp

<x-app-layout title="Nuevo atributo" surface="dark">

    <x-slot name="header">Atributos</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('attributes.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Atributos
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Nuevo atributo</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Un dato que describirá a tus entidades. Se crea una vez y vale para toda la biblioteca.
                </p>
            </div>

            <a href="{{ route('attributes.index') }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </header>


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
        {{-- DÓNDE ENCAJA UN ATRIBUTO --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Qué es un atributo y dónde encaja
                    <span class="font-bold text-slate-500">— atributo, valor y entidad, en un dibujo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">

                    <svg viewBox="0 0 260 126" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- El atributo: el molde del dato --}}
                        <rect x="6" y="44" width="62" height="34" rx="5" stroke-dasharray="5 4" />
                        <path d="M16 56h42M16 66h26" opacity=".5" />
                        <text x="37" y="38" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">El atributo</text>
                        <text x="37" y="90" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">«Clan»</text>

                        {{-- Sus valores, si es catálogo --}}
                        <path d="M74 61h18M92 61l-6-4M92 61l-6 4" opacity=".7" />
                        <rect x="98" y="34" width="46" height="16" rx="3" opacity=".85" />
                        <rect x="98" y="53" width="46" height="16" rx="3" opacity=".85" />
                        <rect x="98" y="72" width="46" height="16" rx="3" opacity=".55" />
                        <circle cx="108" cy="42" r="4" opacity=".6" />
                        <circle cx="108" cy="61" r="4" opacity=".6" />
                        <circle cx="108" cy="80" r="4" opacity=".4" />
                        <text x="121" y="26" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">Sus valores</text>
                        <text x="121" y="100" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">Uzumaki, Uchiha…</text>

                        {{-- La entidad, que recibe uno --}}
                        <path d="M150 61h18M168 61l-6-4M168 61l-6 4" opacity=".7" />
                        <rect x="174" y="34" width="80" height="54" rx="6" />
                        <circle cx="194" cy="52" r="9" opacity=".7" />
                        <path d="M210 46h34" opacity=".4" />
                        <path d="M186 72h30" opacity=".9" />
                        <path d="M222 72h22" opacity=".35" />
                        <text x="214" y="26" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">La entidad</text>
                        <text x="214" y="100" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">Naruto · Clan = Uzumaki</text>

                        <path d="M6 112h248" opacity=".15" />
                        <text x="130" y="122" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".5">el atributo se crea una vez y lo usan todas</text>
                    </svg>

                    <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            Un <strong class="text-white">atributo</strong> es el molde de un dato: «Clan»,
                            «Edad», «Aldea». No es de ninguna entidad en concreto — se crea una vez y lo usan
                            todas las que quieras.
                        </p>

                        <p>
                            Si es de tipo <strong class="text-violet-300">catálogo</strong>, tiene además una
                            lista de <strong class="text-slate-200">valores</strong> —Uzumaki, Uchiha,
                            Hyūga—, cada uno con su imagen. Esos valores se añaden **después**, en la ficha
                            del atributo, no aquí.
                        </p>

                        <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-400">
                            <strong class="text-slate-200">¿Y la jerarquía?</strong> La tienen los valores,
                            no los atributos: una aldea puede colgar de un país. Se monta al añadir los
                            valores. Entre atributos no hay jerarquía; lo que hay son
                            <strong class="text-slate-200">grupos</strong>, que solo ordenan la ficha.
                        </p>

                        <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            Crear el atributo no se lo asigna a nadie. Eso se hace luego, entidad por
                            entidad o en lote.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        <form method="POST" action="{{ route('attributes.store') }}" enctype="multipart/form-data">
            @csrf

            @include('attributes.partials.form')
        </form>

    </div>

</x-app-layout>
