@php
    /*
     * La portada del Centro: tus propias imágenes de fondo, quién eres, cómo
     * está todo en una frase, las cifras de la cuenta y lo que puedes crear
     * ahora mismo sin pasar por ningún menú.
     */

    $urgentes = $atencion->where('urgente', true)->count();

    $cifras = [
        ['Entidades', $statistics['entities'], 'libro', '#818cf8', route('entities.index')],
        ['Colecciones', $statistics['collections'], 'capas', '#34d399', route('collections.index')],
        ['Mundos', $statistics['universes'], 'orbita', '#a78bfa', route('universes.index')],
        ['Torneos', $statistics['tournaments'], 'trofeo', '#fbbf24', route('tournaments.templates.index')],
        ['Competiciones', $statistics['competitions'], 'espadas', '#10b981', route('universes.dashboard')],
        ['En juego', $statistics['live'], 'pulso', '#fb7185', route('universes.dashboard')],
    ];

    $crear = [
        ['Entidad', 'Un personaje, un país, lo que sea', 'libro', '#818cf8', route('entities.create')],
        ['Varias a la vez', 'Crea entidades en bloque', 'cuadricula', '#6366f1', route('entities.bulk.create')],
        ['Colección', 'Agrupa entidades', 'capas', '#34d399', route('collections.create')],
        ['Universo', 'Un mundo con su gente', 'orbita', '#a78bfa', route('universes.create')],
        ['Torneo', 'Diseña el recorrido', 'trofeo', '#fbbf24', route('tournaments.templates.create')],
        ['Fase', 'Liga, grupos, eliminación…', 'grafo', '#f472b6', route('tournaments.phase-templates.create')],
    ];

@endphp

