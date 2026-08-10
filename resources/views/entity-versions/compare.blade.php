<x-app-layout>

    <x-slot name="header">
        Comparar Versiones
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    <section
        class="
            rounded-3xl
            bg-gradient-to-br
            from-cyan-950
            via-indigo-950
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
                text-cyan-300
            ">
            Comparación
        </p>


        <h1 class="
                mt-2
                text-3xl
                font-black
            ">
            {{ $entity->name }}
        </h1>


        <p class="
                mt-3
                text-sm
                text-white/60
            ">
            Compara la Entidad base con hasta cuatro Versiones.
        </p>

    </section>


    {{-- SELECT --}}
    <form method="GET"
        class="
            mt-5
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-5
        ">

        <p class="
                text-xs
                font-black
                text-slate-700
            ">
            Versiones a comparar
        </p>


        <div
            class="
                mt-3
                flex
                flex-wrap
                gap-2
            ">

            @foreach ($entity->entityVersions as $item)
                <label class="
                        cursor-pointer
                    ">

                    <input type="checkbox" name="versions[]" value="{{ $item->id }}" @checked($selectedVersions->contains('id', $item->id))
                        class="
                            peer
                            sr-only
                        ">


                    <span
                        class="
                            inline-flex
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-3
                            py-2
                            text-[9px]
                            font-black
                            text-slate-500
                            peer-checked:border-cyan-400
                            peer-checked:bg-cyan-50
                            peer-checked:text-cyan-700
                        ">
                        {{ $item->name }}
                    </span>

                </label>
            @endforeach

        </div>


        <button
            class="
                mt-4
                rounded-xl
                bg-cyan-600
                px-4
                py-2.5
                text-xs
                font-black
                text-white
            ">
            Actualizar comparación
        </button>

    </form>


    {{-- HEADERS --}}
    <section
        class="
            mt-6
            overflow-x-auto
            rounded-3xl
            border
            border-slate-200
            bg-white
            shadow-sm
        ">

        <table class="
                min-w-[900px]
                w-full
            ">

            <thead>

                <tr
                    class="
                        border-b
                        border-slate-200
                        bg-slate-50
                    ">

                    <th
                        class="
                            sticky
                            left-0
                            z-10
                            w-52
                            bg-slate-50
                            p-4
                            text-left
                            text-xs
                            font-black
                            text-slate-500
                        ">
                        Característica
                    </th>


                    @foreach ($columns as $column)
                        <th
                            class="
                                min-w-48
                                p-4
                                text-left
                            ">

                            <div
                                class="
                                    flex
                                    items-center
                                    gap-3
                                ">

                                @if ($column['image_url'])
                                    <img src="{{ $column['image_url'] }}"
                                        class="
                                            h-12
                                            w-12
                                            rounded-xl
                                            object-cover
                                        ">
                                @endif


                                <div>

                                    <p
                                        class="
                                            text-[8px]
                                            font-black
                                            uppercase
                                            text-cyan-500
                                        ">
                                        {{ $column['label'] }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            text-xs
                                            font-black
                                            text-slate-800
                                        ">
                                        {{ $column['name'] }}
                                    </p>

                                </div>

                            </div>

                        </th>
                    @endforeach

                </tr>

            </thead>


            <tbody class="
                    divide-y
                    divide-slate-100
                ">

                @foreach ($rows as $row)
                    @php

                        $distinct = $row['values']->filter(fn($value) => $value !== null)->unique()->count();

                    @endphp


                    <tr
                        class="
                            {{ $distinct > 1 ? 'bg-amber-50/30' : '' }}
                        ">

                        <td
                            class="
                                sticky
                                left-0
                                z-10
                                bg-white
                                p-4
                            ">

                            <p
                                class="
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                {{ $row['attribute']->name }}
                            </p>


                            @if ($distinct > 1)
                                <span
                                    class="
                                        mt-1
                                        inline-flex
                                        rounded-full
                                        bg-amber-100
                                        px-2
                                        py-1
                                        text-[7px]
                                        font-black
                                        text-amber-700
                                    ">
                                    CAMBIA
                                </span>
                            @endif

                        </td>


                        @foreach ($columns as $column)
                            <td
                                class="
                                    p-4
                                    text-sm
                                    font-bold
                                    text-slate-700
                                ">
                                {{ $row['values'][$column['key']] ?? '—' }}
                            </td>
                        @endforeach

                    </tr>
                @endforeach

            </tbody>

        </table>

    </section>

</x-app-layout>
