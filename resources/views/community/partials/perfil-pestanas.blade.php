@php
    /*
     * Las tres pestañas de un perfil: Todo · Biblioteca · Torneos.
     *
     * Había tres pantallas de perfil que se enlazaban con botones sueltos y no
     * parecían la misma cosa. Ahora comparten esta tira arriba, así que son una
     * sola ficha con tres vistas, y cada una sabe qué tipo de creador es la
     * persona. Una pestaña sin nada publicado se muestra apagada, no se esconde:
     * «este no publica torneos» también es información.
     *
     *   $persona  el User del perfil
     *   $activa   todo | biblioteca | torneos
     */

    $visible = $persona->isPublicProfile() || auth()->user()?->is($persona);

    if ($visible) {
        $resumen = app(\App\Services\Community\CreatorDirectory::class)->resumen($persona);
        $tipos = \App\Services\Community\CreatorDirectory::TIPOS;
    }
@endphp

@if ($visible)
    <nav class="mb-3 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900/60 p-1.5" aria-label="Vistas del perfil">

        @foreach ([['todo', 'Todo', 'chispa', route('profiles.show', $persona->username), null, '#34d399'], ['biblioteca', 'Biblioteca', 'libro', route('community.creators.show', $persona->username), $resumen['biblioteca'], '#818cf8'], ['torneos', 'Torneos', 'trofeo', route('tournaments.community.creator', $persona), $resumen['torneos'], '#fbbf24']] as [$clave, $texto, $icono, $destino, $cuenta, $tono])
            @php $apagada = $cuenta === 0; @endphp

            <a href="{{ $destino }}" @if ($activa === $clave) aria-current="page" @endif
                class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-[12px] font-black transition {{ $activa === $clave ? '' : 'hover:bg-slate-800' }}"
                style="{{ $activa === $clave ? "background-color: {$tono}; color: #020617" : ($apagada ? 'color: #475569' : 'color: #cbd5e1') }}"
                @if ($apagada) title="No ha publicado nada de este lado todavía" @endif>
                <x-omni-icon :name="$icono" size="h-4 w-4" />
                {{ $texto }}
                @if ($cuenta !== null)
                    <span class="rounded px-1 font-mono text-[10px]"
                        style="{{ $activa === $clave ? 'background-color: #02061733' : 'background-color: #1e293b' }}">{{ $cuenta }}</span>
                @endif
            </a>
        @endforeach

        <span class="flex-1"></span>

        @if ($resumen['tipo'])
            @php [$etiquetaTipo, $tonoTipo] = $tipos[$resumen['tipo']]; @endphp
            <span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase tracking-wider"
                style="color: {{ $tonoTipo }}; background-color: {{ $tonoTipo }}1f">{{ $etiquetaTipo }}</span>
        @else
            <span class="rounded-lg bg-slate-800 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500">
                Sin publicar
            </span>
        @endif

        <a href="{{ route('community.creators.index', $resumen['tipo'] ? ['tipo' => $resumen['tipo']] : []) }}"
            class="flex items-center gap-1.5 rounded-xl border border-slate-800 px-2.5 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-300">
            <x-omni-icon name="usuario" size="h-3.5 w-3.5" />
            Otros creadores
        </a>
    </nav>
@endif