<section class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/40">

    {{-- Tus imágenes, de fondo --}}
    @if ($mosaico->isNotEmpty())
        <div class="pointer-events-none absolute inset-0 grid grid-cols-6 grid-rows-3 overflow-hidden opacity-50 sm:grid-cols-9 lg:grid-cols-12">
            @foreach ($mosaico as $cara)
                <span class="block min-h-0 overflow-hidden">
                    <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                </span>
            @endforeach
        </div>
    @endif

    <div class="pointer-events-none absolute inset-0"
        style="background: linear-gradient(100deg, #020617 0%, #020617ee 34%, #020617b0 60%, #02061766 100%)"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-slate-950/90 to-transparent"></div>
    <div class="pointer-events-none absolute -left-20 -top-24 h-72 w-72 rounded-full opacity-30 blur-3xl" style="background-color: var(--omni-accent)"></div>

    <div class="relative grid gap-6 p-5 sm:p-7 lg:grid-cols-[1fr_400px]">

        {{-- ===================================================== --}}
        {{-- QUIÉN ERES Y CÓMO ESTÁ TODO --}}
        {{-- ===================================================== --}}

        <div class="flex min-w-0 flex-col">

            <p class="flex flex-wrap items-center gap-2 text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">
                <span class="omni-accent-text">Centro {{ $sitio->name() }}</span>
                <span class="text-slate-700">·</span>
                <span>{{ now()->translatedFormat('l j \d\e F') }}</span>
            </p>

            <div class="mt-5 flex items-center gap-4">
                <a href="{{ route('profiles.show', $usuario->username) }}" title="Tu perfil público"
                    class="relative shrink-0 overflow-hidden rounded-2xl border-2 shadow-xl shadow-black/40" style="border-color: var(--omni-accent)">
                    <x-user-avatar :user="$usuario" size="xl" square />
                </a>

                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-400">Hola de nuevo,</p>
                    <h1 class="truncate text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $usuario->name }}</h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-400">&#64;{{ $usuario->username }}</span>
                        <x-creator-badge :user="$usuario" />
                        @if ($usuario->isAdmin())
                            <span class="inline-flex items-center gap-1 rounded-full border border-rose-500/40 bg-rose-500/10 px-2 py-0.5 text-[10px] font-black text-rose-300">
                                <x-omni-icon name="escudo" size="h-3 w-3" /> Admin
                            </span>
                        @endif
                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-black {{ $usuario->isPublicProfile() ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/40 bg-amber-500/10 text-amber-300' }}">
                            Perfil {{ $usuario->isPublicProfile() ? 'público' : 'privado' }}
                        </span>
                    </div>
                </div>
            </div>

            <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-slate-300">
                @if ($statistics['total'] === 0)
                    Aquí se crean entidades, se describen con atributos, se organizan en mundos y se las hace competir.
                    Empieza por lo que quieras desde el panel de la derecha.
                @elseif ($statistics['stuck'] > 0)
                    <span class="font-black text-rose-300">{{ $statistics['stuck'] === 1 ? 'Una competición está parada' : $statistics['stuck'] . ' competiciones están paradas' }}</span>
                    esperando una decisión tuya. Lo tienes justo debajo.
                @elseif ($statistics['live'] > 0)
                    Se están jugando <span class="font-black text-emerald-300">{{ $statistics['live'] }} {{ $statistics['live'] === 1 ? 'competición' : 'competiciones' }}</span>
                    en tus mundos. Entra a seguir la acción.
                @elseif ($urgentes > 0)
                    Hay cosas esperándote: te las dejamos ordenadas más abajo.
                @else
                    Todo está al día. Tienes {{ $statistics['entities'] }} entidades repartidas en {{ $statistics['universes'] }}
                    {{ $statistics['universes'] === 1 ? 'mundo' : 'mundos' }} y {{ $statistics['tournaments'] }}
                    {{ $statistics['tournaments'] === 1 ? 'torneo diseñado' : 'torneos diseñados' }}.
                @endif
            </p>

            {{-- Las cifras de la cuenta --}}
            <div class="mt-6 grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach ($cifras as [$texto, $valor, $icono, $color, $destino])
                    <a href="{{ $destino }}"
                        class="group rounded-2xl border bg-slate-950/60 px-3 py-2.5 backdrop-blur transition hover:-translate-y-0.5"
                        style="border-color: {{ $color }}33;">
                        <span class="flex items-center justify-between">
                            <span style="color: {{ $color }}"><x-omni-icon :name="$icono" size="h-4 w-4" /></span>
                            @if ($texto === 'En juego' && $valor > 0)
                                <span class="h-2 w-2 animate-pulse rounded-full bg-rose-400"></span>
                            @endif
                        </span>
                        <span class="mt-1.5 block text-2xl font-black leading-none text-white">{{ number_format($valor) }}</span>
                        <span class="mt-1 block truncate text-[10px] font-black uppercase tracking-wider text-slate-500 group-hover:text-slate-300">{{ $texto }}</span>
                    </a>
                @endforeach
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- CREAR ALGO NUEVO --}}
        {{-- ===================================================== --}}

        <aside class="rounded-2xl border border-white/10 bg-slate-950/70 p-4 backdrop-blur">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-sm font-black text-white">
                    <x-omni-icon name="mas" size="h-4 w-4" class="omni-accent-text" /> Crear algo nuevo
                </h2>
                <button type="button" @click="$dispatch('omni-buscar')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 px-2 py-1 text-[11px] font-bold text-slate-400 hover:text-white">
                    <x-omni-icon name="filtro" size="h-3.5 w-3.5" /> Buscar
                </button>
            </div>
            <p class="mt-0.5 text-[11px] text-slate-500">Va directo al formulario, sin pasar por el módulo.</p>

            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($crear as [$titulo, $pie, $icono, $color, $destino])
                    <a href="{{ $destino }}"
                        class="group flex items-start gap-2.5 rounded-xl border bg-slate-900/60 p-2.5 transition hover:bg-slate-900"
                        style="border-color: {{ $color }}30;">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition group-hover:scale-105"
                            style="color: {{ $color }}; background-color: {{ $color }}1c;">
                            <x-omni-icon :name="$icono" size="h-[18px] w-[18px]" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[13px] font-black text-white">{{ $titulo }}</span>
                            <span class="block truncate text-[10px] text-slate-500">{{ $pie }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="mt-3 flex gap-2">
                <a href="{{ route('profiles.show', $usuario->username) }}"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/10 py-2 text-[11px] font-black text-slate-300 hover:border-white/25 hover:text-white">
                    <x-omni-icon name="chispa" size="h-3.5 w-3.5" /> Mi perfil
                </a>
                <a href="{{ route('profile.edit') }}"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/10 py-2 text-[11px] font-black text-slate-300 hover:border-white/25 hover:text-white">
                    <x-omni-icon name="engranaje" size="h-3.5 w-3.5" /> Ajustes
                </a>
            </div>
        </aside>
    </div>
</section>
