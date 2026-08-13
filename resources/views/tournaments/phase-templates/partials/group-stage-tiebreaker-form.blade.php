@php
    $editingTiebreaker = isset($tiebreaker) && $tiebreaker;

    $action = $editingTiebreaker
        ? route('tournaments.group-stage.tiebreakers.update', [$phaseTemplate, $tiebreaker])
        : route('tournaments.group-stage.tiebreakers.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">

    @csrf

    @if ($editingTiebreaker)
        @method('PUT')
    @endif

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Criterio</label>

        <select name="criterion"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">
            @foreach ($crossGroupCriteria as $value => $definition)
                <option value="{{ $value }}" @selected(old('criterion', $editingTiebreaker ? $tiebreaker->criterion : 'POINTS') === $value)>
                    {{ $definition['label'] }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Normalización</label>

        <select name="normalization" class="mt-2 w-full rounded-xl border-slate-300 text-sm">
            <option value="DEFAULT" @selected(old('normalization', $editingTiebreaker ? $tiebreaker->normalization : 'DEFAULT') === 'DEFAULT')>
                Usar configuración general
            </option>

            <option value="RAW" @selected(old('normalization', $editingTiebreaker ? $tiebreaker->normalization : '') === 'RAW')>
                Valor total
            </option>

            <option value="PER_MATCH" @selected(old('normalization', $editingTiebreaker ? $tiebreaker->normalization : '') === 'PER_MATCH')>
                Por partido
            </option>
        </select>
    </div>

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Dirección</label>

        <select name="direction" class="mt-2 w-full rounded-xl border-slate-300 text-sm">
            <option value="AUTO" @selected(old('direction', $editingTiebreaker ? $tiebreaker->direction : 'AUTO') === 'AUTO')>
                Automática
            </option>

            <option value="DESC" @selected(old('direction', $editingTiebreaker ? $tiebreaker->direction : '') === 'DESC')>
                Mayor primero
            </option>

            <option value="ASC" @selected(old('direction', $editingTiebreaker ? $tiebreaker->direction : '') === 'ASC')>
                Menor primero
            </option>
        </select>
    </div>

    <button type="submit" class="w-full rounded-xl bg-fuchsia-600 px-4 py-3 text-xs font-black text-white">
        {{ $editingTiebreaker ? 'Guardar criterio' : '+ Agregar criterio' }}
    </button>

</form>
