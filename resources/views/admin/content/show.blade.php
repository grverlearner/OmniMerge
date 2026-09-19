<x-admin-layout :title="$modelo->name">

    <x-slot:header>{{ $modelo->name }}</x-slot:header>

    <div class="space-y-5">

        <a href="{{ route('admin.content.index', $type) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white">
            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" /> {{ $meta['label'] }}
        </a>

        {{-- ========================================================= --}}
        {{-- LA PIEZA --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border bg-slate-900/60" style="border-color: {{ $meta['tone'] }}44;">
            <div class="grid md:grid-cols-[280px_1fr]">
                <div class="relative aspect-[4/3] md:aspect-auto md:min-h-[220px]" style="background-color: {{ $meta['tone'] }}12;">
                    @if ($modelo->image_url)
                        <img src="{{ $modelo->image_url }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center" style="color: {{ $meta['tone'] }}88;">
                            <x-omni-icon :name="$meta['icon']" size="h-16 w-16" />
                        </div>
                    @endif
                </div>

                <div class="p-5">
                    <p class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider" style="color: {{ $meta['tone'] }}">
                        <x-omni-icon :name="$meta['icon']" size="h-3.5 w-3.5" /> {{ $meta['module'] }} · {{ $meta['one'] }}
                    </p>
                    <h2 class="mt-1 text-2xl font-black text-white">{{ $modelo->name }}</h2>
                    <p class="text-xs text-slate-500">{{ $modelo->code }}</p>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <x-content-badges :type="$type" :id="$modelo->id" :flags="$marcas->keys()->all()" :all="true" />
                        @if ($modelo->trashed())
                            <span class="rounded-full border border-slate-600 bg-slate-800 px-2 py-0.5 text-[10px] font-black text-slate-300">Eliminado {{ $modelo->deleted_at->diffForHumans() }}</span>
                        @endif
                        @if ($meta['visibility'] && $modelo->visibility)
                            <span class="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-300">{{ \App\Services\Admin\ContentRegistry::VISIBILITIES[$modelo->visibility] ?? $modelo->visibility }}</span>
                        @endif
                        @if (isset($modelo->status) && $modelo->status)
                            <span class="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400">{{ $modelo->status }}</span>
                        @endif
                    </div>

                    @if ($modelo->description)
                        <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-slate-300">{{ $modelo->description }}</p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        @if ($dueno)
                            <a href="{{ route('admin.users.show', $dueno->id) }}" class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/50 py-1.5 pl-1.5 pr-3 hover:border-slate-700">
                                <x-user-avatar :user="$dueno" size="xs" />
                                <span class="text-xs font-bold text-slate-200">{{ $dueno->name }}</span>
                                <x-creator-badge :user="$dueno" size="xs" />
                            </a>
                        @endif

                        @if ($enlace)
                            <a href="{{ $enlace }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 px-3 py-2 text-xs font-black text-slate-200 hover:border-rose-500/50">
                                <x-omni-icon name="lapiz" size="h-3.5 w-3.5" /> Abrir su ficha y editarla
                            </a>
                        @endif
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                        @foreach (array_filter([
                            ['Creado', $modelo->created_at?->translatedFormat('j M Y')],
                            ['Último cambio', $modelo->updated_at?->diffForHumans()],
                            $meta['views'] ? ['Vistas', $modelo->views_count] : null,
                            $meta['clones'] ? ['Clonaciones', $modelo->clones_count] : null,
                        ]) as [$titulo, $valor])
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $titulo }}</dt>
                                <dd class="font-bold text-slate-200">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($interacciones->isNotEmpty())
                        <p class="mt-2 text-[11px] text-slate-500">En la comunidad: {{ $interacciones['VIEW'] ?? 0 }} visitas registradas y {{ $interacciones['CLONE'] ?? 0 }} clonaciones.</p>
                    @endif
                </div>
            </div>
        </section>


        <div class="grid gap-5 lg:grid-cols-3">

            {{-- ===================================================== --}}
            {{-- MARCAS --}}
            {{-- ===================================================== --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 lg:col-span-2">
                <h3 class="text-sm font-black text-white">Marcas del equipo</h3>
                <p class="text-xs text-slate-500">Las tres primeras se ven como insignia junto al nombre en la comunidad. «Oculto» la saca de la comunidad sin borrarla: su dueño la sigue viendo.</p>

                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\ContentFlag::FLAGS as $clave => $flag)
                        @php $puesta = $marcas->get($clave); @endphp
                        <form method="POST" action="{{ route('admin.content.flag', [$type, $modelo->id]) }}"
                            class="rounded-xl border p-3 transition" style="border-color: {{ $flag['tone'] }}{{ $puesta ? '88' : '33' }}; background-color: {{ $flag['tone'] }}{{ $puesta ? '14' : '05' }};">
                            @csrf
                            <input type="hidden" name="flag" value="{{ $clave }}">
                            <input type="hidden" name="on" value="{{ $puesta ? 0 : 1 }}">

                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-sm font-black" style="color: {{ $flag['tone'] }}">
                                    <x-omni-icon :name="$flag['icon']" size="h-4 w-4" /> {{ $flag['label'] }}
                                </span>
                                <button type="submit" @disabled($modelo->trashed())
                                    class="rounded-lg border px-2.5 py-1 text-[11px] font-black transition disabled:opacity-40 {{ $puesta ? 'border-slate-600 text-slate-300 hover:bg-slate-800' : 'text-white' }}"
                                    @unless ($puesta) style="border-color: {{ $flag['tone'] }}; background-color: {{ $flag['tone'] }}33;" @endunless>
                                    {{ $puesta ? 'Quitar' : 'Marcar' }}
                                </button>
                            </div>

                            @if ($puesta)
                                <p class="mt-1.5 text-[11px] text-slate-400">
                                    Desde {{ $puesta->created_at?->diffForHumans() }}{{ $puesta->creator ? ', por ' . $puesta->creator->name : '' }}.
                                    @if ($puesta->note) «{{ $puesta->note }}» @endif
                                </p>
                            @else
                                <input type="text" name="note" maxlength="255" placeholder="Nota interna (opcional)"
                                    class="mt-2 w-full rounded-lg border-slate-700 bg-slate-950 py-1 text-xs text-slate-100 placeholder:text-slate-600">
                            @endif
                        </form>
                    @endforeach
                </div>
            </section>


            {{-- ===================================================== --}}
            {{-- VISIBILIDAD Y BORRADO --}}
            {{-- ===================================================== --}}

            <section class="space-y-4">
                @if ($meta['visibility'] && ! $modelo->trashed())
                    <div class="rounded-2xl border border-violet-500/25 bg-slate-900/60 p-4">
                        <h3 class="text-sm font-black text-white">Visibilidad</h3>
                        <p class="text-xs text-slate-500">La misma que elige su dueño. Cambiarla aquí la cambia para él también.</p>

                        <form method="POST" action="{{ route('admin.content.visibility', [$type, $modelo->id]) }}" class="mt-3 space-y-2">
                            @csrf
                            @foreach (\App\Services\Admin\ContentRegistry::VISIBILITIES as $clave => $texto)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2 text-xs font-bold text-slate-300 has-[:checked]:border-violet-500 has-[:checked]:text-white">
                                    <input type="radio" name="visibility" value="{{ $clave }}" @checked($modelo->visibility === $clave) class="border-slate-600 bg-slate-950 text-violet-500">
                                    {{ $texto }}
                                </label>
                            @endforeach
                            <button class="w-full rounded-xl bg-violet-500 px-3 py-2 text-xs font-black text-white hover:bg-violet-400">Guardar visibilidad</button>
                        </form>
                    </div>
                @endif

                <div class="rounded-2xl border border-rose-500/25 bg-slate-900/60 p-4">
                    @if ($modelo->trashed())
                        <h3 class="text-sm font-black text-white">Está eliminado</h3>
                        <p class="text-xs text-slate-500">Nadie lo ve. Se puede recuperar tal como estaba.</p>
                        <form method="POST" action="{{ route('admin.content.restore', [$type, $modelo->id]) }}" class="mt-3">
                            @csrf
                            <button class="w-full rounded-xl bg-emerald-500 px-3 py-2 text-xs font-black text-white hover:bg-emerald-400">Recuperar</button>
                        </form>
                    @else
                        <h3 class="text-sm font-black text-white">Eliminar</h3>
                        <p class="text-xs text-slate-500">Desaparece para su dueño y para la comunidad, pero no se borra del todo: se puede recuperar desde «Eliminados».</p>
                        <form method="POST" action="{{ route('admin.content.destroy', [$type, $modelo->id]) }}" class="mt-3"
                            data-omni-confirm data-confirm-title="Eliminar «{{ $modelo->name }}»"
                            data-confirm-message="Dejará de verse para su dueño y en la comunidad. Podrás recuperarlo desde «Eliminados»."
                            data-confirm-action="Eliminar">
                            @csrf
                            @method('DELETE')
                            <button class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-rose-500/40 px-3 py-2 text-xs font-black text-rose-300 hover:bg-rose-500/10">
                                <x-omni-icon name="papelera" size="h-3.5 w-3.5" /> Eliminar
                            </button>
                        </form>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                    <h3 class="text-sm font-black text-white">Historial de administración</h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($historial as $accion)
                            @include('admin.partials.action-row', ['accion' => $accion])
                        @empty
                            <p class="text-xs text-slate-500">Ningún admin lo ha tocado todavía.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>

</x-admin-layout>
