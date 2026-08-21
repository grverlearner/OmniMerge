@php

    $editing = isset($season);

@endphp


<div class="space-y-6">

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
                border-b
                border-slate-100
                pb-5
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-600
                ">
                Temporada {{ $editing ? $season->number : $nextNumber }}
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Información de la temporada
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                El número se asigna automáticamente y es correlativo
                dentro de este Universo.
            </p>

        </div>


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
                    value="{{ old('name', $editing ? $season->name : '') }}"
                    placeholder="Ej. Temporada Inaugural"
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
                    placeholder="Qué ocurre en esta temporada..."
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        text-slate-900
                        focus:border-violet-400
                        focus:ring-violet-400
                    ">{{ old('description', $editing ? $season->description : '') }}</textarea>


                <x-input-error :messages="$errors->get('description')" class="mt-2" />

            </div>


            <div
                class="
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
                        Inicio
                    </label>


                    <input type="date" name="starts_at"
                        value="{{ old('starts_at', $editing ? $season->starts_at?->format('Y-m-d') : '') }}"
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-slate-900
                            focus:border-violet-400
                            focus:ring-violet-400
                        ">


                    <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />

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
                        Fin
                    </label>


                    <input type="date" name="ends_at"
                        value="{{ old('ends_at', $editing ? $season->ends_at?->format('Y-m-d') : '') }}"
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-slate-900
                            focus:border-violet-400
                            focus:ring-violet-400
                        ">


                    <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />

                </div>

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

                    @foreach (\App\Models\UniverseSeason::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $editing ? $season->status : 'PLANNED') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>


                <x-input-error :messages="$errors->get('status')" class="mt-2" />


                @if (isset($activeSeason) && $activeSeason && (! $editing || $activeSeason->id !== $season->id))
                    <p
                        class="
                            mt-2
                            rounded-xl
                            bg-amber-50
                            px-3
                            py-2
                            text-xs
                            text-amber-800
                        ">
                        Ahora mismo está en curso la Temporada {{ $activeSeason->number }}
                        ({{ $activeSeason->name }}). Si marcas esta como
                        <strong>En curso</strong>, aquella pasará a
                        <strong>Finalizada</strong>.
                    </p>
                @endif

            </div>

        </div>

    </section>


    <div
        class="
            flex
            flex-col-reverse
            gap-3
            sm:flex-row
            sm:justify-end
        ">

        <a href="{{ route('universes.seasons.index', $universe) }}"
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
            {{ $editing ? 'Guardar cambios' : 'Crear temporada' }}
        </button>

    </div>

</div>
