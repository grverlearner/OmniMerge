@php
    $current = $current ?? 'summary';
    $canUpdatePhase = auth()->user()?->can('update', $phaseTemplate) ?? false;

    $rulesRouteName = match ($phaseTemplate->phase_type) {
        'SINGLE_ELIMINATION' => 'tournaments.single-elimination.show',
        'ROUND_ROBIN' => 'tournaments.round-robin.show',
        'GROUP_STAGE' => 'tournaments.group-stage.show',
        'SWISS' => 'tournaments.swiss.show',
        default => null,
    };

    $workspaceSettings = $settings
        ?? ($phaseTemplate->relationLoaded('singleEliminationSetting')
            ? $phaseTemplate->singleEliminationSetting
            : null);

    $structureStatus = isset($validation)
        ? data_get(
            $validation,
            'structure_status',
            $workspaceSettings?->structure_status
        )
        : $workspaceSettings?->structure_status;

    $validationErrors = isset($validation)
        ? (int) data_get($validation, 'counts.errors', 0)
        : 0;

    $isExecutable = isset($validation)
        ? (bool) data_get(
            $validation,
            'runtime_ready',
            false
        )
        : null;

    [$readinessLabel, $readinessClasses, $readinessDot] = match (true) {
        $phaseTemplate->status === 'ARCHIVED' => [
            'Archivada',
            'border-slate-200 bg-slate-100 text-slate-600',
            'bg-slate-400',
        ],
        $validationErrors > 0 || $structureStatus === 'INVALID' => [
            'Requiere correcciones',
            'border-red-200 bg-red-50 text-red-700',
            'bg-red-500',
        ],
        $structureStatus === 'STALE' => [
            'Estructura desactualizada',
            'border-amber-200 bg-amber-50 text-amber-700',
            'bg-amber-500',
        ],
        $structureStatus === 'BLOCKED' => [
            'Válida, no ejecutable',
            'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700',
            'bg-fuchsia-500',
        ],
        $structureStatus === 'VALID' && $isExecutable === false => [
            'Requiere revalidación',
            'border-amber-200 bg-amber-50 text-amber-700',
            'bg-amber-500',
        ],
        $structureStatus === 'VALID' => [
            'Lista para ejecutar',
            'border-emerald-200 bg-emerald-50 text-emerald-700',
            'bg-emerald-500',
        ],
        $structureStatus === 'GENERATED' => [
            'Pendiente de validación',
            'border-indigo-200 bg-indigo-50 text-indigo-700',
            'bg-indigo-500',
        ],
        $structureStatus === 'NOT_GENERATED' => [
            'Sin estructura',
            'border-slate-200 bg-slate-50 text-slate-600',
            'bg-slate-400',
        ],
        $phaseTemplate->status === 'ACTIVE' => [
            'Definición activa',
            'border-emerald-200 bg-emerald-50 text-emerald-700',
            'bg-emerald-500',
        ],
        default => [
            'En configuración',
            'border-amber-200 bg-amber-50 text-amber-700',
            'bg-amber-500',
        ],
    };

    $tabs = [
        [
            'key' => 'summary',
            'label' => 'Resumen',
            'description' => 'Estado general',
            'icon' => '⌂',
            'url' => route('tournaments.phase-templates.show', $phaseTemplate),
        ],
    ];

    if ($canUpdatePhase) {
        $tabs[] = [
            'key' => 'definition',
            'label' => 'Definición',
            'description' => 'Identidad y contrato',
            'icon' => '✎',
            'url' => route('tournaments.phase-templates.edit', $phaseTemplate),
        ];
    }

    if ($canUpdatePhase && $rulesRouteName !== null && Route::has($rulesRouteName)) {
        $tabs[] = [
            'key' => 'rules',
            'label' => 'Reglas',
            'description' => 'Comportamiento',
            'icon' => '⚙',
            'url' => route($rulesRouteName, $phaseTemplate),
        ];
    }

    /*
     * Pestañas adicionales por motor. Single Elimination necesita
     * "Estructura" (grafo interno editable); Round Robin no, porque su
     * calendario es enteramente determinístico (ver docs/md/18-Fase-2-Round-Robin.md,
     * sección 4). Ambos comparten "Entrada y salida" y "Simulador".
     */
    $engineTabs = match ($phaseTemplate->phase_type) {
        'SINGLE_ELIMINATION' => [
            [
                'key' => 'structure',
                'label' => 'Estructura',
                'description' => 'Etapas y encuentros',
                'icon' => '◇',
                'url' => route('tournaments.single-elimination.structure.show', $phaseTemplate),
            ],
            [
                'key' => 'io',
                'label' => 'Entradas y salidas',
                'description' => 'Puertas y slots',
                'icon' => '⇄',
                'url' => route('tournaments.single-elimination.structure.io', $phaseTemplate),
            ],
            [
                'key' => 'simulator',
                'label' => 'Simulador',
                'description' => 'Probar con participantes ficticios',
                'icon' => '▶',
                'url' => route('tournaments.single-elimination.simulator.show', $phaseTemplate),
            ],
        ],

        'ROUND_ROBIN' => [
            [
                'key' => 'io',
                'label' => 'Entrada y salida',
                'description' => 'Puertas de salida',
                'icon' => '⇄',
                'url' => route('tournaments.round-robin.io', $phaseTemplate),
            ],
            [
                'key' => 'simulator',
                'label' => 'Simulador',
                'description' => 'Probar con participantes ficticios',
                'icon' => '▶',
                'url' => route('tournaments.round-robin.simulator.show', $phaseTemplate),
            ],
        ],

        /*
         * Group Stage no tiene una pestaña "Entrada y salida" separada: sus
         * Phase Exits ya se gestionan junto a las Reglas de clasificación en
         * la pestaña "Reglas" (ver docs/md/19-Fase-3-Group-Stage.md), porque
         * cada regla ya muestra a qué puerta apunta.
         */
        'GROUP_STAGE' => [
            [
                'key' => 'simulator',
                'label' => 'Simulador',
                'description' => 'Probar con participantes ficticios',
                'icon' => '▶',
                'url' => route('tournaments.group-stage.simulator.show', $phaseTemplate),
            ],
        ],

        default => [],
    };

    if ($canUpdatePhase) {
        foreach ($engineTabs as $engineTab) {
            $tabs[] = $engineTab;
        }
    }
