@php
    /*
     * Alta de una puerta de salida, con su criterio en el mismo paso.
     *
     * Una puerta sin criterio no la cruza nadie y un criterio sin puerta no
     * lleva a ningún sitio: separarlos solo servía para poder dejar la
     * mitad hecha y descubrirlo jugando.
     *
     * Editar es otra cosa y va en otro formulario: una puerta ya creada
     * puede tener VARIOS criterios, así que cambiar «el» criterio no
     * significaría nada. Se edita el nombre de la puerta por un lado y cada
     * criterio por el suyo.
     *
     * No se pregunta cuándo se cruza: en una fase de grupos la clasificación
     * no es firme hasta el final, así que siempre es al terminar.
     *
     * groupBy REINDEXA salvo que se le pida lo contrario, y aquí la clave es
     * el value del <option>: sin el segundo argumento el desplegable mandaba
     * rule_type="0" y la salida no se creaba nunca.
     */

    $families = collect($payload['catalog']['rule_types'])
        ->groupBy(fn($definition) => $definition['family'] ?? 'Otros', true);
@endphp

<form method="POST"
    action="{{ route('tournaments.phase-templates.super.exits.store', $phaseTemplate) }}"
    class="space-y-2"
    x-data="exitCriterionFields('EACH_GROUP_TOP_N')">

    @csrf

    @include('tournaments.phase-templates.super.partials.preview-state')

    <input type="text" name="name" required maxlength="120" value=""
        placeholder="Nombre de la salida"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-violet-500 focus:ring-violet-500">

    @include('tournaments.phase-templates.super.group-stage.criterion-fields', [
        'families' => $families,
    ])

    <button class="w-full rounded-md bg-violet-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
        Crear salida
    </button>

</form>
