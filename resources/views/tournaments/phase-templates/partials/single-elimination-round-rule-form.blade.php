@php
    $editingRoundRule = isset($roundRule) && $roundRule;
    $compact = $compact ?? false;
    $advancedMode = $settings->configuration_mode === 'ADVANCED';

    $action = $editingRoundRule
        ? route('tournaments.single-elimination.round-rules.update', [$phaseTemplate, $roundRule])
        : route('tournaments.single-elimination.round-rules.store', $phaseTemplate);

    $initialSeriesFormat = old(
        'series_format',
        $editingRoundRule ? $roundRule->series_format : $settings->series_format,
    );

    $hasAdvancedOverride =
        $advancedMode &&
        (old('entrants_per_match') !== null || ($editingRoundRule && $roundRule->entrants_per_match !== null));
@endphp

<form method="POST" action="{{ $action }}" x-data="{
    seriesFormat: @js($initialSeriesFormat),
    advancedOverride: @js($hasAdvancedOverride)
}" class="space-y-4">
    @csrf

    @if ($editingRoundRule)
        @method('PUT')
    @endif

    <div @class([
        'grid min-w-0 gap-3',
        '2xl:grid-cols-[minmax(180px,1fr)_150px_170px_auto] 2xl:items-end' => !$compact,
    ])>
        <div class="min-w-0">
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Ronda
            </label>

            <p class="mt-1 text-[11px] leading-4 text-slate-400">
                Selecciona dónde aplicar el override.
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
                        {{ $label }}
                        ·
                        {{ $size }} participantes
                    </option>
                @endforeach
            </select>

            <x-input-error :messages="$errors->get('participants_in_round')" class="mt-2" />
        </div>

        <div class="min-w-0">
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Serie
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
                            BO{{ $value }}
                            ·
                            {{ intdiv($value, 2) + 1 }}V
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
    </div>

    @if ($advancedMode)
        <div class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50/60 p-4">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" x-model="advancedOverride"
                    class="mt-0.5 rounded border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">

                <span>
                    <span class="block text-xs font-black text-slate-900">
                        Cambiar también el formato K → Q de esta ronda
                    </span>

                    <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                        Úsalo, por ejemplo, para cerrar con una final 2 → 1 después de rondas 4 → 2.
                    </span>
                </span>
            </label>

            {{-- Permiten limpiar un override existente. --}}

            <input type="hidden" name="entrants_per_match" value="">

            <input type="hidden" name="qualifiers_per_match" value="">

            <input type="hidden" name="encounter_profile" value="">

            <div x-cloak x-show="advancedOverride" x-transition class="mt-4 grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Participantes (K)
                    </label>

                    <input type="number" name="entrants_per_match" min="2" max="64"
                        :disabled="!advancedOverride" :required="advancedOverride"
                        value="{{ old(
                            'entrants_per_match',
                            $editingRoundRule ? $roundRule->entrants_per_match : $settings->entrants_per_match,
                        ) }}"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">

                    <x-input-error :messages="$errors->get('entrants_per_match')" class="mt-2" />
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Clasificados (Q)
                    </label>

                    <input type="number" name="qualifiers_per_match" min="1" max="63"
                        :disabled="!advancedOverride" :required="advancedOverride"
                        value="{{ old(
                            'qualifiers_per_match',
                            $editingRoundRule ? $roundRule->qualifiers_per_match : $settings->qualifiers_per_match,
                        ) }}"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">

                    <x-input-error :messages="$errors->get('qualifiers_per_match')" class="mt-2" />
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Perfil
                    </label>

                    <select name="encounter_profile" :disabled="!advancedOverride" :required="advancedOverride"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">
                        @foreach ([
        'DUEL' => 'Duelo',
        'MULTI_COMPETITOR' => 'Multicompetidor',
        'CUSTOM' => 'Personalizado',
    ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('encounter_profile', $editingRoundRule ? $roundRule->encounter_profile : $settings->encounter_profile) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <x-input-error :messages="$errors->get('encounter_profile')" class="mt-2" />
                </div>
            </div>
        </div>
    @endif
</form>
