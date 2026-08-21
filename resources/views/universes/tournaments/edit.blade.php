<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Editar torneo
    </x-slot>


    <div class="mx-auto max-w-4xl">

        <div class="mb-7">

            <a href="{{ route('universes.tournaments.index', $universe) }}"
                class="
                    text-xs
                    font-black
                    text-slate-400
                    hover:text-violet-600
                ">
                ← Torneos
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
                Editar {{ $universeTournament->name }}
            </h2>

        </div>


        <form method="POST"
            action="{{ route('universes.tournaments.update', [$universe, $universeTournament]) }}">

            @csrf

            @method('PUT')


            {{-- PLANTILLA (SOLO LECTURA) --}}

            <section
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-slate-50
                    p-6
                ">

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-500
                    ">
                    Plantilla de origen
                </p>


                @if ($universeTournament->tournamentTemplate)
                    <div
                        class="
                            mt-3
                            flex
                            flex-wrap
                            items-center
                            justify-between
                            gap-3
                        ">

                        <div>
                            <p
                                class="
                                    text-lg
                                    font-black
                                    text-slate-900
                                ">
                                {{ $universeTournament->tournamentTemplate->name }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    font-mono
                                    text-[10px]
                                    text-slate-400
                                ">
                                {{ $universeTournament->tournamentTemplate->code }}
                            </p>
                        </div>


                        <a href="{{ route('tournaments.templates.show', $universeTournament->tournamentTemplate) }}"
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
                            Ver plantilla →
                        </a>

                    </div>
                @else
                    <p
                        class="
                            mt-3
                            text-sm
                            text-red-500
                        ">
                        La plantilla original ya no está disponible.
                    </p>
                @endif


                <p
                    class="
                        mt-4
                        text-xs
                        text-slate-500
                    ">
                    La plantilla no se puede cambiar: sería otro torneo.
                    Si necesitas otra estructura, añade un torneo nuevo.
                </p>

            </section>


            {{-- CONTEXTO --}}

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
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-600
                    ">
                    Dentro del Universo
                </p>


                <div class="mt-6 space-y-5">

                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                            Nombre *
                        </label>


                        <input type="text" name="name"
                            value="{{ old('name', $universeTournament->name) }}"
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
                            Descripción
                        </label>


                        <textarea name="description" rows="4"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                                text-slate-900
                                focus:border-violet-400
                                focus:ring-violet-400
                            ">{{ old('description', $universeTournament->description) }}</textarea>


                        <x-input-error :messages="$errors->get('description')" class="mt-2" />

                    </div>


                    <div class="max-w-sm">

                        <label
                            class="
                                text-xs
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                            Estado
                        </label>


                        <select name="status"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                                focus:border-violet-400
                                focus:ring-violet-400
                            ">

                            @foreach (\App\Models\UniverseTournament::statuses() as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $universeTournament->status) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach

                        </select>

                    </div>

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

                <a href="{{ route('universes.tournaments.index', $universe) }}"
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
                    Guardar cambios
                </button>

            </div>

        </form>

    </div>

</x-universe-layout>
