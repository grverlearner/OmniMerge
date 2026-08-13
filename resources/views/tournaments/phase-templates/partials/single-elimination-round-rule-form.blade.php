@php
    $editingRoundRule = isset($roundRule) && $roundRule;

    $action = $editingRoundRule
        ? route('tournaments.single-elimination.round-rules.update', [$phaseTemplate, $roundRule])
        : route('tournaments.single-elimination.round-rules.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">

    @csrf

    @if ($editingRoundRule)
        @method('PUT')
    @endif

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Ronda
        </label>

        <select name="participants_in_round"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

            @php
                $sizes = $editingRoundRule ? [$roundRule->participants_in_round] : $availableRoundSizes;
            @endphp

            @foreach ($sizes as $size)
                @php
                    $label = match ((int) $size) {
                        2 => 'Final',
                        4 => 'Semifinal',
                        8 => 'Cuartos de final',
                        16 => 'Ronda de 16',
                        32 => 'Ronda de 32',
                        64 => 'Ronda de 64',
                        128 => 'Ronda de 128',
                        256 => 'Ronda de 256',
                        512 => 'Ronda de 512',
                        default => 'Ronda de ' . $size,
                    };
                @endphp

                <option value="{{ $size }}">
                    {{ $label }}
                </option>
            @endforeach

        </select>
    </div>

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Best Of
        </label>

        <select name="best_of"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

            @foreach ([1, 3, 5, 7, 9] as $value)
                <option value="{{ $value }}" @selected((int) old('best_of', $editingRoundRule ? $roundRule->best_of : $settings->default_best_of) === $value)>
                    BO{{ $value }}
                </option>
            @endforeach

        </select>
    </div>

    <button type="submit" class="rounded-xl bg-slate-950 px-4 py-3 text-xs font-black text-white">
        {{ $editingRoundRule ? 'Guardar' : '+ Agregar' }}
    </button>

</form>
