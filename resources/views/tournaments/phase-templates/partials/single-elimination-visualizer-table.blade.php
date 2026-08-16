<section x-cloak x-show="view === 'table'" x-transition.opacity role="tabpanel" class="mt-6 space-y-6">
    {{-- Tabla de encuentros --}}
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-950 p-5 text-white">
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">
                Vista administrativa
            </p>

            <h2 class="mt-1 text-xl font-black">
                Tabla de encuentros
            </h2>

            <p class="mt-1 text-[11px] text-slate-300">
                Una fila por encuentro. Selecciona una fila para abrir el inspector.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1120px] divide-y divide-slate-200 text-left">
                <thead class="sticky top-0 bg-slate-50">
                    <tr>
                        @foreach (['Ronda', 'Encuentro', 'Fuentes', 'K → Q', 'Perfil / serie', 'Destinos', 'Estado'] as $heading)
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <template x-for="round in visibleRounds()" :key="round.key">
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="encounter in round.visible_encounters" :key="encounter.key">
                            <tr @click="select(encounter.key)" :data-structure-key="encounter.key" tabindex="0"
                                @keydown.enter.prevent="select(encounter.key)"
                                class="cursor-pointer bg-white transition hover:bg-violet-50/60 focus:bg-violet-50 focus:outline-none"
                                :class="[traceClass(encounter), {
                                    'bg-red-50/60': encounter.issue_level === 'ERROR',
                                    'ring-2 ring-inset ring-violet-500': selectedKey === encounter.key,
                                    'opacity-30': isDimmed(encounter)
                                }]">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="text-xs font-black text-slate-800" x-text="round.name"></p>

                                    <p class="mt-1 font-mono text-[9px] font-bold text-slate-400" x-text="round.code">
                                    </p>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="mb-2 flex flex-wrap items-center gap-1.5">
                                        <span
                                            class="rounded-lg bg-slate-950 px-2.5 py-1.5 text-[9px] font-black text-white">
                                            Global

                                            <span class="text-violet-300" x-text="'#' + encounter.global_number"></span>
                                        </span>

                                        <span
                                            class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[9px] font-black text-slate-600">
                                            En ronda

                                            <span x-text="'#' + encounter.local_number"></span>
                                        </span>
                                    </div>

                                    <p class="text-xs font-black text-slate-900" x-text="encounter.name"></p>

                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="font-mono text-[9px] font-bold text-slate-400"
                                            x-text="encounter.code"></span>

                                        <span x-show="encounter.generation_source === 'MANUAL'"
                                            class="rounded-full bg-violet-100 px-2 py-0.5 text-[8px] font-black text-violet-700">
                                            Manual
                                        </span>
                                    </div>
                                </td>

                                <td class="max-w-[260px] px-4 py-3">
                                    <template x-for="source in encounter.source_labels.slice(0, 3)"
                                        :key="source">
                                        <p class="truncate text-[10px] font-bold text-fuchsia-700">
                                            ←

                                            <span x-text="source"></span>
                                        </p>
                                    </template>

                                    <p x-show="encounter.source_labels.length === 0"
                                        class="text-[10px] font-bold text-red-600">
                                        Sin fuentes
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-xl bg-violet-100 px-3 py-2 text-xs font-black text-violet-700"
                                        x-text="encounter.format"></span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="text-[10px] font-black text-slate-700" x-text="encounter.profile"></p>

                                    <p class="mt-1 text-[9px] font-bold text-amber-600" x-text="encounter.series"></p>
                                </td>

                                <td class="max-w-[280px] px-4 py-3">
                                    <template x-for="destination in encounter.destination_labels.slice(0, 3)"
                                        :key="destination">
                                        <p class="truncate text-[10px] font-bold text-emerald-700">
                                            →

                                            <span x-text="destination"></span>
                                        </p>
                                    </template>

                                    <p x-show="encounter.destination_labels.length === 0"
                                        class="text-[10px] font-bold text-red-600">
                                        Sin destinos
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-[9px] font-black"
                                        :class="{
                                            'bg-red-100 text-red-700': encounter.issue_level === 'ERROR',
                                            'bg-amber-100 text-amber-700': encounter.issue_level === 'WARNING',
                                            'bg-cyan-100 text-cyan-700': encounter.issue_level === 'RECOMMENDATION',
                                            'bg-emerald-100 text-emerald-700': encounter.issue_level === 'NONE'
                                        }"
                                        x-text="
                                            encounter.issue_count
                                                ? encounter.issue_count + ' problemas'
                                                : 'Correcto'
                                        "></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </template>
            </table>
        </div>
    </div>

    {{-- Tabla de conexiones --}}
    <div class="overflow-hidden rounded-[32px] border border-indigo-200 bg-white shadow-sm">
        <div class="border-b border-indigo-100 bg-indigo-50 p-5">
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-600">
                Enrutamiento explícito
            </p>

            <h2 class="mt-1 text-xl font-black text-slate-900">
                Tabla de conexiones
            </h2>

            <p class="mt-1 text-[11px] text-slate-500">
                La relación textual siempre está disponible aunque las flechas visuales no se muestren.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[980px] divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach (['Código', 'Origen', 'Destino', 'Asignación', 'Condición', 'Fuente', 'Estado'] as $heading)
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <template x-for="connection in visibleConnections()" :key="connection.key">
                        <tr @click="select(connection.key)" :data-structure-key="connection.key" tabindex="0"
                            @keydown.enter.prevent="select(connection.key)"
                            class="cursor-pointer transition hover:bg-indigo-50/60 focus:bg-indigo-50 focus:outline-none"
                            :class="[traceClass(connection), {
                                'bg-red-50/60': connection.issue_level === 'ERROR',
                                'ring-2 ring-inset ring-indigo-500': selectedKey === connection.key,
                                'opacity-25': isDimmed(connection)
                            }]">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-[9px] font-bold text-slate-400"
                                x-text="connection.code"></td>

                            <td class="max-w-[260px] px-4 py-3 text-xs font-bold text-slate-700">
                                <p class="truncate" x-text="connection.source_label"></p>
                            </td>

                            <td class="max-w-[260px] px-4 py-3 text-xs font-bold text-indigo-700">
                                <p class="truncate" x-text="connection.target_label"></p>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-[10px] font-black text-slate-600"
                                x-text="connection.allocation"></td>

                            <td class="whitespace-nowrap px-4 py-3 text-[10px] font-bold text-slate-500"
                                x-text="connection.condition_type"></td>

                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[9px] font-black"
                                    :class="connection.generation_source === 'MANUAL' ?
                                        'bg-violet-100 text-violet-700' :
                                        'bg-slate-100 text-slate-600'"
                                    x-text="
                                        connection.generation_source === 'MANUAL'
                                            ? 'Manual'
                                            : 'Automática'
                                    "></span>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[9px] font-black"
                                    :class="connection.issue_level === 'ERROR' ?
                                        'bg-red-100 text-red-700' :
                                        'bg-emerald-100 text-emerald-700'"
                                    x-text="
                                        connection.issue_count
                                            ? connection.issue_count + ' problemas'
                                            : 'Correcta'
                                    "></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="visibleConnections().length === 0" class="p-8 text-center text-xs font-bold text-slate-400">
            Ninguna conexión coincide con los filtros actuales.
        </div>
    </div>
</section>
