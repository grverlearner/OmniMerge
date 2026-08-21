<section class="rounded-3xl border border-slate-200 bg-white p-5">

    <div class="flex items-center justify-between">

        <div>

            <p class="text-[9px] font-black uppercase tracking-[0.14em] text-sky-600">
                Competidores
            </p>

            <h3 class="mt-1 font-black text-slate-950">
                Recorridos individuales
            </h3>
        </div>

        <span class="rounded-full bg-sky-100 px-3 py-1 text-[9px] font-black text-sky-700">

            <span x-text="participants().length">
            </span>
            participantes
        </span>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">

        <div class="max-h-[420px] space-y-2 overflow-y-auto pr-1">

            <template x-for="participant in participants()" :key="participant.lab_id">

                <button type="button"
                    @click="selectParticipant(
                        participant.lab_id
                    )"
                    class="w-full rounded-xl border p-3 text-left"
                    :class="selectedParticipantId === participant.lab_id ?
                        'border-sky-500 bg-sky-50' :
                        'border-slate-200 bg-slate-50'">

                    <div class="flex items-center gap-2.5">

                        {{-- Entidad real de la Biblioteca, si la hay --}}
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-violet-100 text-violet-500">

                            <template x-if="participant.image_url">
                                <img :src="participant.image_url" :alt="participant.name"
                                    class="h-full w-full object-cover">
                            </template>

                            <template x-if="!participant.image_url">
                                <span class="text-xs">✦</span>
                            </template>
                        </div>


                        <div class="min-w-0 flex-1">

                            <div class="flex items-center justify-between gap-2">

                                <p class="truncate text-xs font-black text-slate-900" x-text="participant.name">
                                </p>

                                <span class="shrink-0 rounded-full px-2 py-1 text-[8px] font-black"
                                    :class="statusClass(participant.status)" x-text="statusLabel(participant.status)">
                                </span>
                            </div>

                            <p class="mt-0.5 truncate text-[9px] text-slate-400"
                                x-show="participant.entity_type_name"
                                x-text="participant.entity_type_name">
                            </p>

                            <p class="mt-1 truncate text-[9px] text-slate-500"
                                x-text="participant.current_location?.name">
                            </p>
                        </div>
                    </div>
                </button>
            </template>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

            <template x-if="selectedParticipant()">

                <div>

                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-violet-100 text-2xl text-violet-500">

                            <template x-if="selectedParticipant().image_url">
                                <img :src="selectedParticipant().image_url"
                                    :alt="selectedParticipant().name" class="h-full w-full object-cover">
                            </template>

                            <template x-if="!selectedParticipant().image_url">
                                <span>✦</span>
                            </template>
                        </div>


                        <div class="min-w-0">

                            <h4 class="truncate font-black text-slate-950" x-text="selectedParticipant().name">
                            </h4>

                            <p class="mt-0.5 truncate text-[10px] text-slate-400"
                                x-show="selectedParticipant().entity_type_name || selectedParticipant().entity_version_name">

                                <span x-text="selectedParticipant().entity_type_name"></span>

                                <span x-show="selectedParticipant().entity_type_name &&
                                    selectedParticipant().entity_version_name">·</span>

                                <span class="text-violet-500"
                                    x-text="selectedParticipant().entity_version_name"></span>
                            </p>
                        </div>
                    </div>


                    {{-- Atributos congelados de la Biblioteca --}}

                    <div class="mt-4 flex flex-wrap gap-1.5"
                        x-show="selectedParticipant().attributes?.length">

                        <template x-for="attribute in (selectedParticipant().attributes ?? [])"
                            :key="attribute.name">

                            <span class="rounded-lg px-2 py-1 text-[9px] font-bold"
                                :class="attribute.featured
                                    ? 'bg-violet-100 text-violet-700'
                                    : 'bg-white text-slate-600'">

                                <span x-text="attribute.name"></span>
                                <span class="font-black" x-text="attribute.display"></span>
                            </span>
                        </template>
                    </div>


                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5">

                        <template
                            x-for="[label, value] in [
                                ['PJ', selectedParticipant().statistics.matches],
                                ['G', selectedParticipant().statistics.wins],
                                ['E', selectedParticipant().statistics.draws],
                                ['P', selectedParticipant().statistics.losses],
                                ['Pts.', selectedParticipant().statistics.points],
                            ]"
                            :key="label">

                            <div class="rounded-xl bg-white p-3 text-center">

                                <p class="text-[8px] font-black uppercase text-slate-400" x-text="label">
                                </p>

                                <p class="mt-1 text-sm font-black text-slate-900" x-text="value">
                                </p>
                            </div>
                        </template>
                    </div>

                    <p class="mt-5 text-[8px] font-black uppercase text-slate-400">
                        Recorrido
                    </p>

                    <div class="mt-3 space-y-2">

                        <template x-for="(location, index) in selectedParticipant().journey"
                            :key="`${location.type}-${location.id}-${index}`">

                            <div class="flex items-start gap-3">

                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[8px] font-black text-sky-700"
                                    x-text="index + 1">
                                </div>

                                <div class="rounded-xl bg-white px-3 py-2">

                                    <p class="text-[8px] font-black uppercase text-slate-400" x-text="location.type">
                                    </p>

                                    <p class="mt-1 text-[10px] font-black text-slate-800" x-text="location.name">
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div x-show="!selectedParticipant()" class="py-12 text-center">

                <p class="font-black text-slate-800">
                    Selecciona un competidor
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Verás sus estadísticas y su recorrido completo.
                </p>
            </div>
        </div>
    </div>
</section>
