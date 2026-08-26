@php
    /*
     * Los campos que describen QUIEN sale por una puerta.
     *
     * Viven aparte porque hacen falta en tres sitios —al crear la salida,
     * al anadirle otro criterio y al editar uno existente— y antes eran
     * tres copias que se iban separando entre si.
     *
     * No trae <form>: lo pone quien lo incluye, porque cada sitio manda a
     * una ruta distinta.
     *
     * $phaseTemplate       la fase
     * $ruleTypes           catalogo de criterios, con familia y descripcion
     * $groupDefinitions    grupos definidos, para los criterios de un grupo
     * $advancementRule     el criterio que se edita, o null
     */

    $editing = isset($advancementRule) && $advancementRule;

    /*
     * El reparto real en grupos, para poder decir en voz alta cuanta gente
     * sale con el numero que se acaba de escribir. Una regla «de cada
     * grupo» multiplica, y esa multiplicacion no se veia por ninguna parte.
     */
    $scale = app(\App\Services\Tournaments\GroupStage\GroupStageExitForecastService::class);

    $scaleParticipants = $scale->referenceParticipants($phaseTemplate);

    $scaleSizes = $scale->groupSizes($phaseTemplate, $scaleParticipants);

    $scaleGroups = count($scaleSizes);

    $scaleSmallest = $scaleSizes === [] ? 0 : min($scaleSizes);

    /* Los criterios agrupados por familia, para que el desplegable se lea */
    /* groupBy reindexa: sin preservar la clave, el <option> valdria 0, 1, 2 */
    $families = collect($ruleTypes)->groupBy(fn($definition) => $definition['family'] ?? 'Otros', true);
@endphp

<div class="space-y-3" x-data="{
    type: @js(old('rule_type', $editing ? $advancementRule->rule_type : 'EACH_GROUP_TOP_N')),
    take: @js((int) old('take', $editing ? $advancementRule->take : 2)),
    from: @js((int) old('position_from', $editing ? $advancementRule->position_from : 1)),

    groups: @js($scaleGroups),
    groupSize: @js($scaleSmallest),

    /* Familias de criterio, para saber que campos pedir */
    get perGroupTake() {
        return ['EACH_GROUP_TOP_N', 'EACH_GROUP_BOTTOM_N'].includes(this.type);
    },
    get totalTake() {
        return ['CROSS_GROUP_POSITION_TOP_N', 'CROSS_GROUP_POSITION_BOTTOM_N',
                'BEST_REMAINING', 'WORST_REMAINING'].includes(this.type);
    },
    get usesTake() {
        return this.perGroupTake || this.totalTake;
    },
    get usesFrom() {
        return ['EACH_GROUP_POSITION', 'EACH_GROUP_RANGE',
                'CROSS_GROUP_POSITION_TOP_N', 'CROSS_GROUP_POSITION_BOTTOM_N',
                'SPECIFIC_GROUP_POSITION', 'SPECIFIC_GROUP_RANGE'].includes(this.type);
    },
    get usesTo() {
        return ['EACH_GROUP_RANGE', 'SPECIFIC_GROUP_RANGE'].includes(this.type);
    },
    get usesGroup() {
        return ['SPECIFIC_GROUP_POSITION', 'SPECIFIC_GROUP_RANGE'].includes(this.type);
    },

    /* Cuanta gente sale de verdad con lo escrito */
    get reach() {
        if (!this.groups || !this.groupSize) {
            return null;
        }

        const n = Math.max(0, parseInt(this.take) || 0);

        if (this.perGroupTake) {
            return {
                perGroup: true,
                total: Math.min(n, this.groupSize) * this.groups,
                everyone: n >= this.groupSize,
            };
        }

        if (this.totalTake) {
            return { perGroup: false, total: n, everyone: false };
        }

        if (['EACH_GROUP_POSITION'].includes(this.type)) {
            const p = Math.max(0, parseInt(this.from) || 0);

            return {
                perGroup: true,
                total: p > this.groupSize ? 0 : this.groups,
                everyone: false,
                impossible: p > this.groupSize,
            };
        }

        return null;
    },
}">

    {{-- QUE CRITERIO --}}

    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Quién sale por aquí
        </label>

        <select name="rule_type" x-model="type"
            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
            @foreach ($families as $family => $definitions)
                <optgroup label="{{ $family }}">
                    @foreach ($definitions as $value => $definition)
                        <option value="{{ $value }}">{{ $definition['label'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>

        @foreach ($ruleTypes as $value => $definition)
            <p x-show="type === @js($value)" x-cloak
                class="mt-1.5 text-[10px] leading-relaxed text-slate-500">
                {{ $definition['description'] }}
            </p>
        @endforeach
    </div>


    {{-- CUANTOS --}}

    <div class="grid gap-3 sm:grid-cols-2">

        <div x-show="usesTake" x-cloak>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                <span x-show="perGroupTake">Cuántos de cada grupo</span>
                <span x-show="totalTake" x-cloak>Cuántos en total</span>
            </label>

            <input type="number" name="take" min="1" max="512" x-model="take"
                :disabled="!usesTake"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
        </div>

        <div x-show="usesFrom" x-cloak>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                <span x-show="usesTo">Desde el puesto</span>
                <span x-show="!usesTo" x-cloak>Puesto</span>
            </label>

            <input type="number" name="position_from" min="1" max="512" x-model="from"
                :disabled="!usesFrom"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
        </div>

        <div x-show="usesTo" x-cloak>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Hasta el puesto
            </label>

            <input type="number" name="position_to" min="1" max="512"
                value="{{ old('position_to', $editing ? $advancementRule->position_to : '') }}"
                :disabled="!usesTo"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
        </div>

        <div x-show="usesGroup" x-cloak>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                De qué grupo
            </label>

            <select name="phase_group_stage_group_id" :disabled="!usesGroup"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                @foreach ($groupDefinitions as $definition)
                    <option value="{{ $definition->id }}"
                        @selected((int) old('phase_group_stage_group_id', $editing ? $advancementRule->phase_group_stage_group_id : 0) === $definition->id)>
                        {{ $definition->name }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>


    {{-- LA CUENTA, HECHA EN VOZ ALTA --}}

    <template x-if="reach">
        <p class="rounded-xl px-3 py-2 text-[11px] font-bold leading-relaxed"
            :class="reach.everyone || reach.impossible
                ? 'bg-red-50 text-red-700'
                : 'bg-violet-50 text-violet-700'">

            <template x-if="perGroupTake">
                <span>
                    <span x-text="take"></span> de cada uno de los
                    <span x-text="groups"></span> grupos de
                    <span x-text="groupSize"></span>
                    =
                    <strong x-text="reach.total"></strong>
                    de {{ $scaleParticipants }}.
                </span>
            </template>

            <template x-if="totalTake">
                <span>
                    Aquí la cantidad es el total:
                    <strong x-text="reach.total"></strong>
                    de {{ $scaleParticipants }}.
                </span>
            </template>

            <template x-if="type === 'EACH_GROUP_POSITION'">
                <span>
                    El puesto <span x-text="from"></span> de cada uno de los
                    <span x-text="groups"></span> grupos =
                    <strong x-text="reach.total"></strong>
                    de {{ $scaleParticipants }}.
                </span>
            </template>

            <span x-show="reach.everyone" x-cloak class="mt-1 block font-black">
                Salen todos: este criterio no dejaría a nadie fuera.
            </span>

            <span x-show="reach.impossible" x-cloak class="mt-1 block font-black">
                Ese puesto no existe: los grupos tienen {{ $scaleSmallest }}.
            </span>
        </p>
    </template>

</div>
