<x-tournament-layout>

    <x-slot name="header">
        Fases
    </x-slot>


    @include('tournaments.partials.template-navigation')


    <div
        class="
            flex
            flex-col
            justify-between
            gap-5
            sm:flex-row
            sm:items-start
        ">

        <div>

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-amber-600
                ">
                {{ $tournamentTemplate->code }}
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Fases de {{ $tournamentTemplate->name }}
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Una plantilla se construye como una composición
                ordenada de fases.
            </p>

        </div>


        <a href="{{ route('tournaments.phases.create', $tournamentTemplate) }}"
            class="
                rounded-xl
                bg-amber-500
                px-5
                py-3
                text-sm
                font-black
                text-white
            ">
            + Agregar fase
        </a>

    </div>


    @if ($tournamentTemplate->phases->isEmpty())

        <div
            class="
                mt-8
                rounded-3xl
                border
                border-dashed
                border-amber-300
                bg-white
                p-12
                text-center
            ">

            <div class="
                    text-5xl
                ">
                ⌘
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                Empieza a construir el torneo
            </h3>


            <p
                class="
                    mx-auto
                    mt-2
                    max-w-lg
                    text-sm
                    leading-6
                    text-slate-500
                ">
                La primera fase disponible será Eliminación directa.
                Luego incorporaremos grupos, Round Robin, Suizo
                y otros formatos.
            </p>


            <a href="{{ route('tournaments.phases.create', $tournamentTemplate) }}"
                class="
                    mt-6
                    inline-flex
                    rounded-xl
                    bg-amber-500
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                ">
                Crear primera fase
            </a>

        </div>
    @else
        <div class="
                relative
                mt-8
                space-y-4
            ">

            @foreach ($tournamentTemplate->phases as $phase)
                <article x-data="{
                    deleting: false
                }"
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                    ">

                    <div
                        class="
                            flex
                            flex-col
                            gap-4
                            sm:flex-row
                            sm:items-center
                        ">

                        <div
                            class="
                                flex
                                h-14
                                w-14
                                shrink-0
                                items-center
                                justify-center
                                rounded-2xl
                                bg-gradient-to-br
                                from-amber-400
                                to-orange-500
                                text-xl
                                font-black
                                text-white
                            ">
                            {{ $loop->iteration }}
                        </div>


                        <div
                            class="
                                min-w-0
                                flex-1
                            ">

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-2
                                ">

                                <h3
                                    class="
                                        font-black
                                        text-slate-900
                                    ">
                                    {{ $phase->name }}
                                </h3>


                                <span
                                    class="
                                        rounded-full
                                        bg-amber-100
                                        px-2.5
                                        py-1
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-amber-700
                                    ">
                                    {{ $phase->type_label }}
                                </span>

                            </div>


                            <p
                                class="
                                    mt-2
                                    text-xs
                                    text-slate-500
                                ">
                                {{ $phase->input_participants ?: '?' }}
                                entran

                                ·

                                {{ $phase->qualifiers_count ?: '?' }}
                                clasifican

                                ·

                                Best of
                                {{ $phase->best_of }}

                                ·

                                {{ $phase->allow_byes ? 'BYE' : 'Sin BYE' }}
                            </p>


                            @if ($phase->description)
                                <p
                                    class="
                                        mt-2
                                        text-sm
                                        text-slate-400
                                    ">
                                    {{ $phase->description }}
                                </p>
                            @endif

                        </div>


                        <div
                            class="
                                flex
                                items-center
                                gap-2
                            ">

                            <a href="{{ route('tournaments.phases.edit', [$tournamentTemplate, $phase]) }}"
                                class="
                                    rounded-xl
                                    border
                                    border-slate-200
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-slate-600
                                ">
                                Editar
                            </a>


                            <button type="button"
                                @click="
                                    deleting = !deleting
                                "
                                class="
                                    rounded-xl
                                    bg-red-50
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-red-600
                                ">
                                Eliminar
                            </button>

                        </div>

                    </div>


                    <div x-show="
                            deleting
                        "
                        style="
                            display: none;
                        " x-transition
                        class="
                            mt-4
                            flex
                            flex-col
                            justify-between
                            gap-3
                            rounded-2xl
                            border
                            border-red-200
                            bg-red-50
                            p-4
                            sm:flex-row
                            sm:items-center
                        ">

                        <p
                            class="
                                text-sm
                                font-semibold
                                text-red-700
                            ">
                            ¿Eliminar esta fase?
                        </p>


                        <div
                            class="
                                flex
                                gap-2
                            ">

                            <button type="button"
                                @click="
                                    deleting = false
                                "
                                class="
                                    rounded-lg
                                    border
                                    border-red-200
                                    bg-white
                                    px-3
                                    py-2
                                    text-xs
                                    font-black
                                    text-red-600
                                ">
                                Cancelar
                            </button>


                            <form method="POST"
                                action="{{ route('tournaments.phases.destroy', [$tournamentTemplate, $phase]) }}">

                                @csrf

                                @method('DELETE')


                                <button type="submit"
                                    class="
                                        rounded-lg
                                        bg-red-600
                                        px-3
                                        py-2
                                        text-xs
                                        font-black
                                        text-white
                                    ">
                                    Sí, eliminar
                                </button>

                            </form>

                        </div>

                    </div>

                </article>
            @endforeach

        </div>

    @endif

</x-tournament-layout>
