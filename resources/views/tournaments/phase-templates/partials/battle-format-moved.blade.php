{{--
    El formato de batalla ya no se edita en la plantilla.

    Cuántos juegos dura un enfrentamiento no describe la FORMA de un torneo
    —cuántas rondas tiene, cómo se cruzan sus puestos— sino cómo se juega una
    edición concreta. La misma Copa puede ser al mejor de 3 este año y al
    mejor de 5 el siguiente sin que su plantilla cambie una coma.

    Se decide al crear una competición, y ahí puede además ponerse una
    excepción por fase: «todo al mejor de 3, menos la final».

    Las columnas siguen en la base de datos y el motor las lee como valor por
    defecto; lo que se ha quitado es la posibilidad de editarlas aquí.
--}}

<div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">

    <div class="flex items-start gap-3">

        <span class="text-lg">⚔</span>

        <div class="min-w-0">
            <p class="text-sm font-black text-slate-700">
                El formato de batalla se decide en la competición
            </p>

            <p class="mt-1 text-xs leading-relaxed text-slate-500">
                Al mejor de N o enfrentamiento fijo dependen de la edición que
                se juegue, no de la forma del torneo. Se eligen al crear una
                competición dentro de un universo, y ahí puedes poner una
                excepción para una fase concreta.
            </p>
        </div>

    </div>

</div>
