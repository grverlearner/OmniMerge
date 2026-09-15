@php
    /*
     * Que se puede crear, y la comunidad.
     *
     * La idea de la seccion anterior era buena -«OmniMerge no decide que es una
     * entidad»- y se conserva. Lo que cambia son los emojis por iconos y los
     * ejemplos por otros que enseñan el punto: que lo que se compara puede ser
     * cualquier cosa mientras comparta atributos.
     */

    $ejemplos = [
        ['Personajes', 'usuario', '#818cf8', 'Un ninja con su aldea, su rango y su elemento.'],
        ['Países', 'globo', '#22d3ee', 'Con su continente, su idioma y su población.'],
        ['Criaturas', 'chispa', '#a78bfa', 'Con su tipo, su hábitat y de qué es capaz.'],
        ['Objetos', 'capas', '#fbbf24', 'Un arma, un libro, una carta de un juego.'],
        ['Equipos', 'espadas', '#34d399', 'Clubes, escuadrones, casas rivales.'],
        ['Conceptos', 'matraz', '#f472b6', 'Lenguajes, ideas, teorías. Si tiene atributos, compite.'],
    ];
@endphp

<section class="relative border-y border-white/5 bg-slate-950/40 px-5 py-14 lg:px-8">

    <div class="mx-auto max-w-7xl">

        <div class="mx-auto max-w-2xl text-center">
            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-fuchsia-400/70">
                Sin categorías de fábrica
            </span>

            <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                ¿Qué puedes crear?
            </h2>

            <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                OmniMerge no decide qué es una entidad. Tú decides qué quieres representar, y
                dos cosas pueden enfrentarse mientras compartan los atributos que el torneo
                mire.
            </p>
        </div>

        <div class="mt-9 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($ejemplos as [$titulo, $icono, $tono, $texto])
                <div class="flex items-start gap-3 rounded-2xl border bg-slate-900/40 p-4"
                    style="border-color: {{ $tono }}2e">

                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                        style="background-color: {{ $tono }}1f; color: {{ $tono }}">
                        <x-omni-icon :name="$icono" size="h-5 w-5" />
                    </span>

                    <div class="min-w-0">
                        <h3 class="text-[14px] font-black text-white">{{ $titulo }}</h3>
                        <p class="mt-0.5 text-[11px] leading-relaxed text-slate-500">{{ $texto }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ===================================================== --}}
{{-- COMUNIDAD --}}
{{-- ===================================================== --}}

<section id="comunidad" class="relative px-5 py-14 lg:px-8">

    <div class="mx-auto max-w-7xl">

        <div class="grid items-center gap-8 lg:grid-cols-2">

            <div>
                <span class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-400/70">
                    Comunidad
                </span>

                <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Empieza de lo que otros ya hicieron
                </h2>

                <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                    Lo que alguien marca como público se puede explorar y copiar. La copia llega
                    a tu biblioteca y <strong class="text-slate-200">es tuya</strong>: la editas,
                    la versionas y la metes en tus mundos sin que el original se entere.
                </p>

                <div class="mt-5 space-y-2">
                    @foreach ([
                        ['Copiar no es enlazar', 'Tu copia evoluciona por su cuenta. Si el original cambia, la tuya no.', '#34d399'],
                        ['Se recuerda de dónde vino', 'Cada copia guarda a quién se lo debe, y ese crédito viaja con ella.', '#22d3ee'],
                        ['Tú eliges qué se ve', 'Nada es público hasta que lo marcas. Puedes tener toda tu biblioteca en privado.', '#a78bfa'],
                    ] as [$titulo, $texto, $tono])

                        <div class="flex items-start gap-2.5 rounded-xl border border-white/5 bg-slate-900/40 p-3">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $tono }}"></span>
                            <span class="min-w-0">
                                <span class="block text-[13px] font-black text-white">{{ $titulo }}</span>
                                <span class="block text-[11px] leading-4 text-slate-500">{{ $texto }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>


            {{-- Cómo viaja una copia --}}
            <div class="overflow-hidden rounded-2xl border border-emerald-500/20 bg-slate-900/40 p-4">

                <svg viewBox="0 0 520 250" class="block h-auto w-full">

                    <defs>
                        <marker id="flechaCopia" markerWidth="9" markerHeight="9" refX="8" refY="4.5" orient="auto">
                            <path d="M 0 0 L 9 4.5 L 0 9 z" fill="#34d399" />
                        </marker>
                    </defs>

                    {{-- El original --}}
                    <rect x="18" y="60" width="170" height="92" rx="16"
                        fill="#22d3ee12" stroke="#22d3ee55" stroke-width="1.5" />
                    <text x="103" y="88" text-anchor="middle" font-size="12" font-weight="800" fill="#22d3ee">
                        Biblioteca de otro
                    </text>
                    <rect x="42" y="100" width="122" height="34" rx="10" fill="#0f172a" stroke="#1e293b" />
                    <rect x="52" y="110" width="14" height="14" rx="4" fill="#22d3ee44" />
                    <rect x="74" y="112" width="70" height="4" rx="2" fill="#334155" />
                    <rect x="74" y="121" width="46" height="4" rx="2" fill="#1e293b" />

                    {{-- La flecha --}}
                    <line x1="192" y1="106" x2="322" y2="106" stroke="#34d399" stroke-width="2"
                        stroke-dasharray="5 4" marker-end="url(#flechaCopia)" />
                    <text x="257" y="96" text-anchor="middle" font-size="11" font-weight="700" fill="#34d399">
                        copiar
                    </text>

                    {{-- La copia --}}
                    <rect x="330" y="60" width="172" height="92" rx="16"
                        fill="#34d39912" stroke="#34d39955" stroke-width="1.5" />
                    <text x="416" y="88" text-anchor="middle" font-size="12" font-weight="800" fill="#34d399">
                        La tuya
                    </text>
                    <rect x="354" y="100" width="124" height="34" rx="10" fill="#0f172a" stroke="#1e293b" />
                    <rect x="364" y="110" width="14" height="14" rx="4" fill="#34d39944" />
                    <rect x="386" y="112" width="72" height="4" rx="2" fill="#334155" />
                    <rect x="386" y="121" width="38" height="4" rx="2" fill="#1e293b" />

                    <text x="260" y="182" text-anchor="middle" font-size="11" fill="#64748b">
                        A partir de aquí son dos cosas distintas.
                    </text>
                    <text x="260" y="200" text-anchor="middle" font-size="11" fill="#64748b">
                        Editar una no toca la otra.
                    </text>

                    <text x="260" y="228" text-anchor="middle" font-size="10" fill="#475569">
                        Tu copia guarda el crédito de quien la hizo primero.
                    </text>
                </svg>
            </div>
        </div>
    </div>
</section>
