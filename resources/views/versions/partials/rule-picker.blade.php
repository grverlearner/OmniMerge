@php
    /*
     * El selector visual de reglas de catálogo.
     *
     * Antes eran dos desplegables encadenados: elegir «Clanes» y luego buscar
     * «Uzumaki» entre cincuenta y dos líneas de texto idénticas. Los catálogos
     * y sus valores tienen imagen —para eso se les puso—, así que aquí se
     * eligen por la cara, que es como se reconocen.
     *
     * Tres pasos, y cada uno solo aparece cuando el anterior está resuelto:
     *
     *   1. qué catálogo
     *   2. qué valor de ese catálogo   ← con su imagen y cuántas entidades lo tienen
     *   3. qué hace la regla, y la frase resultante escrita antes de guardarla
     *
     * El contador de cada valor no es decoración: elegir un valor que no tiene
     * ninguna entidad es escribir una regla que no se cumplirá nunca, y así se
     * ve antes de guardarla, no después.
     */
@endphp

<form method="POST" action="{{ route('versions.catalog-links.store', $version) }}"
    x-show="anadiendo" x-cloak x-collapse
    class="border-b border-slate-800 bg-slate-950/50">
    @csrf

    <input type="hidden" name="attribute_id" :value="catalogo">
    <input type="hidden" name="attribute_option_id" :value="opcion">


    {{-- ============ PASO 1 · EL CATÁLOGO ============ --}}

    <div class="border-b border-slate-800/70 p-4">

        <div class="mb-2.5 flex flex-wrap items-center gap-2">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 font-mono text-[10px] font-black text-slate-950">1</span>
            <h3 class="text-[12px] font-black text-white">¿De qué catálogo?</h3>

            <button type="button" x-show="catalogo" x-cloak @click="reiniciar()"
                class="ml-auto rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-300">
                Empezar de nuevo
            </button>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1">

            @forelse ($catalogAttributes as $cat)
                @php $vacio = $cat->options->isEmpty(); @endphp

                <button type="button" @disabled($vacio)
                    @if (! $vacio) @click="elegirCatalogo({{ $cat->id }}, @js($cat->name))" @endif
                    title="{{ $vacio ? 'Este catálogo no tiene ningún valor activo todavía' : $cat->name }}"
                    :class="catalogo === {{ $cat->id }}
                        ? 'border-cyan-500 bg-cyan-500/10'
                        : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
                    class="group flex w-24 shrink-0 flex-col overflow-hidden rounded-xl border transition disabled:cursor-not-allowed disabled:opacity-35">

                    <span class="relative block aspect-square overflow-hidden bg-slate-900">
                        @if ($cat->image_url)
                            <img src="{{ $cat->image_url }}" alt="" loading="lazy"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-lg text-slate-700">◱</span>
                        @endif

                        <span class="absolute bottom-1 right-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black {{ $vacio ? 'text-slate-600' : 'text-cyan-300' }}">
                            {{ $cat->options->count() }}
                        </span>
                    </span>

                    <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black"
                        :class="catalogo === {{ $cat->id }} ? 'text-cyan-200' : 'text-slate-400'">
                        {{ $cat->name }}
                    </span>
                </button>
            @empty
                <p class="py-4 text-[11px] text-slate-600">
                    No tienes ningún catálogo. Las reglas de activación necesitan uno:
                    <a href="{{ route('attributes.index') }}" class="font-black text-cyan-300 underline">créalo primero →</a>
                </p>
            @endforelse

        </div>

    </div>


    {{-- ============ PASO 2 · EL VALOR ============ --}}

    <div x-show="catalogo" x-cloak x-collapse class="border-b border-slate-800/70 p-4">

        <div class="mb-2.5 flex flex-wrap items-center gap-2">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 font-mono text-[10px] font-black text-slate-950">2</span>

            <h3 class="text-[12px] font-black text-white">
                ¿Qué valor de <span class="text-cyan-300" x-text="catalogoNombre"></span>?
            </h3>

            <label class="relative ml-auto w-48">
                <span class="sr-only">Filtrar valores</span>
                <input type="search" x-model="buscar" placeholder="Filtrar…"
                    class="w-full rounded-lg border-slate-800 bg-slate-900 py-1.5 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-cyan-500 focus:ring-cyan-500">
            </label>
        </div>

        @foreach ($catalogAttributes as $cat)
            @continue($cat->options->isEmpty())

            <div x-show="catalogo === {{ $cat->id }}"
                class="grid max-h-72 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-5 lg:grid-cols-8">

                @foreach ($cat->options as $valor)
                    @php
                        $usos = (int) ($optionUsage[$valor->id] ?? 0);
                        $yaEnlazada = $linkedOptionIds->contains($valor->id);
                    @endphp

                    <button type="button"
                        x-show="! buscar || @js(mb_strtolower($valor->name)).includes(buscar.toLowerCase())"
                        @click="elegirOpcion({{ $valor->id }}, @js($valor->name), @js($valor->image_url), {{ $usos }})"
                        title="{{ $usos === 0 ? 'Ninguna entidad tuya tiene este valor' : $usos . ' entidades tienen este valor' }}"
                        :class="opcion === {{ $valor->id }}
                            ? 'border-cyan-500 ring-1 ring-cyan-500'
                            : '{{ $yaEnlazada ? 'border-violet-500/40' : 'border-slate-800' }} hover:border-slate-600'"
                        class="group overflow-hidden rounded-xl border bg-slate-950 text-left transition">

                        <span class="relative block aspect-square overflow-hidden bg-slate-900">
                            @if ($valor->image_url)
                                <img src="{{ $valor->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-base text-slate-700">◇</span>
                            @endif

                            <span class="absolute bottom-1 right-1 rounded px-1 font-mono text-[9px] font-black {{ $usos > 0 ? 'bg-slate-950/85 text-cyan-300' : 'bg-rose-500/80 text-white' }}">
                                {{ $usos }}
                            </span>

                            @if ($yaEnlazada)
                                <span class="absolute left-1 top-1 rounded bg-violet-500 px-1 text-[8px] font-black text-white"
                                    title="Esta definición ya tiene una regla con este valor">YA</span>
                            @endif

                            <span x-show="opcion === {{ $valor->id }}" x-cloak
                                class="absolute inset-0 flex items-center justify-center bg-cyan-500/30 text-base font-black text-white">✓</span>
                        </span>

                        <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300">
                            {{ $valor->name }}
                        </span>
                    </button>
                @endforeach

            </div>
        @endforeach

    </div>


    {{-- ============ PASO 3 · QUÉ HACE ============ --}}

    <div x-show="opcion" x-cloak x-collapse class="p-4">

        <div class="mb-2.5 flex items-center gap-2">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 font-mono text-[10px] font-black text-slate-950">3</span>
            <h3 class="text-[12px] font-black text-white">¿Qué hace esta regla?</h3>
        </div>

        <div class="grid gap-3 lg:grid-cols-[minmax(0,300px)_minmax(0,1fr)]">

            {{-- Lo elegido, con su cara --}}
            <div class="flex items-center gap-2.5 rounded-xl border border-cyan-500/30 bg-cyan-500/5 p-2.5">

                <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                    <template x-if="opcionImagen">
                        <img :src="opcionImagen" alt="" class="h-full w-full object-cover">
                    </template>
                    <template x-if="! opcionImagen">
                        <span class="flex h-full w-full items-center justify-center text-slate-700">◇</span>
                    </template>
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-black uppercase tracking-wider text-cyan-400/70" x-text="catalogoNombre"></span>
                    <span class="block truncate text-[13px] font-black text-white" x-text="opcionNombre"></span>
                    <span class="block font-mono text-[10px]"
                        :class="opcionUsos > 0 ? 'text-slate-500' : 'text-rose-400'"
                        x-text="opcionUsos + (opcionUsos === 1 ? ' entidad lo tiene' : ' entidades lo tienen')"></span>
                </span>
            </div>

            <div class="space-y-2.5">

                <div class="grid gap-2 sm:grid-cols-3">
                    <label>
                        <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600">Qué hace</span>
                        <select name="relation_type" x-model="tipo"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                            <option value="ACTIVATES">Activa la versión</option>
                            <option value="CONTEXT">Es su contexto</option>
                            <option value="RELATED">Solo relacionada</option>
                        </select>
                    </label>

                    <label x-show="tipo === 'ACTIVATES'" x-cloak>
                        <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600">Grupo</span>
                        <input type="number" name="condition_group" x-model="grupo" min="1" max="100"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                    </label>

                    <label x-show="tipo === 'ACTIVATES'" x-cloak>
                        <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600">Se une con</span>
                        <select name="logical_operator" x-model="operador"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                            <option value="AND">Y — también hace falta</option>
                            <option value="OR">O — basta con esta</option>
                        </select>
                    </label>
                </div>

                {{-- La frase, escrita antes de guardarla --}}
                <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] leading-relaxed text-slate-300">
                    <span x-show="tipo === 'ACTIVATES'">
                        Esta versión <strong class="text-cyan-300">se activará</strong> cuando la entidad
                        tenga <strong class="text-white" x-text="catalogoNombre + ' = «' + opcionNombre + '»'"></strong>,
                        <span x-text="grupo == 1 && {{ $activationGroups->count() }} === 0
                            ? 'y con eso basta.'
                            : 'dentro del grupo ' + grupo + '.'"></span>
                    </span>

                    <span x-show="tipo === 'CONTEXT'" x-cloak>
                        Queda anotado que esta versión ocurre en el contexto
                        <strong class="text-white" x-text="'«' + opcionNombre + '»'"></strong>.
                        <strong class="text-amber-300">No activa nada</strong>: es documentación.
                    </span>

                    <span x-show="tipo === 'RELATED'" x-cloak>
                        Queda anotado que esta versión tiene que ver con
                        <strong class="text-white" x-text="'«' + opcionNombre + '»'"></strong>.
                        <strong class="text-amber-300">No activa nada</strong>: es documentación.
                    </span>
                </p>

                <p x-show="tipo === 'ACTIVATES' && opcionUsos === 0" x-cloak
                    class="rounded-xl border border-rose-500/30 bg-rose-500/5 px-3 py-2 text-[10px] leading-relaxed text-rose-200">
                    Ojo: ninguna de tus entidades tiene ese valor, así que esta regla no se cumplirá
                    nunca mientras siga así. Puedes guardarla igual —a lo mejor lo asignas después—,
                    pero mejor saberlo ahora.
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit"
                        class="rounded-xl bg-cyan-500 px-4 py-2 text-[11px] font-black text-slate-950 transition hover:bg-cyan-400">
                        Añadir la regla
                    </button>

                    <p x-show="tipo === 'ACTIVATES'" class="min-w-0 flex-1 text-[10px] leading-relaxed text-slate-500">
                        Los <strong class="text-slate-300">grupos</strong> son alternativas entre sí —basta con
                        que se cumpla uno—; dentro de cada grupo las reglas se encadenan con su
                        <strong class="text-slate-300">Y</strong> o su <strong class="text-slate-300">O</strong>.
                        Si solo quieres una condición, deja el grupo en 1.
                    </p>
                </div>

            </div>

        </div>

    </div>

</form>
