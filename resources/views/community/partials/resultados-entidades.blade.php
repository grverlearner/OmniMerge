@php
    /*
     * Las entidades de la comunidad, de cinco formas.
     *
     * La especial es «por creador»: agrupa lo que se está viendo por quién lo
     * hizo, que es la pregunta que uno se hace de verdad al explorar una
     * comunidad —no «qué entidades hay» sino «quién está haciendo cosas
     * buenas»—.
     *
     * Los modos conviven ocultos con `x-show` y no desmontados, para que las
     * casillas de selección no pierdan lo marcado al cambiar de vista.
     */

    $porCreador = $items instanceof \Illuminate\Contracts\Pagination\Paginator
        || $items instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $items->getCollection()->groupBy('user_id')
        : $items->groupBy('user_id');
@endphp

{{-- ---------- GALERÍA ---------- --}}

<div x-show="vista === 'gallery'" x-cloak class="grid gap-2" :class="columnas">
    @foreach ($items as $entidad)
        @php $cara = $entidad->public_image_url ?: $entidad->image_url; @endphp

        <a href="{{ route('community.entities.show', $entidad) }}" title="{{ $entidad->name }}"
            class="group relative overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-violet-500/40">

            <span class="block aspect-[4/5] overflow-hidden bg-slate-900">
                @if ($cara)
                    <img src="{{ $cara }}" alt="" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                @endif
            </span>

            @if ($entidad->clones->isNotEmpty())
                <span class="absolute right-1 top-1 rounded bg-emerald-500 px-1 text-[8px] font-black text-emerald-950">✓</span>
            @endif

            <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                {{ $entidad->name }}
            </span>
            <span class="block truncate px-1.5 pb-1 text-center text-[9px] text-slate-600">
                {{ '@' . ($entidad->creator?->username ?? '?') }}
            </span>
        </a>
    @endforeach
</div>


{{-- ---------- CUADRÍCULA ---------- --}}

<div x-show="vista === 'grid'" class="grid gap-2.5" :class="columnas">
    @foreach ($items as $entidad)
        @include('community.partials.tarjeta-entidad', ['entidad' => $entidad])
    @endforeach
</div>


{{-- ---------- LISTA ---------- --}}

