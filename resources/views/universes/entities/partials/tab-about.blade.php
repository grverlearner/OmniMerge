@php
    /*
     * QUIÉN ES — sus atributos, su catálogo y sus versiones.
     *
     * Las versiones no son decoración: son con qué cara sale en cada
     * torneo. Un torneo definido como «los que llevan saga → shippuden»
     * enseña la versión que también lo lleva, y eso se decide con los
     * mismos atributos que se ven aquí.
     */
@endphp

<div x-show="tab === 'about'" x-cloak class="grid gap-3 lg:grid-cols-2">


    {{-- ============ SUS ATRIBUTOS ============ --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-2 border-b border-slate-800 bg-emerald-500/10 px-4 py-2">
            <span class="text-[11px]">◈</span>
            <h2 class="text-[11px] font-black uppercase tracking-wider text-emerald-300">Atributos</h2>
            <span class="ml-auto font-mono text-[10px] text-slate-600">{{ $attrs->count() }}</span>
        </div>

        <div class="p-3">
            @if ($attrs->isEmpty())
                <p class="rounded-xl border border-dashed border-slate-700 px-3 py-6 text-center text-[10px] leading-relaxed text-slate-600">
                    Este competidor no lleva ningún atributo.<br>
                    Sin atributos no puede entrar en un torneo que filtre por ellos.
                </p>
            @else
                <div class="space-y-1.5">
                    @foreach ($attrs as $a)
                        <div class="rounded-xl border p-2.5
                            {{ ($a['featured'] ?? false) ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-slate-800 bg-slate-950/60' }}">

                            <div class="flex items-center gap-1.5">
                                <span class="h-3 w-1 shrink-0 rounded-full
                                    {{ ($a['featured'] ?? false) ? 'bg-emerald-400' : 'bg-slate-700' }}"></span>

                                <span class="text-[11px] font-black text-slate-200">{{ $a['name'] }}</span>

                                @if ($a['featured'] ?? false)
                                    <span class="rounded bg-emerald-500/20 px-1 py-0.5 text-[8px] font-black text-emerald-300">
                                        destacado
                                    </span>
                                @endif

                                @if ($a['numeric'] ?? null)
                                    <span class="ml-auto font-mono text-[11px] font-black text-slate-300">
                                        {{ rtrim(rtrim(number_format((float) $a['numeric'], 2, ',', ''), '0'), ',') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Su catálogo: cada valor, uno a uno --}}

                            <div class="mt-1.5 flex flex-wrap gap-1 pl-2.5">
                                @forelse ((array) ($a['values'] ?? []) as $v)
                                    <a href="{{ route('universes.entities.index', $universe) }}?attr[{{ mb_strtolower($a['name']) }}][]={{ urlencode(mb_strtolower($v)) }}"
                                        class="rounded-full border border-slate-800 bg-slate-950 px-2 py-0.5 text-[9px] font-bold text-slate-300 transition hover:border-emerald-500/50 hover:text-emerald-200"
                                        title="Ver a todos los que llevan esto">
                                        {{ $v }}
                                    </a>
                                @empty
                                    <span class="text-[9px] text-slate-700">{{ $a['display'] ?? '—' }}</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>


    {{-- ============ SUS VERSIONES ============ --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-2 border-b border-slate-800 bg-violet-500/10 px-4 py-2">
            <span class="text-[11px]">◎</span>
            <h2 class="text-[11px] font-black uppercase tracking-wider text-violet-300">Versiones</h2>
            <span class="ml-auto font-mono text-[10px] text-slate-600">{{ count($versiones) }}</span>
        </div>

        <div class="p-3">

            <p class="mb-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-[10px] leading-relaxed text-slate-500">
                Con cuál sale depende del torneo. Uno definido como
                <span class="font-bold text-slate-300">«saga → shippuden»</span> enseña la versión
                que también lo lleva; sin reglas, la marcada como base
                <span class="text-violet-300">★</span>.
            </p>

            @if (empty($versiones))
                <p class="rounded-xl border border-dashed border-slate-700 px-3 py-6 text-center text-[10px] leading-relaxed text-slate-600">
                    No tiene versiones: sale siempre con la misma imagen.<br>
                    Se crean en la Biblioteca y se traen con
                    <span class="text-sky-400">↻ traer de la Biblioteca</span>.
                </p>
            @else
                <div class="grid gap-1.5 sm:grid-cols-2">
                    @foreach ($versiones as $v)
                        <div class="overflow-hidden rounded-xl border
                            {{ $v['is_base'] ? 'border-violet-500/50 bg-violet-500/5' : 'border-slate-800 bg-slate-950/60' }}">

                            <div class="flex gap-2 p-2">

                                <span class="flex h-16 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950">
                                    @if ($v['image_url'])
                                        <img src="{{ $v['image_url'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="px-1 text-center text-[8px] leading-tight text-slate-700">sin<br>imagen</span>
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-1">
                                        <span class="truncate text-[11px] font-black text-slate-100">{{ $v['name'] }}</span>
                                        @if ($v['is_base'])
                                            <span class="shrink-0 text-[10px] text-violet-400" title="La versión base">★</span>
                                        @endif
                                    </span>

                                    @if ($v['version_name'])
                                        <span class="block truncate font-mono text-[8px] text-slate-600">{{ $v['version_name'] }}</span>
                                    @endif

                                    @if ($v['description'])
                                        <span class="mt-0.5 block line-clamp-2 text-[9px] leading-relaxed text-slate-500">{{ $v['description'] }}</span>
                                    @endif
                                </span>
                            </div>

                            {{--
                                Los atributos de la versión. Son los que
                                deciden si esta cara es la que sale en un
                                torneo concreto, así que no son un adorno.
                            --}}
                            <div class="border-t px-2 py-1.5
                                {{ $v['is_base'] ? 'border-violet-500/20' : 'border-slate-800' }}">

                                <span class="flex flex-wrap gap-0.5">
                                    @forelse ($v['attributes'] as $a)
                                        <span class="rounded bg-slate-950 px-1 py-0.5 text-[8px]">
                                            <span class="text-slate-600">{{ $a['name'] }}</span>
                                            <span class="ml-1 text-slate-300">{{ $a['display'] ?? implode(', ', $a['values'] ?? []) }}</span>
                                        </span>
                                    @empty
                                        <span class="text-[8px] text-slate-700">
                                            sin atributos propios: solo se elige si nada más encaja
                                        </span>
                                    @endforelse
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

</div>
