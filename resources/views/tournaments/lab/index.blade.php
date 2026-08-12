<x-tournament-layout>

    <x-slot name="header">
        Competition Lab
    </x-slot>


    <section
        class="
            relative
            overflow-hidden
            rounded-[32px]
            bg-slate-950
            p-7
            text-white
            sm:p-9
        ">

        <div
            class="
                pointer-events-none
                absolute
                -right-20
                -top-20
                h-72
                w-72
                rounded-full
                bg-amber-400/10
                blur-3xl
            ">
        </div>


        <div class="
                relative
                max-w-3xl
            ">

            <div
                class="
                    inline-flex
                    items-center
                    gap-2
                    rounded-full
                    bg-amber-400/10
                    px-4
                    py-2
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-amber-300
                ">
                ⚗ Entorno temporal
            </div>


            <h2
                class="
                    mt-5
                    text-3xl
                    font-black
                    sm:text-4xl
                ">
                Competition Lab
            </h2>


            <p
                class="
                    mt-4
                    text-sm
                    leading-7
                    text-slate-400
                ">
                Este será el espacio donde podremos probar una
                plantilla utilizando Entidades de Biblioteca o
                competidores ficticios sin generar estadísticas,
                historial ni competiciones persistentes.
            </p>

        </div>

    </section>


    <section
        class="
            mt-6
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-6
        ">

        <p
            class="
                text-xs
                font-black
                uppercase
                tracking-wider
                text-amber-600
            ">
            Seleccionar plantilla
        </p>


        <form method="GET"
            class="
                mt-4
                flex
                flex-col
                gap-3
                sm:flex-row
            ">

            <select name="template"
                class="
                    flex-1
                    rounded-xl
                    border-slate-300
                    focus:border-amber-400
                    focus:ring-amber-400
                ">

                <option value="">
                    Selecciona una plantilla...
                </option>


                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected($selectedTemplate?->id === $template->id)>
                        {{ $template->name }}
                        ·
                        {{ $template->phases_count }}
                        fases
                    </option>
                @endforeach

            </select>


            <button
                class="
                    rounded-xl
                    bg-slate-950
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                ">
                Cargar
            </button>

        </form>

    </section>


    @if ($selectedTemplate)

        <section
            class="
                mt-6
                grid
                gap-5
                lg:grid-cols-[1fr_340px]
            ">

            <div
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                ">

                <p
                    class="
                        font-mono
                        text-[9px]
                        font-black
                        text-slate-400
                    ">
                    {{ $selectedTemplate->code }}
                </p>


                <h3
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    {{ $selectedTemplate->name }}
                </h3>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    {{ $selectedTemplate->participant_range_label }}
                    ·
                    {{ $selectedTemplate->phases->count() }}
                    fases
                </p>


                <div class="
                        mt-6
                        space-y-3
                    ">

                    @forelse ($selectedTemplate->phases as $phase)
                        <div
                            class="
                                flex
                                items-center
                                gap-4
                                rounded-2xl
                                bg-slate-50
                                p-4
                            ">

                            <div
                                class="
                                    flex
                                    h-10
                                    w-10
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-xl
                                    bg-amber-100
                                    text-sm
                                    font-black
                                    text-amber-700
                                ">
                                {{ $loop->iteration }}
                            </div>


                            <div
                                class="
                                    min-w-0
                                    flex-1
                                ">

                                <p
                                    class="
                                        text-sm
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $phase->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-xs
                                        text-slate-400
                                    ">
                                    {{ $phase->type_label }}
                                </p>

                            </div>

                        </div>

                    @empty

                        <div
                            class="
                                rounded-2xl
                                border
                                border-dashed
                                border-slate-200
                                bg-slate-50
                                p-6
                                text-center
                            ">

                            <div class="
                                text-3xl
                            ">
                                ⌘
                            </div>


                            <p
                                class="
                                    mt-3
                                    text-sm
                                    font-black
                                    text-slate-700
                                ">
                                Esta plantilla todavía no tiene fases
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-xs
                                    text-slate-400
                                ">
                                Agrega al menos una fase antes de comenzar
                                a probar su estructura.
                            </p>


                            <a href="{{ route('tournaments.phases.create', $selectedTemplate) }}"
                                class="
                                    mt-4
                                    inline-flex
                                    rounded-xl
                                    bg-amber-500
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-white
                                ">
                                + Agregar fase
                            </a>

                        </div>
                    @endforelse

                </div>

            </div>


            <aside
                class="
                    rounded-3xl
                    border
                    border-dashed
                    border-amber-300
                    bg-amber-50
                    p-6
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-amber-700
                    ">
                    Próxima etapa
                </p>


                <h4
                    class="
                        mt-3
                        text-lg
                        font-black
                        text-amber-950
                    ">
                    Participantes temporales
                </h4>


                <p
                    class="
                        mt-3
                        text-sm
                        leading-6
                        text-amber-800
                    ">
                    En el siguiente Sprint este panel permitirá
                    utilizar Entidades de tu Biblioteca y crear
                    competidores ficticios.
                </p>


                <div
                    class="
                        mt-5
                        space-y-2
                        text-xs
                        font-semibold
                        text-amber-800
                    ">

                    <p>✓ Entidades de Biblioteca</p>

                    <p>✓ Participantes ficticios</p>

                    <p>✓ Generación de llaves</p>

                    <p>✓ Resultado manual</p>

                    <p>✓ Random Test Resolver</p>

                </div>


                <div
                    class="
                        mt-6
                        rounded-xl
                        bg-white/70
                        p-4
                        text-xs
                        leading-5
                        text-amber-900
                    ">
                    Nada de lo probado aquí se guardará
                    como historial oficial del Universo.
                </div>

            </aside>

        </section>
    @else
        <div
            class="
                mt-6
                rounded-3xl
                border
                border-dashed
                border-slate-300
                bg-white
                p-12
                text-center
            ">

            <div class="
                    text-5xl
                ">
                ⚗
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                Selecciona una plantilla
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Aquí podrás revisar su estructura antes
                de empezar las pruebas temporales.
            </p>

        </div>

    @endif

</x-tournament-layout>
