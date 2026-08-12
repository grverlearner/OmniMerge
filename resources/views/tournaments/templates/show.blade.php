<x-tournament-layout>

    <x-slot name="header">
        {{ $tournamentTemplate->name }}
    </x-slot>


    @include('tournaments.partials.template-navigation')


    {{-- HERO --}}

    <section
        class="
            overflow-hidden
            rounded-3xl
            border
            border-slate-200
            bg-white
        ">

        <div class="
                grid
                lg:grid-cols-[340px_1fr]
            ">

            <div
                class="
                    min-h-[280px]
                    bg-gradient-to-br
                    from-slate-950
                    via-slate-900
                    to-amber-950
                ">

                @if ($tournamentTemplate->image_url)
                    <img src="{{ $tournamentTemplate->image_url }}" alt="{{ $tournamentTemplate->name }}"
                        class="
                            h-full
                            min-h-[280px]
                            w-full
                            object-cover
                        ">
                @else
                    <div
                        class="
                            flex
                            h-full
                            min-h-[280px]
                            items-center
                            justify-center
                            text-7xl
                        ">
                        🏆
                    </div>
                @endif

            </div>


            <div class="
                    p-7
                    sm:p-8
                ">

                <div
                    class="
                        flex
                        flex-wrap
                        items-center
                        gap-2
                    ">

                    <span
                        class="
                            rounded-full
                            bg-slate-100
                            px-3
                            py-1
                            font-mono
                            text-[9px]
                            font-black
                            text-slate-500
                        ">
                        {{ $tournamentTemplate->code }}
                    </span>


                    <span
                        class="
                            rounded-full
                            px-3
                            py-1
                            text-[9px]
                            font-black
                            uppercase

                            {{ $tournamentTemplate->status === 'ACTIVE'
                                ? 'bg-emerald-100 text-emerald-700'
                                : ($tournamentTemplate->status === 'DRAFT'
                                    ? 'bg-amber-100 text-amber-700'
                                    : 'bg-slate-200 text-slate-600') }}
                        ">
                        {{ $tournamentTemplate->status_label }}
                    </span>


                    <span
                        class="
                            rounded-full
                            bg-violet-100
                            px-3
                            py-1
                            text-[9px]
                            font-black
                            uppercase
                            text-violet-700
                        ">
                        {{ $tournamentTemplate->visibility_label }}
                    </span>

                </div>


                <h2
                    class="
                        mt-5
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    {{ $tournamentTemplate->name }}
                </h2>


                <p
                    class="
                        mt-4
                        max-w-3xl
                        whitespace-pre-line
                        text-sm
                        leading-7
                        text-slate-500
                    ">
                    {{ $tournamentTemplate->description ?: 'Esta plantilla todavía no tiene descripción.' }}
                </p>


                <div
                    class="
                        mt-7
                        flex
                        flex-wrap
                        gap-3
                    ">

                    <a href="{{ route('tournaments.templates.edit', $tournamentTemplate) }}"
                        class="
                            rounded-xl
                            bg-amber-500
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Editar
                    </a>


                    <form method="POST"
                        action="{{ route('tournaments.templates.duplicate', $tournamentTemplate) }}">

                        @csrf


                        <button type="submit"
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            ⧉ Duplicar
                        </button>

                    </form>


                    @if ($tournamentTemplate->status !== 'ARCHIVED')
                        <form method="POST"
                            action="{{ route('tournaments.templates.archive', $tournamentTemplate) }}">

                            @csrf

                            @method('PATCH')


                            <button type="submit"
                                class="
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-white
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-slate-500
                                ">
                                Archivar
                            </button>

                        </form>
                    @endif

                </div>

            </div>

        </div>

    </section>


    {{-- INFO --}}

    <section
        class="
            mt-6
            grid
            gap-3
            sm:grid-cols-2
            lg:grid-cols-4
        ">

        @foreach ([
        [
            'label' => 'Participantes',
            'value' => $tournamentTemplate->participant_range_label,
        ],
        [
            'label' => 'Fases',
            'value' => $tournamentTemplate->phases_count,
        ],
        [
            'label' => 'BYE',
            'value' => $tournamentTemplate->allow_byes ? 'Permitido' : 'No permitido',
        ],
        [
            'label' => 'Clonación',
            'value' => $tournamentTemplate->allow_cloning ? 'Permitida' : 'Desactivada',
        ],
    ] as $item)
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    {{ $item['label'] }}
                </p>


                <p
                    class="
                        mt-2
                        text-sm
                        font-black
                        text-slate-800
                    ">
                    {{ $item['value'] }}
                </p>

            </article>
        @endforeach

    </section>


    {{-- FASES --}}

    <section class="
            mt-8
        ">

        <div
            class="
                flex
                items-end
                justify-between
                gap-4
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
                    Estructura
                </p>


                <h3
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Fases del torneo
                </h3>

            </div>


            <a href="{{ route('tournaments.phases.index', $tournamentTemplate) }}"
                class="
                    text-sm
                    font-black
                    text-amber-600
                ">
                Administrar fases →
            </a>

        </div>


        @if ($tournamentTemplate->phases->isEmpty())

            <div
                class="
                    mt-5
                    rounded-3xl
                    border
                    border-dashed
                    border-amber-300
                    bg-amber-50/50
                    p-8
                    text-center
                ">

                <div class="
                        text-4xl
                    ">
                    ⌘
                </div>


                <h4
                    class="
                        mt-3
                        font-black
                        text-slate-900
                    ">
                    La plantilla todavía no tiene fases
                </h4>


                <a href="{{ route('tournaments.phases.create', $tournamentTemplate) }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-amber-500
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-white
                    ">
                    + Crear primera fase
                </a>

            </div>
        @else
            <div class="
                    mt-5
                    space-y-3
                ">

                @foreach ($tournamentTemplate->phases as $phase)
                    <article
                        class="
                            flex
                            flex-col
                            gap-4
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            p-5
                            sm:flex-row
                            sm:items-center
                        ">

                        <div
                            class="
                                flex
                                h-12
                                w-12
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-amber-100
                                text-lg
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
                                    text-slate-900
                                ">
                                {{ $phase->name }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-xs
                                    text-slate-500
                                ">
                                {{ $phase->type_label }}

                                ·

                                {{ $phase->input_participants ?: '?' }}

                                participantes

                                →

                                {{ $phase->qualifiers_count ?: '?' }}

                                clasifican
                            </p>

                        </div>


                        <span
                            class="
                                rounded-full
                                bg-slate-100
                                px-3
                                py-1
                                font-mono
                                text-[9px]
                                font-black
                                text-slate-500
                            ">
                            {{ $phase->code }}
                        </span>

                    </article>
                @endforeach

            </div>

        @endif

    </section>


    {{-- DANGER ZONE --}}

    <section x-data="{
        deleting: false
    }"
        class="
            mt-10
            rounded-3xl
            border
            border-red-200
            bg-red-50
            p-6
        ">

        <div
            class="
                flex
                flex-col
                justify-between
                gap-4
                sm:flex-row
                sm:items-center
            ">

            <div>

                <p
                    class="
                        text-sm
                        font-black
                        text-red-800
                    ">
                    Eliminar plantilla
                </p>


                <p
                    class="
                        mt-1
                        text-xs
                        text-red-600
                    ">
                    Se aplicará Soft Delete y dejará de aparecer
                    en el módulo.
                </p>

            </div>


            <button type="button" @click="
                    deleting = true
                "
                class="
                    rounded-xl
                    bg-red-600
                    px-4
                    py-2.5
                    text-xs
                    font-black
                    text-white
                ">
                Eliminar
            </button>

        </div>


        <div x-show="
                deleting
            " x-transition
            class="
                mt-5
                rounded-2xl
                border
                border-red-200
                bg-white
                p-5
            "
            style="
                display: none;
            ">

            <p class="
                    font-black
                    text-slate-900
                ">
                ¿Eliminar “{{ $tournamentTemplate->name }}”?
            </p>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Esta acción eliminará lógicamente la plantilla.
            </p>


            <div class="
                    mt-4
                    flex
                    gap-3
                ">

                <button type="button" @click="
                        deleting = false
                    "
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
                    Cancelar
                </button>


                <form method="POST"
                    action="{{ route('tournaments.templates.destroy', $tournamentTemplate) }}">

                    @csrf

                    @method('DELETE')


                    <button type="submit"
                        class="
                            rounded-xl
                            bg-red-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Sí, eliminar
                    </button>

                </form>

            </div>

        </div>

    </section>

</x-tournament-layout>
