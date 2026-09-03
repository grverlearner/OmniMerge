@php
    /*
     * El panel de creador.
     *
     * Publicar una plantilla es enseñársela a alguien, y hasta ahora no había
     * ninguna pantalla que dijera qué está viendo ese alguien. Esta lo dice,
     * lo deja cambiar sin salir de aquí, y sobre todo responde a la pregunta
     * que antes solo se contestaba preguntándole a otro: «¿por qué no
     * encuentran lo mío?».
     *
     * Una pieza aparece en la comunidad si cumple TRES cosas —activa, pública
     * y con fecha de publicación— y sirve de algo si cumple una cuarta:
     * permitir la copia. El bloque «qué impide que te vean» enseña cuántas se
     * quedan fuera por cada motivo.
     */

    $gravedad = [
        'error' => ['punto' => 'bg-rose-400', 'texto' => 'text-rose-300', 'borde' => 'border-rose-500/30', 'fondo' => 'bg-rose-500/5'],
        'warning' => ['punto' => 'bg-amber-400', 'texto' => 'text-amber-300', 'borde' => 'border-amber-500/30', 'fondo' => 'bg-amber-500/5'],
        'info' => ['punto' => 'bg-sky-400', 'texto' => 'text-sky-300', 'borde' => 'border-sky-500/25', 'fondo' => 'bg-sky-500/5'],
    ];

    $bloqueos = collect($blockers);
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">Mi panel de creador</x-slot>

    <div x-data="{
        headline: @js($creator->headline ?? ''),
        bio: @js($creator->bio ?? ''),
        location: @js($creator->location ?? ''),
        website: @js($creator->website ?? ''),
        visibility: @js($creator->profile_visibility ?? 'PRIVATE'),
        dirty: false,
    }" @input="dirty = true" @change="dirty = true" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header
            class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-violet-950/40">

            <span class="pointer-events-none absolute -right-24 -top-28 h-72 w-72 rounded-full bg-violet-500/10 blur-3xl"></span>

            <div class="relative flex flex-wrap items-end gap-5 px-5 py-5">

                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-400">
                        Torneos · Creador
                    </p>

                    <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                        Cómo te ven los demás
                    </h1>

                    <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                        Esto es lo que ve alguien que se encuentra una plantilla tuya en la comunidad
                        y quiere saber de quién es. Aquí se configura, y aquí se ve qué impide que
                        encuentren lo que has montado.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ([['Publicadas', $totals['published'], 'text-violet-300'], ['Se pueden copiar', $totals['clonable'], 'text-emerald-300'], ['Copias', $totals['clones'], 'text-amber-300'], ['Vistas', $totals['views'], 'text-slate-300']] as [$etiqueta, $valor, $color])
                        <span class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                            <span class="font-mono text-lg font-black {{ $color }}">{{ $valor }}</span>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                {{ $etiqueta }}
                            </span>
                        </span>
                    @endforeach
                </div>

            </div>

        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-xs font-black text-emerald-300"
                role="status">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
                <ul class="list-disc space-y-1 pl-5 text-[11px] font-bold text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px] xl:items-start">

            <div class="space-y-4">

                {{-- ===================================================== --}}
                {{-- QUÉ IMPIDE QUE TE VEAN --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg {{ $bloqueos->isEmpty() ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }}">
                            <x-omni-icon name="globo" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">Qué impide que te vean</h2>
                            <p class="text-[10px] text-slate-500">
                                Activa, pública y con fecha: las tres cosas, o no aparece.
                            </p>
                        </div>
                    </header>

                    @if ($bloqueos->isEmpty())

                        <div class="px-5 py-10 text-center">
                            <span class="inline-flex text-emerald-400/60">
                                <x-omni-icon name="medalla" size="h-9 w-9" />
                            </span>

                            <p class="mt-2 text-sm font-black text-white">Nada te está frenando</p>

                            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                                Tu perfil se ve y todo lo que has marcado como público está publicado
                                y se puede copiar.
                            </p>
                        </div>

                    @else

                        <ul class="divide-y divide-slate-800/70">
                            @foreach ($bloqueos as $bloqueo)
                                @php $g = $gravedad[$bloqueo['severity']]; @endphp

                                <li class="flex items-center gap-3 px-5 py-3">

                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $g['punto'] }}"></span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[12px] font-black text-slate-200">
                                            {{ $bloqueo['title'] }}
                                        </span>
                                        <span class="block text-[10px] leading-4 text-slate-500">
                                            {{ $bloqueo['detail'] }}
                                        </span>
                                    </span>

                                    <span
                                        class="shrink-0 rounded-lg border {{ $g['borde'] }} {{ $g['fondo'] }} px-2 py-1 font-mono text-[12px] font-black {{ $g['texto'] }}">
                                        {{ $bloqueo['count'] }}
                                    </span>

                                    @if ($bloqueo['action'])
                                        <a href="{{ $bloqueo['action'] }}"
                                            class="hidden shrink-0 text-[10px] font-black text-slate-600 transition hover:text-violet-300 sm:block">
                                            Arreglarlo →
                                        </a>
                                    @endif

                                </li>
                            @endforeach
                        </ul>

                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- TU FICHA PÚBLICA --}}
                {{-- ===================================================== --}}

                <form method="POST" action="{{ route('tournaments.creator.update') }}">

                    @csrf
                    @method('PUT')

                    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                        <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                                <x-omni-icon name="usuario" size="h-4 w-4" />
                            </span>

                            <div>
                                <h2 class="text-sm font-black text-white">Tu ficha pública</h2>
                                <p class="text-[10px] text-slate-500">
                                    Lo único que ve quien abre tu perfil desde una plantilla.
                                </p>
                            </div>
                        </header>

                        <div class="space-y-4 p-5">

                            {{-- Si te ven o no --}}
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    Tu perfil de creador
                                </p>

                                <input type="hidden" name="profile_visibility" :value="visibility">

                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach ([['PUBLIC', 'Visible', 'Cualquiera puede abrir tu perfil y ver todo lo que has publicado junto.'], ['PRIVATE', 'Oculto', 'Tus plantillas públicas se siguen viendo, pero tu perfil no se puede abrir.']] as [$valor, $titulo, $texto])
                                        <button type="button" @click="visibility = '{{ $valor }}'; dirty = true"
                                            :aria-pressed="visibility === '{{ $valor }}'"
                                            :class="visibility === '{{ $valor }}' ?
                                                'border-violet-500/50 bg-violet-500/10' :
                                                'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                            class="rounded-xl border p-3 text-left transition">
                                            <span class="block text-xs font-black text-white">{{ $titulo }}</span>
                                            <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                                                {{ $texto }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <div class="flex items-baseline justify-between">
                                    <label for="headline" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                        Titular
                                    </label>
                                    <span class="font-mono text-[9px] text-slate-600">
                                        <span x-text="headline.length"></span>/120
                                    </span>
                                </div>

                                <input id="headline" type="text" name="headline" x-model="headline" maxlength="120"
                                    placeholder="Ej. Monto formatos raros de eliminación directa"
                                    class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">

                                <p class="mt-1.5 text-[10px] leading-4 text-slate-600">
                                    Sale bajo tu nombre en cada plantilla tuya. Es lo que más se lee.
                                </p>
                            </div>

                            <div>
                                <div class="flex items-baseline justify-between">
                                    <label for="bio" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                        Biografía
                                    </label>
                                    <span class="font-mono text-[9px] text-slate-600">
                                        <span x-text="bio.length"></span>/1000
                                    </span>
                                </div>

                                <textarea id="bio" name="bio" x-model="bio" rows="4" maxlength="1000"
                                    placeholder="Qué clase de torneos montas, con qué criterio, qué te gusta probar..."
                                    class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs leading-relaxed text-slate-300 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500"></textarea>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="location" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                        Dónde estás
                                    </label>
                                    <input id="location" type="text" name="location" x-model="location" maxlength="120"
                                        placeholder="Opcional"
                                        class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">
                                </div>

                                <div>
                                    <label for="website" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                        Tu sitio
                                    </label>
                                    <input id="website" type="url" name="website" x-model="website" maxlength="255"
                                        placeholder="https://"
                                        class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">
                                </div>
                            </div>

                            <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] leading-4 text-slate-600">
                                El nombre, el usuario, el avatar y el correo se cambian en
                                <a href="{{ route('profile.edit') }}" class="font-black text-slate-400 underline transition hover:text-violet-300">tu perfil</a>:
                                no son «cómo te ven», son quién eres para el sistema.
                            </p>

                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-slate-800 px-5 py-3">
                            <span class="text-[11px] font-bold">
                                <span x-show="dirty" x-cloak class="text-amber-300">● Hay cambios sin guardar</span>
                                <span x-show="!dirty" class="text-slate-600">Sin cambios pendientes</span>
                            </span>

                            <button type="submit"
                                class="rounded-xl bg-violet-500 px-5 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400">
                                Guardar
                            </button>
                        </div>

                    </section>

                </form>


                {{-- ===================================================== --}}
                {{-- LO QUE YA ESTÁ FUERA --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                            <x-omni-icon name="capas" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">Lo que ya está publicado</h2>
                            <p class="text-[10px] text-slate-500">
                                Con lo que ha pasado desde que lo soltaste.
                            </p>
                        </div>

                        <a href="{{ route('tournaments.community.creator', $creator) }}"
                            class="shrink-0 text-[10px] font-black text-slate-500 transition hover:text-violet-300">
                            Verlo como lo ven →
                        </a>
                    </header>

                    @if ($published->isEmpty())

                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-black text-white">Todavía no has publicado nada</p>

                            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                                Para publicar algo, ábrelo, ponlo en <strong class="text-slate-300">Activa</strong>,
                                <strong class="text-slate-300">Pública</strong> y deja marcado que se pueda copiar.
                            </p>

                            <div class="mt-4 flex flex-wrap justify-center gap-2">
                                <a href="{{ route('tournaments.templates.index') }}"
                                    class="rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                                    Mis torneos
                                </a>

                                <a href="{{ route('tournaments.phase-templates.index') }}"
                                    class="rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-cyan-500 hover:text-cyan-300">
                                    Mis fases
                                </a>
                            </div>
                        </div>

                    @else

                        <ul class="divide-y divide-slate-800/70">
                            @foreach ($published as $pieza)
                                @php
                                    $esTorneo = $pieza instanceof \App\Models\TournamentTemplate;

                                    $ruta = $esTorneo
                                        ? route('tournaments.community.tournament', $pieza)
                                        : route('tournaments.community.phase', $pieza);
                                @endphp

                                <li>
                                    <a href="{{ $ruta }}"
                                        class="group flex items-center gap-3 px-5 py-3 transition hover:bg-slate-900">

                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($pieza->image_url)
                                                <img src="{{ $pieza->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <span class="text-sm opacity-50">{{ $pieza->display_icon }}</span>
                                            @endif
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-1.5">
                                                <span class="rounded px-1 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $esTorneo ? 'bg-amber-500/15 text-amber-300' : 'bg-cyan-500/15 text-cyan-300' }}">
                                                    {{ $esTorneo ? 'torneo' : 'fase' }}
                                                </span>

                                                <span class="truncate text-[12px] font-black text-slate-200">
                                                    {{ $pieza->name }}
                                                </span>
                                            </span>

                                            <span class="block truncate text-[10px] text-slate-500">
                                                Publicada {{ $pieza->published_at?->locale('es')->diffForHumans() }}
                                                @if (! $pieza->allow_cloning)
                                                    · <span class="text-amber-300">no se puede copiar</span>
                                                @endif
                                            </span>
                                        </span>

                                        <span class="hidden shrink-0 items-baseline gap-3 sm:flex">
                                            <span class="text-right">
                                                <span class="block font-mono text-[12px] font-black text-slate-300">
                                                    {{ $pieza->views_count }}
                                                </span>
                                                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                                    vistas
                                                </span>
                                            </span>

                                            <span class="text-right">
                                                <span class="block font-mono text-[12px] font-black {{ $pieza->clones_count > 0 ? 'text-emerald-300' : 'text-slate-600' }}">
                                                    {{ $pieza->clones_count }}
                                                </span>
                                                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                                    copias
                                                </span>
                                            </span>
                                        </span>

                                    </a>
                                </li>
                            @endforeach
                        </ul>

                    @endif

                </section>

            </div>


            {{-- ============================================================= --}}
            {{-- LA VISTA PREVIA --}}
            {{-- ============================================================= --}}

            <aside class="space-y-4 xl:sticky xl:top-4">

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Así te verán
                    </p>

                    <div class="mt-3 overflow-hidden rounded-2xl border border-violet-500/30 bg-gradient-to-br from-slate-900 to-violet-950/30 p-4">

                        <div class="flex items-start gap-3">
                            <x-user-avatar :user="$creator" size="lg" ring />

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px] font-black text-white">{{ $creator->name }}</p>
                                <p class="text-[11px] text-slate-500">{{ '@' . $creator->username }}</p>

                                <p class="mt-1.5 text-[11px] font-bold text-violet-300" x-show="headline"
                                    x-text="headline"></p>

                                <p class="mt-1.5 text-[11px] font-bold text-slate-700" x-show="!headline">
                                    Sin titular
                                </p>
                            </div>
                        </div>

                        <p class="mt-3 whitespace-pre-line text-[11px] leading-relaxed text-slate-400" x-show="bio"
                            x-text="bio"></p>

                        <p class="mt-3 text-[11px] leading-relaxed text-slate-700" x-show="!bio">
                            Sin biografía. Quien te encuentre no sabrá qué clase de cosas montas.
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-3 text-[10px] text-slate-500">
                            <span class="flex items-center gap-1.5" x-show="location">
                                <x-omni-icon name="brujula" size="h-3 w-3" />
                                <span x-text="location"></span>
                            </span>

                            <span class="flex items-center gap-1.5" x-show="website">
                                <x-omni-icon name="globo" size="h-3 w-3" />
                                <span x-text="website.replace(/^https?:\/\//, '')"></span>
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5 border-t border-slate-800 pt-3">
                            @foreach ([['Torneos', $tournaments->filter->isPublished()->count()], ['Fases', $phases->filter->isPublished()->count()], ['Copias', $totals['clones']]] as [$etiqueta, $valor])
                                <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1">
                                    <span class="font-mono text-[11px] font-black text-slate-300">{{ $valor }}</span>
                                    <span class="ml-1 text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        {{ $etiqueta }}
                                    </span>
                                </span>
                            @endforeach
                        </div>

                    </div>

                    <p class="mt-2 text-[10px] leading-4 text-slate-600" x-show="visibility === 'PRIVATE'" x-cloak>
                        Con el perfil oculto nadie llega a esta ficha, aunque encuentre tus
                        plantillas.
                    </p>

                </section>


                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Qué hace falta para publicar
                    </p>

                    <ol class="mt-2.5 space-y-2 text-[11px] leading-4 text-slate-400">
                        <li class="flex gap-2">
                            <span class="font-mono text-[10px] font-black text-violet-400">01</span>
                            <span>Estado <strong class="text-slate-200">Activa</strong>: si sigue en
                                borrador, no sale.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-mono text-[10px] font-black text-violet-400">02</span>
                            <span>Visibilidad <strong class="text-slate-200">Pública</strong>: es lo
                                que pone la fecha de publicación.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-mono text-[10px] font-black text-violet-400">03</span>
                            <span><strong class="text-slate-200">Permitir que la copien</strong>, o
                                será un escaparate y no una pieza.</span>
                        </li>
                    </ol>

                    <a href="{{ route('tournaments.community.index') }}"
                        class="mt-3 flex items-center justify-center gap-2 rounded-xl border border-slate-800 px-4 py-2.5 text-[11px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                        <x-omni-icon name="globo" size="h-3.5 w-3.5" />
                        Ver la comunidad
                    </a>

                </section>

            </aside>

        </div>

    </div>

</x-tournament-layout>
