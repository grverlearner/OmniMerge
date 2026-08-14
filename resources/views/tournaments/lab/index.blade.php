<x-tournament-layout>

    <x-slot name="header">
        Competition Lab
    </x-slot>

    <section
        class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-slate-900 to-violet-950 p-7 text-white sm:p-9">

        <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-violet-400/15 blur-3xl">
        </div>

        <div class="relative max-w-3xl">
            <div
                class="inline-flex items-center gap-2 rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-wider text-violet-300">
                ⚗ Entorno competitivo temporal
            </div>

            <h1 class="mt-5 text-3xl font-black sm:text-4xl">
                Competition Lab
            </h1>

            <p class="mt-4 text-sm leading-7 text-slate-300">
                Prepara participantes y ejecuta una competición temporal.
                Nada de lo probado aquí generará historia, estadísticas
                ni resultados oficiales.
            </p>
        </div>
    </section>

    <section class="mt-6">
        <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">

            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
                    Plantillas disponibles
                </p>

                <h2 class="mt-1 text-xl font-black text-slate-950">
                    Selecciona qué torneo deseas probar
                </h2>
            </div>

            <span class="rounded-full bg-violet-100 px-3 py-1.5 text-[10px] font-black text-violet-700">
                {{ $templates->count() }} plantillas
            </span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($templates as $template)
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

                    <div class="flex items-start justify-between gap-4">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-100 font-black text-violet-700">
                            ⚗
                        </div>

                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-black text-slate-500">
                            {{ $template->code }}
                        </span>
                    </div>

                    <h3 class="mt-4 font-black text-slate-950">
                        {{ $template->name }}
                    </h3>

                    <p class="mt-2 line-clamp-2 text-xs leading-6 text-slate-500">
                        {{ $template->description ?: 'Sin descripción.' }}
                    </p>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        @foreach ([['Inicios', $template->graph_starts_count], ['Fases', $template->graph_nodes_count], ['Finales', $template->graph_terminals_count]] as [$label, $value])
                            <div class="rounded-xl bg-slate-50 p-3 text-center">
                                <p class="text-[8px] font-black uppercase text-slate-400">
                                    {{ $label }}
                                </p>

                                <p class="mt-1 font-black text-slate-900">
                                    {{ $value }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('tournaments.lab.show', $template) }}"
                        class="mt-5 flex w-full items-center justify-center rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white transition hover:bg-violet-700">
                        Abrir en Competition Lab
                    </a>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

                    <p class="font-black text-slate-900">
                        Todavía no tienes plantillas
                    </p>

                    <a href="{{ route('tournaments.templates.create') }}"
                        class="mt-4 inline-flex rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white">
                        Crear plantilla
                    </a>
                </div>
            @endforelse
        </div>
    </section>

</x-tournament-layout>
