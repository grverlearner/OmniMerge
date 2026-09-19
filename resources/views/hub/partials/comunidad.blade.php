{{--
    Lo que se mira en la comunidad: lo público de otras personas con más
    visitas (sin lo oculto por un admin ni lo de cuentas bloqueadas, ver
    HubController), y al lado tu huella: cuánto has publicado, cuánto te han
    copiado y cuánto has traído.
--}}

@if ($sitio->get('community_open'))
    <section class="grid gap-5 xl:grid-cols-[1fr_320px]">

        <div>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-black text-white">
                        <x-omni-icon name="globo" size="h-5 w-5" class="text-emerald-300" /> Lo que se mira en la comunidad
                    </h2>
                    <p class="text-xs text-slate-500">Lo más visitado de otros creadores. Ábrelo y cópialo si te sirve.</p>
                </div>
                <a href="{{ route('community.home') }}" class="text-xs font-black text-emerald-300 hover:text-emerald-200">Explorar</a>
            </div>

            @if ($tendencias->isEmpty())
                <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center text-sm text-slate-500">
                    Todavía nadie ha publicado nada con imagen. Sé el primero desde tu Biblioteca.
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($tendencias as $t)
                        @php $m = $t['modelo']; @endphp
                        <a href="{{ $t['url'] }}"
                            class="group overflow-hidden rounded-2xl border border-emerald-500/20 bg-slate-900/60 transition hover:-translate-y-0.5 hover:border-emerald-500/50">
                            <span class="relative block aspect-[4/5] overflow-hidden">
                                <img src="{{ $m->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                <span class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></span>
                                <span class="absolute left-2 top-2 rounded-full bg-slate-950/85 px-2 py-0.5 text-[10px] font-black text-emerald-300">{{ $t['etiqueta'] }}</span>
                                <span class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-slate-950/85 px-2 py-0.5 text-[10px] font-black text-slate-200">
                                    <x-omni-icon name="ojo" size="h-3 w-3" /> {{ $m->views_count }}
                                </span>
                                <span class="absolute inset-x-0 bottom-0 p-2.5">
                                    <x-content-badges :type="$t['tipo']" :id="$m->id" size="xs" wrap="mb-1 flex flex-wrap gap-1" />
                                    <span class="block truncate text-sm font-black text-white">{{ $m->name }}</span>
                                    <span class="flex items-center gap-1 truncate text-[11px] text-slate-400">
                                        &#64;{{ $m->user?->username }} <x-creator-badge :user="$m->user" size="xs" />
                                    </span>
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="rounded-3xl border border-emerald-500/20 bg-gradient-to-b from-emerald-500/10 to-slate-900/60 p-5">
            <h3 class="text-sm font-black text-white">Tu huella en la comunidad</h3>
            <p class="text-xs text-slate-500">Lo que compartes y lo que otros hacen con ello.</p>

            <div class="mt-4 space-y-2">
                @foreach ([
                    ['Piezas públicas', $statistics['public'], 'globo', 'Entidades, colecciones y atributos que cualquiera puede ver.'],
                    ['Te han copiado', $statistics['copied_from_me'], 'copiar', 'Entidades tuyas que otras personas se llevaron.'],
                    ['Has traído', $statistics['brought'], 'flecha-izquierda', 'Entidades que copiaste de otros creadores.'],
                ] as [$texto, $valor, $icono, $pie])
                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-300">
                            <x-omni-icon :name="$icono" size="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-black text-slate-200">{{ $texto }}</span>
                            <span class="block text-[10px] leading-snug text-slate-500">{{ $pie }}</span>
                        </span>
                        <span class="text-2xl font-black text-white">{{ $valor }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <a href="{{ route('profiles.show', auth()->user()->username) }}"
                    class="rounded-xl bg-emerald-500 py-2 text-center text-xs font-black text-emerald-950 hover:bg-emerald-400">Mi perfil público</a>
                <a href="{{ route('community.creators.index') }}"
                    class="rounded-xl border border-emerald-500/40 py-2 text-center text-xs font-black text-emerald-200 hover:bg-emerald-500/10">Ver creadores</a>
            </div>
        </aside>
    </section>
@endif
