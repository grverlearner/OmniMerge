<x-app-layout>

    <x-slot name="header">
        Cobertura de Versiones
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    <section
        class="
            rounded-3xl
            bg-gradient-to-br
            from-cyan-950
            via-slate-950
            to-indigo-950
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
            Calidad de datos
        </p>


        <h1 class="
                mt-2
                text-3xl
                font-black
            ">
            Cobertura
        </h1>


        <p
            class="
                mt-3
                max-w-3xl
                text-sm
                leading-6
                text-white/60
            ">
            Detecta Entidades compatibles mediante
            relaciones ACTIVATES y comprueba cuáles
            todavía necesitan su representación.
        </p>

    </section>


    <div class="
            mt-6
            space-y-4
        ">

        @foreach ($coverageRows as $row)
            @php

                $version = $row['version'];

            @endphp


            <article
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                ">

                <div
                    class="
                        flex
                        flex-col
                        gap-5
                        lg:flex-row
                        lg:items-center
                    ">

                    <img src="{{ $version->image_url }}"
                        class="
                            h-24
                            w-24
                            shrink-0
                            rounded-2xl
                            object-cover
                        ">


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

                            <a href="{{ route('versions.show', $version) }}"
                                class="
                                    text-lg
                                    font-black
                                    text-slate-900
                                    hover:text-violet-600
                                ">
                                {{ $version->name }}
                            </a>


                            <span
                                class="
                                    rounded-full
                                    bg-slate-100
                                    px-2
                                    py-1
                                    text-[8px]
                                    font-black
                                    text-slate-500
                                ">
                                {{ $version->kind_label }}
                            </span>

                        </div>


                        @if ($row['eligible'] === null)
                            <p
                                class="
                                    mt-3
                                    text-xs
                                    text-slate-500
                                ">
                                No posee ninguna relación
                                <strong>ACTIVATES</strong>;
                                no se puede calcular cobertura automática.
                            </p>
                        @else
                            <div
                                class="
                                    mt-4
                                    flex
                                    flex-wrap
                                    gap-4
                                    text-xs
                                ">

                                <span>
                                    <strong>
                                        {{ $row['covered'] }}
                                    </strong>
                                    listas
                                </span>

                                <span>
                                    <strong>
                                        {{ $row['eligible'] }}
                                    </strong>
                                    compatibles
                                </span>

                                <span
                                    class="
                                        {{ $row['missing'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}
                                    ">
                                    <strong>
                                        {{ $row['missing'] }}
                                    </strong>
                                    faltantes
                                </span>

                            </div>


                            <div
                                class="
                                    mt-3
                                    h-2
                                    overflow-hidden
                                    rounded-full
                                    bg-slate-100
                                ">

                                <div class="
                                        h-full
                                        rounded-full
                                        bg-cyan-500
                                    "
                                    style="
                                        width:
                                        {{ $row['percentage'] }}%;
                                    ">
                                </div>

                            </div>
                        @endif

                    </div>


                    <div
                        class="
                            shrink-0
                            text-right
                        ">

                        @if ($row['percentage'] !== null)
                            <p
                                class="
                                    text-3xl
                                    font-black
                                    text-cyan-600
                                ">
                                {{ $row['percentage'] }}%
                            </p>
                        @endif


                        <a href="{{ route('versions.entities.bulk.create', $version) }}"
                            class="
                                mt-2
                                inline-flex
                                rounded-xl
                                bg-violet-600
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                            ">
                            Gestionar Entidades
                        </a>

                    </div>

                </div>

            </article>
        @endforeach

    </div>


    <div class="mt-6">
        {{ $versions->links() }}
    </div>

</x-app-layout>
