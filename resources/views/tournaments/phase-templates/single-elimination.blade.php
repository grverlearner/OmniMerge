<x-tournament-layout>
    <x-slot name="header">
        Eliminación Simple · {{ $phaseTemplate->name }}
    </x-slot>

    <div x-data="singleEliminationWorkspace({
        initialView: 'summary',
        previewUrl: @js(route('tournaments.single-elimination.preview', $phaseTemplate)),
        previewParticipants: @js((int) $previewParticipants),
        hasErrors: @js($errors->any()),
        completionMode: @js(old('completion_mode', $settings->completion_mode)),
        targetSurvivors: @js((int) old('target_survivors', $settings->target_survivors)),
        seedingMode: @js(old('seeding_mode', $settings->seeding_mode)),
        pairingMode: @js(old('pairing_mode', $settings->pairing_mode)),
        byeAssignment: @js(old('bye_assignment', $settings->bye_assignment)),
        defaultBestOf: @js((int) old('default_best_of', $settings->default_best_of)),
        seriesFormat: @js(old('series_format', $settings->series_format)),
        fixedGames: @js((int) old('fixed_games', $settings->fixed_games)),
        reseedEachRound: @js((bool) old('reseed_each_round', $settings->reseed_each_round))
    })" x-init="init()" class="pb-28">
        {{-- VOLVER --}}

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}"
                class="inline-flex items-center gap-2 text-sm font-black text-slate-400 transition hover:text-amber-600">
                ← Volver a la Fase
            </a>

            <span
                class="rounded-full border border-slate-200 bg-white px-3 py-1.5 font-mono text-[10px] font-bold text-slate-400">
                {{ $phaseTemplate->code }}
            </span>
        </div>

        {{-- HERO --}}

        <section
            class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-amber-950 to-orange-950 p-6 text-white shadow-xl sm:p-8">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-amber-400/15 blur-3xl">
            </div>

            <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-amber-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
                        ⚔ Motor de Eliminación Simple
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">
                        {{ $phaseTemplate->name }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                        Configura la entrada, distribución, rondas,
                        series y salida de esta fase.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:min-w-[420px]">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur">
                        <p class="text-[8px] font-black uppercase tracking-wider text-amber-300">
                            Objetivo
                        </p>

                        <p class="mt-1 text-sm font-black" x-text="completionLabel()"></p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur">
                        <p class="text-[8px] font-black uppercase tracking-wider text-indigo-300">
                            Entrada
                        </p>

                        <p class="mt-1 text-sm font-black">
                            @if ($phaseTemplate->exact_participants)
                                {{ $phaseTemplate->exact_participants }} exactos
                            @else
                                {{ $phaseTemplate->min_participants }}
                                @if ($phaseTemplate->max_participants)
                                    –{{ $phaseTemplate->max_participants }}
                                @else
                                    +
                                @endif
                            @endif
                        </p>
                    </div>

                    <div
                        class="col-span-2 rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur sm:col-span-1">
                        <p class="text-[8px] font-black uppercase tracking-wider text-violet-300">
                            Series
                        </p>

                        <p class="mt-1 text-sm font-black">
                            <span x-text="seriesLabel()"></span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ACCESOS RÁPIDOS --}}

        <section class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ([['completion', 'Finalización', 'Objetivo', 'amber'], ['distribution', 'Distribución', 'Seeding', 'indigo'], ['byes', 'BYEs', $phaseTemplate->allow_byes ? 'Permitidos' : 'Desactivados', 'cyan'], ['series', 'Series', $settings->series_label, 'violet'], ['reseed', 'Reseed', $settings->reseed_each_round ? 'Activado' : 'Desactivado', 'emerald']] as [$section, $label, $detail, $color])
                <button type="button"
                    class="rounded-2xl border border-slate-200 bg-white p-3 text-left transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md"
                    @click="openSection('{{ $section }}')">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                        {{ $label }}
                    </p>

                    <p class="mt-1 text-xs font-black text-slate-800">
                        {{ $detail }}
                    </p>
                </button>
            @endforeach
        </section>

        {{-- CONFIGURACIÓN Y PREVIEW --}}

        <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_480px]">
            <div>
                <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
                            Configuración
                        </p>

                        <h2 class="mt-1 text-2xl font-black text-slate-900">
                            Reglas del bracket
                        </h2>
                    </div>

                    <p class="max-w-sm text-xs leading-5 text-slate-400">
                        Abre solamente las secciones que necesites.
                        La barra inferior permite guardar en cualquier momento.
                    </p>
                </div>

                @include('tournaments.phase-templates.partials.single-elimination-settings-form')
            </div>

            <aside>
                <div class="xl:sticky xl:top-24">
                    <div class="mb-3 min-h-5">
                        <p x-show="previewLoading" class="text-[11px] font-black text-amber-600"
                            x-text="previewMessage"></p>

                        <p x-show="! previewLoading && previewError"
                            class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-[11px] font-bold text-red-700"
                            x-text="previewError"></p>

                        <p x-show="! previewLoading && ! previewError && previewMessage"
                            class="text-[11px] font-black text-emerald-600" x-text="previewMessage"></p>
                    </div>

                    <div x-ref="previewContainer">
                        @include('tournaments.phase-templates.partials.single-elimination-preview')
                    </div>
                </div>
            </aside>
        </section>

        <div x-ref="diagnosticContainer">
            @include('tournaments.phase-templates.partials.single-elimination-diagnostic')
        </div>

        {{-- OVERRIDES --}}

        <section class="mt-8 overflow-hidden rounded-3xl border border-violet-200 bg-white">
            <div class="border-b border-violet-100 bg-violet-50/60 p-5">
                <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                            Reglas por ronda
                        </p>

                        <h2 class="mt-1 text-xl font-black text-slate-900">
                            Overrides
                        </h2>

                        <p class="mt-1 text-[11px] leading-5 text-slate-500">
                            Permiten usar un Best of diferente en una ronda específica.
                        </p>
                    </div>

                    <span class="w-fit rounded-full bg-violet-100 px-3 py-2 text-[10px] font-black text-violet-700">
                        {{ $roundRules->count() }}
                        {{ $roundRules->count() === 1 ? 'regla' : 'reglas' }}
                    </span>
                </div>
            </div>

            <div class="grid gap-5 p-5 xl:grid-cols-[minmax(0,1fr)_420px]">
                <div class="space-y-3">
                    @forelse ($roundRules as $roundRule)
                        @php
                            $obsoleteRule = in_array((int) $roundRule->id, $diagnostic['obsolete_rule_ids'], true);

                            $redundantRule = in_array((int) $roundRule->id, $diagnostic['redundant_rule_ids'], true);
                        @endphp

                        <article @class([
                            'rounded-2xl border p-4',
                            'border-red-300 bg-red-50' => $obsoleteRule,
                            'border-amber-300 bg-amber-50' => !$obsoleteRule && $redundantRule,
                            'border-slate-200 bg-slate-50' => !$obsoleteRule && !$redundantRule,
                        ])>
                            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-black text-slate-900">
                                            {{ $roundRule->round_label }}
                                        </p>

                                        <span
                                            class="rounded-full bg-violet-100 px-2 py-1 text-[9px] font-black uppercase text-violet-700">
                                            Override
                                        </span>
                                        @if ($obsoleteRule)
                                            <span
                                                class="rounded-full bg-red-100 px-2 py-1 text-[9px] font-black uppercase text-red-700">
                                                Obsoleto
                                            </span>
                                        @elseif ($redundantRule)
                                            <span
                                                class="rounded-full bg-amber-100 px-2 py-1 text-[9px] font-black uppercase text-amber-700">
                                                Redundante
                                            </span>
                                        @endif
                                        
                                        @if ($obsoleteRule)
                                            <p class="mt-2 text-xs font-bold leading-5 text-red-700">
                                                Esta ronda no puede existir con el contrato y objetivo actuales.
                                            </p>
                                        @elseif ($redundantRule)
                                            <p class="mt-2 text-xs font-bold leading-5 text-amber-700">
                                                Utiliza el mismo formato que la configuración general.
                                            </p>
                                        @endif
                                    </div>


                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $roundRule->series_label }}
                                        ·

                                        @if ($roundRule->series_format === 'FIXED_GAMES')
                                            se disputan todos
                                        @else
                                            {{ $roundRule->wins_required }}
                                            {{ $roundRule->wins_required === 1 ? 'victoria necesaria' : 'victorias necesarias' }}
                                        @endif
                                    </p>
                                </div>

                                <form method="POST"
                                    action="{{ route('tournaments.single-elimination.round-rules.destroy', [$phaseTemplate, $roundRule]) }}"
                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                    data-confirm-title="Eliminar override"
                                    data-confirm-message="Esta ronda volverá a utilizar el formato de serie predeterminado."
                                    data-confirm-subject="{{ $roundRule->round_label }}"
                                    data-confirm-action="Eliminar override">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100">
                                        Eliminar
                                    </button>
                                </form>
                            </div>

                            <div class="mt-4 border-t border-slate-200 pt-4">
                                @include(
                                    'tournaments.phase-templates.partials.single-elimination-round-rule-form',
                                    [
                                        'roundRule' => $roundRule,
                                    ]
                                )
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-violet-300 bg-violet-50/30 p-6 text-center">
                            <p class="font-black text-slate-800">
                                Todas las rondas utilizan {{ $settings->series_label }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Agrega una regla solamente cuando una ronda necesite un formato diferente.
                            </p>
                        </div>
                    @endforelse
                </div>

                <aside class="h-fit rounded-2xl border border-violet-200 bg-violet-50/60 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-violet-600">
                        Nueva regla
                    </p>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Elige la ronda y su formato de serie particular.
                    </p>

                    @if (count($availableRoundSizes) > 0)
                        <div class="mt-4">
                            @include(
                                'tournaments.phase-templates.partials.single-elimination-round-rule-form',
                                [
                                    'roundRule' => null,
                                    'compact' => true,
                                ]
                            )
                        </div>
                    @else
                        <div class="mt-4 rounded-xl bg-white p-4 text-xs text-slate-500">
                            Ya configuraste todos los tamaños de ronda disponibles.
                        </div>
                    @endif
                </aside>
            </div>
        </section>

        {{-- BARRA PERSISTENTE --}}

        <div
            class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 p-3 shadow-[0_-12px_35px_rgba(15,23,42,0.12)] backdrop-blur lg:left-72">
            <div class="mx-auto flex max-w-7xl flex-col justify-between gap-3 px-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <span class="h-2.5 w-2.5 rounded-full"
                        :class="{
                            'bg-amber-400 animate-pulse': dirty && !submitting,
                        
                            'bg-indigo-500 animate-pulse': submitting,
                        
                            'bg-emerald-500':
                                !dirty && !submitting
                        }"></span>

                    <div>
                        <p class="text-xs font-black text-slate-900"
                            x-text="submitting
                                ? 'Guardando configuración...'
                                : (
                                    dirty
                                        ? 'Tienes cambios sin guardar'
                                        : 'Configuración guardada'
                                )">
                        </p>

                        <p class="text-[10px] text-slate-400">
                            La vista previa matemática se actualiza al volver a calcular.
                        </p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button"
                        class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:flex-none"
                        :disabled="!dirty || submitting" @click="discardSettings()">
                        Descartar
                    </button>

                    <button type="button"
                        class="flex-1 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-black text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none sm:flex-none"
                        :disabled="!dirty || submitting" @click="submitSettings()">
                        <span x-show="! submitting">
                            Guardar cambios
                        </span>

                        <span x-cloak x-show="submitting">
                            Guardando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-tournament-layout>
