<x-app-layout>

    <x-slot name="header">
        Versiones de Entidades
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    <section
        class="
            rounded-3xl
            bg-gradient-to-br
            from-indigo-950
            via-violet-950
            to-slate-950
            p-6
            text-white
            sm:p-8
        ">

        <p
            class="
                text-[10px]
                font-black
                uppercase
                tracking-widest
                text-indigo-300
            ">
            Workspace
        </p>


        <h1 class="
                mt-2
                text-3xl
                font-black
            ">
            Todas las Versiones de Entidades
        </h1>


        <p
            class="
                mt-3
                max-w-3xl
                text-sm
                leading-6
                text-white/60
            ">
            Aquí ves las representaciones concretas:
            Naruto Shippuden, Sasuke Shippuden,
            Naruto Baryon, Luffy Gear 5 y cualquier
            otra Version creada en tu Biblioteca.
        </p>


        <div
            class="
                mt-6
                grid
                grid-cols-2
                gap-2
                md:grid-cols-5
            ">

            @foreach ([['Total', $stats['total']], ['Activas', $stats['active']], ['Predeterminadas', $stats['default']], ['Con cambios', $stats['with_overrides']], ['Con multimedia', $stats['with_media']]] as [$label, $value])
                <div
                    class="
                        rounded-2xl
                        bg-white/10
                        p-4
                    ">

                    <p
                        class="
                            text-[8px]
                            font-black
                            uppercase
                            text-white/45
                        ">
                        {{ $label }}
                    </p>


                    <p
                        class="
                            mt-1
                            text-2xl
                            font-black
                        ">
                        {{ $value }}
                    </p>

                </div>
            @endforeach

        </div>

    </section>


    {{-- FILTER --}}
    <form method="GET"
        class="
            mt-5
            grid
            min-w-0
            gap-3
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-5
            md:grid-cols-2
            xl:grid-cols-6
        ">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar Version, Entidad..."
            class="
                min-w-0
                w-full
                rounded-xl
                border-slate-300
                xl:col-span-2
            ">


        <select name="version"
            class="
                w-full
                rounded-xl
                border-slate-300
                text-xs
            ">

            <option value="">
                Todas las definiciones
            </option>


            @foreach ($versions as $version)
                <option value="{{ $version->id }}" @selected($versionId === $version->id)>
                    {{ $version->name }}
                </option>
            @endforeach

        </select>


        <select name="type"
            class="
                w-full
                rounded-xl
                border-slate-300
                text-xs
            ">

            <option value="">
                Todos los tipos
            </option>


            @foreach ($entityTypes as $type)
                <option value="{{ $type->id }}" @selected($typeId === $type->id)>
                    {{ $type->name }}
                </option>
            @endforeach

        </select>


        <select name="overrides"
            class="
                w-full
                rounded-xl
                border-slate-300
                text-xs
            ">

            <option value="">
                Cualquier cambio
            </option>

            <option value="YES" @selected($overrides === 'YES')>
                Con sobrescrituras
            </option>

            <option value="NO" @selected($overrides === 'NO')>
                Solo heredadas
            </option>

        </select>


        <button
            class="
                rounded-xl
                bg-slate-900
                px-4
                py-2.5
                text-xs
                font-black
                text-white
            ">
            Filtrar
        </button>

    </form>


    <div
        class="
            mt-6
            grid
            gap-4
            sm:grid-cols-2
            lg:grid-cols-3
            xl:grid-cols-4
        ">

        @foreach ($entityVersions as $item)
            <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                class="
                    group
                    overflow-hidden
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    shadow-sm
                    transition
                    hover:-translate-y-1
                    hover:border-indigo-300
                    hover:shadow-lg
                ">

                <div
                    class="
                        relative
                        aspect-square
                        overflow-hidden
                        bg-slate-100
                    ">

                    <img src="{{ $item->image_url }}"
                        class="
                            h-full
                            w-full
                            object-cover
                            transition
                            duration-300
                            group-hover:scale-105
                        ">


                    @if ($item->is_default)
                        <span
                            class="
                                absolute
                                right-3
                                top-3
                                rounded-full
                                bg-amber-400
                                px-2
                                py-1
                                text-[7px]
                                font-black
                                text-amber-950
                            ">
                            ★
                        </span>
                    @endif

                </div>


                <div class="p-4">

                    <p
                        class="
                            truncate
                            text-sm
                            font-black
                            text-slate-800
                        ">
                        {{ $item->name }}
                    </p>


                    <p
                        class="
                            mt-1
                            truncate
                            text-[9px]
                            text-slate-400
                        ">
                        {{ $item->entity->name }}
                        ·
                        {{ $item->version->name }}
                    </p>


                    <div
                        class="
                            mt-3
                            flex
                            flex-wrap
                            gap-1.5
                        ">

                        <span
                            class="
                                rounded-full
                                bg-violet-50
                                px-2
                                py-1
                                text-[7px]
                                font-black
                                text-violet-600
                            ">
                            {{ $item->version_attributes_count }}
                            cambios
                        </span>


                        <span
                            class="
                                rounded-full
                                bg-fuchsia-50
                                px-2
                                py-1
                                text-[7px]
                                font-black
                                text-fuchsia-600
                            ">
                            {{ $item->images_count }}
                            imágenes
                        </span>

                    </div>

                </div>

            </a>
        @endforeach

    </div>


    <div class="mt-6">
        {{ $entityVersions->links() }}
    </div>

</x-app-layout>
