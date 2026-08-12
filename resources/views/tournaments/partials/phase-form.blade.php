@php

    $editing = isset($phase);

@endphp


<div class="
        space-y-6
    ">

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
                text-amber-600
            ">
            Configuración de fase
        </p>


        <div
            class="
                mt-6
                grid
                gap-5
                md:grid-cols-2
            ">

            <div class="
                    md:col-span-2
                ">

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Nombre *
                </label>


                <input type="text" name="name"
                    value="{{ old('name', $editing ? $phase->name : '') }}"
                    placeholder="Ej. Ronda eliminatoria"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">


                <x-input-error :messages="$errors->get('name')" class="mt-2" />

            </div>


            <div class="
                    md:col-span-2
                ">

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
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
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">{{ old('description', $editing ? $phase->description : '') }}</textarea>

            </div>


            <div class="
                    md:col-span-2
                ">

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Tipo
                </label>


                <select name="phase_type"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">

                    <option value="SINGLE_ELIMINATION">
                        Eliminación directa
                    </option>

                </select>


                <p
                    class="
                        mt-2
                        text-xs
                        text-slate-400
                    ">
                    Round Robin, Grupos, Suizo y Doble Eliminación
                    se habilitarán progresivamente.
                </p>

            </div>


            <div>

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Participantes de entrada
                </label>


                <input type="number" name="input_participants" min="2" max="512"
                    value="{{ old('input_participants', $editing ? $phase->input_participants : '') }}"
                    placeholder="Ej. 16"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">

            </div>


            <div>

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Clasificados
                </label>


                <input type="number" name="qualifiers_count" min="1" max="512"
                    value="{{ old('qualifiers_count', $editing ? $phase->qualifiers_count : '') }}"
                    placeholder="Ej. 8"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">

            </div>


            <div>

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Best of
                </label>


                <select name="best_of"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">

                    @foreach ([1, 3, 5, 7, 9] as $value)
                        <option value="{{ $value }}" @selected((int) old('best_of', $editing ? $phase->best_of : 1) === $value)>
                            Best of {{ $value }}
                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
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
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">

                    <option value="ACTIVE" @selected(old('status', $editing ? $phase->status : 'ACTIVE') === 'ACTIVE')>
                        Activa
                    </option>


                    <option value="INACTIVE" @selected(old('status', $editing ? $phase->status : 'ACTIVE') === 'INACTIVE')>
                        Inactiva
                    </option>

                </select>

            </div>

        </div>


        <label
            class="
                mt-6
                flex
                cursor-pointer
                gap-3
                rounded-2xl
                border
                border-slate-200
                p-4
            ">

            <input type="checkbox" name="allow_byes" value="1" @checked(old('allow_byes', $editing ? $phase->allow_byes : $tournamentTemplate->allow_byes))
                class="
                    mt-0.5
                    rounded
                    border-slate-300
                    text-amber-500
                    focus:ring-amber-500
                ">


            <span>

                <span
                    class="
                        block
                        text-sm
                        font-black
                        text-slate-800
                    ">
                    Permitir BYE en esta fase
                </span>


                <span
                    class="
                        mt-1
                        block
                        text-xs
                        text-slate-500
                    ">
                    El algoritmo real de asignación se implementará
                    durante el Tournament Engine.
                </span>

            </span>

        </label>

    </section>


    <div class="
            flex
            justify-end
            gap-3
        ">

        <a href="{{ route('tournaments.phases.index', $tournamentTemplate) }}"
            class="
                rounded-xl
                border
                border-slate-200
                bg-white
                px-5
                py-3
                text-sm
                font-black
                text-slate-600
            ">
            Cancelar
        </a>


        <button type="submit"
            class="
                rounded-xl
                bg-amber-500
                px-6
                py-3
                text-sm
                font-black
                text-white
            ">
            {{ $editing ? 'Guardar fase' : 'Crear fase' }}
        </button>

    </div>

</div>
