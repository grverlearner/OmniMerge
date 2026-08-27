@php
    $current = $current ?? 'summary';
    $canUpdatePhase = auth()->user()?->can('update', $phaseTemplate) ?? false;

    /*
     * Super Edicion.
     *
     * Donde existe, absorbe las pestanas cuyo contenido ya vive dentro:
     * Reglas (configuracion del motor) y Estructura (representacion). Las
     * que NO absorbe siguen: Entradas y salidas todavia es donde se crean
     * las puertas, y el Simulador es ejecucion, no edicion.
     */
    $superEditor = app(\App\Services\Tournaments\PhaseEditor\PhaseSuperEditorRegistry::class);

    $hasSuperEdition = $canUpdatePhase && $superEditor->supports($phaseTemplate);

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

    if ($hasSuperEdition) {
        $tabs[] = [
            'key' => 'super',
            'label' => 'Super Edición',
            'description' => 'Editor completo de la fase',
            'icon' => '◈',
            'url' => route('tournaments.phase-templates.super.show', $phaseTemplate),
            'highlight' => true,
        ];
    }

    if (! $hasSuperEdition && $canUpdatePhase && $rulesRouteName !== null && Route::has($rulesRouteName)) {
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
            /*
             * Estructura y Entradas y salidas siguen aqui, a diferencia de
             * los otros dos motores.
             *
             * No es un descuido: Eliminacion Directa es el unico que
             * PERSISTE su estructura interna -rondas, encuentros, slots y
             * rutas de resultado- y esa pantalla edita el grafo interno, que
             * la Super Edicion todavia no cubre. Retirarla dejaria sin
             * acceso a cosas que solo existen ahi.
             */
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
        ],

        'ROUND_ROBIN' => [
            /*
             * Round Robin ya no tiene pestana de Estructura: su calendario se
             * ve dentro de la Super Edicion, junto a la configuracion que lo
             * produce. La ruta antigua sigue viva y funcionando.
             */
            [
                'key' => 'simulator',
                'label' => 'Simulador',
                'description' => 'Probar con participantes ficticios',
                'icon' => '▶',
                'url' => route('tournaments.round-robin.simulator.show', $phaseTemplate),
            ],
        ],

        /*
         * Group Stage llegó a tener solo "Simulador": su estructura no se
         * veía en ninguna parte y sus salidas vivían escondidas dentro de
         * "Reglas". Ahora comparte las tres pestañas con los demás motores
         * (ver docs/md/31-Fase-14-Group-Stage.md).
         */
        'GROUP_STAGE' => [
            /*
             * Fase de grupos ya no tiene Estructura ni Entradas y salidas:
             * las dos viven dentro de la Super Edicion, al lado de la
             * configuracion que las produce. Sus rutas siguen vivas y
             * redirigen alli.
             */
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

@php
    /*
     * La misma navegacion sobre fondo claro u oscuro.
     *
     * Se hace con una bandera y no con una segunda copia porque lo que esta
     * pantalla decide -que pestanas existen para cada tipo de fase- es lo
     * unico que de verdad importa aqui, y tenerlo en dos archivos garantiza
     * que un dia diverjan.
     */
    $dark = $dark ?? false;
@endphp

<section class="mb-5 overflow-hidden rounded-3xl border shadow-sm {{ $dark ? 'border-slate-800 bg-slate-900/50' : 'border-slate-200 bg-white' }}">
    <div class="flex flex-col gap-4 border-b px-5 py-4 lg:flex-row lg:items-center lg:justify-between {{ $dark ? 'border-slate-800' : 'border-slate-100' }}">
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
                <h1 class="truncate text-lg font-black {{ $dark ? 'text-slate-100' : 'text-slate-950' }}">
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
                <span class="rounded-full px-3 py-1.5 {{ $dark ? 'bg-slate-800' : 'bg-slate-100' }}">
                    {{ $workspaceSettings->configuration_mode_label }}
                </span>
                <span class="rounded-full px-3 py-1.5 {{ $dark ? 'bg-violet-500/15 text-violet-300' : 'bg-violet-50 text-violet-700' }}">
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
                        ($dark ? 'text-slate-100' : 'text-slate-900') => $current === $tab['key'],
                        ($dark ? 'text-slate-500 hover:text-slate-200' : 'text-slate-400 hover:text-slate-700') => $current !== $tab['key'],
                    ])>
                    <span @class([
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-black transition',
                        'bg-amber-500 text-white shadow-lg shadow-amber-500/20' => $current === $tab['key'],
                        'bg-gradient-to-br from-slate-900 to-slate-700 text-amber-400 shadow-lg shadow-slate-900/20'
                            => $current !== $tab['key'] && ($tab['highlight'] ?? false),
                        ($dark
                            ? 'bg-slate-800 text-slate-500 group-hover:bg-amber-500/20 group-hover:text-amber-300'
                            : 'bg-slate-100 text-slate-400 group-hover:bg-amber-50 group-hover:text-amber-700')
                            => $current !== $tab['key'] && ! ($tab['highlight'] ?? false),
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
