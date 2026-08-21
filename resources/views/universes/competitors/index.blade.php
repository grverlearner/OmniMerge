<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Competidores
    </x-slot>


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
                    text-violet-600
                ">
                {{ $universe->name }} · Competidores
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Competidores
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Entidades de tu Biblioteca incorporadas a este Universo.
                La entidad original no se copia ni se modifica: solo
                adquiere contexto aquí dentro.
            </p>

        </div>


        @can('update', $universe)
            <a href="{{ route('universes.competitors.create', $universe) }}"
                class="
                    shrink-0
                    rounded-xl
                    bg-violet-600
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                    shadow-lg
                    shadow-violet-600/20
                    transition
                    hover:bg-violet-700
                ">
                + Añadir competidores
            </a>
        @endcan

    </div>


    {{-- STATS --}}

    <div
        class="
            mt-7
            grid
            grid-cols-3
            gap-3
        ">

        @foreach ([['Total', $statistics['total']], ['Activos', $statistics['active']], ['Retirados', $statistics['retired']]] as [$label, $value])
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                ">

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    {{ $label }}
                </p>


                <p
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    {{ $value }}
                </p>

            </article>
        @endforeach

    </div>


    {{-- FILTERS --}}

    <form method="GET"
        class="
            mt-6
            grid
            gap-3
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-4
            md:grid-cols-3
        ">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar competidor..."
            class="
                rounded-xl
                border-slate-300
                text-sm
                text-slate-900
                placeholder:text-slate-400
                focus:border-violet-400
                focus:ring-violet-400
            ">


        <select name="status"
            class="
                rounded-xl
                border-slate-300
                bg-white
                text-sm
                text-slate-900
                focus:border-violet-400
                focus:ring-violet-400
            ">

            <option value="">
                Todo estado
            </option>

            @foreach (\App\Models\UniverseCompetitor::statuses() as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>
                    {{ $label }}
                </option>
            @endforeach

        </select>


        <button
            class="
                rounded-xl
                bg-slate-950
                px-4
                py-3
                text-sm
                font-black
                text-white
            ">
            Aplicar
        </button>

    </form>


    @if ($competitors->isEmpty())

        <div
            class="
                mt-8
                rounded-3xl
                border
                border-dashed
                border-slate-300
                bg-white
                p-12
                text-center
            ">

            <div class="text-5xl">
                ✦
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                Todavía no hay competidores
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Incorpora entidades de tu Biblioteca para que formen parte
                de este Universo.
            </p>


            @can('update', $universe)
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
            @endcan

        </div>
    @else

        <div
            class="
                mt-6
                grid
                gap-4
                sm:grid-cols-2
                lg:grid-cols-3
            ">

            @foreach ($competitors as $competitor)
                <article x-data="{ editing: false }"
                    class="
                        overflow-hidden
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                    ">

                    <div class="
                            flex
                            items-center
                            gap-4
                            p-4
                        ">

                        <div
                            class="
                                flex
                                h-14
                                w-14
                                shrink-0
                                items-center
                                justify-center
                                overflow-hidden
                                rounded-xl
                                bg-violet-100
                                text-2xl
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


                        <div class="min-w-0 flex-1">

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-black
                                    text-slate-900
                                ">
                                {{ $competitor->display_label }}
                            </p>


                            @if ($competitor->display_name && $competitor->entity)
                                <p
                                    class="
                                        mt-0.5
                                        truncate
                                        text-[10px]
                                        text-slate-400
                                    ">
                                    Entidad: {{ $competitor->entity->name }}
                                </p>
                            @endif


                            <div
                                class="
                                    mt-1.5
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-1.5
                                ">

                                <span
                                    class="
                                        rounded-full
                                        px-2
                                        py-0.5
                                        text-[9px]
                                        font-black
                                        uppercase

                                        {{ $competitor->status === 'ACTIVE'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : ($competitor->status === 'RETIRED'
                                                ? 'bg-slate-200 text-slate-600'
                                                : 'bg-violet-100 text-violet-700') }}
                                    ">
                                    {{ $competitor->status_label }}
                                </span>


                                @if ($competitor->entity?->entityType)
                                    <span
                                        class="
                                            rounded-full
                                            bg-slate-100
                                            px-2
                                            py-0.5
                                            text-[9px]
                                            font-bold
                                            text-slate-500
                                        ">
                                        {{ $competitor->entity->entityType->name }}
                                    </span>
                                @endif

                            </div>

                        </div>

                    </div>


                    @can('update', $universe)
                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-t
                                border-slate-100
                                bg-slate-50/60
                                px-4
                                py-2.5
                            ">

                            <button type="button" @click="editing = ! editing"
                                class="
                                    text-[11px]
                                    font-black
                                    text-violet-600
                                ">
                                <span x-show="! editing">Editar contexto</span>
                                <span x-show="editing" style="display: none;">Cancelar</span>
                            </button>


                            <form method="POST"
                                action="{{ route('universes.competitors.destroy', [$universe, $competitor]) }}">

                                @csrf

                                @method('DELETE')


                                <button type="submit"
                                    class="
                                        text-[11px]
                                        font-black
                                        text-red-500
                                    ">
                                    Quitar
                                </button>

                            </form>

                        </div>


                        <form method="POST" x-show="editing" x-transition style="display: none;"
                            action="{{ route('universes.competitors.update', [$universe, $competitor]) }}"
                            class="
                                space-y-3
                                border-t
                                border-slate-100
                                p-4
                            ">

                            @csrf

                            @method('PUT')


                            <div>
                                <label
                                    class="
                                        text-[9px]
                                        font-black
                                        uppercase
                                        tracking-wider
                                        text-slate-500
                                    ">
                                    Alias en este Universo
                                </label>

                                <input type="text" name="display_name"
                                    value="{{ $competitor->display_name }}"
                                    placeholder="{{ $competitor->entity?->name }}"
                                    class="
                                        mt-1.5
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-sm
                                        focus:border-violet-400
                                        focus:ring-violet-400
                                    ">
                            </div>


                            <div>
                                <label
                                    class="
                                        text-[9px]
                                        font-black
                                        uppercase
                                        tracking-wider
                                        text-slate-500
                                    ">
                                    Estado
                                </label>

                                <select name="status"
                                    class="
                                        mt-1.5
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-sm
                                        focus:border-violet-400
                                        focus:ring-violet-400
                                    ">

                                    @foreach (\App\Models\UniverseCompetitor::statuses() as $value => $label)
                                        <option value="{{ $value }}" @selected($competitor->status === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>


                            <div>
                                <label
                                    class="
                                        text-[9px]
                                        font-black
                                        uppercase
                                        tracking-wider
                                        text-slate-500
                                    ">
                                    Notas
                                </label>

                                <textarea name="notes" rows="2"
                                    class="
                                        mt-1.5
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-sm
                                        focus:border-violet-400
                                        focus:ring-violet-400
                                    ">{{ $competitor->notes }}</textarea>
                            </div>


                            <button type="submit"
                                class="
                                    w-full
                                    rounded-xl
                                    bg-violet-600
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-white
                                ">
                                Guardar
                            </button>

                        </form>
                    @endcan

                </article>
            @endforeach

        </div>


        <div class="mt-8">
            {{ $competitors->links() }}
        </div>
    @endif

</x-universe-layout>
