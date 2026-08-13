@php
    $editingTiebreaker = isset($tiebreaker) && $tiebreaker;

    $action = $editingTiebreaker
        ? route('tournaments.round-robin.tiebreakers.update', [$phaseTemplate, $tiebreaker])
        : route('tournaments.round-robin.tiebreakers.store', $phaseTemplate);

    $criterionOptions = [];

    foreach ($criteria as $key => $definition) {
        if ($editingTiebreaker) {
            if ($key === $tiebreaker->criterion || array_key_exists($key, $availableCriteria)) {
                $criterionOptions[$key] = $definition;
            }
        } elseif (array_key_exists($key, $availableCriteria)) {
            $criterionOptions[$key] = $definition;
        }
    }
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">

    @csrf

    @if ($editingTiebreaker)
        @method('PUT')
    @endif

    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Criterio
        </label>

        <select name="criterion"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">

            @foreach ($criterionOptions as $key => $definition)
                <option value="{{ $key }}" @selected(old('criterion', $editingTiebreaker ? $tiebreaker->criterion : '') === $key)>
                    {{ $definition['label'] }}
                </option>
            @endforeach

        </select>

    </div>

    <div>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Dirección
        </label>

        <select name="direction"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">

            <option value="AUTO" @selected(old('direction', $editingTiebreaker ? $tiebreaker->direction : 'AUTO') === 'AUTO')>
                Automática
            </option>

            <option value="DESC" @selected(old('direction', $editingTiebreaker ? $tiebreaker->direction : 'AUTO') === 'DESC')>
                Mayor primero
            </option>

            <option value="ASC" @selected(old('direction', $editingTiebreaker ? $tiebreaker->direction : 'AUTO') === 'ASC')>
                Menor primero
            </option>

        </select>

    </div>

    <button type="submit"
        class="w-full rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white transition hover:bg-violet-700">
        {{ $editingTiebreaker ? 'Guardar criterio' : '+ Agregar criterio' }}
    </button>

</form>
