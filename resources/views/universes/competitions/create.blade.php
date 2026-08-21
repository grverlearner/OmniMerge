<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Nueva competición
    </x-slot>


    <div class="mx-auto max-w-5xl">

        <div class="mb-7">

            <a href="{{ route('universes.tournaments.show', [$universe, $universeTournament]) }}"
                class="
                    text-xs
                    font-black
                    text-slate-400
                    hover:text-violet-600
                ">
                ← {{ $universeTournament->name }}
            </a>


            <p
                class="
                    mt-5
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-600
                ">
                {{ $universe->name }}
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Iniciar una competición
            </h2>


            <p
                class="
                    mt-2
                    max-w-3xl
                    text-slate-500
                ">
                Al crearla se congela la configuración del torneo. A partir
                de ese momento, editar la plantilla o sus fases no afectará
                a esta competición.
            </p>

        </div>


        @if ($graphErrors)

            <section
                class="
                    rounded-3xl
                    border
                    border-red-200
                    bg-red-50
                    p-6
                ">

                <p class="font-black text-red-900">
                    El Tournament Graph tiene problemas sin resolver
                </p>


                <p class="mt-2 text-xs text-red-700">
                    No se puede iniciar una competición hasta corregirlos en
                    el diseño de la plantilla.
                </p>


                <div class="mt-4 space-y-2">
                    @foreach ($graphErrors as $problem)
                        <p class="rounded-xl bg-white px-4 py-3 text-xs text-red-800">
                            {{ $problem['message'] ?? '' }}
                        </p>
                    @endforeach
                </div>


                @if ($template)
                    <a href="{{ route('tournaments.graph.show', $template) }}"
                        class="
                            mt-5
                            inline-flex
                            rounded-xl
                            bg-red-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Abrir Tournament Graph
                    </a>
                @endif

            </section>

        @elseif ($competitors->isEmpty())

            <section
                class="
                    rounded-3xl
                    border
                    border-dashed
                    border-slate-300
                    bg-white
                    p-12
                    text-center
                ">

                <div class="text-5xl">✦</div>

                <h3 class="mt-4 text-xl font-black text-slate-900">
                    Este Universo no tiene competidores activos
                </h3>

                <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                    Incorpora entidades de tu Biblioteca al Universo antes
                    de jugar una competición.
                </p>

                <a href="{{ route('universes.competitors.create', $universe) }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-violet-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    + Añadir competidores
                </a>

            </section>

        @else

            <form method="POST" action="{{ route('universes.competitions.store', $universe) }}"
                x-data="{
                    assigned: {},

                    recount() {
                        const next = {};

                        this.$el
                            .querySelectorAll('[data-start]')
                            .forEach((box) => {
                                next[box.dataset.start] =
                                    box.querySelectorAll('input:checked').length;
                            });

                        this.assigned = next;
                    },

                    get total() {
                        return Object
                            .values(this.assigned)
                            .reduce((sum, value) => sum + value, 0);
                    }
                }"
                x-init="recount()"
                @change="recount()">

                @csrf

                <input type="hidden" name="universe_tournament_id"
                    value="{{ $universeTournament->id }}">


                {{-- IDENTIDAD --}}

                <section
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-violet-600
                        ">
                        01 · Identidad
                    </p>


                    <div
                        class="
                            mt-5
                            grid
                            gap-5
                            md:grid-cols-2
                        ">

                        <div>

                            <label
                                class="
                                    text-xs
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                Nombre de la competición *
                            </label>


                            <input type="text" name="name"
                                value="{{ old('name', $universeTournament->name) }}"
                                placeholder="Ej. Exámenes Chunin · Temporada 3"
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-slate-900
                                    focus:border-violet-400
                                    focus:ring-violet-400
                                ">


                            <x-input-error :messages="$errors->get('name')" class="mt-2" />

                        </div>


                        <div>

                            <label
                                class="
                                    text-xs
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                Temporada
                            </label>


                            <select name="universe_season_id"
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    focus:border-violet-400
                                    focus:ring-violet-400
                                ">

                                <option value="">
                                    Sin temporada
                                </option>

                                @foreach ($seasons as $season)
                                    <option value="{{ $season->id }}"
                                        @selected(old('universe_season_id', $activeSeason?->id) == $season->id)>
                                        Temporada {{ $season->number }} · {{ $season->name }}
                                    </option>
                                @endforeach

                            </select>


                            <p class="mt-2 text-xs text-slate-400">
                                Solo informativa: queda registrada para saber
                                cuándo ocurrió.
                            </p>


                            <x-input-error :messages="$errors->get('universe_season_id')" class="mt-2" />

                        </div>

                    </div>

                </section>


                {{-- PARTICIPANTES --}}

                <section
                    class="
                        mt-6
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                    ">

                    <div
                        class="
                            flex
                            flex-wrap
                            items-center
                            justify-between
                            gap-3
                        ">

                        <div>
                            <p
                                class="
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-violet-600
                                ">
                                02 · Competidores
                            </p>


                            <h3
                                class="
                                    mt-2
                                    text-xl
                                    font-black
                                    text-slate-900
                                ">
                                ¿Quién entra por cada puerta?
                            </h3>
                        </div>


                        <span
                            class="
                                rounded-xl
                                bg-violet-50
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-violet-700
                            ">
                            <span x-text="total">0</span> seleccionados
                        </span>

                    </div>


                    <p
                        class="
                            mt-3
                            text-sm
                            text-slate-500
                        ">
                        Sus nombres se congelan ahora. Si más adelante
                        cambias un competidor en el Universo, esta
                        competición conservará los datos con los que jugó.
                    </p>


                    <x-input-error :messages="$errors->get('assignments')" class="mt-3" />


                    @if ($starts->isEmpty())
                        <p
                            class="
                                mt-5
                                rounded-xl
                                bg-red-50
                                px-4
                                py-3
                                text-sm
                                text-red-700
                            ">
                            Esta plantilla no tiene puntos de entrada activos.
                        </p>
                    @endif


                    <div class="mt-6 space-y-6">

                        @foreach ($starts as $start)
                            <div
                                data-start="{{ $start->id }}"
                                class="
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    p-5
                                ">

                                <div
                                    class="
                                        flex
                                        flex-wrap
                                        items-center
                                        justify-between
                                        gap-3
                                    ">

                                    <div>
                                        <p
                                            class="
                                                text-sm
                                                font-black
                                                text-slate-900
                                            ">
                                            ▶ {{ $start->name }}
                                        </p>

                                        <p
                                            class="
                                                mt-0.5
                                                font-mono
                                                text-[9px]
                                                text-slate-400
                                            ">
                                            {{ $start->code }}
                                        </p>
                                    </div>


                                    <span
                                        class="
                                            rounded-full
                                            bg-slate-100
                                            px-3
                                            py-1
                                            text-[10px]
                                            font-black
                                            text-slate-600
                                        ">
                                        <span x-text="assigned['{{ $start->id }}'] ?? 0">0</span>
                                        asignados
                                    </span>

                                </div>


                                <div
                                    class="
                                        mt-4
                                        grid
                                        max-h-72
                                        gap-2
                                        overflow-y-auto
                                        pr-1
                                        sm:grid-cols-2
                                        lg:grid-cols-3
                                    ">

                                    @foreach ($competitors as $competitor)
                                        <label
                                            class="
                                                flex
                                                cursor-pointer
                                                items-center
                                                gap-3
                                                rounded-xl
                                                border-2
                                                border-slate-200
                                                p-2.5
                                                transition
                                                has-[:checked]:border-violet-500
                                                has-[:checked]:bg-violet-50
                                            ">

                                            <input type="checkbox"
                                                name="assignments[{{ $start->id }}][]"
                                                value="{{ $competitor->id }}"
                                                @checked(in_array((string) $competitor->id, (array) (old('assignments')[$start->id] ?? []), true))
                                                class="
                                                    shrink-0
                                                    rounded
                                                    text-violet-600
                                                    focus:ring-violet-500
                                                ">


                                            <div
                                                class="
                                                    flex
                                                    h-8
                                                    w-8
                                                    shrink-0
                                                    items-center
                                                    justify-center
                                                    overflow-hidden
                                                    rounded-lg
                                                    bg-violet-100
                                                    text-violet-500
                                                ">

                                                @if ($competitor->entity?->image_url)
                                                    <img src="{{ $competitor->entity->image_url }}"
                                                        alt="{{ $competitor->display_label }}"
                                                        class="h-full w-full object-cover">
                                                @else
                                                    ✦
                                                @endif

                                            </div>


                                            <span
                                                class="
                                                    truncate
                                                    text-[11px]
                                                    font-black
                                                    text-slate-700
                                                ">
                                                {{ $competitor->display_label }}
                                            </span>

                                        </label>
                                    @endforeach

                                </div>

                            </div>
                        @endforeach

                    </div>

                </section>


                <div
                    class="
                        mt-6
                        flex
                        flex-col-reverse
                        gap-3
                        sm:flex-row
                        sm:justify-end
                    ">

                    <a href="{{ route('universes.tournaments.show', [$universe, $universeTournament]) }}"
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-5
                            py-3
                            text-center
                            text-sm
                            font-black
                            text-slate-600
                        ">
                        Cancelar
                    </a>


                    <button type="submit"
                        class="
                            rounded-xl
                            bg-violet-600
                            px-6
                            py-3
                            text-sm
                            font-black
                            text-white
                            shadow-lg
                            shadow-violet-600/20
                            transition
                            hover:bg-violet-700
                        ">
                        Preparar competición
                    </button>

                </div>

            </form>
        @endif

    </div>

</x-universe-layout>
