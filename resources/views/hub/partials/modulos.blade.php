@php
    /*
     * Los cuatro módulos como portales.
     *
     * Cada uno entra por sus imágenes de verdad —tus entidades, las portadas
     * de tus mundos, tus torneos, lo que se mira en la comunidad—, dice qué
     * hay dentro con cifras grandes y ofrece los atajos a sus partes. Un
     * módulo se reconoce por lo que contiene, no por un adjetivo.
     */

    $portales = [
        [
            'nombre' => 'Biblioteca',
            'lema' => 'Lo que existe: entidades, cómo se describen y cómo se agrupan.',
            'icono' => 'libro',
            'color' => '#818cf8',
            'url' => route('dashboard'),
            'imagenes' => $carasBiblioteca->map(fn ($e) => $e->image_url)->filter()->values(),
            'cifras' => [
                ['Entidades', $statistics['entities']],
                ['Atributos', $statistics['attributes']],
                ['Colecciones', $statistics['collections']],
                ['Versiones', $statistics['versions']],
            ],
            'atajos' => [
                ['Entidades', 'libro', route('entities.index')],
                ['Tipos', 'cuadricula', route('entity-types.index')],
                ['Atributos', 'controles', route('attributes.index')],
                ['Catálogo', 'capas', route('attribute-options.index')],
                ['Colecciones', 'capas', route('collections.index')],
                ['Versiones', 'historial', route('versions.index')],
            ],
        ],
        [
            'nombre' => 'Universos',
            'lema' => 'Mundos con su propia gente, su calendario y su clasificación.',
            'icono' => 'orbita',
            'color' => '#a78bfa',
            'url' => route('universes.dashboard'),
            'imagenes' => $carasUniversos->map(fn ($u) => $u->image_url)->filter()->values(),
            'cifras' => [
                ['Mundos', $statistics['universes']],
                ['Habitantes', $statistics['inhabitants']],
                ['Competiciones', $statistics['competitions']],
                ['En juego', $statistics['live']],
            ],
            'atajos' => [
                ['Panel', 'panel', route('universes.dashboard')],
                ['Mis mundos', 'orbita', route('universes.index')],
                ['Nuevo mundo', 'mas', route('universes.create')],
            ],
        ],
        [
            'nombre' => 'Torneos',
            'lema' => 'La forma de la competición: fases, recorridos y salidas.',
            'icono' => 'trofeo',
            'color' => '#fbbf24',
            'url' => route('tournaments.dashboard'),
            /* Sin portadas propias, el torneo se reconoce por quién lo ganó */
            'imagenes' => $carasTorneos->map(fn ($t) => $t->image_url)
                ->concat($campeones->pluck('cara'))
                ->concat($enJuego->map(fn ($c) => $c->image_url))
                ->filter()->unique()->values(),
            'cifras' => [
                ['Torneos', $statistics['tournaments']],
                ['Fases', $statistics['phases']],
            ],
            'atajos' => [
                ['Panel', 'panel', route('tournaments.dashboard')],
                ['Torneos', 'trofeo', route('tournaments.templates.index')],
                ['Fases', 'grafo', route('tournaments.phase-templates.index')],
                ['Panel de creador', 'controles', route('tournaments.creator.show')],
            ],
        ],
        [
            'nombre' => 'Comunidad',
            'lema' => 'Lo que hacen otros: explorar, copiar y compartir lo tuyo.',
            'icono' => 'globo',
            'color' => '#34d399',
            'url' => route('community.home'),
            'imagenes' => $tendencias->map(fn ($t) => $t['modelo']->image_url)->concat($carasComunidad->map(fn ($e) => $e->image_url))->filter()->values(),
            'cifras' => [
                ['Publicado', $statistics['public']],
                ['Traído', $statistics['brought']],
                ['Te copiaron', $statistics['copied_from_me']],
            ],
            'atajos' => [
                ['Inicio', 'casa', route('community.home')],
                ['Biblioteca', 'libro', route('community.index')],
                ['Torneos', 'trofeo', route('tournaments.community.index')],
                ['Creadores', 'usuario', route('community.creators.index')],
            ],
        ],
    ];
@endphp

