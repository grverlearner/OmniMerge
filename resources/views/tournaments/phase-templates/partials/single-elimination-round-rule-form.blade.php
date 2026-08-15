@php
    $editingRoundRule = isset($roundRule) && $roundRule;
    $compact = $compact ?? false;

    $action = $editingRoundRule
        ? route('tournaments.single-elimination.round-rules.update', [$phaseTemplate, $roundRule])
        : route('tournaments.single-elimination.round-rules.store', $phaseTemplate);

    $initialSeriesFormat = old(
        'series_format',
        $editingRoundRule ? $roundRule->series_format : $settings->series_format,
    );
@endphp

<form method="POST" action="{{ $action }}" x-data="{
    seriesFormat: @js($initialSeriesFormat)
}" @class([
    'grid min-w-0 gap-3',
    '2xl:grid-cols-[minmax(180px,1fr)_150px_170px_auto] 2xl:items-end' => !$compact,
])>
    @csrf

    @if ($editingRoundRule)
        @method('PUT')
    @endif

    <div class="min-w-0">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Ronda
        </label>

        <p class="mt-1 text-[11px] leading-4 text-slate-400">
            Selecciona dónde cambiar el formato.
        </p>

        <select name="participants_in_round"
            class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-violet-400 focus:ring-violet-400">
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
                    {{ $label }} · {{ $size }} participantes
                </option>
            @endforeach
        </select>

        <x-input-error :messages="$errors->get('participants_in_round')" class="mt-2" />
    </div>

    <div class="min-w-0">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Formato
        </label>

        <select name="series_format" x-model="seriesFormat"
            class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-violet-400 focus:ring-violet-400">
            <option value="BEST_OF">
                Best of
            </option>

            <option value="FIXED_GAMES">
                Cantidad fija
            </option>
        </select>

        <x-input-error :messages="$errors->get('series_format')" class="mt-2" />
    </div>

    <div class="min-w-0">
        <div x-show="seriesFormat === 'BEST_OF'">
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Best of
            </label>

            <select name="best_of"
                class="mt-2 w-full min-w-0 rounded-xl border-slate-300 bg-white text-sm focus:border-violet-400 focus:ring-violet-400">
                @foreach ([1, 3, 5, 7, 9] as $value)
                    <option value="{{ $value }}" @selected((int) old('best_of', $editingRoundRule ? $roundRule->best_of : $settings->default_best_of) === $value)>
                        BO{{ $value }} · {{ intdiv($value, 2) + 1 }}V
                    </option>
                @endforeach
            </select>

            <x-input-error :messages="$errors->get('best_of')" class="mt-2" />
        </div>

        <div x-show="seriesFormat === 'FIXED_GAMES'">
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Enfrentamientos
            </label>

            <input type="number" name="fixed_games" min="1" max="99"
                value="{{ old('fixed_games', $editingRoundRule ? $roundRule->fixed_games : $settings->fixed_games) }}"
                class="mt-2 w-full min-w-0 rounded-xl border-slate-300 bg-white text-sm focus:border-cyan-400 focus:ring-cyan-400">

            <x-input-error :messages="$errors->get('fixed_games')" class="mt-2" />
        </div>
    </div>

    <button type="submit" @class([
        'whitespace-nowrap rounded-xl bg-slate-950 px-4 py-3 text-xs font-black text-white transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50',
        'w-full' => $compact,
        'w-full 2xl:w-auto' => !$compact,
    ])>
        {{ $editingRoundRule ? 'Guardar regla' : '+ Agregar regla' }}
    </button>
</form>
