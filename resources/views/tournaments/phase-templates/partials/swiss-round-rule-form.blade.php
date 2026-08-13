@php
    $editingRoundRule = isset($roundRule) && $roundRule;

    $action = $editingRoundRule
        ? route('tournaments.swiss.round-rules.update', [$phaseTemplate, $roundRule])
        : route('tournaments.swiss.round-rules.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" x-data="{
    trigger: @js(old('trigger_type', $editingRoundRule ? $roundRule->trigger_type : 'QUALIFICATION_OR_ELIMINATION'))
}" class="space-y-4">

    @csrf

    @if ($editingRoundRule)
        @method('PUT')
    @endif


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Activar cuando
        </label>

        <select name="trigger_type" x-model="trigger" class="mt-2 w-full rounded-xl border-slate-300 text-sm">

            @foreach ($roundRuleTypes as $value => $label)
                <option value="{{ $value }}">
                    {{ $label }}
                </option>
            @endforeach

        </select>

    </div>


    <div x-show="trigger === 'ROUND_NUMBER'" x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Número de ronda
        </label>

        <input type="number" name="round_number" min="1" max="100"
            value="{{ old('round_number', $editingRoundRule ? $roundRule->round_number : 1) }}"
            :disabled="trigger !== 'ROUND_NUMBER'" class="mt-2 w-full rounded-xl border-slate-300 text-sm">

    </div>


    <div x-show="trigger === 'EXACT_RECORD'" x-transition class="grid gap-3 sm:grid-cols-3">

        <div>

            <label class="text-[10px] font-black uppercase text-emerald-600">
                W
            </label>

            <input type="number" name="record_wins" min="0" max="100"
                value="{{ old('record_wins', $editingRoundRule ? $roundRule->record_wins : 2) }}"
                :disabled="trigger !== 'EXACT_RECORD'" class="mt-2 w-full rounded-xl border-slate-300">

        </div>


        <div>

            <label class="text-[10px] font-black uppercase text-amber-600">
                D
            </label>

            <input type="number" name="record_draws" min="0" max="100"
                value="{{ old('record_draws', $editingRoundRule ? $roundRule->record_draws : 0) }}"
                :disabled="trigger !== 'EXACT_RECORD'" class="mt-2 w-full rounded-xl border-slate-300">

        </div>


        <div>

            <label class="text-[10px] font-black uppercase text-red-600">
                L
            </label>

            <input type="number" name="record_losses" min="0" max="100"
                value="{{ old('record_losses', $editingRoundRule ? $roundRule->record_losses : 0) }}"
                :disabled="trigger !== 'EXACT_RECORD'" class="mt-2 w-full rounded-xl border-slate-300">

        </div>

    </div>


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Best Of
        </label>

        <select name="best_of" class="mt-2 w-full rounded-xl border-slate-300">

            @foreach ([1, 3, 5, 7, 9] as $bestOf)
                <option value="{{ $bestOf }}" @selected((int) old('best_of', $editingRoundRule ? $roundRule->best_of : 3) === $bestOf)>

                    BO{{ $bestOf }}

                </option>
            @endforeach

        </select>

    </div>


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Empate
        </label>

        <select name="allow_draws_override" class="mt-2 w-full rounded-xl border-slate-300">

            <option value="" @selected(old('allow_draws_override', $editingRoundRule ? ($roundRule->allow_draws_override === null ? '' : ($roundRule->allow_draws_override ? '1' : '0')) : '') === '')>

                Heredar configuración general

            </option>

            <option value="1" @selected((string) old('allow_draws_override', $editingRoundRule ? ($roundRule->allow_draws_override ? '1' : '') : '') === '1')>

                Permitir empate

            </option>

            <option value="0" @selected((string) old('allow_draws_override', $editingRoundRule && $roundRule->allow_draws_override === false ? '0' : '') === '0')>

                Requiere ganador

            </option>

        </select>

    </div>


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Estado
        </label>

        <select name="status" class="mt-2 w-full rounded-xl border-slate-300">

            <option value="ACTIVE" @selected(old('status', $editingRoundRule ? $roundRule->status : 'ACTIVE') === 'ACTIVE')>
                Activa
            </option>

            <option value="INACTIVE" @selected(old('status', $editingRoundRule ? $roundRule->status : 'ACTIVE') === 'INACTIVE')>
                Inactiva
            </option>

        </select>

    </div>


    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-4 py-3 text-xs font-black text-white">

        {{ $editingRoundRule ? 'Guardar regla' : '+ Agregar regla' }}

    </button>

</form>
