{{--
    Tu huella en la comunidad.

    Cuánto de lo tuyo está publicado, cuánto se han llevado otros y cuántas
    veces lo han mirado. Si tu perfil es privado se dice aquí, arriba: es la
    razón por la que no apareces en Creadores, y no se ve desde ningún otro sitio.
--}}

@php
    $publicado = collect($huella)->sum('publicado');
    $copiasMias = collect($huella)->sum('copias');
    $vistasMias = collect($huella)->sum('vistas');
    $privado = ! $yo->isPublicProfile();
@endphp

<section class="overflow-hidden rounded-2xl border border-emerald-500/25 bg-slate-900/50">

    <div class="flex items-center gap-3 px-3 py-3" style="background: linear-gradient(120deg, #34d39922, transparent 70%)">
        <a href="{{ route('profiles.show', $yo->username) }}" class="shrink-0">
            <x-user-avatar :user="$yo" size="lg" square />
        </a>

        <div class="min-w-0 flex-1">
            <p class="text-[9px] font-black uppercase tracking-wider text-emerald-400">Tu huella</p>
            <a href="{{ route('profiles.show', $yo->username) }}" class="block truncate text-[14px] font-black text-white transition hover:text-emerald-300">{{ $yo->name }}</a>

            @if ($miTipo)
                <span class="mt-0.5 inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                    style="color: {{ $tiposCreador[$miTipo][1] }}; background-color: {{ $tiposCreador[$miTipo][1] }}1f">{{ $tiposCreador[$miTipo][0] }}</span>
            @else
                <span class="mt-0.5 inline-block rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-500">Aún sin publicar</span>
            @endif
        </div>
    </div>

    @if ($privado)
        <div class="mx-3 mb-2 flex items-start gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 p-2.5">
            <span class="mt-0.5 shrink-0 text-amber-300"><x-omni-icon name="candado" size="h-4 w-4" /></span>
            <p class="flex-1 text-[11px] leading-4 text-amber-100/90">
                Tu perfil es privado: no apareces en Creadores y nadie ve tu ficha, aunque tengas cosas publicadas.
            </p>
            <a href="{{ route('profile.edit') }}" class="shrink-0 rounded-lg bg-amber-400 px-2 py-1 text-[10px] font-black text-slate-950 transition hover:bg-amber-300">Cambiar</a>
        </div>
    @endif

    <div class="grid grid-cols-3 gap-px border-y border-slate-800 bg-slate-800">
        @foreach ([[$publicado, 'publicadas', '#34d399'], [$copiasMias, 'copias de lo tuyo', '#f472b6'], [$vistasMias, 'vistas', '#22d3ee']] as [$valor, $texto, $tono])
            <span class="bg-slate-900 px-2 py-2 text-center">
                <span class="block font-mono text-lg font-black" style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">{{ $texto }}</span>
            </span>
        @endforeach
    </div>

    <ul class="space-y-1.5 px-3 py-2.5">
        @foreach ($tipos as $tipo => [$singular, $plural, $tono, $icono])
            @php $h = $huella[$tipo]; @endphp
            <li class="flex items-center gap-2" title="{{ $h['publicado'] }} de {{ $h['total'] }} publicadas">
                <span class="shrink-0" style="color: {{ $tono }}"><x-omni-icon :name="$icono" size="h-3.5 w-3.5" /></span>
                <span class="w-20 shrink-0 truncate text-[11px] font-bold text-slate-400">{{ $plural }}</span>
                <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-800">
                    <span class="block h-full rounded-full" style="width: {{ $h['total'] > 0 ? round($h['publicado'] / $h['total'] * 100) : 0 }}%; background-color: {{ $tono }}"></span>
                </span>
                <span class="w-12 shrink-0 text-right font-mono text-[10px] text-slate-500">
                    <span style="color: {{ $h['publicado'] > 0 ? $tono : '#475569' }}">{{ $h['publicado'] }}</span>/{{ $h['total'] }}
                </span>
            </li>
        @endforeach
    </ul>

    <div class="grid grid-cols-2 gap-1.5 border-t border-slate-800 p-2">
        <a href="{{ route('profiles.show', $yo->username) }}"
            class="flex items-center justify-center gap-1.5 rounded-xl bg-emerald-500 px-2 py-2 text-[11px] font-black text-slate-950 transition hover:bg-emerald-400">
            <x-omni-icon name="chispa" size="h-3.5 w-3.5" />
            Mi perfil público
        </a>
        <a href="{{ route('profile.edit') }}"
            class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 px-2 py-2 text-[11px] font-black text-slate-300 transition hover:border-emerald-500 hover:text-emerald-300">
            <x-omni-icon name="ojo" size="h-3.5 w-3.5" />
            Qué se ve de lo mío
        </a>
    </div>
</section>
