@php
    $editingTiebreaker = isset($tiebreaker) && $tiebreaker;

    $action = $editingTiebreaker
        ? route('tournaments.swiss.tiebreakers.update', [$phaseTemplate, $tiebreaker])
        : route('tournaments.swiss.tiebreakers.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" x-data="{
    criterion: @js(old('criterion', $editingTiebreaker ? $tiebreaker->criterion : 'OPPONENT_SCORE_SUM'))
}" class="space-y-4">

    @csrf

    @if ($editingTiebreaker)
        @method('PUT')
    @endif


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Criterio
        </label>

        <select name="criterion" x-model="criterion" class="mt-2 w-full rounded-xl border-slate-300 text-sm">

            @foreach ($tiebreakerCriteria as $value => $label)
                <option value="{{ $value }}">
                    {{ $label }}
                </option>
            @endforeach

        </select>

    </div>


    <div x-show="criterion === 'OPPONENT_SCORE_CUT_LOWEST'" x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            ¿Cuántos scores bajos descartar?
        </label>

        <input type="number" name="parameter_int" min="1" max="20"
            value="{{ old('parameter_int', $editingTiebreaker ? $tiebreaker->parameter_int : 1) }}"
            :disabled="criterion !== 'OPPONENT_SCORE_CUT_LOWEST'"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm">

    </div>


    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Dirección
        </label>

        <select name="direction" class="mt-2 w-full rounded-xl border-slate-300 text-sm">

            @foreach ([
        'AUTO' => 'Automática',
        'DESC' => 'Mayor primero',
        'ASC' => 'Menor primero',
    ] as $value => $label)
                <option value="{{ $value }}" @selected(old('direction', $editingTiebreaker ? $tiebreaker->direction : 'AUTO') === $value)>

                    {{ $label }}

                </option>
            @endforeach

        </select>

    </div>


    <button type="submit" class="w-full rounded-xl bg-fuchsia-600 px-4 py-3 text-xs font-black text-white">

        {{ $editingTiebreaker ? 'Guardar criterio' : '+ Agregar criterio' }}

    </button>

</form>
