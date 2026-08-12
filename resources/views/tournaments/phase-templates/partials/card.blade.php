@php
    $statusClass = match ($phaseTemplate->status) {
        'ACTIVE' => 'bg-emerald-100 text-emerald-700',
        'DRAFT' => 'bg-amber-100 text-amber-700',
        'ARCHIVED' => 'bg-slate-200 text-slate-600',
        default => 'bg-slate-100 text-slate-600',
    };

    $typeIcon = match ($phaseTemplate->phase_type) {
        'SINGLE_ELIMINATION' => '⚔',
        'ROUND_ROBIN' => '↻',
        'GROUP_STAGE' => '▦',
        'LEAGUE' => '⇅',
        'SWISS' => '◆',
        'CUSTOM' => '✦',
        default => '⌘',
    };
@endphp

<article
    class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-amber-300 hover:shadow-xl hover:shadow-amber-950/5">

    <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}" class="block">

        <div class="relative h-40 overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950">

            @if ($phaseTemplate->image_url)
                <img src="{{ $phaseTemplate->image_url }}" alt="{{ $phaseTemplate->name }}"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
            @else
                <div class="absolute inset-0 flex items-center justify-center">
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-400/10 text-5xl text-amber-300 ring-1 ring-amber-300/10">
                        {{ $typeIcon }}
                    </div>
                </div>
            @endif

            <span
                class="absolute left-4 top-4 rounded-full bg-slate-950/70 px-3 py-1.5 font-mono text-[9px] font-black tracking-wider text-white/70 backdrop-blur">
                {{ $phaseTemplate->code }}
            </span>

            <div class="absolute bottom-4 left-4 right-4">
                <p class="truncate text-lg font-black text-white">
                    {{ $phaseTemplate->name }}
                </p>
            </div>
        </div>

        <div class="p-5">

            <div class="flex flex-wrap gap-2">
                <span
                    class="{{ $statusClass }} rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider">
                    {{ $phaseTemplate->status_label }}
                </span>

                <span
                    class="rounded-full bg-indigo-100 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-indigo-700">
                    {{ $phaseTemplate->type_label }}
                </span>
            </div>

            <p class="mt-4 line-clamp-2 min-h-[40px] text-sm leading-5 text-slate-500">
                {{ $phaseTemplate->description ?: 'Fase competitiva sin descripción.' }}
            </p>

            <div class="mt-5 grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[9px] font-black uppercase text-slate-400">Entrada</p>

                    <p class="mt-1 text-xs font-black text-slate-700">
                        {{ $phaseTemplate->participant_contract_label }}
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[9px] font-black uppercase text-slate-400">Salidas</p>

                    <p class="mt-1 text-xs font-black text-slate-700">
                        {{ $phaseTemplate->exits_count ?? $phaseTemplate->exits()->count() }}
                    </p>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                <span class="text-xs text-slate-400">
                    {{ $phaseTemplate->participant_mode_label }}
                </span>

                <span class="text-xs font-black text-amber-600 transition group-hover:translate-x-1">
                    Abrir →
                </span>
            </div>

        </div>
    </a>
</article>
