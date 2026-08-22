<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Añadir torneo
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
                Añadir torneo
            </h2>


            <p
                class="
                    mt-2
                    max-w-3xl
                    text-slate-500
                ">
                Elige una plantilla de tu Biblioteca de Torneos y dale el
                nombre que tendrá dentro de este Universo.
            </p>

        </div>


        @if ($templates->isEmpty())

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
                    🏆
                </div>


                <h3
                    class="
                        mt-4
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Todavía no tienes plantillas de torneo
                </h3>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Diseña primero una plantilla en el módulo de Torneos y
                    después podrás usarla aquí.
                </p>


                <a href="{{ route('tournaments.templates.create') }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-amber-500
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear plantilla de torneo
                </a>

            </div>
        @else

            <form method="POST" enctype="multipart/form-data" action="{{ route('universes.tournaments.store', $universe) }}"
                x-data="{
                    templateId: @js(old('tournament_template_id')),
                    name: @js(old('name', '')),
                    touchedName: @js((bool) old('name')),

                    pick(id, templateName) {

                        this.templateId = id;

                        if (! this.touchedName) {
                            this.name = templateName;
                        }
                    }
                }">

                @csrf


                {{-- PLANTILLA --}}

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
                        01 · Plantilla
                    </p>


                    <h3
                        class="
                            mt-2
                            text-xl
                            font-black
                            text-slate-900
                        ">
                        ¿Qué estructura competitiva usará?
                    </h3>


                    <x-input-error :messages="$errors->get('tournament_template_id')" class="mt-3" />


                    <div
                        class="
                            mt-5
                            grid
                            max-h-[420px]
                            gap-3
                            overflow-y-auto
                            pr-1
                            sm:grid-cols-2
                        ">

                        @foreach ($templates as $template)
                            <label
                                @click="pick(
                                    '{{ $template->id }}',
                                    @js($template->name)
                                )"
                                class="
                                    cursor-pointer
                                    rounded-2xl
                                    border-2
                                    border-slate-200
                                    p-4
                                    transition
                                    has-[:checked]:border-violet-500
                                    has-[:checked]:bg-violet-50
                                ">

                                <div
                                    class="
                                        flex
                                        items-start
                                        justify-between
                                        gap-3
                                    ">

                                    <div class="min-w-0">

                                        <p
                                            class="
                                                truncate
                                                text-sm
                                                font-black
                                                text-slate-900
                                            ">
                                            {{ $template->name }}
                                        </p>


                                        <p
                                            class="
                                                mt-1
                                                font-mono
                                                text-[9px]
                                                text-slate-400
                                            ">
                                            {{ $template->code }}
                                        </p>

                                    </div>


                                    <input type="radio" name="tournament_template_id"
                                        value="{{ $template->id }}"
                                        @checked((string) old('tournament_template_id') === (string) $template->id)
                                        class="
                                            mt-0.5
                                            shrink-0
                                            text-violet-600
                                            focus:ring-violet-500
                                        ">

                                </div>


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
                                            bg-slate-100
                                            px-2
                                            py-0.5
                                            text-[9px]
                                            font-bold
                                            text-slate-500
                                        ">
                                        {{ $template->participant_range_label }}
                                    </span>


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
                                        {{ $template->graph_nodes_count }} fases
                                    </span>

                                </div>

                            </label>
                        @endforeach

                    </div>

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
                        02 · Dentro del Universo
                    </p>


                    <h3
                        class="
                            mt-2
                            text-xl
                            font-black
                            text-slate-900
                        ">
                        Identidad en {{ $universe->name }}
                    </h3>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-slate-500
                        ">
                        Puedes darle un nombre propio, distinto al de la
                        plantilla. Ejemplo: la plantilla "Eliminación 16"
                        puede llamarse aquí "Exámenes Chūnin".
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


                            <input type="text" name="name" x-model="name"
                                @input="touchedName = true"
                                placeholder="Ej. Exámenes Chūnin"
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
                                placeholder="Qué representa este torneo dentro del Universo..."
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-slate-900
                                    focus:border-violet-400
                                    focus:ring-violet-400
                                ">{{ old('description') }}</textarea>


                            <x-input-error :messages="$errors->get('description')" class="mt-2" />

                        </div>



                        {{-- Ambientación y recurrencia (Fase 10) --}}

                        <div>
                            <label class="text-xs font-black uppercase tracking-wider text-slate-500">
                                Contexto / reglas del Universo
                            </label>

                            <textarea name="context" rows="3"
                                placeholder="Qué representa este torneo dentro del mundo, sus reglas propias..."
                                class="mt-2 w-full rounded-xl border-slate-300 text-slate-900 focus:border-violet-400 focus:ring-violet-400">{{ old('context', '') }}</textarea>

                            <x-input-error :messages="$errors->get('context')" class="mt-2" />
                        </div>


                        <div>
                            <label class="text-xs font-black uppercase tracking-wider text-slate-500">
                                Portada
                            </label>

                            <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                                class="mt-2 w-full rounded-xl border border-slate-300 p-2 text-xs text-slate-600">

                            <x-input-error :messages="$errors->get('image')" class="mt-2" />
                        </div>


                        <div x-data="{ mode: @js(old('recurrence_mode', 'ONCE')) }">

                            <label class="text-xs font-black uppercase tracking-wider text-slate-500">
                                ¿Cada cuánto se juega?
                            </label>

                            <div class="mt-2 grid gap-3 sm:grid-cols-3">

                                <select name="recurrence_mode" x-model="mode"
                                    class="rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

                                    @foreach (\App\Models\UniverseTournament::recurrenceModes() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach

                                </select>

                                <div x-show="mode === 'EVERY_N_SEASONS'" x-cloak>
                                    <input type="number" name="recurrence_interval" min="1" max="50"
                                        value="{{ old('recurrence_interval', '') }}"
                                        placeholder="Cada N temporadas"
                                        class="w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">
                                </div>

                                <div x-show="mode !== 'MANUAL'" x-cloak>
                                    <input type="number" name="first_season_number" min="1" max="9999"
                                        value="{{ old('first_season_number', 1) }}"
                                        placeholder="Desde la temporada..."
                                        class="w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">
                                </div>

                            </div>

                            <p class="mt-2 text-xs text-slate-400">
                                «Manual» significa que lo lanzas tú cuando quieras: no se anuncia como programado.
                            </p>

                            <x-input-error :messages="$errors->get('recurrence_interval')" class="mt-2" />
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
                                    <option value="{{ $value }}" @selected(old('status', 'DRAFT') === $value)>
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
                        Añadir al Universo
                    </button>

                </div>

            </form>
        @endif

    </div>

</x-universe-layout>
