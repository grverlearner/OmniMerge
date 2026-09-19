<x-admin-layout title="Resumen">

    <x-slot:header>Resumen del sitio</x-slot:header>

    @php
        $maxDia = max(1, $actividad->max(fn ($d) => max($d['altas'], $d['creado'])));
        $estadosCompeticion = [
            'DRAFT' => ['Preparadas', '#94a3b8'],
            'RUNNING' => ['En juego', '#34d399'],
            'COMPLETED' => ['Jugadas', '#38bdf8'],
            'CANCELLED' => ['Canceladas', '#fb7185'],
        ];
    @endphp

    <div class="space-y-5">

        {{-- ========================================================= --}}
        {{-- CABECERA --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border border-rose-500/25 bg-gradient-to-br from-rose-500/10 via-slate-900/60 to-slate-900/60 p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-2xl">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-300">Espacio del administrador</p>
                    <h2 class="mt-1 text-2xl font-black text-white">Todo {{ $sitio->name() }} desde aquí</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-400">
                        Revisa las cuentas y lo que crea cada una, marca el contenido de confianza, oculta lo que no debe verse
                        y decide cómo se presenta el sitio. Todo lo que cambies queda en el
                        <a href="{{ route('admin.audit') }}" class="font-bold text-rose-300 hover:text-rose-200">registro de acciones</a>.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2 text-xs font-black text-white transition hover:bg-rose-400">
                        <x-omni-icon name="usuario" size="h-4 w-4" /> Revisar usuarios
                    </a>
                    <a href="{{ route('admin.settings.edit') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-2 text-xs font-black text-slate-200 transition hover:border-rose-500/50">
                        <x-omni-icon name="engranaje" size="h-4 w-4" /> Configurar el sitio
                    </a>
                </div>
            </div>

            {{-- Lo que está pasando ahora y afecta a todos --}}
            <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-black">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 {{ $sitio->inMaintenance() ? 'border-amber-500/40 bg-amber-500/10 text-amber-300' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' }}">
                    <x-omni-icon :name="$sitio->inMaintenance() ? 'llave' : 'check'" size="h-3.5 w-3.5" />
                    {{ $sitio->inMaintenance() ? 'En mantenimiento' : 'Sitio abierto' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 {{ $sitio->get('registration_open') ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 bg-slate-900 text-slate-400' }}">
                    <x-omni-icon :name="$sitio->get('registration_open') ? 'puerta' : 'candado'" size="h-3.5 w-3.5" />
                    {{ $sitio->get('registration_open') ? 'Registro abierto' : 'Registro cerrado' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 {{ $sitio->get('community_open') ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 bg-slate-900 text-slate-400' }}">
                    <x-omni-icon name="globo" size="h-3.5 w-3.5" />
                    {{ $sitio->get('community_open') ? 'Comunidad abierta' : 'Comunidad cerrada' }}
                </span>
                @if ($sitio->announcement())
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-500/30 bg-sky-500/10 px-3 py-1 text-sky-300">
                        <x-omni-icon name="megafono" size="h-3.5 w-3.5" /> Anuncio publicado
                    </span>
                @endif
            </div>
        </section>


        {{-- ========================================================= --}}
        {{-- PERSONAS --}}
        {{-- ========================================================= --}}

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                ['Cuentas', $personas['total'], 'usuario', '#fb7185', route('admin.users.index'), 'en total'],
                ['Nuevas', $personas['nuevas'], 'chispa', '#34d399', route('admin.users.index', ['orden' => 'recientes']), 'esta semana'],
                ['Activas', $personas['activas'], 'pulso', '#38bdf8', route('admin.users.index', ['orden' => 'actividad']), 'entraron esta semana'],
                ['Bloqueadas', $personas['bloqueadas'], 'prohibido', '#f97316', route('admin.users.index', ['estado' => 'bloqueadas']), 'ahora mismo'],
                ['Admins', $personas['admins'], 'escudo', '#a78bfa', route('admin.users.index', ['rol' => 'ADMIN']), 'con todo el control'],
                ['Eliminadas', $personas['eliminadas'], 'papelera', '#94a3b8', route('admin.users.index', ['estado' => 'eliminadas']), 'recuperables'],
            ] as [$titulo, $valor, $icono, $color, $destino, $pie])
                <a href="{{ $destino }}" class="group rounded-2xl border bg-slate-900/60 p-4 transition hover:bg-slate-900"
                    style="border-color: {{ $color }}33;">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $titulo }}</span>
                        <span style="color: {{ $color }}"><x-omni-icon :name="$icono" size="h-4 w-4" /></span>
                    </div>
                    <p class="mt-2 text-3xl font-black text-white">{{ number_format($valor) }}</p>
                    <p class="text-[11px] text-slate-500 group-hover:text-slate-400">{{ $pie }}</p>
                </a>
            @endforeach
        </section>


        <div class="grid gap-5 xl:grid-cols-3">

            {{-- ===================================================== --}}
            {{-- ACTIVIDAD, 14 DÍAS --}}
            {{-- ===================================================== --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 xl:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-black text-white">Las dos últimas semanas</h3>
                        <p class="text-xs text-slate-500">Cuentas nuevas y piezas creadas cada día, de todos los tipos.</p>
                    </div>
                    <div class="flex gap-3 text-[11px] font-bold text-slate-400">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-400"></span> Altas</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-sky-400"></span> Contenido</span>
                    </div>
                </div>

                <div class="mt-5 flex h-44 items-end gap-1.5">
                    @foreach ($actividad as $dia)
                        <div class="group relative flex h-full flex-1 flex-col justify-end">
                            <div class="flex h-full items-end justify-center gap-0.5">
                                <span class="w-1/2 rounded-t bg-rose-400/80" style="height: {{ max(2, $dia['altas'] / $maxDia * 100) }}%"></span>
                                <span class="w-1/2 rounded-t bg-sky-400/80" style="height: {{ max(2, $dia['creado'] / $maxDia * 100) }}%"></span>
                            </div>
                            <span class="mt-1.5 text-center text-[9px] font-bold text-slate-600">{{ $dia['dia']->format('d') }}</span>

                            <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-950 px-2 py-1 text-[10px] font-bold text-slate-200 group-hover:block">
                                {{ $dia['dia']->translatedFormat('D j M') }} · {{ $dia['altas'] }} altas · {{ $dia['creado'] }} piezas
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-800 pt-4 sm:grid-cols-4">
                    @foreach ([['VIEW', 'Visitas en la comunidad', 'ojo'], ['CLONE', 'Clonaciones', 'copiar']] as [$clave, $texto, $icono])
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                            <p class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500"><x-omni-icon :name="$icono" size="h-3 w-3" /> {{ $texto }}</p>
                            <p class="text-lg font-black text-white">{{ $vistas[$clave] ?? 0 }} <span class="text-[10px] font-bold text-slate-500">7 días</span></p>
                        </div>
                    @endforeach

                    @foreach ($estadosCompeticion as $clave => [$texto, $color])
                        @continue(! in_array($clave, ['RUNNING', 'COMPLETED']))
                        <a href="{{ route('admin.content.index', 'competition') }}" class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2 hover:border-slate-700">
                            <p class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500"><x-omni-icon name="espadas" size="h-3 w-3" /> Competiciones {{ \Illuminate\Support\Str::lower($texto) }}</p>
                            <p class="text-lg font-black" style="color: {{ $color }}">{{ $competiciones[$clave] ?? 0 }}</p>
                        </a>
                    @endforeach
                </div>
            </section>


            {{-- ===================================================== --}}
            {{-- MARCAS DEL EQUIPO --}}
            {{-- ===================================================== --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h3 class="text-sm font-black text-white">Marcas del equipo</h3>
                <p class="text-xs text-slate-500">Lo que los admins han verificado, destacado u ocultado.</p>

                <div class="mt-4 space-y-2">
                    @foreach (\App\Models\ContentFlag::FLAGS as $clave => $flag)
                        <div class="flex items-center justify-between rounded-xl border bg-slate-950/40 px-3 py-2.5" style="border-color: {{ $flag['tone'] }}33;">
                            <span class="flex items-center gap-2 text-sm font-bold" style="color: {{ $flag['tone'] }}">
                                <x-omni-icon :name="$flag['icon']" size="h-4 w-4" /> {{ $flag['label'] }}
                            </span>
                            <span class="text-lg font-black text-white">{{ $marcas[$clave] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="mt-3 text-[11px] leading-relaxed text-slate-500">
                    Verificado, confiable y destacado se ven como insignia en la comunidad. Lo oculto deja de aparecer en ella sin borrarse.
                </p>
            </section>
        </div>


        {{-- ========================================================= --}}
        {{-- CONTENIDO POR TIPO --}}
        {{-- ========================================================= --}}

        <section>
            <div class="mb-3 flex items-end justify-between">
                <div>
                    <h3 class="text-sm font-black text-white">Todo el contenido</h3>
                    <p class="text-xs text-slate-500">Entra en cualquier tipo para buscar, filtrar, marcar o eliminar.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach ($contenido as $clave => $tipo)
                    <a href="{{ route('admin.content.index', $clave) }}"
                        class="group rounded-2xl border bg-slate-900/60 p-4 transition hover:bg-slate-900"
                        style="border-color: {{ $tipo['tone'] }}33;">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl border" style="color: {{ $tipo['tone'] }}; border-color: {{ $tipo['tone'] }}55; background-color: {{ $tipo['tone'] }}14;">
                                <x-omni-icon :name="$tipo['icon']" size="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-black text-slate-300">{{ $tipo['label'] }}</p>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">{{ $tipo['module'] }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-2xl font-black text-white">{{ number_format($tipo['total']) }}</p>
                        <div class="mt-1 flex flex-wrap gap-1.5 text-[10px] font-bold">
                            @if ($tipo['nuevos'])
                                <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-emerald-300">+{{ $tipo['nuevos'] }} esta semana</span>
                            @endif
                            @if ($tipo['trashed'])
                                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-slate-400">{{ $tipo['trashed'] }} eliminados</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>


        <div class="grid gap-5 lg:grid-cols-3">

            {{-- ===================================================== --}}
            {{-- LO MÁS VISTO --}}
            {{-- ===================================================== --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-white">Lo más visto</h3>
                    <a href="{{ route('admin.popular') }}" class="text-[11px] font-black text-rose-300 hover:text-rose-200">Ver todo</a>
                </div>

                <div class="mt-3 space-y-2">
                    @forelse ($masVisto as $fila)
                        @php $meta = \App\Services\Admin\ContentRegistry::TYPES[$fila['key']]; @endphp
                        <a href="{{ route('admin.content.show', [$fila['key'], $fila['model']->id]) }}" class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/40 p-2 transition hover:border-slate-700">
                            @include('admin.partials.thumb', ['modelo' => $fila['model'], 'meta' => $meta, 'clase' => 'h-10 w-10 rounded-lg', 'icono' => 'h-4 w-4'])
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold text-slate-200">{{ $fila['model']->name }}</span>
                                <span class="block truncate text-[11px] text-slate-500">{{ $meta['one'] }} · {{ $fila['model']->user?->name }}</span>
                            </span>
                            <span class="flex items-center gap-1 text-xs font-black text-slate-300"><x-omni-icon name="ojo" size="h-3.5 w-3.5" /> {{ $fila['model']->views_count }}</span>
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-800 p-4 text-center text-xs text-slate-500">Todavía nadie ha visitado nada en la comunidad.</p>
                    @endforelse
                </div>
            </section>


            {{-- ===================================================== --}}
            {{-- CREADORES --}}
            {{-- ===================================================== --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h3 class="text-sm font-black text-white">Quién más crea</h3>

                <div class="mt-3 space-y-2">
                    @foreach ($creadores as $creador)
                        @php $suma = $creador->entities_count + $creador->collections_count + $creador->tournament_templates_count + $creador->universes_count; @endphp
                        <a href="{{ route('admin.users.show', $creador->id) }}" class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/40 p-2 transition hover:border-slate-700">
                            <x-user-avatar :user="$creador" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-1.5">
                                    <span class="truncate text-sm font-bold text-slate-200">{{ $creador->name }}</span>
                                    <x-creator-badge :user="$creador" size="xs" />
                                </span>
                                <span class="block truncate text-[11px] text-slate-500">
                                    {{ $creador->entities_count }} entidades · {{ $creador->collections_count }} colecciones · {{ $creador->tournament_templates_count }} torneos · {{ $creador->universes_count }} universos
                                </span>
                            </span>
                            <span class="text-sm font-black text-white">{{ $suma }}</span>
                        </a>
                    @endforeach
                </div>
            </section>


            {{-- ===================================================== --}}
            {{-- ÚLTIMOS MOVIMIENTOS --}}
            {{-- ===================================================== --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-white">Lo último de los admins</h3>
                    <a href="{{ route('admin.audit') }}" class="text-[11px] font-black text-rose-300 hover:text-rose-200">Registro completo</a>
                </div>

                <div class="mt-3 space-y-2">
                    @forelse ($acciones as $accion)
                        @include('admin.partials.action-row', ['accion' => $accion])
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-800 p-4 text-center text-xs text-slate-500">Aún no se ha hecho nada desde aquí. Lo que hagas quedará anotado.</p>
                    @endforelse
                </div>

                <h4 class="mt-5 text-[10px] font-black uppercase tracking-wider text-slate-500">Cuentas recién creadas</h4>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($recientes as $nueva)
                        <a href="{{ route('admin.users.show', $nueva->id) }}" title="{{ $nueva->name }} · {{ $nueva->created_at?->diffForHumans() }}"
                            class="flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/40 py-1 pl-1 pr-3 text-xs font-bold text-slate-300 hover:border-slate-700">
                            <x-user-avatar :user="$nueva" size="xs" />
                            {{ \Illuminate\Support\Str::limit($nueva->name, 16) }}
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

</x-admin-layout>