<div x-show="vista === 'list'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    @foreach ($items as $entidad)
        @php
            $cara = $entidad->public_image_url ?: $entidad->image_url;
            $miCopia = $entidad->clones->first();
        @endphp

        <div class="flex items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

            @auth
                @if ($entidad->user_id !== auth()->id() && $entidad->allow_cloning && ! $miCopia)
                    <label class="flex h-5 w-5 shrink-0 cursor-pointer items-center justify-center rounded border border-slate-700 transition hover:border-violet-500"
                        :class="seleccionadas.includes({{ $entidad->id }}) ? 'border-violet-500 bg-violet-500' : ''">
                        <input type="checkbox" value="{{ $entidad->id }}" x-model.number="seleccionadas" class="sr-only">
                        <span class="text-[10px] font-black text-white" x-show="seleccionadas.includes({{ $entidad->id }})">✓</span>
                    </label>
                @else
                    <span class="h-5 w-5 shrink-0"></span>
                @endif
            @endauth

            <a href="{{ route('community.entities.show', $entidad) }}"
                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                @if ($cara)
                    <img src="{{ $cara }}" alt="" loading="lazy" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('community.entities.show', $entidad) }}"
                        class="truncate text-[12px] font-black text-white">{{ $entidad->name }}</a>

                    @if ($entidad->entityType)
                        <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-500">
                            {{ $entidad->entityType->name }}
                        </span>
                    @endif
                </div>

                @include('community.partials.atribucion', [
                    'autor' => $entidad->creator,
                    'origen' => $entidad->sourceEntity?->creator,
                ])
            </div>

            <span class="hidden shrink-0 font-mono text-[10px] text-slate-600 sm:block"
                title="Veces copiada">↺{{ $entidad->clones_count }}</span>

            @include('community.partials.copiar', [
                'ruta' => route('community.entities.clone', $entidad),
                'esMio' => $entidad->user_id === auth()->id(),
                'miCopia' => $miCopia,
                'seDeja' => (bool) $entidad->allow_cloning,
                'rutaMia' => $miCopia ? route('entities.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </div>
    @endforeach
</div>


{{-- ---------- TABLA ---------- --}}

<div x-show="vista === 'table'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px]">
            <thead class="border-b border-slate-800 text-left">
                <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    <th class="px-4 py-2.5">Entidad</th>
                    <th class="px-3 py-2.5">Tipo</th>
                    <th class="px-3 py-2.5">Creador</th>
                    <th class="px-3 py-2.5">Inspirada en</th>
                    <th class="px-3 py-2.5 text-right">Atributos</th>
                    <th class="px-3 py-2.5 text-right">Copias</th>
                    <th class="px-3 py-2.5"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800/70">
                @foreach ($items as $entidad)
                    @php
                        $cara = $entidad->public_image_url ?: $entidad->image_url;
                        $miCopia = $entidad->clones->first();
                        $inspirada = $entidad->sourceEntity?->creator;
                    @endphp

                    <tr class="transition hover:bg-slate-950/50">
                        <td class="px-4 py-2">
                            <a href="{{ route('community.entities.show', $entidad) }}" class="flex items-center gap-2">
                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    @if ($cara)
                                        <img src="{{ $cara }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-700">◍</span>
                                    @endif
                                </span>
                                <span class="truncate text-[12px] font-black text-white">{{ $entidad->name }}</span>
                            </a>
                        </td>

                        <td class="px-3 py-2 text-[11px] text-slate-500">{{ $entidad->entityType?->name ?? '—' }}</td>

                        <td class="px-3 py-2 text-[11px]">
                            <a href="{{ route('community.creators.show', $entidad->creator->username) }}"
                                class="text-violet-300 transition hover:underline">
                                {{ '@' . $entidad->creator->username }}
                            </a>
                        </td>

                        <td class="px-3 py-2 text-[11px]">
                            @if ($inspirada)
                                <a href="{{ route('community.creators.show', $inspirada->username) }}"
                                    class="text-amber-400/80 transition hover:underline">
                                    {{ '@' . $inspirada->username }}
                                </a>
                            @else
                                <span class="text-slate-700">original</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400">
                            {{ $entidad->entity_attributes_count }}
                        </td>

                        <td class="px-3 py-2 text-right font-mono text-[11px] {{ $entidad->clones_count > 0 ? 'text-violet-300' : 'text-slate-700' }}">
                            {{ $entidad->clones_count }}
                        </td>

                        <td class="px-3 py-2 text-right">
                            @include('community.partials.copiar', [
                                'ruta' => route('community.entities.clone', $entidad),
                                'esMio' => $entidad->user_id === auth()->id(),
                                'miCopia' => $miCopia,
                                'seDeja' => (bool) $entidad->allow_cloning,
                                'rutaMia' => $miCopia ? route('entities.show', $miCopia) : '#',
                                'compacto' => true,
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- ---------- POR CREADOR ---------- --}}

<div x-show="vista === 'special'" x-cloak class="space-y-4">

    <p class="text-[10px] leading-relaxed text-slate-500">
        Lo que hay en esta página, repartido por quien lo hizo. Sirve para lo que uno hace de verdad al
        explorar: encontrar a alguien que trabaja bien y quedarse con su biblioteca entera.
    </p>

    @foreach ($porCreador as $idCreador => $suyas)
        @php $autor = $suyas->first()->creator; @endphp

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-3 py-2">

                <a href="{{ route('community.creators.show', $autor->username) }}"
                    class="flex min-w-0 flex-1 items-center gap-2.5">

                    <span class="h-9 w-9 shrink-0 overflow-hidden rounded-full border border-slate-700 bg-slate-950">
                        @if ($autor->avatar_url)
                            <img src="{{ $autor->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-[11px] font-black text-slate-500">
                                {{ mb_strtoupper(mb_substr($autor->username, 0, 1)) }}
                            </span>
                        @endif
                    </span>

                    <span class="min-w-0">
                        <span class="block truncate text-[12px] font-black text-white">{{ $autor->name }}</span>
                        <span class="block truncate text-[10px] font-bold text-violet-400">{{ '@' . $autor->username }}</span>
                    </span>
                </a>

                <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[11px] font-black text-slate-400">
                    {{ $suyas->count() }}
                </span>

                <a href="{{ route('community.creators.show', $autor->username) }}"
                    class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                    Su biblioteca →
                </a>
            </div>

            <div class="grid grid-cols-3 gap-2 p-3 sm:grid-cols-6 lg:grid-cols-8">
                @foreach ($suyas as $entidad)
                    @php $cara = $entidad->public_image_url ?: $entidad->image_url; @endphp

                    <a href="{{ route('community.entities.show', $entidad) }}" title="{{ $entidad->name }}"
                        class="group relative overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5">
                        <span class="block aspect-square overflow-hidden bg-slate-900">
                            @if ($cara)
                                <img src="{{ $cara }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-xl text-slate-800">◍</span>
                            @endif
                        </span>

                        @if ($entidad->clones->isNotEmpty())
                            <span class="absolute right-1 top-1 rounded bg-emerald-500 px-1 text-[8px] font-black text-emerald-950">✓</span>
                        @endif

                        <span class="block truncate px-1 py-1 text-center text-[9px] font-black text-slate-400">
                            {{ $entidad->name }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
