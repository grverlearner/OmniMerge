@php
    $editingRule = isset($advancementRule) && $advancementRule;

    /*
     * El reparto real en grupos, para que el formulario pueda hacer la
     * cuenta en voz alta mientras se escribe.
     *
     * Una regla «de cada grupo» multiplica, y esa multiplicacion no se ve
     * por ninguna parte: escribir 8 en una fase de 4 grupos de 4 clasifica
     * a los 16. Aqui abajo se dice cuantos pasan, con el numero puesto.
     */
    $scaleService = app(\App\Services\Tournaments\GroupStage\GroupStageExitForecastService::class);

    $scaleParticipants = $scaleService->referenceParticipants($phaseTemplate);

    $scaleSizes = $scaleService->groupSizes($phaseTemplate, $scaleParticipants);

    $scaleGroups = count($scaleSizes);

    $scaleSmallest = $scaleSizes === [] ? 0 : min($scaleSizes);

    $action = $editingRule
        ? route('tournaments.group-stage.advancement-rules.update', [$phaseTemplate, $advancementRule])
        : route('tournaments.group-stage.advancement-rules.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" x-data="{
    type: @js(old('rule_type', $editingRule ? $advancementRule->rule_type : 'EACH_GROUP_TOP_N')),
    take: @js((int) old('take', $editingRule ? $advancementRule->take : 2)),
    groups: @js($scaleGroups),
    groupSize: @js($scaleSmallest),

    /* Cuantos clasifica de verdad la cantidad escrita */
    get reach() {
        const n = Math.max(0, parseInt(this.take) || 0);

        if (!this.groups || !this.groupSize) {
            return null;
        }

        if (['EACH_GROUP_TOP_N', 'EACH_GROUP_BOTTOM_N'].includes(this.type)) {
            return {
                perGroup: true,
                total: Math.min(n, this.groupSize) * this.groups,
                everyone: n >= this.groupSize,
            };
        }

        if (['CROSS_GROUP_POSITION_TOP_N', 'CROSS_GROUP_POSITION_BOTTOM_N',
             'BEST_REMAINING', 'WORST_REMAINING'].includes(this.type)) {
            return { perGroup: false, total: n, everyone: false };
        }

        return null;
    },
}" class="space-y-4">

    @csrf

    @if ($editingRule)
        @method('PUT')
    @endif

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Regla</label>

        <select name="rule_type" x-model="type"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
            @foreach ($ruleTypes as $value => $definition)
                <option value="{{ $value }}">{{ $definition['label'] }}</option>
            @endforeach
        </select>

        {{-- Qué hace exactamente la regla elegida --}}

        @foreach ($ruleTypes as $value => $definition)
            <p x-show="type === @js($value)" x-cloak
                class="mt-1.5 text-[10px] leading-relaxed text-slate-500">
                {{ $definition['description'] }}
            </p>
        @endforeach
    </div>

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Puerta de salida</label>

        <select name="phase_exit_id"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
            @foreach ($phaseExits as $exit)
                <option value="{{ $exit->id }}" @selected((int) old('phase_exit_id', $editingRule ? $advancementRule->phase_exit_id : 0) === $exit->id)>
                    {{ $exit->name }}
                    ·
                    {{ $exit->code }}
                </option>
            @endforeach
        </select>
    </div>

    <div x-show="['SPECIFIC_GROUP_POSITION', 'SPECIFIC_GROUP_RANGE'].includes(type)" x-transition>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Grupo específico</label>

        <select name="phase_group_stage_group_id"
            :disabled="![
                'SPECIFIC_GROUP_POSITION',
                'SPECIFIC_GROUP_RANGE'
            ].includes(type)"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
            @foreach ($activeGroupDefinitions as $definition)
                <option value="{{ $definition->id }}" @selected((int) old('phase_group_stage_group_id', $editingRule ? $advancementRule->phase_group_stage_group_id : 0) === $definition->id)>
                    {{ $definition->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div x-show="[
        'EACH_GROUP_POSITION',
        'EACH_GROUP_RANGE',
        'CROSS_GROUP_POSITION_TOP_N',
        'CROSS_GROUP_POSITION_BOTTOM_N',
        'SPECIFIC_GROUP_POSITION',
        'SPECIFIC_GROUP_RANGE'
    ].includes(type)"
        x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Posición
        </label>

        <input type="number" name="position_from" min="1" max="512"
            value="{{ old('position_from', $editingRule ? $advancementRule->position_from : 1) }}"
            :disabled="![
                'EACH_GROUP_POSITION',
                'EACH_GROUP_RANGE',
                'CROSS_GROUP_POSITION_TOP_N',
                'CROSS_GROUP_POSITION_BOTTOM_N',
                'SPECIFIC_GROUP_POSITION',
                'SPECIFIC_GROUP_RANGE'
            ].includes(type)"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm">

    </div>

    <div x-show="[
        'EACH_GROUP_RANGE',
        'SPECIFIC_GROUP_RANGE'
    ].includes(type)" x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Hasta posición
        </label>

        <input type="number" name="position_to" min="1" max="512"
            value="{{ old('position_to', $editingRule ? $advancementRule->position_to : '') }}"
            :disabled="![
                'EACH_GROUP_RANGE',
                'SPECIFIC_GROUP_RANGE'
            ].includes(type)"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm">

    </div>

    <div x-show="[
        'EACH_GROUP_TOP_N',
        'EACH_GROUP_BOTTOM_N',
        'CROSS_GROUP_POSITION_TOP_N',
        'CROSS_GROUP_POSITION_BOTTOM_N',
        'BEST_REMAINING',
        'WORST_REMAINING'
    ].includes(type)"
        x-transition>

        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Cantidad
        </label>

        <input type="number" name="take" min="1" max="512" x-model="take"
            :disabled="![
                'EACH_GROUP_TOP_N',
                'EACH_GROUP_BOTTOM_N',
                'CROSS_GROUP_POSITION_TOP_N',
                'CROSS_GROUP_POSITION_BOTTOM_N',
                'BEST_REMAINING',
                'WORST_REMAINING'
            ].includes(type)"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm">

        {{-- La cuenta hecha en voz alta --}}

        <template x-if="reach">
            <p class="mt-2 rounded-xl px-3 py-2 text-[11px] font-bold leading-relaxed"
                :class="reach.everyone
                    ? 'bg-red-50 text-red-700'
                    : 'bg-violet-50 text-violet-700'">

                <template x-if="reach.perGroup">
                    <span>
                        <span x-text="take"></span> de cada uno de los
                        <span x-text="groups"></span> grupos de
                        <span x-text="groupSize"></span>
                        =
                        <strong x-text="reach.total"></strong>
                        clasificados de {{ $scaleParticipants }}.
                    </span>
                </template>

                <template x-if="!reach.perGroup">
                    <span>
                        Aquí la cantidad es el total:
                        <strong x-text="reach.total"></strong>
                        clasificados de {{ $scaleParticipants }}.
                    </span>
                </template>

                <span x-show="reach.everyone" x-cloak class="mt-1 block font-black">
                    Pasan todos: esta regla no eliminaría a nadie.
                </span>
            </p>
        </template>

    </div>

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Estado</label>

        <select name="status" class="mt-2 w-full rounded-xl border-slate-300 text-sm">
            <option value="ACTIVE" @selected(old('status', $editingRule ? $advancementRule->status : 'ACTIVE') === 'ACTIVE')>
                Activa
            </option>

            <option value="INACTIVE" @selected(old('status', $editingRule ? $advancementRule->status : 'ACTIVE') === 'INACTIVE')>
                Inactiva
            </option>
        </select>
    </div>

    <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white">
        {{ $editingRule ? 'Guardar regla' : '+ Agregar regla' }}
    </button>

</form>