<section>
    <div class="mb-3">
        <h2 class="text-lg font-black text-white">Tus módulos</h2>
        <p class="text-xs text-slate-500">Cuatro espacios, cada uno con su color. Entra por el portal o salta directo a una de sus partes.</p>
    </div>

    @if (auth()->user()->isAdmin())
        @php
            $admin = [
                'cuentas' => \App\Models\User::query()->count(),
                'bloqueadas' => \App\Models\User::query()->whereNotNull('banned_at')->count(),
                'ocultos' => \App\Models\ContentFlag::query()->where('flag', 'HIDDEN')->count(),
                'verificados' => \App\Models\ContentFlag::query()->where('flag', 'VERIFIED')->count(),
            ];
        @endphp

        {{-- Solo lo ve quien tiene el rol: la puerta al espacio de administración --}}
        <a href="{{ route('admin.dashboard') }}"
            class="group mb-4 flex flex-wrap items-center gap-4 rounded-2xl border border-rose-500/30 bg-gradient-to-r from-rose-500/15 via-slate-900/60 to-slate-900/60 px-5 py-4 transition hover:border-rose-500/60">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-300">
                <x-omni-icon name="escudo" size="h-6 w-6" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-base font-black text-white">Administración</span>
                <span class="block text-xs text-slate-400">Todas las cuentas, todo el contenido y la configuración del sitio.</span>
            </span>
            <span class="flex flex-wrap gap-2 text-[11px] font-black">
                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-slate-300">{{ $admin['cuentas'] }} cuentas</span>
                @if ($admin['bloqueadas'])
                    <span class="rounded-full bg-orange-500/15 px-2.5 py-1 text-orange-300">{{ $admin['bloqueadas'] }} bloqueadas</span>
                @endif
                <span class="rounded-full bg-sky-500/15 px-2.5 py-1 text-sky-300">{{ $admin['verificados'] }} verificados</span>
                @if ($admin['ocultos'])
                    <span class="rounded-full bg-rose-500/15 px-2.5 py-1 text-rose-300">{{ $admin['ocultos'] }} ocultos</span>
                @endif
                @if ($sitio->inMaintenance())
                    <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-amber-300">En mantenimiento</span>
                @endif
            </span>
            <span class="text-slate-600 transition group-hover:translate-x-0.5 group-hover:text-rose-300">
                <x-omni-icon name="flecha-derecha" size="h-5 w-5" />
            </span>
        </a>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($portales as $portal)
            @php
                $imagenes = $portal['imagenes']->take(5);
                $color = $portal['color'];
            @endphp

            <article class="group/portal overflow-hidden rounded-3xl border bg-slate-900/50 transition hover:bg-slate-900/80"
                style="border-color: {{ $color }}33; --c: {{ $color }};">

                {{-- La cara del módulo: sus imágenes --}}
                <a href="{{ $portal['url'] }}" class="relative block h-44 overflow-hidden">
                    @if ($imagenes->isNotEmpty())
                        <div class="absolute inset-0 grid gap-px"
                            style="grid-template-columns: repeat({{ $imagenes->count() }}, minmax(0, 1fr));">
                            @foreach ($imagenes as $imagen)
                                <img src="{{ $imagen }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-700 group-hover/portal:scale-105">
                            @endforeach
                        </div>
                    @else
                        <div class="absolute inset-0 flex items-center justify-center"
                            style="background: radial-gradient(circle at 30% 20%, {{ $color }}33, transparent 60%), #0f172a; color: {{ $color }}55;">
                            <x-omni-icon :name="$portal['icono']" size="h-20 w-20" />
                        </div>
                    @endif

                    <div class="absolute inset-0" style="background: linear-gradient(180deg, #02061722 0%, #020617aa 55%, #020617 100%);"></div>
                    <div class="absolute inset-x-0 top-0 h-1" style="background-color: {{ $color }}"></div>

                    <div class="absolute inset-x-0 bottom-0 flex items-end gap-3 p-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border backdrop-blur"
                            style="color: {{ $color }}; border-color: {{ $color }}66; background-color: {{ $color }}26;">
                            <x-omni-icon :name="$portal['icono']" size="h-6 w-6" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-2xl font-black leading-tight text-white">{{ $portal['nombre'] }}</span>
                            <span class="block truncate text-xs text-slate-300">{{ $portal['lema'] }}</span>
                        </span>
                        <span class="hidden shrink-0 items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-black text-slate-950 transition group-hover/portal:translate-x-0.5 sm:inline-flex"
                            style="background-color: {{ $color }}">
                            Entrar <x-omni-icon name="flecha-derecha" size="h-3.5 w-3.5" />
                        </span>
                    </div>
                </a>

                {{-- Lo que hay dentro --}}
                <div class="grid gap-px bg-white/5" style="grid-template-columns: repeat({{ count($portal['cifras']) }}, minmax(0, 1fr));">
                    @foreach ($portal['cifras'] as [$texto, $valor])
                        <div class="bg-slate-950/60 px-3 py-3 text-center">
                            <span class="block text-2xl font-black leading-none" style="color: {{ $valor > 0 ? $color : '#475569' }}">{{ number_format($valor) }}</span>
                            <span class="mt-1 block truncate text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $texto }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Atajos a sus partes --}}
                <div class="flex flex-wrap gap-1.5 p-4">
                    @foreach ($portal['atajos'] as [$texto, $icono, $destino])
                        <a href="{{ $destino }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-[11px] font-bold text-slate-300 transition hover:border-[var(--c)] hover:text-white">
                            <span style="color: {{ $color }}"><x-omni-icon :name="$icono" size="h-3.5 w-3.5" /></span>
                            {{ $texto }}
                        </a>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</section>
