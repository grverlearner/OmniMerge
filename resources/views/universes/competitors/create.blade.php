<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Añadir competidores
    </x-slot>


    <div class="mb-7">

        <a href="{{ route('universes.competitors.index', $universe) }}"
            class="
                text-xs
                font-black
                text-slate-400
                hover:text-violet-600
            ">
            ← Competidores
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
            Añadir competidores
        </h2>


        <p
            class="
                mt-2
                max-w-3xl
                text-slate-500
            ">
            Elige entidades de tu Biblioteca para incorporarlas a este
            Universo. No se crean copias: la entidad sigue siendo la misma
            y puede pertenecer a varios Universos a la vez.
        </p>

    </div>


    @if ($entities->isEmpty())

        <div
            class="
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
                No hay entidades disponibles
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Todas tus entidades ya forman parte de este Universo, o
                todavía no has creado ninguna en tu Biblioteca.
            </p>


            <a href="{{ route('entities.create') }}"
                class="
                    mt-5
                    inline-flex
                    rounded-xl
                    bg-indigo-600
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                ">
                Crear una entidad
            </a>

        </div>
    @else

        <form method="POST" action="{{ route('universes.competitors.store', $universe) }}"
            x-data="{
                entitySearch: '',
                entityType: 'ALL',
                selected: 0,

                recount() {
                    this.selected =
                        this.$el.querySelectorAll(
                            'input[name=\'entity_ids[]\']:checked'
                        ).length;
                },

                selectVisible(value) {

                    this.$el
                        .querySelectorAll('[data-entity-card]')
                        .forEach((card) => {

                            if (card.offsetParent === null) {
                                return;
                            }

                            card.querySelector('input').checked = value;
                        });

                    this.recount();
                }
            }"
            @change="recount()">

            @csrf


            <section
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                ">

                <div
                    class="
                        grid
                        gap-3
                        md:grid-cols-2
                    ">

                    <input x-model="entitySearch" placeholder="Buscar entidad..."
                        class="
                            rounded-xl
                            border-slate-300
                            text-sm
                            text-slate-900
                            focus:border-violet-400
                            focus:ring-violet-400
                        ">


                    <select x-model="entityType"
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

                        @foreach ($entityTypes as $type)
                            <option value="{{ $type->id }}">
                                {{ $type->name }}
                            </option>
                        @endforeach

                    </select>

                </div>


                <div
                    class="
                        mt-4
                        flex
                        flex-wrap
                        items-center
                        gap-3
                    ">

                    <button type="button" @click="selectVisible(true)"
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            px-3
                            py-2
                            text-[11px]
                            font-black
                            text-slate-600
                        ">
                        Seleccionar visibles
                    </button>


                    <button type="button" @click="selectVisible(false)"
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            px-3
                            py-2
                            text-[11px]
                            font-black
                            text-slate-600
                        ">
                        Quitar selección
                    </button>


                    <span
                        class="
                            text-xs
                            font-black
                            text-violet-600
                        ">
                        <span x-text="selected">0</span> seleccionadas
                    </span>

                </div>


                <x-input-error :messages="$errors->get('entity_ids')" class="mt-3" />


                <div
                    class="
                        mt-5
                        grid
                        max-h-[600px]
                        grid-cols-2
                        gap-3
                        overflow-y-auto
                        pr-1
                        sm:grid-cols-3
                        lg:grid-cols-5
                    ">

                    @foreach ($entities as $entity)
                        <label data-entity-card
                            x-show="
                                (! entitySearch
                                    ||
                                    @js(mb_strtolower($entity->name . ' ' . $entity->code)).includes(
                                        entitySearch.toLowerCase()
                                    ))
                                &&
                                (
                                    entityType === 'ALL'
                                    ||
                                    entityType === '{{ $entity->entity_type_id }}'
                                )
                            "
                            class="
                                relative
                                cursor-pointer
                                overflow-hidden
                                rounded-xl
                                border-2
                                border-slate-200
                                bg-white
                                transition
                                has-[:checked]:border-violet-500
                                has-[:checked]:bg-violet-50
                            ">

                            <input type="checkbox" name="entity_ids[]" value="{{ $entity->id }}"
                                @checked(in_array((string) $entity->id, (array) old('entity_ids', []), true))
                                class="
                                    absolute
                                    right-2
                                    top-2
                                    z-10
                                    rounded
                                    text-violet-600
                                    focus:ring-violet-500
                                ">


                            <div class="aspect-square bg-slate-100">

                                @if ($entity->image_url)
                                    <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        ">
                                @else
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            text-3xl
                                            text-violet-300
                                        ">
                                        ✦
                                    </div>
                                @endif

                            </div>


                            <div class="p-2.5">

                                <p
                                    class="
                                        truncate
                                        text-[11px]
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $entity->name }}
                                </p>


                                <p
                                    class="
                                        mt-0.5
                                        truncate
                                        font-mono
                                        text-[9px]
                                        text-slate-400
                                    ">
                                    {{ $entity->code }}
                                </p>

                            </div>

                        </label>
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

                <a href="{{ route('universes.competitors.index', $universe) }}"
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
                    Incorporar al Universo
                </button>

            </div>

        </form>
    @endif

</x-universe-layout>
