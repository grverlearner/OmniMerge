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

        @elseif ($universeEntities->isEmpty())

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

                <a href="{{ route('universes.entities.create', $universe) }}"
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

                    search: '',

                    typeFilter: 'ALL',

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
                    },

                    /*
                     * Un competidor es visible si coincide con la búsqueda
                     * y con el tipo. Se evalúa por ficha con x-show.
                     */
                    matches(haystack, typeId) {
                        const bySearch =
                            !this.search
                            ||
                            haystack.includes(this.search.toLowerCase());

                        const byType =
                            this.typeFilter === 'ALL'
                            ||
                            this.typeFilter === typeId;

                        return bySearch && byType;
                    },

                    selectVisible(startId, value) {
                        this.$el
                            .querySelector('[data-start=\'' + startId + '\']')
                            .querySelectorAll('[data-competitor]')
                            .forEach((card) => {
                                if (card.offsetParent === null) {
                                    return;
                                }

                                card.querySelector('input').checked = value;
                            });

                        this.recount();
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


                    {{-- ============ CON QUÉ FORMA SE JUEGA ============ --}}

                    {{--
                        Un torneo es una marca; su plantilla es la forma con
                        la que suele jugarse, no una condena.

                        Las temporadas cambian: la cuarta edición puede
                        necesitar una fase previa que la primera no tenía,
                        porque ahora se apunta el triple de gente. Por eso
                        esto se elige aquí, en cada competición, y no una
                        sola vez al crear el torneo.
                    --}}

                    <div class="mt-6 border-t border-slate-100 pt-6">

                        <label class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Con qué forma se juega
                        </label>

                        <select name="tournament_template_id"
                            class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

                            @foreach ($availableTemplates as $option)
                                <option value="{{ $option['id'] }}"
                                    @selected(old('tournament_template_id', $universeTournament->tournament_template_id) == $option['id'])>
                                    {{ $option['name'] }}
                                    @if ($option['is_default']) · la del torneo @endif
                                    · {{ $option['phases'] }} fases
                                </option>
                            @endforeach

                        </select>

                        <p class="mt-2 text-xs text-slate-400">
                            Por defecto, la del torneo. Cambiarla aquí no toca el
                            torneo ni las competiciones ya jugadas: esta edición se
                            congela con la forma que elijas.
                        </p>

                        <x-input-error :messages="$errors->get('tournament_template_id')" class="mt-2" />

                    </div>


                    {{-- ============ EL FORMATO DE BATALLA ============ --}}

                    {{--
                        Cuántos juegos dura un enfrentamiento se decide AQUÍ y
                        solo aquí.

                        No describe la forma del torneo —esa es la misma cada
                        año— sino cómo se juega esta edición. La misma Copa
                        puede ser al mejor de 3 este año y al mejor de 5 el
                        siguiente sin que su plantilla cambie una coma.
                    --}}

                    <div class="mt-6 border-t border-slate-100 pt-6"
                        x-data="{ formato: @js(old('series_format', 'BEST_OF')) }">

                        <label class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Formato de batalla
                        </label>

                        <div class="mt-2 grid gap-2 sm:grid-cols-2">

                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border p-3 transition"
                                :class="formato === 'BEST_OF'
                                    ? 'border-violet-400 bg-violet-50'
                                    : 'border-slate-200 hover:border-slate-300'">

                                <input type="radio" name="series_format" value="BEST_OF"
                                    x-model="formato"
                                    class="mt-0.5 text-violet-500 focus:ring-violet-400">

                                <span>
                                    <span class="block text-sm font-black text-slate-800">
                                        Al mejor de N
                                    </span>
                                    <span class="block text-xs text-slate-500">
                                        Se juega hasta que uno gana la mayoría. Termina
                                        en cuanto está decidido.
                                    </span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border p-3 transition"
                                :class="formato === 'FIXED_GAMES'
                                    ? 'border-violet-400 bg-violet-50'
                                    : 'border-slate-200 hover:border-slate-300'">

                                <input type="radio" name="series_format" value="FIXED_GAMES"
                                    x-model="formato"
                                    class="mt-0.5 text-violet-500 focus:ring-violet-400">

                                <span>
                                    <span class="block text-sm font-black text-slate-800">
                                        Enfrentamiento fijo
                                    </span>
                                    <span class="block text-xs text-slate-500">
                                        Se juegan siempre los mismos juegos, aunque ya
                                        esté decidido.
                                    </span>
                                </span>
                            </label>

                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">

                            <label x-show="formato === 'BEST_OF'" class="block">
                                <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                    Al mejor de
                                </span>

                                <select name="best_of"
                                    class="mt-1 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">
                                    @foreach ([1, 3, 5, 7, 9] as $n)
                                        <option value="{{ $n }}" @selected(old('best_of', 1) == $n)>
                                            {{ $n === 1 ? 'Un solo juego' : $n . ' juegos' }}
                                        </option>
                                    @endforeach
                                </select>

                                <span class="mt-1 block text-xs text-slate-400">
                                    Siempre impar: al mejor de un número par se
                                    empata y no hay forma de decidirlo.
                                </span>
                            </label>

                            <label x-show="formato === 'FIXED_GAMES'" x-cloak class="block">
                                <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                    Cuántos juegos
                                </span>

                                <input type="number" name="fixed_games" min="1" max="15"
                                    value="{{ old('fixed_games', 1) }}"
                                    class="mt-1 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

                                <span class="mt-1 block text-xs text-slate-400">
                                    Se juegan todos, decida o no decida.
                                </span>
                            </label>

                        </div>

                        <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-500">
                            Esto se decide por competición, no por plantilla. Después
                            podrás poner una excepción en una fase concreta —«todo al
                            mejor de 3, menos la final»—.
                        </p>

                        <x-input-error :messages="$errors->get('series_format')" class="mt-2" />
                        <x-input-error :messages="$errors->get('best_of')" class="mt-2" />
                        <x-input-error :messages="$errors->get('fixed_games')" class="mt-2" />

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


                    {{-- BUSCADOR Y FILTRO --}}

                    @if ($starts->isNotEmpty())
                        <div
                            class="
                                mt-6
                                grid
                                gap-3
                                md:grid-cols-2
                            ">

                            <input x-model="search" type="search"
                                placeholder="Buscar competidor..."
                                class="
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                    text-slate-900
                                    placeholder:text-slate-400
                                    focus:border-violet-400
                                    focus:ring-violet-400
                                ">


                            <select x-model="typeFilter"
                                class="
                                    rounded-xl
                                    border-slate-300
                                    bg-white
                                    text-sm
                                    text-slate-900
                                    focus:border-violet-400
                                    focus:ring-violet-400
                                ">

                                <option value="ALL">
                                    Todos los tipos
                                </option>

                                @foreach ($universeEntities->pluck('entity.entityType')->filter()->unique('id') as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>
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
                                        mt-3
                                        flex
                                        flex-wrap
                                        gap-2
                                    ">

                                    <button type="button"
                                        @click="selectVisible('{{ $start->id }}', true)"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-black text-slate-600">
                                        Seleccionar visibles
                                    </button>

                                    <button type="button"
                                        @click="selectVisible('{{ $start->id }}', false)"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-black text-slate-600">
                                        Quitar todos
                                    </button>

                                </div>


                                <div
                                    class="
                                        mt-4
                                        grid
                                        max-h-96
                                        gap-2
                                        overflow-y-auto
                                        pr-1
                                        sm:grid-cols-2
                                        xl:grid-cols-3
                                    ">

                                    @foreach ($universeEntities as $universeEntity)
                                        <label
                                            data-competitor
                                            x-show="matches(
                                                @js(mb_strtolower($universeEntity->display_label . ' ' . ($universeEntity->code ?? '') . ' ' . ($universeEntity->entity_type_name ?? ''))),
                                                '{{ $universeEntity->id }}'
                                            )"
                                            class="
                                                flex
                                                cursor-pointer
                                                items-center
                                                gap-2.5
                                                rounded-xl
                                                border-2
                                                border-slate-200
                                                bg-white
                                                p-2.5
                                                transition
                                                hover:border-violet-300
                                                has-[:checked]:border-violet-500
                                                has-[:checked]:bg-violet-50
                                            ">

                                            <input type="checkbox"
                                                name="assignments[{{ $start->id }}][]"
                                                value="{{ $universeEntity->id }}"
                                                @checked(in_array((string) $universeEntity->id, (array) (old('assignments')[$start->id] ?? []), true))
                                                class="
                                                    shrink-0
                                                    rounded
                                                    text-violet-600
                                                    focus:ring-violet-500
                                                ">


                                            @include('universes.competitions.partials.participant-chip', [
                                                'name' => $universeEntity->display_label,
                                                'imageUrl' => $universeEntity->image_url,
                                                'typeName' => $universeEntity->entity_type_name,
                                                'versionName' => null,
                                                'attributes' => [],
                                                'seed' => null,
                                                'size' => 'sm',
                                            ])

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
