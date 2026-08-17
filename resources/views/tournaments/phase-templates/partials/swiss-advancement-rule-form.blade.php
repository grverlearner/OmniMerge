@php
    $editingRule = isset($advancementRule) && $advancementRule;

    $action = $editingRule
        ? route('tournaments.swiss.advancement-rules.update', [$phaseTemplate, $advancementRule])
        : route('tournaments.swiss.advancement-rules.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" x-data="{
    type: @js(old('rule_type', $editingRule ? $advancementRule->rule_type : ($settings->completion_mode === 'RECORD_THRESHOLDS' ? 'WIN_THRESHOLD' : 'FINAL_TOP_N')))
}" class="space-y-4">

    @csrf

    @if ($editingRule)
        @method('PUT')
    @endif


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Regla de selección
        </label>
        <p class="mt-1 text-[10px] leading-4 text-slate-400">
            Define qué condición convierte a un participante en candidato para esta salida.
        </p>

        <select name="rule_type" x-model="type" class="mt-2 w-full rounded-xl border-slate-300 text-sm">

            @foreach ($advancementRuleTypes as $value => $label)
                <option value="{{ $value }}">
                    {{ $label }}
                </option>
            @endforeach

        </select>

    </div>


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Puerta de salida de destino
        </label>
        <p class="mt-1 text-[10px] leading-4 text-slate-400">
            Cuando la condición se cumpla, el participante será asociado con esta salida.
        </p>

        <select name="phase_exit_id" class="mt-2 w-full rounded-xl border-slate-300 text-sm">

            @foreach ($phaseExits as $exit)
                <option value="{{ $exit->id }}" @selected((int) old('phase_exit_id', $editingRule ? $advancementRule->phase_exit_id : 0) === $exit->id)>

                    {{ $exit->name }}
                    ·
                    {{ $exit->code }}

                </option>
            @endforeach

        </select>

    </div>


    {{-- WIN THRESHOLD --}}

    <div x-show="type === 'WIN_THRESHOLD'" x-transition>

        <label class="text-[10px] font-black uppercase text-emerald-600">
            Victorias para activar
        </label>

        <input type="number" name="threshold_wins" min="1" max="100"
            value="{{ old('threshold_wins', $editingRule ? $advancementRule->threshold_wins : $settings->qualification_wins) }}"
            :disabled="type !== 'WIN_THRESHOLD'" class="mt-2 w-full rounded-xl border-emerald-200">

    </div>


    {{-- LOSS THRESHOLD --}}

    <div x-show="type === 'LOSS_THRESHOLD'" x-transition>

        <label class="text-[10px] font-black uppercase text-red-600">
            Derrotas para activar
        </label>

        <input type="number" name="threshold_losses" min="1" max="100"
            value="{{ old('threshold_losses', $editingRule ? $advancementRule->threshold_losses : $settings->elimination_losses) }}"
            :disabled="type !== 'LOSS_THRESHOLD'" class="mt-2 w-full rounded-xl border-red-200">

    </div>


    {{-- EXACT RECORD --}}

    <div x-show="type === 'EXACT_RECORD'" x-transition>
        <p class="mb-2 text-[10px] leading-4 text-slate-400">
            La regla solo se activa cuando el récord W-D-L coincide exactamente con estos tres valores.
        </p>

        <div class="grid gap-3 sm:grid-cols-3">
            <label>
                <span class="text-[9px] font-black uppercase text-emerald-600">Victorias (W)</span>
                <input type="number" name="record_wins" min="0" max="100"
                    value="{{ old('record_wins', $editingRule ? $advancementRule->record_wins : 3) }}"
                    :disabled="type !== 'EXACT_RECORD'" placeholder="Ej. 3"
                    class="mt-1 w-full rounded-xl border-slate-300">
            </label>

            <label>
                <span class="text-[9px] font-black uppercase text-amber-600">Empates (D)</span>
                <input type="number" name="record_draws" min="0" max="100"
                    value="{{ old('record_draws', $editingRule ? $advancementRule->record_draws : 0) }}"
                    :disabled="type !== 'EXACT_RECORD'" placeholder="Ej. 0"
                    class="mt-1 w-full rounded-xl border-slate-300">
            </label>

            <label>
                <span class="text-[9px] font-black uppercase text-red-600">Derrotas (L)</span>
                <input type="number" name="record_losses" min="0" max="100"
                    value="{{ old('record_losses', $editingRule ? $advancementRule->record_losses : 0) }}"
                    :disabled="type !== 'EXACT_RECORD'" placeholder="Ej. 1"
                    class="mt-1 w-full rounded-xl border-slate-300">
            </label>
        </div>
    </div>


    {{-- TAKE --}}

    <div x-show="[
        'FINAL_TOP_N',
        'FINAL_BOTTOM_N'
    ].includes(type)" x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Cantidad de participantes
        </label>
        <p class="mt-1 text-[10px] leading-4 text-slate-400">
            Número de puestos que captura FINAL_TOP_N o FINAL_BOTTOM_N.
        </p>

        <input type="number" name="take" min="1" max="512"
            value="{{ old('take', $editingRule ? $advancementRule->take : 8) }}"
            :disabled="![
                'FINAL_TOP_N',
                'FINAL_BOTTOM_N'
            ].includes(type)"
            class="mt-2 w-full rounded-xl border-slate-300">

    </div>


    {{-- RANK FROM --}}

    <div x-show="[
        'FINAL_RANK_POSITION',
        'FINAL_RANK_RANGE'
    ].includes(type)" x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Posición inicial
        </label>
        <p class="mt-1 text-[10px] leading-4 text-slate-400">
            Primer puesto de la clasificación final que debe seleccionar esta regla.
        </p>

        <input type="number" name="rank_from" min="1" max="512"
            value="{{ old('rank_from', $editingRule ? $advancementRule->rank_from : 1) }}"
            :disabled="![
                'FINAL_RANK_POSITION',
                'FINAL_RANK_RANGE'
            ].includes(type)"
            class="mt-2 w-full rounded-xl border-slate-300">

    </div>


    {{-- RANK TO --}}

    <div x-show="type === 'FINAL_RANK_RANGE'" x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Hasta posición
        </label>

        <input type="number" name="rank_to" min="1" max="512"
            value="{{ old('rank_to', $editingRule ? $advancementRule->rank_to : 8) }}"
            :disabled="type !== 'FINAL_RANK_RANGE'" class="mt-2 w-full rounded-xl border-slate-300">

    </div>


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Estado
        </label>

        <select name="status" class="mt-2 w-full rounded-xl border-slate-300">

            <option value="ACTIVE" @selected(old('status', $editingRule ? $advancementRule->status : 'ACTIVE') === 'ACTIVE')>

                Activa

            </option>

            <option value="INACTIVE" @selected(old('status', $editingRule ? $advancementRule->status : 'ACTIVE') === 'INACTIVE')>

                Inactiva

            </option>

        </select>

    </div>


    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-xs font-black text-white">

        {{ $editingRule ? 'Guardar regla' : '+ Agregar regla' }}

    </button>

</form>
