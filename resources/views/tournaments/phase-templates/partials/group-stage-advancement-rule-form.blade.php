@php
    /*
     * Alta o edicion de un criterio de clasificacion.
     *
     * Se usa dentro de la tarjeta de una puerta de salida, asi que casi
     * siempre la puerta ya se sabe y no hay que preguntarla: se pasa en
     * $lockedExit y viaja en un campo oculto. El desplegable de puertas
     * solo aparece cuando de verdad hay algo que elegir.
     *
     * $phaseTemplate     la fase
     * $advancementRule   el criterio que se edita, o null
     * $lockedExit        la puerta a la que pertenece, o null
     */

    $editingRule = isset($advancementRule) && $advancementRule;

    $lockedExit = $lockedExit ?? null;

    $action = $editingRule
        ? route('tournaments.group-stage.advancement-rules.update', [$phaseTemplate, $advancementRule])
        : route('tournaments.group-stage.advancement-rules.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">

    @csrf

    @if ($editingRule)
        @method('PUT')
    @endif


    {{-- POR QUE PUERTA SALE --}}

    @if ($lockedExit)
        <input type="hidden" name="phase_exit_id" value="{{ $lockedExit->id }}">
    @else
        <div>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Puerta de salida
            </label>

            <select name="phase_exit_id"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                @foreach ($phaseExits as $exit)
                    <option value="{{ $exit->id }}"
                        @selected((int) old('phase_exit_id', $editingRule ? $advancementRule->phase_exit_id : 0) === $exit->id)>
                        {{ $exit->name }} · {{ $exit->code }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif


    @include('tournaments.phase-templates.partials.group-stage-rule-fields', [
        'phaseTemplate' => $phaseTemplate,
        'ruleTypes' => $ruleTypes,
        'groupDefinitions' => $activeGroupDefinitions ?? $groupDefinitions,
        'advancementRule' => $editingRule ? $advancementRule : null,
    ])


    <div>
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Estado</label>

        <select name="status"
            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
            <option value="ACTIVE" @selected(old('status', $editingRule ? $advancementRule->status : 'ACTIVE') === 'ACTIVE')>
                Activo
            </option>

            <option value="INACTIVE" @selected(old('status', $editingRule ? $advancementRule->status : 'ACTIVE') === 'INACTIVE')>
                Inactivo
            </option>
        </select>

        <p class="mt-1 text-[10px] text-slate-500">
            Un criterio inactivo se conserva pero no saca a nadie.
        </p>
    </div>


    <button type="submit"
        class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-violet-700">
        {{ $editingRule ? 'Guardar criterio' : '+ Añadir criterio' }}
    </button>

</form>
