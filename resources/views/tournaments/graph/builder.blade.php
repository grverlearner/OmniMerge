<x-tournament-layout>

    <x-slot name="header">
        Flujo del torneo · {{ $tournamentTemplate->name }}
    </x-slot>

    @include('tournaments.partials.template-navigation')

    {{-- ========================================================= --}}
    {{-- ERRORES DE FORMULARIO --}}
    {{-- ========================================================= --}}

    @if ($errors->any())
        <section class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-5">
            <div class="flex gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 font-black text-red-600">
                    !
                </div>

                <div>
                    <p class="font-black text-red-900">
                        Revisa los datos ingresados
                    </p>

                    <div class="mt-2 space-y-1">
                        @foreach ($errors->all() as $error)
                            <p class="text-xs leading-5 text-red-700">
                                • {{ $error }}
                            </p>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <div x-data="tournamentFlowBuilder(@js($graphPayload))" class="space-y-5">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}

        <section
            class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 p-6 text-white shadow-xl sm:p-8">

            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-amber-400/20 blur-3xl">
            </div>

            <div
                class="pointer-events-none absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-violet-500/10 blur-3xl">
            </div>

            <div class="relative flex flex-col justify-between gap-7 xl:flex-row xl:items-end">

                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-amber-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
                        <span>◇</span>
                        Tournament Flow Builder
                    </div>

                    <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
                        {{ $tournamentTemplate->name }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                        Construye el recorrido completo de la competición.
                        Agrega varios inicios, crea fases, divide salidas,
                        vuelve a unir rutas y define uno o varios destinos finales.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold text-slate-300">
                            {{ $tournamentTemplate->code }}
                        </span>

                        <span
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold text-slate-300">
                            {{ $flowAnalysis['stats']['levels'] }} niveles
                        </span>

                        <span
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold text-slate-300">
                            {{ $flowAnalysis['stats']['branches'] }} bifurcaciones
                        </span>

                        <span
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold text-slate-300">
                            {{ $flowAnalysis['stats']['convergences'] }} convergencias
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="showProblems = !showProblems"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black text-white transition hover:bg-white/15">

                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[9px]"
                            :class="hasErrors() ?
                                'bg-red-500 text-white' :
                                'bg-emerald-500 text-white'"
                            x-text="problemsCount()">
                        </span>

                        Revisar estructura
                    </button>

                    <form method="POST" action="{{ route('tournaments.graph.validate', $tournamentTemplate) }}">
                        @csrf

                        <button type="submit"
                            class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black text-white transition hover:bg-white/15">
                            ✓ Validar
                        </button>
                    </form>

                    <form method="POST" action="{{ route('tournaments.graph.auto-layout', $tournamentTemplate) }}">
                        @csrf

                        <button type="submit"
                            class="rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white transition hover:bg-amber-400">
                            ✦ Reorganizar
                        </button>
                    </form>
                </div>
            </div>
        </section>

        {{-- ===================================================== --}}
        {{-- VALIDACIÓN EXPANDIBLE --}}
        {{-- ===================================================== --}}

        <section x-cloak x-show="showProblems" x-transition class="grid gap-4 xl:grid-cols-3">

            <article class="rounded-3xl border p-5"
                :class="hasErrors() ?
                    'border-red-200 bg-red-50' :
                    'border-emerald-200 bg-emerald-50'">

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.16em]"
                            :class="hasErrors() ?
                                'text-red-600' :
                                'text-emerald-600'">
                            Errores bloqueantes
                        </p>

                        <h2 class="mt-1 font-black"
                            :class="hasErrors() ?
                                'text-red-950' :
                                'text-emerald-950'">
                            <span x-text="payload.validation.errors.length">
                            </span>
                            problemas
                        </h2>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl font-black"
                        :class="hasErrors() ?
                            'bg-red-100 text-red-600' :
                            'bg-emerald-100 text-emerald-600'">
                        <span x-text="hasErrors() ? '!' : '✓'"></span>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <template x-for="problem in payload.validation.errors" :key="problem.code + problem.message">

                        <div
                            class="rounded-xl border border-red-200 bg-white/70 px-4 py-3 text-xs leading-5 text-red-800">
                            <span class="mr-1 font-black" x-text="problem.code">
                            </span>

                            <span x-text="problem.message"></span>
                        </div>
                    </template>

                    <p x-show="payload.validation.errors.length === 0" class="text-xs leading-6 text-emerald-700">
                        No existen errores estructurales bloqueantes.
                    </p>
                </div>
            </article>

            <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5">

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-amber-600">
                            Advertencias
                        </p>

                        <h2 class="mt-1 font-black text-amber-950">
                            <span x-text="payload.validation.warnings.length">
                            </span>
                            observaciones
                        </h2>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 font-black text-amber-700">
                        !
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <template x-for="problem in payload.validation.warnings" :key="problem.code + problem.message">

                        <div
                            class="rounded-xl border border-amber-200 bg-white/70 px-4 py-3 text-xs leading-5 text-amber-900">
                            <span class="mr-1 font-black" x-text="problem.code">
                            </span>

                            <span x-text="problem.message"></span>
                        </div>
                    </template>

                    <p x-show="payload.validation.warnings.length === 0" class="text-xs leading-6 text-amber-800">
                        No existen advertencias adicionales.
                    </p>
                </div>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-sky-50 p-5">

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-sky-600">
                            Análisis
                        </p>

                        <h2 class="mt-1 font-black text-sky-950">
                            <span x-text="payload.validation.information?.length ?? 0">
                            </span>
                            resultados
                        </h2>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 font-black text-sky-700">
                        i
                    </div>
                </div>

                <div class="mt-4 max-h-72 space-y-2 overflow-y-auto">
                    <template
                        x-for="information in (
                payload.validation.information ?? []
            )"
                        :key="information.code + information.message">

                        <div
                            class="rounded-xl border border-sky-200 bg-white/70 px-4 py-3 text-xs leading-5 text-sky-900">

                            <span x-text="information.message"></span>
                        </div>
                    </template>

                    <p x-show="(
                payload.validation.information?.length
                ??
                0
            ) === 0"
                        class="text-xs leading-6 text-sky-800">
                        Todavía no existen cantidades suficientes para analizar.
                    </p>
                </div>
            </article>
        </section>

        {{-- ===================================================== --}}
        {{-- ESTADÍSTICAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-8">
            @php
                $stats = [
                    ['Inicios', $flowAnalysis['stats']['starts'], 'text-emerald-600'],
                    ['Fases', $flowAnalysis['stats']['nodes'], 'text-amber-600'],
                    ['Rutas', $flowAnalysis['stats']['connections'], 'text-violet-600'],
                    ['Finales', $flowAnalysis['stats']['terminals'], 'text-rose-600'],
                    ['Niveles', $flowAnalysis['stats']['levels'], 'text-sky-600'],
                    ['Ramas', $flowAnalysis['stats']['branches'], 'text-indigo-600'],
                    ['Uniones', $flowAnalysis['stats']['convergences'], 'text-fuchsia-600'],
                    [
                        'Estado',
                        $graphValidation['valid'] ? 'Válido' : 'Incompleto',
                        $graphValidation['valid'] ? 'text-emerald-600' : 'text-red-600',
                    ],
                ];
            @endphp

            @foreach ($stats as [$label, $value, $color])
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[9px] font-black uppercase tracking-[0.14em] text-slate-400">
                        {{ $label }}
                    </p>

                    <p class="mt-2 text-lg font-black {{ $color }}">
                        {{ $value }}
                    </p>
                </article>
            @endforeach
        </section>

        {{-- ===================================================== --}}
        {{-- TOOLBAR --}}
        {{-- ===================================================== --}}

        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">

            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="activeView = 'FLOW'"
                        class="rounded-xl px-4 py-2.5 text-xs font-black transition"
                        :class="activeView === 'FLOW'
                            ?
                            'bg-slate-950 text-white' :
                            'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Flujo visual
                    </button>

                    <button type="button" @click="activeView = 'CONNECTIONS'"
                        class="rounded-xl px-4 py-2.5 text-xs font-black transition"
                        :class="activeView === 'CONNECTIONS'
                            ?
                            'bg-slate-950 text-white' :
                            'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Tabla de rutas
                    </button>

                    <button type="button" @click="activeView = 'ROUTES'"
                        class="rounded-xl px-4 py-2.5 text-xs font-black transition"
                        :class="activeView === 'ROUTES'
                            ?
                            'bg-slate-950 text-white' :
                            'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Recorridos
                    </button>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <div class="relative">
                        <input type="search" x-model.debounce.250ms="search" placeholder="Buscar fase o código..."
                            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-4 pr-10 text-xs sm:w-64">

                        <span class="pointer-events-none absolute right-3 top-2.5 text-slate-400">
                            ⌕
                        </span>
                    </div>

                    <button type="button" @click="showPresetForm = true"
                        class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-xs font-black text-sky-700 transition hover:bg-sky-100">
                        ✦ Usar preset
                    </button>

                    <button type="button" @click="showConnectionForm = true; resetConnection()"
                        class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-xs font-black text-violet-700 transition hover:bg-violet-100">
                        ↗ Conectar ruta
                    </button>

                    <button type="button" @click="showCreateMenu = !showCreateMenu"
                        class="rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-white transition hover:bg-amber-600">
                        + Agregar bloque
                    </button>
                </div>
            </div>

            {{-- Menú para crear bloques --}}

            <div x-cloak x-show="showCreateMenu" x-transition @click.outside="showCreateMenu = false"
                class="mt-4 grid gap-4 border-t border-slate-100 pt-4 xl:grid-cols-3">

                {{-- START --}}

                <form method="POST" action="{{ route('tournaments.graph.starts.store', $tournamentTemplate) }}"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    @csrf

                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-emerald-600">
                        Nuevo inicio
                    </p>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-emerald-700">Nombre del inicio</span>
                        <input name="name" required placeholder="Ej. Clasificación UEFA"
                            class="mt-1.5 w-full rounded-xl border-emerald-200 bg-white text-sm">
                    </label>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-emerald-700">Tipo de participantes de origen</span>
                        <select name="source_type" class="mt-1.5 w-full rounded-xl border-emerald-200 bg-white text-sm">
                            <option value="MAIN_POOL">Pool principal</option>
                            <option value="SEEDED_POOL">Participantes sembrados</option>
                            <option value="QUALIFIER_POOL">Clasificados previos</option>
                            <option value="INVITED_POOL">Invitados</option>
                            <option value="CUSTOM">Personalizado</option>
                        </select>
                        <span class="mt-1 block text-[9px] leading-4 text-emerald-800/70">
                            Describe de dónde proviene este conjunto inicial; no cambia la identidad de los participantes.
                        </span>
                    </label>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-emerald-700">Participantes esperados (opcional)</span>
                        <input type="number" name="expected_participants" min="1"
                            placeholder="Ej. 32"
                            class="mt-1.5 w-full rounded-xl border-emerald-200 bg-white text-sm">
                        <span class="mt-1 block text-[9px] leading-4 text-emerald-800/70">
                            Se usa para validar el flujo y las capacidades; este campo no crea participantes.
                        </span>
                    </label>

                    <input type="hidden" name="status" value="ACTIVE">

                    <button type="submit"
                        class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white">
                        Crear inicio
                    </button>
                </form>

                {{-- PHASE NODE --}}

                <form method="POST" action="{{ route('tournaments.graph.nodes.store', $tournamentTemplate) }}"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    @csrf

                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-amber-600">
                        Nueva fase
                    </p>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-amber-700">Plantilla de fase</span>
                        <select name="phase_template_id" required
                            class="mt-1.5 w-full rounded-xl border-amber-200 bg-white text-sm">

                            <option value="">Seleccionar plantilla...</option>

                            @foreach ($availablePhaseTemplates as $phaseTemplate)
                                <option value="{{ $phaseTemplate->id }}">
                                    {{ $phaseTemplate->name }}
                                    · {{ $phaseTemplate->type_label }}
                                </option>
                            @endforeach
                        </select>
                        <span class="mt-1 block text-[9px] leading-4 text-amber-800/70">
                            La plantilla define el motor, entradas, salidas y reglas que ejecutará este nodo.
                        </span>
                    </label>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-amber-700">Nombre dentro del torneo</span>
                        <input name="name" required placeholder="Ej. Playoffs principales"
                            class="mt-1.5 w-full rounded-xl border-amber-200 bg-white text-sm">
                    </label>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-amber-700">Función / descripción</span>
                        <textarea name="description" rows="2" placeholder="Explica para qué sirve esta fase dentro del recorrido..."
                            class="mt-1.5 w-full rounded-xl border-amber-200 bg-white text-sm"></textarea>
                    </label>

                    <input type="hidden" name="status" value="ACTIVE">

                    <button type="submit"
                        class="mt-3 w-full rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-white">
                        Agregar fase
                    </button>
                </form>

                {{-- TERMINAL --}}

                <form method="POST" action="{{ route('tournaments.graph.terminals.store', $tournamentTemplate) }}"
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                    @csrf

                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-rose-600">
                        Nuevo destino final
                    </p>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-rose-700">Nombre del destino</span>
                        <input name="name" required placeholder="Ej. Clasificados al Mundial"
                            class="mt-1.5 w-full rounded-xl border-rose-200 bg-white text-sm">
                    </label>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-rose-700">Tipo de destino</span>
                        <select name="terminal_type" class="mt-1.5 w-full rounded-xl border-rose-200 bg-white text-sm">
                            <option value="CHAMPION">Campeón</option>
                            <option value="QUALIFIED">Clasificados</option>
                            <option value="ELIMINATED">Eliminados</option>
                            <option value="SECONDARY">Ruta secundaria</option>
                            <option value="PLACEMENT">Posición final</option>
                            <option value="CUSTOM">Personalizado</option>
                        </select>
                    </label>

                    <label class="mt-3 block">
                        <span class="text-[8px] font-black uppercase tracking-wider text-rose-700">Participantes esperados (opcional)</span>
                        <input type="number" name="expected_participants" min="1"
                            placeholder="Ej. 1"
                            class="mt-1.5 w-full rounded-xl border-rose-200 bg-white text-sm">
                        <span class="mt-1 block text-[9px] leading-4 text-rose-800/70">
                            Sirve para comprobar que las rutas entreguen una cantidad compatible con este destino.
                        </span>
                    </label>

                    <input type="hidden" name="status" value="ACTIVE">

                    <button type="submit"
                        class="mt-3 w-full rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-black text-white">
                        Crear destino
                    </button>
                </form>
            </div>
        </section>

        {{-- ===================================================== --}}
        {{-- WORKSPACE --}}
        {{-- ===================================================== --}}

        <div class="grid items-start gap-5 2xl:grid-cols-[minmax(0,1fr)_350px]">

            {{-- ================================================= --}}
            {{-- FLUJO VISUAL --}}
            {{-- ================================================= --}}

            <main x-show="activeView === 'FLOW'" class="min-w-0 space-y-5">

                {{-- STARTS --}}

                <section
                    class="overflow-hidden rounded-3xl border border-emerald-200 bg-gradient-to-b from-emerald-50 to-white">

                    <header
                        class="flex flex-col justify-between gap-3 border-b border-emerald-100 p-5 sm:flex-row sm:items-center">

                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-emerald-600">
                                Orígenes
                            </p>

                            <h2 class="mt-1 font-black text-slate-950">
                                Inicios del torneo
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                El torneo puede comenzar desde una o varias fuentes independientes.
                            </p>
                        </div>

                        <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-[10px] font-black text-emerald-700"
                            x-text="`${payload.starts.length} inicios`">
                        </span>
                    </header>

                    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">

                        <template x-for="start in payload.starts" :key="`start-${start.id}`">

                            <article x-show="matchesSearch(start)" @click="selectItem('START', start)"
                                class="group cursor-pointer rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                :class="isSelected('START', start.id) ?
                                    'border-emerald-500 ring-4 ring-emerald-100' :
                                    'border-emerald-200'">

                                <div class="flex items-start justify-between gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700">
                                        IN
                                    </div>

                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-black text-slate-500"
                                        x-text="start.code">
                                    </span>
                                </div>

                                <h3 class="mt-4 font-black text-slate-950" x-text="start.name">
                                </h3>

                                <p class="mt-1 text-[11px] font-bold text-emerald-700"
                                    x-text="start.source_type_label">
                                </p>

                                <div
                                    class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-[10px]">

                                    <span class="font-bold text-slate-500">
                                        <span x-text="start.expected_participants ?? '?'">
                                        </span>
                                        participantes
                                    </span>

                                    <span class="font-black text-violet-600" x-text="`${start.outgoing_count} rutas`">
                                    </span>
                                </div>

                                <button type="button" @click.stop="openConnectionFromStart(start)"
                                    class="mt-3 w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[10px] font-black text-emerald-700 transition hover:bg-emerald-100">
                                    Continuar desde este inicio →
                                </button>
                            </article>
                        </template>

                        <div x-show="payload.starts.length === 0"
                            class="col-span-full rounded-2xl border border-dashed border-emerald-300 bg-white/60 p-8 text-center">
                            <p class="font-black text-slate-800">
                                El torneo todavía no tiene inicios
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                Utiliza “Agregar bloque” para crear la primera fuente de participantes.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- DIRECTION --}}

                <div class="flex justify-center">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-black text-violet-500 shadow-sm">
                        ↓
                    </div>
                </div>

                {{-- LEVELS --}}

                <template x-for="level in payload.analysis.levels" :key="`level-${level.level}`">

                    <div class="space-y-5">
                        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">

                            <header
                                class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">

                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-[0.16em] text-amber-600"
                                        x-text="`Nivel ${level.level}`">
                                    </p>

                                    <h2 class="mt-1 font-black text-slate-950" x-text="level.label">
                                    </h2>
                                </div>

                                <span
                                    class="rounded-full bg-amber-100 px-3 py-1.5 text-[10px] font-black text-amber-700"
                                    x-text="`${levelNodes(level).length} fases`">
                                </span>
                            </header>

                            <div class="grid gap-5 p-5 xl:grid-cols-2 3xl:grid-cols-3">

                                <template x-for="node in levelNodes(level)" :key="`node-${node.id}`">

                                    <article @click="selectItem('NODE', node)"
                                        class="cursor-pointer overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                        :class="isSelected('NODE', node.id) ?
                                            'border-amber-500 ring-4 ring-amber-100' :
                                            'border-slate-200'">

                                        {{-- NODE HEADER --}}

                                        <div
                                            class="border-b border-slate-100 bg-gradient-to-r from-slate-950 to-slate-800 p-4 text-white">

                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0">
                                                    <p class="text-[9px] font-black uppercase tracking-[0.14em] text-amber-300"
                                                        x-text="node.phase_type_label">
                                                    </p>

                                                    <h3 class="mt-1 truncate font-black" x-text="node.name">
                                                    </h3>
                                                </div>

                                                <span
                                                    class="shrink-0 rounded-full border border-white/10 bg-white/10 px-2 py-1 text-[9px] font-black text-slate-300"
                                                    x-text="node.code">
                                                </span>
                                            </div>

                                            <p class="mt-2 truncate text-[10px] text-slate-400"
                                                x-text="node.phase_template_name">
                                            </p>

                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <span
                                                    class="rounded-full bg-white/10 px-2.5 py-1 text-[9px] font-bold text-slate-300"
                                                    x-text="node.participant_contract">
                                                </span>
                                                <span
                                                    class="rounded-full bg-emerald-500/20 px-2.5 py-1 text-[9px] font-black text-emerald-200">
                                                    Flujo:
                                                    <span x-text="node.flow_forecast_label"></span>
                                                </span>

                                                <span x-show="branchCount(node.id) > 1"
                                                    class="rounded-full bg-violet-500/20 px-2.5 py-1 text-[9px] font-black text-violet-200">
                                                    <span x-text="branchCount(node.id)">
                                                    </span>
                                                    ramas
                                                </span>

                                                <span x-show="convergenceCount(node.id) > 1"
                                                    class="rounded-full bg-fuchsia-500/20 px-2.5 py-1 text-[9px] font-black text-fuchsia-200">
                                                    <span x-text="convergenceCount(node.id)">
                                                    </span>
                                                    rutas unidas
                                                </span>
                                            </div>
                                        </div>

                                        {{-- ENTRIES --}}

                                        <div class="p-4">
                                            <div class="flex items-center justify-between">
                                                <p
                                                    class="text-[9px] font-black uppercase tracking-[0.14em] text-emerald-600">
                                                    Puertas de entrada
                                                </p>

                                                <span class="text-[9px] font-bold text-slate-400"
                                                    x-text="`${nodeIncomingConnections(node).length} conexiones`">
                                                </span>
                                            </div>

                                            <div class="mt-2 space-y-2">
                                                <template x-for="entry in node.entries" :key="`entry-${entry.id}`">

                                                    <div class="rounded-xl border px-3 py-2.5"
                                                        :class="entry.incoming_count > 0 ?
                                                            'border-emerald-200 bg-emerald-50' :
                                                            entry.is_required ?
                                                            'border-red-200 bg-red-50' :
                                                            'border-slate-200 bg-slate-50'">

                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-[10px] font-black text-slate-800"
                                                                x-text="entry.name">
                                                            </span>

                                                            <span class="text-[9px] font-bold"
                                                                :class="entry.incoming_count > 0 ?
                                                                    'text-emerald-700' :
                                                                    entry.is_required ?
                                                                    'text-red-600' :
                                                                    'text-slate-400'"
                                                                x-text="entry.incoming_count > 0
                                                                    ? `${entry.incoming_count} rutas`
                                                                    : entry.is_required
                                                                        ? 'Sin conectar'
                                                                        : 'Opcional'">
                                                            </span>
                                                        </div>

                                                        <p class="mt-1 text-[9px] text-slate-500">
                                                            Contrato:
                                                            <span class="font-bold" x-text="entry.contract">
                                                            </span>

                                                            · Flujo:
                                                            <span class="font-black text-emerald-700"
                                                                x-text="entry.flow_forecast_label">
                                                            </span>

                                                            ·
                                                            <span x-text="entry.merge_policy_label">
                                                            </span>
                                                        </p>
                                                    </div>
                                                </template>

                                                <p x-show="node.entries.length === 0"
                                                    class="rounded-xl border border-dashed border-red-200 bg-red-50 px-3 py-3 text-[10px] font-bold text-red-600">
                                                    Esta fase no tiene puertas de entrada.
                                                </p>
                                            </div>
                                        </div>

                                        {{-- EXITS --}}

                                        <div class="border-t border-slate-100 bg-violet-50/60 p-4">

                                            <div class="flex items-center justify-between">
                                                <p
                                                    class="text-[9px] font-black uppercase tracking-[0.14em] text-violet-600">
                                                    Puertas de salida
                                                </p>

                                                <span class="text-[9px] font-bold text-violet-500"
                                                    x-text="`${nodeOutgoingConnections(node).length} rutas`">
                                                </span>
                                            </div>

                                            <div class="mt-2 space-y-2">
                                                <template x-for="exit in node.exits"
                                                    :key="`exit-${node.id}-${exit.id}`">

                                                    <div class="rounded-xl border border-violet-200 bg-white p-3">

                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <p class="text-[10px] font-black text-slate-900"
                                                                    x-text="exit.name">
                                                                </p>

                                                                <p class="mt-1 truncate text-[9px] text-slate-500"
                                                                    x-text="exit.selector">
                                                                </p>
                                                                <p class="mt-1 text-[9px] font-black text-violet-600">
                                                                    Flujo:
                                                                    <span x-text="exit.flow_forecast_label"></span>
                                                                </p>
                                                            </div>

                                                            <span
                                                                class="shrink-0 rounded-full px-2 py-1 text-[8px] font-black"
                                                                :class="outgoingForExit(node.id, exit.id).length > 0 ?
                                                                    'bg-violet-100 text-violet-700' :
                                                                    'bg-amber-100 text-amber-700'"
                                                                x-text="outgoingForExit(node.id, exit.id).length > 0
                                                                    ? `${outgoingForExit(node.id, exit.id).length} destinos`
                                                                    : 'Pendiente'">
                                                            </span>
                                                        </div>

                                                        <template x-for="route in outgoingForExit(node.id, exit.id)"
                                                            :key="`route-${route.id}`">

                                                            <button type="button"
                                                                @click.stop="selectItem('CONNECTION', route)"
                                                                class="mt-2 flex w-full items-center gap-2 rounded-lg bg-violet-50 px-2.5 py-2 text-left text-[9px] font-bold text-violet-700 transition hover:bg-violet-100">
                                                                <span>↳</span>
                                                                <span class="truncate" x-text="route.target_label">
                                                                </span>
                                                                <span class="ml-auto shrink-0 text-violet-400"
                                                                    x-text="route.allocation_label">
                                                                </span>
                                                            </button>
                                                        </template>

                                                        <button type="button"
                                                            @click.stop="openConnectionFromExit(node, exit)"
                                                            class="mt-2 w-full rounded-lg border border-dashed border-violet-300 px-2.5 py-2 text-[9px] font-black text-violet-600 transition hover:bg-violet-50">
                                                            + Continuar esta salida
                                                        </button>
                                                    </div>
                                                </template>

                                                <p x-show="node.exits.length === 0"
                                                    class="rounded-xl border border-dashed border-amber-200 bg-amber-50 px-3 py-3 text-[10px] font-bold text-amber-700">
                                                    La plantilla no tiene salidas activas.
                                                </p>
                                            </div>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </section>

                        <div class="flex justify-center">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-black text-violet-500 shadow-sm">
                                ↓
                            </div>
                        </div>
                    </div>
                </template>

                {{-- UNREACHABLE --}}

                <section x-show="unreachableNodes().length > 0"
                    class="overflow-hidden rounded-3xl border border-red-200 bg-red-50">

                    <header class="border-b border-red-200 p-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-red-600">
                            Fuera del recorrido
                        </p>

                        <h2 class="mt-1 font-black text-red-950">
                            Fases no alcanzables
                        </h2>

                        <p class="mt-1 text-xs text-red-700">
                            Estas fases no pueden alcanzarse desde ningún inicio.
                        </p>
                    </header>

                    <div class="grid gap-3 p-5 md:grid-cols-2 xl:grid-cols-3">

                        <template x-for="node in unreachableNodes()" :key="`unreachable-${node.id}`">

                            <button type="button" @click="selectItem('NODE', node)"
                                class="rounded-2xl border border-red-200 bg-white p-4 text-left transition hover:border-red-400">

                                <span class="text-[9px] font-black text-red-500" x-text="node.code">
                                </span>

                                <p class="mt-1 font-black text-slate-900" x-text="node.name">
                                </p>

                                <p class="mt-1 text-[10px] text-slate-500" x-text="node.phase_type_label">
                                </p>
                            </button>
                        </template>
                    </div>
                </section>

                {{-- TERMINALS --}}

                <section
                    class="overflow-hidden rounded-3xl border border-rose-200 bg-gradient-to-b from-rose-50 to-white">

                    <header
                        class="flex flex-col justify-between gap-3 border-b border-rose-100 p-5 sm:flex-row sm:items-center">

                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-rose-600">
                                Resultados
                            </p>

                            <h2 class="mt-1 font-black text-slate-950">
                                Destinos finales
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Pueden existir campeones, clasificados, posiciones y eliminados.
                            </p>
                        </div>

                        <span class="rounded-full bg-rose-100 px-3 py-1.5 text-[10px] font-black text-rose-700"
                            x-text="`${payload.terminals.length} destinos`">
                        </span>
                    </header>

                    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">

                        <template x-for="terminal in payload.terminals" :key="`terminal-${terminal.id}`">

                            <article @click="selectItem('TERMINAL', terminal)"
                                class="cursor-pointer rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                :class="isSelected('TERMINAL', terminal.id) ?
                                    'border-rose-500 ring-4 ring-rose-100' :
                                    terminal.incoming_count > 0 ?
                                    'border-rose-200' :
                                    'border-red-300'">

                                <div class="flex items-start justify-between gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 font-black text-rose-700">
                                        END
                                    </div>

                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-black text-slate-500"
                                        x-text="terminal.code">
                                    </span>
                                </div>

                                <h3 class="mt-4 font-black text-slate-950" x-text="terminal.name">
                                </h3>

                                <p class="mt-1 text-[11px] font-bold text-rose-700"
                                    x-text="terminal.terminal_type_label">
                                </p>
                                <p class="mt-2 text-[10px] font-black text-violet-600">
                                    Recibirá:
                                    <span x-text="terminal.flow_forecast_label"></span>
                                </p>

                                <div
                                    class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-[10px]">

                                    <span class="font-bold text-slate-500">
                                        <span x-text="terminal.expected_participants ?? '?'">
                                        </span>
                                        participantes
                                    </span>

                                    <span class="font-black"
                                        :class="terminal.incoming_count > 0 ?
                                            'text-emerald-600' :
                                            'text-red-600'"
                                        x-text="terminal.incoming_count > 0
                                            ? `${terminal.incoming_count} rutas`
                                            : 'Sin conectar'">
                                    </span>
                                </div>
                            </article>
                        </template>

                        <div x-show="payload.terminals.length === 0"
                            class="col-span-full rounded-2xl border border-dashed border-rose-300 bg-white/60 p-8 text-center">
                            <p class="font-black text-slate-800">
                                El torneo todavía no tiene resultados finales
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                Agrega al menos un destino para completar una ruta.
                            </p>
                        </div>
                    </div>
                </section>
            </main>

            {{-- ================================================= --}}
            {{-- TABLA DE CONEXIONES --}}
            {{-- ================================================= --}}

            <main x-cloak x-show="activeView === 'CONNECTIONS'" class="min-w-0">

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                    <header
                        class="flex flex-col justify-between gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center">

                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
                                Matriz de circulación
                            </p>

                            <h2 class="mt-1 font-black text-slate-950">
                                Todas las conexiones
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Revisa con precisión el origen, destino y distribución de cada ruta.
                            </p>
                        </div>

                        <button type="button" @click="showConnectionForm = true; resetConnection()"
                            class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white">
                            + Nueva conexión
                        </button>
                    </header>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-slate-50">
                                <tr class="text-[9px] font-black uppercase tracking-[0.12em] text-slate-400">
                                    <th class="px-5 py-3">Código</th>
                                    <th class="px-5 py-3">Origen</th>
                                    <th class="px-5 py-3">Asignación</th>
                                    <th class="px-5 py-3">Destino</th>
                                    <th class="px-5 py-3">Prioridad</th>
                                    <th class="px-5 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <template x-for="route in payload.connections" :key="`table-route-${route.id}`">

                                    <tr @click="selectItem('CONNECTION', route)"
                                        class="cursor-pointer text-xs transition hover:bg-violet-50/50">

                                        <td class="whitespace-nowrap px-5 py-4 font-black text-slate-500"
                                            x-text="route.code">
                                        </td>

                                        <td class="min-w-56 px-5 py-4">
                                            <p class="font-black text-slate-900" x-text="route.source_label">
                                            </p>

                                            <p class="mt-1 text-[9px] font-bold text-emerald-600"
                                                x-text="route.source_type">
                                            </p>
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4">
                                            <span
                                                class="rounded-full bg-violet-100 px-2.5 py-1 text-[9px] font-black text-violet-700"
                                                x-text="route.allocation_label">
                                            </span>
                                        </td>

                                        <td class="min-w-56 px-5 py-4">
                                            <p class="font-black text-slate-900" x-text="route.target_label">
                                            </p>

                                            <p class="mt-1 text-[9px] font-bold text-rose-600"
                                                x-text="route.target_type">
                                            </p>
                                        </td>

                                        <td class="px-5 py-4 font-black text-slate-600" x-text="route.priority">
                                        </td>

                                        <td class="px-5 py-4 text-right">
                                            <form method="POST" :action="route.delete_url"
                                                @submit="deleteWithConfirmation(
                                                    $event,
                                                    '¿Eliminar esta conexión? Los bloques permanecerán, pero la ruta será desconectada.'
                                                )">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="rounded-lg bg-red-50 px-3 py-2 text-[9px] font-black text-red-600 transition hover:bg-red-100">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="payload.connections.length === 0">
                                    <td colspan="6" class="px-5 py-12 text-center text-xs text-slate-500">
                                        Todavía no existen conexiones entre los bloques.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            {{-- ================================================= --}}
            {{-- RECORRIDOS --}}
            {{-- ================================================= --}}

            <main x-cloak x-show="activeView === 'ROUTES'" class="min-w-0 space-y-4">

                <template x-for="route in payload.analysis.start_routes" :key="`start-route-${route.start_id}`">

                    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 font-black text-emerald-700">
                                IN
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-black uppercase tracking-[0.14em] text-emerald-600">
                                    Recorrido desde
                                </p>

                                <h3 class="mt-1 font-black text-slate-950"
                                    x-text="startById(route.start_id)?.name ?? 'Inicio'">
                                </h3>

                                <div class="mt-4">
                                    <p class="text-[9px] font-black uppercase text-slate-400">
                                        Fases alcanzables
                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <template x-for="name in route.reachable_nodes" :key="name">

                                            <span
                                                class="rounded-full bg-amber-100 px-3 py-1.5 text-[9px] font-black text-amber-700"
                                                x-text="name">
                                            </span>
                                        </template>

                                        <span x-show="route.reachable_nodes.length === 0"
                                            class="text-xs text-red-600">
                                            No alcanza ninguna fase.
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <p class="text-[9px] font-black uppercase text-slate-400">
                                        Destinos posibles
                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <template x-for="name in route.reachable_terminals" :key="name">

                                            <span
                                                class="rounded-full bg-rose-100 px-3 py-1.5 text-[9px] font-black text-rose-700"
                                                x-text="name">
                                            </span>
                                        </template>

                                        <span x-show="route.reachable_terminals.length === 0"
                                            class="text-xs text-red-600">
                                            No alcanza ningún destino final.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>

                <div x-show="payload.analysis.start_routes.length === 0"
                    class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

                    <p class="font-black text-slate-900">
                        No existen recorridos para analizar
                    </p>

                    <p class="mt-2 text-xs text-slate-500">
                        Crea al menos un inicio y conéctalo con una fase.
                    </p>
                </div>
            </main>

            {{-- ================================================= --}}
            {{-- INSPECTOR --}}
            {{-- ================================================= --}}

            <aside class="sticky top-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <header class="border-b border-slate-200 bg-slate-950 p-5 text-white">

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-amber-300">
                                Inspector
                            </p>

                            <h2 class="mt-1 font-black" x-text="selectedTitle()">
                            </h2>

                            <p class="mt-1 text-[10px] text-slate-400" x-text="selectedSubtitle()">
                            </p>
                        </div>

                        <button type="button" @click="clearSelection()"
                            aria-label="Cerrar inspector" title="Cerrar inspector"
                            class="rounded-lg bg-white/10 px-2.5 py-1.5 text-xs text-slate-300">
                            ×
                        </button>
                    </div>
                </header>

                <div x-show="!selected" class="p-8 text-center">

                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                        ◇
                    </div>

                    <p class="mt-4 font-black text-slate-800">
                        Selecciona un bloque
                    </p>

                    <p class="mt-2 text-xs leading-6 text-slate-500">
                        Pulsa un inicio, una fase, un destino o una conexión para revisar sus detalles.
                    </p>
                </div>

                {{-- INSPECT START --}}

                <div x-cloak x-show="selected && selectedKind === 'START'" class="space-y-5 p-5">

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-emerald-50 p-3">
                            <p class="text-[8px] font-black uppercase text-emerald-600">
                                Participantes
                            </p>

                            <p class="mt-1 font-black text-emerald-900"
                                x-text="selected?.expected_participants ?? 'Flexible'">
                            </p>
                        </div>

                        <div class="rounded-xl bg-violet-50 p-3">
                            <p class="text-[8px] font-black uppercase text-violet-600">
                                Rutas
                            </p>

                            <p class="mt-1 font-black text-violet-900" x-text="selected?.outgoing_count ?? 0">
                            </p>
                        </div>
                    </div>

                    <button type="button" @click="openConnectionFromStart(selected)"
                        class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-xs font-black text-white">
                        Crear ruta desde este inicio
                    </button>

                    <form method="POST" :action="selected?.delete_url"
                        @submit="deleteWithConfirmation(
                            $event,
                            '¿Eliminar este inicio? Sus conexiones también serán eliminadas.'
                        )">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-black text-red-600">
                            Eliminar inicio
                        </button>
                    </form>
                </div>

                {{-- INSPECT NODE --}}

                <div x-cloak x-show="selected && selectedKind === 'NODE'" class="space-y-5 p-5">

                    <div class="rounded-2xl bg-amber-50 p-4">
                        <p class="text-[8px] font-black uppercase text-amber-600">
                            Plantilla utilizada
                        </p>

                        <p class="mt-1 text-xs font-black text-slate-900" x-text="selected?.phase_template_name">
                        </p>

                        <p class="mt-2 text-[10px] leading-5 text-slate-500" x-text="selected?.participant_contract">
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-emerald-50 p-3">
                            <p class="text-[8px] font-black uppercase text-emerald-600">
                                Entradas
                            </p>

                            <p class="mt-1 font-black text-emerald-900" x-text="selected?.entries?.length ?? 0">
                            </p>
                        </div>

                        <div class="rounded-xl bg-violet-50 p-3">
                            <p class="text-[8px] font-black uppercase text-violet-600">
                                Salidas
                            </p>

                            <p class="mt-1 font-black text-violet-900" x-text="selected?.exits?.length ?? 0">
                            </p>
                        </div>
                    </div>

                    <form method="POST" :action="selected?.duplicate_url">
                        @csrf

                        <button type="submit"
                            class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-black text-amber-700">
                            Duplicar fase
                        </button>
                    </form>

                    <form method="POST" :action="selected?.delete_url"
                        @submit="deleteWithConfirmation(
                            $event,
                            '¿Eliminar esta fase? Sus puertas y conexiones asociadas también serán eliminadas.'
                        )">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-black text-red-600">
                            Eliminar fase
                        </button>
                    </form>
                </div>

                {{-- INSPECT TERMINAL --}}

                <div x-cloak x-show="selected && selectedKind === 'TERMINAL'" class="space-y-5 p-5">

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-rose-50 p-3">
                            <p class="text-[8px] font-black uppercase text-rose-600">
                                Esperados
                            </p>

                            <p class="mt-1 font-black text-rose-900"
                                x-text="selected?.expected_participants ?? 'Flexible'">
                            </p>
                        </div>

                        <div class="rounded-xl bg-violet-50 p-3">
                            <p class="text-[8px] font-black uppercase text-violet-600">
                                Entradas
                            </p>

                            <p class="mt-1 font-black text-violet-900" x-text="selected?.incoming_count ?? 0">
                            </p>
                        </div>
                    </div>

                    <form method="POST" :action="selected?.delete_url"
                        @submit="deleteWithConfirmation(
                            $event,
                            '¿Eliminar este destino final? También se eliminarán sus conexiones.'
                        )">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-black text-red-600">
                            Eliminar destino
                        </button>
                    </form>
                </div>

                {{-- INSPECT CONNECTION --}}

                <div x-cloak x-show="selected && selectedKind === 'CONNECTION'" class="space-y-5 p-5">

                    <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4">
                        <p class="text-[8px] font-black uppercase text-violet-600">
                            Recorrido
                        </p>

                        <p class="mt-2 text-xs font-black leading-5 text-slate-900" x-text="selected?.source_label">
                        </p>

                        <div class="my-2 text-center font-black text-violet-500">
                            ↓
                        </div>

                        <p class="text-xs font-black leading-5 text-slate-900" x-text="selected?.target_label">
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[8px] font-black uppercase text-slate-500">
                                Asignación
                            </p>

                            <p class="mt-1 text-xs font-black text-slate-900" x-text="selected?.allocation_label">
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[8px] font-black uppercase text-slate-500">
                                Prioridad
                            </p>

                            <p class="mt-1 text-xs font-black text-slate-900" x-text="selected?.priority">
                            </p>
                        </div>
                    </div>

                    <form method="POST" :action="selected?.delete_url"
                        @submit="deleteWithConfirmation(
                            $event,
                            '¿Eliminar esta conexión? Los bloques permanecerán separados.'
                        )">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-black text-red-600">
                            Desconectar ruta
                        </button>
                    </form>
                </div>
            </aside>
        </div>

        {{-- ===================================================== --}}
        {{-- MODAL DE PRESETS --}}
        {{-- ===================================================== --}}

        <div x-cloak x-show="showPresetForm" x-transition.opacity @keydown.escape.window="showPresetForm = false"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">

            <div x-show="showPresetForm" x-transition @click.outside="showPresetForm = false"
                class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">

                <header
                    class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-br from-slate-950 to-sky-950 p-6 text-white">

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-sky-300">
                            Generador de estructura
                        </p>

                        <h2 class="mt-1 text-xl font-black">
                            Comenzar desde un preset
                        </h2>

                        <p class="mt-2 max-w-xl text-xs leading-6 text-slate-400">
                            OmniMerge creará automáticamente inicios, fases,
                            conexiones y destinos. Los presets solamente pueden
                            aplicarse cuando el grafo está vacío.
                        </p>
                    </div>

                    <button type="button" @click="showPresetForm = false"
                        aria-label="Cerrar generador de presets" title="Cerrar"
                        class="rounded-xl bg-white/10 px-3 py-2 text-sm text-slate-300">
                        ×
                    </button>
                </header>

                <form method="POST" action="{{ route('tournaments.graph.presets.store', $tournamentTemplate) }}"
                    class="space-y-6 p-6">

                    @csrf

                    <section>
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-sky-600">
                            Tipo de estructura
                        </label>

                        <select name="preset" x-model="presetType"
                            class="mt-2 w-full rounded-xl border-slate-200 text-sm">

                            <option value="LINEAR">
                                Flujo lineal personalizado
                            </option>

                            <option value="GROUPS_KNOCKOUT">
                                Fase de grupos → Eliminación
                            </option>

                            <option value="SWISS_PLAYOFFS">
                                Sistema suizo → Playoffs
                            </option>

                            <option value="MULTI_QUALIFIER">
                                Varios clasificatorios → Torneo principal
                            </option>
                        </select>
                    </section>

                    {{-- LINEAR --}}

                    <section x-show="presetType === 'LINEAR'"
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5">

                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">
                            Fases del recorrido
                        </p>

                        <p class="mt-2 text-xs leading-6 text-slate-500">
                            Mantén presionada la tecla Ctrl para seleccionar varias
                            Plantillas. Se utilizarán en el orden mostrado en la lista.
                        </p>

                        <select name="phase_template_ids[]" :disabled="presetType !== 'LINEAR'" multiple
                            size="7" class="mt-3 w-full rounded-xl border-slate-200 bg-white text-sm">

                            @foreach ($availablePhaseTemplates as $phaseTemplate)
                                <option value="{{ $phaseTemplate->id }}">
                                    {{ $phaseTemplate->name }}
                                    · {{ $phaseTemplate->type_label }}
                                </option>
                            @endforeach
                        </select>
                    </section>

                    {{-- TWO STAGES --}}

                    <section
                        x-show="[
                    'GROUPS_KNOCKOUT',
                    'SWISS_PLAYOFFS',
                    'MULTI_QUALIFIER'
                ].includes(presetType)"
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-[9px] font-black uppercase text-amber-700"
                                    x-text="presetType === 'MULTI_QUALIFIER'
                                ? 'Plantilla de clasificatorio'
                                : 'Primera etapa'">
                                </label>

                                <select name="primary_phase_template_id"
                                    :disabled="![
                                        'GROUPS_KNOCKOUT',
                                        'SWISS_PLAYOFFS',
                                        'MULTI_QUALIFIER'
                                    ].includes(presetType)"
                                    class="mt-2 w-full rounded-xl border-amber-200 bg-white text-sm">

                                    <option value="">
                                        Seleccionar...
                                    </option>

                                    @foreach ($availablePhaseTemplates as $phaseTemplate)
                                        <option value="{{ $phaseTemplate->id }}">
                                            {{ $phaseTemplate->name }}
                                            · {{ $phaseTemplate->type_label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-[9px] font-black uppercase text-amber-700"
                                    x-text="presetType === 'MULTI_QUALIFIER'
                                ? 'Plantilla del torneo principal'
                                : 'Segunda etapa'">
                                </label>

                                <select name="secondary_phase_template_id"
                                    :disabled="![
                                        'GROUPS_KNOCKOUT',
                                        'SWISS_PLAYOFFS',
                                        'MULTI_QUALIFIER'
                                    ].includes(presetType)"
                                    class="mt-2 w-full rounded-xl border-amber-200 bg-white text-sm">

                                    <option value="">
                                        Seleccionar...
                                    </option>

                                    @foreach ($availablePhaseTemplates as $phaseTemplate)
                                        <option value="{{ $phaseTemplate->id }}">
                                            {{ $phaseTemplate->name }}
                                            · {{ $phaseTemplate->type_label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    {{-- REGIONS --}}

                    <section x-show="presetType === 'MULTI_QUALIFIER'"
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">

                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-emerald-700">
                            Clasificatorios
                        </label>

                        <p class="mt-2 text-xs leading-6 text-emerald-700">
                            Escribe una región o federación por línea.
                        </p>

                        <textarea name="region_names" :disabled="presetType !== 'MULTI_QUALIFIER'" rows="7"
                            placeholder="UEFA&#10;CONMEBOL&#10;CONCACAF&#10;CAF&#10;AFC&#10;OFC"
                            class="mt-3 w-full rounded-xl border-emerald-200 bg-white text-sm"></textarea>

                        <label class="mt-4 block text-[9px] font-black uppercase text-emerald-700">
                            Participantes por región
                        </label>

                        <input type="number" name="participants_per_region"
                            :disabled="presetType !== 'MULTI_QUALIFIER'" min="1" placeholder="Ej. 16"
                            class="mt-2 w-full rounded-xl border-emerald-200 bg-white text-sm">
                    </section>

                    {{-- GENERAL --}}

                    <section class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-500">
                                Nombre del inicio
                            </label>

                            <input name="start_name" value="Participantes"
                                class="mt-2 w-full rounded-xl border-slate-200 text-sm">
                        </div>

                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-500">
                                Participantes esperados
                            </label>

                            <input type="number" name="expected_participants" min="1" placeholder="Opcional"
                                class="mt-2 w-full rounded-xl border-slate-200 text-sm">
                        </div>

                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-500">
                                Nombre del resultado
                            </label>

                            <input name="terminal_name" value="Campeón"
                                class="mt-2 w-full rounded-xl border-slate-200 text-sm">
                        </div>

                        <div>
                            <label class="text-[9px] font-black uppercase text-slate-500">
                                Tipo de resultado
                            </label>

                            <select name="terminal_type" class="mt-2 w-full rounded-xl border-slate-200 text-sm">

                                <option value="CHAMPION">Campeón</option>
                                <option value="QUALIFIED">Clasificados</option>
                                <option value="ELIMINATED">Eliminados</option>
                                <option value="SECONDARY">Ruta secundaria</option>
                                <option value="PLACEMENT">Posición final</option>
                                <option value="CUSTOM">Personalizado</option>
                            </select>
                        </div>
                    </section>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">

                        <p class="text-xs font-black text-amber-900">
                            Importante
                        </p>

                        <p class="mt-1 text-[10px] leading-5 text-amber-700">
                            El preset seleccionará automáticamente la salida principal
                            de cada Fase. Las demás salidas permanecerán disponibles
                            para que agregues eliminados, repechajes, terceros,
                            consolación u otras ramas.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">

                        <button type="button" @click="showPresetForm = false"
                            class="rounded-xl border border-slate-200 px-5 py-3 text-xs font-black text-slate-600">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="rounded-xl bg-sky-600 px-5 py-3 text-xs font-black text-white transition hover:bg-sky-700">
                            Generar estructura
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- MODAL DE CONEXIÓN --}}
        {{-- ===================================================== --}}

        <div x-cloak x-show="showConnectionForm" x-transition.opacity
            @keydown.escape.window="showConnectionForm = false"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">

            <div x-show="showConnectionForm" x-transition @click.outside="showConnectionForm = false"
                class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">

                <header
                    class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-950 p-6 text-white">

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-violet-300">
                            Nueva ruta
                        </p>

                        <h2 class="mt-1 text-xl font-black">
                            Conectar dos bloques
                        </h2>

                        <p class="mt-2 text-xs leading-6 text-slate-400">
                            Selecciona exactamente de dónde salen los participantes y a qué puerta o destino llegarán.
                        </p>
                    </div>

                    <button type="button" @click="showConnectionForm = false"
                        aria-label="Cerrar formulario de conexión" title="Cerrar"
                        class="rounded-xl bg-white/10 px-3 py-2 text-sm text-slate-300">
                        ×
                    </button>
                </header>

                <form method="POST" action="{{ route('tournaments.graph.connections.store', $tournamentTemplate) }}"
                    class="space-y-6 p-6">

                    @csrf

                    {{-- SOURCE --}}

                    <section>
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-emerald-600">
                            1. Origen
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <label class="cursor-pointer rounded-xl border p-3 text-xs font-black"
                                :class="connection.source_type === 'START' ?
                                    'border-emerald-500 bg-emerald-50 text-emerald-700' :
                                    'border-slate-200 text-slate-500'">

                                <input type="radio" name="source_type" value="START"
                                    x-model="connection.source_type" class="mr-2 border-slate-300 text-emerald-600">

                                Inicio
                            </label>

                            <label class="cursor-pointer rounded-xl border p-3 text-xs font-black"
                                :class="connection.source_type === 'PHASE_EXIT' ?
                                    'border-violet-500 bg-violet-50 text-violet-700' :
                                    'border-slate-200 text-slate-500'">

                                <input type="radio" name="source_type" value="PHASE_EXIT"
                                    x-model="connection.source_type" class="mr-2 border-slate-300 text-violet-600">

                                Salida de fase
                            </label>
                        </div>

                        <label x-show="connection.source_type === 'START'" class="mt-3 block">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Inicio de origen</span>
                            <select name="source_start_id" x-model="connection.source_start_id"
                                class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">

                                <option value="">Seleccionar inicio...</option>

                                <template x-for="start in payload.starts" :key="`source-start-${start.id}`">
                                    <option :value="start.id" x-text="`${start.name} · ${start.code}`">
                                    </option>
                                </template>
                            </select>
                        </label>

                        <div x-show="connection.source_type === 'PHASE_EXIT'" class="mt-3 grid gap-3 sm:grid-cols-2">

                            <label>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Fase de origen</span>
                                <select name="source_node_id" x-model="connection.source_node_id"
                                    class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">

                                    <option value="">Seleccionar fase...</option>

                                    <template x-for="node in payload.nodes" :key="`source-node-${node.id}`">
                                        <option :value="node.id" x-text="`${node.name} · ${node.code}`">
                                        </option>
                                    </template>
                                </select>
                            </label>

                            <label>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Puerta de salida de origen</span>
                                <select name="source_phase_exit_id" x-model="connection.source_phase_exit_id"
                                    class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">

                                    <option value="">Seleccionar salida...</option>

                                    <template
                                        x-for="exit in (
                                            nodeById(
                                                connection.source_node_id
                                            )?.exits ?? []
                                        )"
                                        :key="`source-exit-${exit.id}`">

                                        <option :value="exit.id" x-text="`${exit.name} · ${exit.code}`">
                                        </option>
                                    </template>
                                </select>
                            </label>
                        </div>
                    </section>

                    {{-- TARGET --}}

                    <section>
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-rose-600">
                            2. Destino
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <label class="cursor-pointer rounded-xl border p-3 text-xs font-black"
                                :class="connection.target_type === 'ENTRY_PORT' ?
                                    'border-amber-500 bg-amber-50 text-amber-700' :
                                    'border-slate-200 text-slate-500'">

                                <input type="radio" name="target_type" value="ENTRY_PORT"
                                    x-model="connection.target_type" @change="changeTargetType()"
                                    class="mr-2 border-slate-300 text-amber-600">

                                Entrada de fase
                            </label>

                            <label class="cursor-pointer rounded-xl border p-3 text-xs font-black"
                                :class="connection.target_type === 'TERMINAL' ?
                                    'border-rose-500 bg-rose-50 text-rose-700' :
                                    'border-slate-200 text-slate-500'">

                                <input type="radio" name="target_type" value="TERMINAL"
                                    x-model="connection.target_type" @change="changeTargetType()"
                                    class="mr-2 border-slate-300 text-rose-600">

                                Destino final
                            </label>
                        </div>

                        <label x-show="connection.target_type === 'ENTRY_PORT'" class="mt-3 block">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Puerta de entrada de destino</span>
                            <select name="target_entry_port_id" x-model="connection.target_entry_port_id"
                                class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">

                                <option value="">Seleccionar puerta de entrada...</option>

                                <template x-for="entry in availableEntryPorts()" :key="`target-entry-${entry.id}`">
                                    <option :value="entry.id" x-text="`${entry.label} · ${entry.contract}`">
                                    </option>
                                </template>
                            </select>
                        </label>

                        <label x-show="connection.target_type === 'TERMINAL'" class="mt-3 block">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Destino final</span>
                            <select name="target_terminal_id" x-model="connection.target_terminal_id"
                                class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">

                                <option value="">Seleccionar destino final...</option>

                                <template x-for="terminal in payload.terminals" :key="`target-terminal-${terminal.id}`">
                                    <option :value="terminal.id"
                                        x-text="`${terminal.name} · ${terminal.terminal_type_label}`">
                                    </option>
                                </template>
                            </select>
                        </label>
                    </section>

                    {{-- ALLOCATION --}}

                    <section>
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-violet-600">
                            3. Distribución
                        </p>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Modo de distribución</span>
                                <select name="allocation_mode" x-model="connection.allocation_mode"
                                    @change="changeAllocationMode()" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">

                                    <option value="ALL">Enviar todo</option>
                                    <option value="TAKE_N">Tomar una cantidad</option>
                                    <option value="PERCENTAGE">Enviar porcentaje</option>
                                    <option value="REMAINDER">Enviar el restante</option>
                                </select>
                                <span class="mt-1 block text-[9px] leading-4 text-slate-500">
                                    Define qué parte de los participantes disponibles viajará por esta conexión.
                                </span>
                            </label>

                            <label x-show="allocationNeedsValue()">
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500"
                                    x-text="connection.allocation_mode === 'PERCENTAGE' ? 'Porcentaje a enviar' : 'Cantidad a enviar'"></span>
                                <input type="number" name="allocation_value"
                                    x-model="connection.allocation_value" min="0.01" step="0.01"
                                    :max="connection.allocation_mode === 'PERCENTAGE' ?
                                        100 :
                                        null"
                                    placeholder="Ej. 4"
                                    class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">
                                <span class="mt-1 block text-[9px] leading-4 text-slate-500">
                                    Solo se utiliza cuando el modo necesita una cantidad o un porcentaje explícito.
                                </span>
                            </label>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Etiqueta de la conexión (opcional)</span>
                                <input name="label" x-model="connection.label" placeholder="Ej. Mejores terceros"
                                    class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">
                                <span class="mt-1 block text-[9px] leading-4 text-slate-500">
                                    Nombre visual para reconocer esta ruta en el grafo y en la tabla.
                                </span>
                            </label>

                            <label>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Prioridad de la conexión</span>
                                <input type="number" name="priority" x-model="connection.priority" min="1"
                                    placeholder="Ej. 10" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">
                                <span class="mt-1 block text-[9px] leading-4 text-slate-500">
                                    Se usa para establecer un orden explícito cuando varias rutas deben evaluarse de forma coordinada.
                                </span>
                            </label>
                        </div>
                    </section>

                    <input type="hidden" name="status" value="ACTIVE">

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">

                        <button type="button" @click="showConnectionForm = false"
                            class="rounded-xl border border-slate-200 px-5 py-3 text-xs font-black text-slate-600">
                            Cancelar
                        </button>

                        <button type="submit" :disabled="!connectionCanSubmit()"
                            class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white transition disabled:cursor-not-allowed disabled:opacity-40">
                            Crear conexión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-tournament-layout>