@endphp

<section class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">
                <a href="{{ route('tournaments.phase-templates.index') }}"
                    class="transition hover:text-amber-600">
                    Biblioteca de fases
                </a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-600">{{ $phaseTemplate->type_label }}</span>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="truncate text-lg font-black text-slate-950">
                    {{ $phaseTemplate->name }}
                </h1>

                <span class="rounded-full border px-3 py-1 text-[9px] font-black uppercase tracking-wider {{ $readinessClasses }}">
                    <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full {{ $readinessDot }}"></span>
                    {{ $readinessLabel }}
                </span>

                <span class="font-mono text-[10px] font-bold text-slate-400">
                    {{ $phaseTemplate->code }}
                </span>
            </div>
        </div>

        @if ($workspaceSettings && $phaseTemplate->phase_type === 'SINGLE_ELIMINATION')
            <div class="flex shrink-0 flex-wrap items-center gap-2 text-[9px] font-black uppercase tracking-wider text-slate-500">
                <span class="rounded-full bg-slate-100 px-3 py-1.5">
                    {{ $workspaceSettings->configuration_mode_label }}
                </span>
                <span class="rounded-full bg-violet-50 px-3 py-1.5 text-violet-700">
                    {{ $workspaceSettings->structure_mode === 'MANUAL' ? 'Estructura personalizada' : 'Estructura automática' }}
                </span>
            </div>
        @endif
    </div>

    <nav aria-label="Navegación de la fase" class="overflow-x-auto">
        <div class="flex min-w-max px-2 sm:px-3">
            @foreach ($tabs as $tab)
                <a href="{{ $tab['url'] }}"
                    @if ($current === $tab['key']) aria-current="page" @endif
                    @class([
                        'group relative flex min-w-[150px] items-center gap-3 px-4 py-4 transition',
                        'text-slate-900' => $current === $tab['key'],
                        'text-slate-400 hover:text-slate-700' => $current !== $tab['key'],
                    ])>
                    <span @class([
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-black transition',
                        'bg-amber-500 text-white shadow-lg shadow-amber-500/20' => $current === $tab['key'],
                        'bg-slate-100 text-slate-400 group-hover:bg-amber-50 group-hover:text-amber-700' => $current !== $tab['key'],
                    ])>
                        {{ $tab['icon'] }}
                    </span>

                    <span>
                        <span class="block text-xs font-black">{{ $tab['label'] }}</span>
                        <span class="mt-0.5 block text-[9px] font-bold text-slate-400">
                            {{ $tab['description'] }}
                        </span>
                    </span>

                    @if ($current === $tab['key'])
                        <span class="absolute inset-x-4 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
</section>
