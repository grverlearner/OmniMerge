@php
    /*
     * Comparar versiones de una entidad.
     *
     * La pantalla existía y ponía las columnas una al lado de otra, pero no
     * hacía lo único que hace útil una comparación: **decir en qué se
     * diferencian**. Todas las filas se veían igual, así que había que leer
     * cuatro columnas enteras para encontrar los tres valores que cambian.
     *
     * Ahora las filas que cambian se resaltan y las que son iguales en todas se
     * apagan, con un interruptor para esconderlas del todo. Y elegir qué
     * versiones comparar se hace por la cara, no en un desplegable.
     */

    /* Qué filas cambian de verdad. */
    $clavesDeColumna = $columns->pluck('key');

    $filas = $rows->map(function ($fila) use ($clavesDeColumna) {
        $valores = $clavesDeColumna->map(fn($clave) => $fila['values'][$clave] ?? null);

        $fila['difiere'] = $valores->map(fn($v) => (string) ($v ?? ''))->unique()->count() > 1;

        $fila['vacia'] = $valores->filter(fn($v) => filled($v))->isEmpty();

        return $fila;
    });

    $cuantasDifieren = $filas->where('difiere', true)->count();

    $seleccionadas = $selectedVersions->pluck('id');
@endphp

<x-app-layout :title="'Comparar versiones de ' . $entity->name" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <div x-data="{
        soloDiferencias: false,
        eligiendo: {{ $selectedVersions->isEmpty() ? 'true' : 'false' }},
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('entity-versions.index', $entity) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($entity->image_url)
                    <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('entity-versions.index', $entity) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-cyan-400">
                    ← Versiones de {{ $entity->name }}
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    Comparar versiones
                </h1>

                <p class="text-[10px] text-slate-500">
                    Sus características, una columna por versión. La primera es siempre la entidad original.
                </p>
            </div>

            <button type="button" @click="eligiendo = !eligiendo"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-cyan-500 hover:text-cyan-300">
                <span x-text="eligiendo ? 'Ocultar el selector' : 'Elegir cuáles comparar'"></span>
            </button>
        </header>


        @include('versions.partials.workspace-navigation')


        {{-- ===================================================== --}}
        {{-- ELEGIR CUÁLES --}}
        {{-- ===================================================== --}}

        <form method="GET" action="{{ route('entity-versions.compare', $entity) }}"
            x-show="eligiendo" x-cloak x-collapse
            class="overflow-hidden rounded-2xl border border-cyan-500/25 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                    <x-omni-icon name="controles" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] text-slate-400">
                    Marca hasta <strong class="text-cyan-300">cuatro</strong> versiones. La entidad original
                    entra siempre, para saber de qué se parte.
                </p>

                <button type="submit"
                    class="rounded-xl bg-cyan-500 px-4 py-2 text-[11px] font-black text-slate-950 transition hover:bg-cyan-400">
                    Comparar
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($entity->entityVersions as $candidata)
                    @php $marcada = $seleccionadas->contains($candidata->id); @endphp

                    <label class="group cursor-pointer overflow-hidden rounded-xl border bg-slate-950 transition has-[:checked]:border-cyan-500 has-[:checked]:ring-1 has-[:checked]:ring-cyan-500 {{ $marcada ? '' : 'border-slate-800 hover:border-slate-600' }}">

                        <input type="checkbox" name="versions[]" value="{{ $candidata->id }}"
                            @checked($marcada) class="peer sr-only">

                        <span class="relative block aspect-square overflow-hidden bg-slate-900">
                            @if ($candidata->image_url)
                                <img src="{{ $candidata->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◈</span>
                            @endif

                            <span class="absolute inset-0 hidden items-center justify-center bg-cyan-500/30 text-lg font-black text-white peer-checked:flex">✓</span>
                        </span>

                        <span class="block px-1.5 py-1">
                            <span class="block truncate text-[10px] font-black text-white">{{ $candidata->name }}</span>
                            <span class="block truncate text-[9px] text-violet-300">{{ $candidata->version?->name }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </form>


        @if ($selectedVersions->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="controles" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-lg font-black text-white">Elige al menos una versión</h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    Comparar pone las características de varias versiones en columnas y marca en qué se
                    diferencian. Con una sola ya se ve qué cambia respecto a la entidad original.
                </p>
            </div>

        @else

            {{-- ===================================================== --}}
            {{-- QUÉ SE ESTÁ MIRANDO --}}
            {{-- ===================================================== --}}

            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/50 px-4 py-3">

                <div class="min-w-0 flex-1">
                    <p class="text-[12px] font-black text-white">
                        {{ $filas->count() }}
                        {{ $filas->count() === 1 ? 'característica' : 'características' }} en total
                    </p>

                    <p class="text-[10px] leading-relaxed text-slate-500">
                        @if ($cuantasDifieren === 0)
                            <span class="font-black text-slate-400">Ninguna cambia.</span>
                            Estas versiones se distinguen por su cara y su nombre, no por sus datos.
                        @else
                            <strong class="text-cyan-300">{{ $cuantasDifieren }}</strong>
                            {{ $cuantasDifieren === 1 ? 'cambia' : 'cambian' }} de una versión a otra;
                            {{ $filas->count() - $cuantasDifieren }}
                            {{ $filas->count() - $cuantasDifieren === 1 ? 'es igual' : 'son iguales' }} en todas.
                        @endif
                    </p>
                </div>

                @if ($cuantasDifieren > 0)
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                        <input type="checkbox" x-model="soloDiferencias"
                            class="rounded border-slate-700 bg-slate-900 text-cyan-500">
                        <span class="text-[11px] font-black text-slate-300">Solo lo que cambia</span>
                    </label>
                @endif
            </div>


            {{-- ===================================================== --}}
            {{-- LA COMPARACIÓN --}}
            {{-- ===================================================== --}}

            <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full" style="min-width: {{ 200 + $columns->count() * 190 }}px">

                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="sticky left-0 z-10 bg-slate-900 px-3 py-3 text-left align-bottom">
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    Característica
                                </span>
                            </th>

                            @foreach ($columns as $columna)
                                <th class="px-3 py-3 text-left align-bottom {{ $loop->first ? 'bg-slate-950/40' : '' }}">

                                    <span class="mb-1.5 block h-20 w-20 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                                        @if ($columna['image_url'])
                                            <img src="{{ $columna['image_url'] }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-xl text-slate-700">
                                                {{ $loop->first ? '◍' : '◈' }}
                                            </span>
                                        @endif
                                    </span>

                                    <span class="block text-[9px] font-black uppercase tracking-wider {{ $loop->first ? 'text-slate-600' : 'text-violet-400' }}">
                                        {{ $columna['label'] }}
                                    </span>

                                    <span class="block max-w-[170px] truncate text-[12px] font-black text-white">
                                        {{ $columna['name'] }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($filas as $fila)
                            <tr x-show="! soloDiferencias || {{ $fila['difiere'] ? 'true' : 'false' }}"
                                class="transition {{ $fila['difiere'] ? 'bg-cyan-500/[0.04] hover:bg-cyan-500/[0.08]' : 'hover:bg-slate-900/60' }}">

                                <th scope="row"
                                    class="sticky left-0 z-10 px-3 py-2 text-left {{ $fila['difiere'] ? 'bg-slate-900' : 'bg-slate-900' }}">
                                    <span class="flex items-center gap-1.5">
                                        @if ($fila['difiere'])
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-cyan-400"
                                                title="Cambia de una versión a otra"></span>
                                        @else
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-slate-800"></span>
                                        @endif

                                        <span class="truncate text-[11px] font-black {{ $fila['difiere'] ? 'text-white' : 'text-slate-500' }}">
                                            {{ $fila['attribute']->name }}
                                        </span>
                                    </span>
                                </th>

                                @foreach ($columns as $columna)
                                    @php $valor = $fila['values'][$columna['key']] ?? null; @endphp

                                    <td class="px-3 py-2 align-top {{ $loop->first ? 'bg-slate-950/40' : '' }}">
                                        @if (filled($valor))
                                            <span class="text-[11px] leading-relaxed {{ $fila['difiere'] ? 'text-slate-100' : 'text-slate-500' }}">
                                                {{ $valor }}
                                            </span>
                                        @else
                                            <span class="text-[11px] text-slate-700">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

            <p class="px-1 text-[10px] leading-relaxed text-slate-600">
                Un punto <span class="text-cyan-400">azul</span> marca las características que
                <strong class="text-slate-400">cambian</strong> entre estas versiones; las apagadas son
                iguales en todas. Un guion significa que esa versión no tiene valor para esa
                característica —ni propio ni heredado—.
            </p>

        @endif

    </div>

</x-app-layout>
