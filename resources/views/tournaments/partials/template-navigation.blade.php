<nav class="mb-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2">

    <div class="flex min-w-max gap-1">

        <a href="{{ route('tournaments.templates.show', $tournamentTemplate) }}"
            class="{{ request()->routeIs('tournaments.templates.show')
                ? 'bg-amber-500 text-white'
                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}
                rounded-xl px-4 py-2.5 text-xs font-black transition">
            Resumen
        </a>

        <a href="{{ route('tournaments.graph.show', $tournamentTemplate) }}"
            class="{{ request()->routeIs(
                'tournaments.graph.show',
                'tournaments.graph.nodes.*',
                'tournaments.graph.starts.*',
                'tournaments.graph.terminals.*',
                'tournaments.graph.connections.*',
                'tournaments.graph.entry-ports.*',
                'tournaments.graph.presets.*',
            )
                ? 'bg-amber-500 text-white'
                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}
                rounded-xl px-4 py-2.5 text-xs font-black transition">

            ◇ Camino

        </a>
        <a href="{{ route('tournaments.graph.preview.show', $tournamentTemplate) }}"
            class="{{ request()->routeIs('tournaments.graph.preview.*')
                ? 'bg-violet-600 text-white'
                : 'text-slate-500 hover:bg-violet-50 hover:text-violet-700' }}
                rounded-xl px-4 py-2.5 text-xs font-black transition">

            ▶ Preview

        </a>

        <a href="{{ route('tournaments.phase-templates.index') }}"
            class="rounded-xl px-4 py-2.5 text-xs font-black text-slate-500 transition hover:bg-slate-100 hover:text-amber-700">
            ⌘ Biblioteca de Fases
        </a>

        <span class="cursor-not-allowed rounded-xl px-4 py-2.5 text-xs font-black text-slate-300">
            Reglas · Próximo
        </span>

        <span class="cursor-not-allowed rounded-xl px-4 py-2.5 text-xs font-black text-slate-300">
            Premios · Próximo
        </span>

        <a href="{{ route('tournaments.lab.index', [
            'template' => $tournamentTemplate->id,
        ]) }}"
            class="rounded-xl px-4 py-2.5 text-xs font-black text-slate-500 transition hover:bg-slate-100 hover:text-amber-700">
            ⚗ Probar
        </a>

    </div>

</nav>
