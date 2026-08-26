@php
    /*
     * Cabecera de la Super Edición.
     *
     * Dos cosas, en una sola franja: la identidad de la fase —que se edita
     * aquí mismo— y su estado —que solo se lee.
     *
     * Lo editable es lo que describe a la fase para una persona: imagen,
     * nombre y descripción. El resto del contrato (tipo, mínimos, máximos)
     * se enseña como dato porque cambiarlo altera qué fases son compatibles
     * en un grafo ya montado; eso sigue viviendo en Definición, que avisa
     * de las consecuencias.
     *
     * El formulario de identidad manda al endpoint de siempre y lleva
     * ocultos los campos que no toca, para que la validación sea
     * exactamente la misma que en Definición y no existan dos verdades.
     */

    $statusTone = match ($phaseTemplate->status) {
        'ACTIVE' => ['bg-emerald-400', 'text-emerald-300'],
        'ARCHIVED' => ['bg-slate-500', 'text-slate-400'],
        default => ['bg-amber-400', 'text-amber-300'],
    };
@endphp

<header class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 border-b border-slate-800 bg-slate-900/70 px-3 py-2">

    {{-- VOLVER --}}

    <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}"
        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100"
        title="Volver a la fase">
        ←
    </a>


    {{-- IDENTIDAD --}}

    <div class="flex min-w-0 flex-1 items-center gap-3" x-data="{ editing: false }">

        <div class="relative h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-800 ring-1 ring-slate-700">
            @if ($payload['phase']['image_url'])
                <img src="{{ $payload['phase']['image_url'] }}" alt=""
                    class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center text-sm text-slate-600">◇</div>
            @endif
        </div>

        <div class="min-w-0" x-show="!editing">

            <div class="flex items-center gap-2">
                <h1 class="truncate text-sm font-black text-slate-100">
                    {{ $phaseTemplate->name }}
                </h1>

                <button type="button" @click="editing = true"
                    class="shrink-0 text-[10px] font-black text-slate-500 transition hover:text-amber-400"
                    title="Editar nombre, descripción e imagen">
                    ✎
                </button>
            </div>

            <p class="truncate text-[10px] text-slate-500">
                {{ $phaseTemplate->description ?: 'Sin descripción' }}
            </p>

        </div>


        {{-- EDICIÓN DE IDENTIDAD --}}

        <form x-show="editing" x-cloak method="POST"
            action="{{ route('tournaments.phase-templates.update', $phaseTemplate) }}"
            enctype="multipart/form-data"
            class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

            @csrf
            @method('PUT')

            {{--
                Lo que este formulario no edita viaja igual, porque el
                endpoint valida el contrato entero. Sin esto, guardar el
                nombre borraría los mínimos.
            --}}
            <input type="hidden" name="phase_type" value="{{ $phaseTemplate->phase_type }}">
            <input type="hidden" name="participant_mode" value="{{ $phaseTemplate->participant_mode }}">
            <input type="hidden" name="min_participants" value="{{ $phaseTemplate->min_participants }}">
            <input type="hidden" name="max_participants" value="{{ $phaseTemplate->max_participants }}">
            <input type="hidden" name="exact_participants" value="{{ $phaseTemplate->exact_participants }}">
            <input type="hidden" name="participant_multiple" value="{{ $phaseTemplate->participant_multiple }}">
            <input type="hidden" name="allow_byes" value="{{ $phaseTemplate->allow_byes ? 1 : 0 }}">
            <input type="hidden" name="best_of" value="{{ $phaseTemplate->best_of }}">
            <input type="hidden" name="status" value="{{ $phaseTemplate->status }}">
            <input type="hidden" name="visibility" value="{{ $phaseTemplate->visibility }}">
            <input type="hidden" name="allow_cloning" value="{{ $phaseTemplate->allow_cloning ? 1 : 0 }}">

            <input type="text" name="name" value="{{ $phaseTemplate->name }}" required
                class="w-40 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-xs font-bold text-slate-100 focus:border-amber-500 focus:ring-amber-500"
                placeholder="Nombre">

            <input type="text" name="description" value="{{ $phaseTemplate->description }}"
                class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-xs text-slate-300 focus:border-amber-500 focus:ring-amber-500"
                placeholder="Descripción">

            <label class="cursor-pointer rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-400">
                Imagen
                <input type="file" name="image" accept="image/*" class="hidden">
            </label>

            <button class="rounded-lg bg-amber-500 px-3 py-1 text-[10px] font-black text-slate-950 transition hover:bg-amber-400">
                Guardar
            </button>

            <button type="button" @click="editing = false"
                class="text-[10px] font-black text-slate-500 transition hover:text-slate-300">
                Cancelar
            </button>

        </form>

    </div>


    {{-- DATOS TÉCNICOS --}}

    <div class="hidden shrink-0 items-center gap-1.5 md:flex">

        <span class="rounded-md bg-slate-800 px-2 py-1 font-mono text-[10px] text-slate-400">
            {{ $phaseTemplate->code }}
        </span>

        <span class="rounded-md bg-slate-800 px-2 py-1 text-[10px] font-black text-slate-300">
            {{ $phaseTemplate->type_label }}
        </span>

        <span class="rounded-md bg-slate-800 px-2 py-1 text-[10px] font-black {{ $statusTone[1] }}">
            <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full {{ $statusTone[0] }}"></span>
            {{ $phaseTemplate->status_label }}
        </span>

        <span class="rounded-md bg-slate-800 px-2 py-1 text-[10px] font-black text-slate-400"
            title="Contrato de participantes de la fase">
            {{ $payload['phase']['contract_label'] }}
        </span>

    </div>


    {{-- ESTADO Y GUARDADO --}}

    <div class="flex shrink-0 items-center gap-2">

        <div class="flex items-center gap-1.5 rounded-lg px-2 py-1 text-[10px] font-black"
            :class="{
                'bg-emerald-500/10 text-emerald-300': diagnostics.status === 'VALID',
                'bg-amber-500/10 text-amber-300': diagnostics.status === 'WARNING',
                'bg-rose-500/10 text-rose-300': diagnostics.status === 'INVALID',
            }">
            <span x-show="diagnostics.status === 'VALID'">✓ Válida</span>
            <span x-show="diagnostics.status === 'WARNING'" x-cloak>⚠ Con avisos</span>
            <span x-show="diagnostics.status === 'INVALID'" x-cloak>✕ Inválida</span>
        </div>

        <span x-show="loading" x-cloak
            class="text-[10px] font-black text-slate-500">
            calculando…
        </span>

        <form method="POST"
            action="{{ route('tournaments.phase-templates.super.update', $phaseTemplate) }}">

            @csrf
            @method('PUT')

            <input type="hidden" name="cycles" :value="cycles">
            <input type="hidden" name="initial_order_mode" :value="orderMode">
            <input type="hidden" name="ranking_source" :value="rankingSource">
            <input type="hidden" name="allow_draws" :value="allowDraws ? 1 : 0">
            <input type="hidden" name="win_points" :value="points.win">
            <input type="hidden" name="draw_points" :value="points.draw">
            <input type="hidden" name="loss_points" :value="points.loss">
            <input type="hidden" name="round_limit" :value="isTrimmed ? roundLimit : ''">
            <input type="hidden" name="pin_participants" :value="pinParticipants ? 1 : 0">
            <input type="hidden" name="participants" :value="participants">

            <button :disabled="!isValid"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition"
                :class="isValid
                    ? (dirty ? 'bg-amber-500 text-slate-950 hover:bg-amber-400' : 'bg-slate-800 text-slate-400 hover:bg-slate-700')
                    : 'cursor-not-allowed bg-slate-800 text-slate-600'">
                <span x-show="dirty">Guardar cambios</span>
                <span x-show="!dirty" x-cloak>Guardado</span>
            </button>

        </form>

    </div>

</header>


{{-- DIAGNÓSTICO DETALLADO --}}

<template x-if="diagnostics.errors.length || diagnostics.warnings.length">
    <div class="shrink-0 border-b border-slate-800 bg-slate-900/40 px-3 py-1.5">
        <div class="flex flex-wrap gap-x-4 gap-y-1">

            <template x-for="error in diagnostics.errors" :key="error">
                <p class="text-[10px] font-bold text-rose-300">
                    <span class="mr-1">✕</span><span x-text="error"></span>
                </p>
            </template>

            <template x-for="warning in diagnostics.warnings" :key="warning">
                <p class="text-[10px] text-amber-300/80">
                    <span class="mr-1">⚠</span><span x-text="warning"></span>
                </p>
            </template>

        </div>
    </div>
</template>
