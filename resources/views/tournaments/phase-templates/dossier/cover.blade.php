@php
    /*
     * La portada de la fase.
     *
     * Es lo primero que se ve al entrar, así que dice QUÉ es esta fase antes
     * que nada: su cara, su tipo, cuánta gente admite y en qué estado está.
     * Las cifras van a la derecha porque son la respuesta corta —«un cuadro
     * de 16, cuatro rondas, quince duelos»— y a menudo la única que hace
     * falta.
     *
     * El acento sale del tipo de fase y se repite en toda la ficha: el mismo
     * color en la portada, en las pestañas y en los bordes.
     */

    $accent = $typeAccent;
@endphp

<section class="relative mb-5 overflow-hidden rounded-3xl border {{ $accent['border'] }} bg-slate-900/60">

    {{-- Un resplandor del color del tipo, para que cada fase se reconozca de lejos --}}
    <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full blur-3xl {{ $accent['glow'] }}"></div>

    <div class="relative flex flex-col gap-5 p-5 lg:flex-row lg:items-center">

        {{-- ============ LA CARA ============ --}}

        <div class="flex shrink-0 items-center gap-4">

            <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-2xl border {{ $accent['border'] }} bg-slate-950 sm:h-28 sm:w-28">
                @if ($phaseTemplate->image_url)
                    <img src="{{ $phaseTemplate->image_url }}" alt=""
                        class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-4xl {{ $accent['text'] }}">
                        {{ $typeIcon }}
                    </div>
                @endif
            </div>

            <div class="min-w-0 lg:hidden">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] {{ $accent['text'] }}">
                    {{ $phaseTemplate->type_label }}
                </p>
                <h1 class="mt-1 text-xl font-black leading-tight text-slate-100">
                    {{ $phaseTemplate->name }}
                </h1>
            </div>

        </div>


        {{-- ============ IDENTIDAD ============ --}}

        <div class="min-w-0 flex-1">

            <div class="hidden lg:block">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.18em] {{ $accent['soft'] }} {{ $accent['text'] }}">
                        {{ $typeIcon }} {{ $phaseTemplate->type_label }}
                    </span>

                    <span class="font-mono text-[10px] font-bold text-slate-600">
                        {{ $phaseTemplate->code }}
                    </span>
                </div>

                <h1 class="mt-1.5 text-2xl font-black leading-tight text-slate-100">
                    {{ $phaseTemplate->name }}
                </h1>
            </div>

            @if ($phaseTemplate->description)
                <p class="mt-2 max-w-2xl text-xs leading-relaxed text-slate-400">
                    {{ $phaseTemplate->description }}
                </p>
            @endif

            <div class="mt-3 flex flex-wrap items-center gap-1.5">

                <span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider
                    {{ match ($phaseTemplate->status) {
                        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
                        'DRAFT' => 'bg-amber-500/15 text-amber-300',
                        'ARCHIVED' => 'bg-slate-700/50 text-slate-400',
                        default => 'bg-slate-700/50 text-slate-400',
                    } }}">
                    {{ $phaseTemplate->status_label }}
                </span>

                <span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider
                    {{ match ($phaseTemplate->visibility) {
                        'PUBLIC' => 'bg-violet-500/15 text-violet-300',
                        'UNLISTED' => 'bg-cyan-500/15 text-cyan-300',
                        default => 'bg-slate-700/50 text-slate-400',
                    } }}">
                    {{ $phaseTemplate->visibility }}
                </span>

                <span class="rounded-full bg-slate-800/70 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-300">
                    {{ $phaseTemplate->participant_contract_label }}
                </span>

            </div>

        </div>


        {{-- ============ LAS CIFRAS ============ --}}

        {{--
            La respuesta corta. Cada motor trae las suyas —una liga cuenta
            jornadas, un cuadro cuenta rondas y descansos— así que la lista
            la decide el controlador, no esta vista.
        --}}

        <div class="grid shrink-0 grid-cols-2 gap-1.5 sm:grid-cols-4 lg:w-auto lg:grid-cols-2 xl:grid-cols-4">

            @foreach ($figures as $figure)
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 lg:min-w-[86px]">
                    <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">
                        {{ $figure['label'] }}
                    </p>
                    <p class="font-mono text-xl font-black {{ $figure['accent'] ?? 'text-slate-100' }}">
                        {{ $figure['value'] }}
                    </p>
                </div>
            @endforeach

        </div>

    </div>


    {{-- ============ LA BARRA DE ACCIÓN ============ --}}

    <div class="relative flex flex-wrap items-center gap-2 border-t border-slate-800 bg-slate-950/40 px-5 py-2.5">

        <p class="mr-auto text-[10px] leading-relaxed text-slate-500">
            Esta ficha es de <span class="font-bold text-slate-400">solo lectura</span>.
            Lo que ves configurado se decide en la Super Edición.
        </p>

        @can('update', $phaseTemplate)
            <a href="{{ route('tournaments.phase-templates.super.show', $phaseTemplate) }}"
                class="rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                ✎ Super Edición
            </a>
        @endcan

    </div>

</section>
