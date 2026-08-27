@php
    /*
     * LA FICHA DE UNA FASE
     *
     * Lo que ves al entrar en una fase, para los motores que tienen Super
     * Edición. Es la fase PRESENTADA, no editada: portada, configuración
     * contada en frases, la estructura entera con su simulación, las
     * aduanas de entrada y salida, y todos los enfrentamientos abajo.
     *
     * Se lee de arriba abajo como una respuesta:
     *
     *   qué es          portada, cifras
     *   cómo se juega   configuración
     *   qué forma tiene estructura, y se puede simular
     *   con qué conecta entradas y salidas
     *   qué se juega    enfrentamientos
     *
     * ---------------------------------------------------------------
     *
     * La estructura y los enfrentamientos NO se dibujan aquí: se incluyen
     * las MISMAS vistas de la Super Edición. Un cuadro de eliminación
     * directa es difícil de dibujar bien y ya estaba dibujado; tener una
     * segunda versión «de solo lectura» habría significado arreglar cada
     * fallo dos veces y verlas separarse con el tiempo.
     *
     * Lo único que sobra de esas vistas son los controles que CAMBIAN la
     * configuración —reordenar la parrilla a mano, activar un grupo de
     * puestos—, porque aquí no hay botón de guardar. Van escondidos tras
     * `readonly`, que es una bandera del componente y no una copia del
     * archivo.
     *
     * Simular sí se queda: los resultados inventados nunca se guardaron, ni
     * aquí ni en el editor, y ver cómo se llena el cuadro es media razón de
     * que esta pantalla exista.
     *
     * Lo que no aparece en ningún sitio: el formato de batalla. Cuántos
     * juegos tiene un enfrentamiento pertenece al torneo real.
     */
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">{{ $phaseTemplate->name }}</x-slot>


    @include('tournaments.phase-templates.partials.workspace-navigation', [
        'phaseTemplate' => $phaseTemplate,
        'current' => 'overview',
        'dark' => true,
    ])


    @include('tournaments.phase-templates.dossier.cover', [
        'phaseTemplate' => $phaseTemplate,
        'typeIcon' => $typeIcon,
        'typeAccent' => $typeAccent,
        'figures' => $figures,
    ])


    {{--
        De aquí para abajo todo vive dentro del mismo componente: la
        estructura y los enfrentamientos comparten los resultados
        simulados, así que simular arriba tiene que verse abajo.
    --}}

    <div x-data="phaseSuperEditor({
            engine: @js($clientEngine),
            payload: @js($payload),
            previewUrl: @js(route('tournaments.phase-templates.super.preview', $phaseTemplate)),
            readonly: true,
        })">


        @include('tournaments.phase-templates.dossier.configuration', [
            'summary' => $summary,
        ])


        {{-- ============ LA ESTRUCTURA ============ --}}

        <section class="mb-4">

            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="h-3 w-1 rounded-full bg-slate-600"></span>

                <h2 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
                    La estructura
                </h2>

                <span class="text-[10px] text-slate-600">— y cómo quedaría si se jugara</span>

                <div class="ml-auto flex items-center gap-1.5">
                    <template x-if="hasResults">
                        <span class="rounded bg-amber-500/15 px-2 py-0.5 text-[9px] font-black text-amber-300">
                            <span x-text="playedCount"></span> jugados
                        </span>
                    </template>

                    <button type="button" @click="clearResults()" x-show="hasResults" x-cloak
                        class="rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-400">
                        Limpiar
                    </button>
                </div>
            </div>

            {{--
                Los competidores son caras prestadas de tus universos y tu
                biblioteca: no son inscritos y nada de lo que se simule aquí
                se guarda.
            --}}
            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">
                @include($stageView)
            </div>

        </section>


        @include('tournaments.phase-templates.dossier.flow', [
            'payload' => $payload,
        ])


        {{-- ============ LOS ENFRENTAMIENTOS ============ --}}

        {{--
            La vista de jornadas nace pegada al fondo de la Super Edición
            —de ahí su `border-t` y su falta de esquinas—, así que aquí se
            envuelve para que sea una tarjeta más.
        --}}
        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40 [&>section]:border-t-0">
            @include($scheduleView)
        </div>

    </div>

</x-tournament-layout>
