{{-- Una línea del registro de acciones: quién, qué, sobre qué y cuándo --}}

@php
    $destino = null;

    if ($accion->target_type === 'user' && $accion->target_id) {
        $destino = route('admin.users.show', $accion->target_id);
    } elseif ($accion->target_type && \App\Services\Admin\ContentRegistry::has($accion->target_type)) {
        $destino = route('admin.content.show', [$accion->target_type, $accion->target_id]);
    } elseif ($accion->action === 'settings.update') {
        $destino = route('admin.settings.edit');
    }

    $detalle = collect($accion->details ?? [])
        ->map(function ($valor, $clave) {
            if (is_array($valor)) {
                $valor = implode(', ', $valor);
            }

            return $valor === null || $valor === '' ? null : ucfirst($clave) . ': ' . $valor;
        })
        ->filter()
        ->implode(' · ');
@endphp

<div class="flex items-start gap-3 rounded-xl border bg-slate-950/40 p-2.5" style="border-color: {{ $accion->tone }}33;">
    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $accion->tone }}"></span>

    <div class="min-w-0 flex-1">
        <p class="text-xs text-slate-300">
            <span class="font-black text-white">{{ $accion->admin?->name ?? 'Alguien' }}</span>
            <span style="color: {{ $accion->tone }}">{{ \Illuminate\Support\Str::lcfirst($accion->label) }}</span>
            @if ($accion->target_label)
                @if ($destino)
                    <a href="{{ $destino }}" class="font-bold text-slate-100 underline decoration-slate-700 underline-offset-2 hover:decoration-slate-400">{{ $accion->target_label }}</a>
                @else
                    <span class="font-bold text-slate-100">{{ $accion->target_label }}</span>
                @endif
            @endif
        </p>

        @if ($detalle)
            <p class="mt-0.5 truncate text-[11px] text-slate-500" title="{{ $detalle }}">{{ $detalle }}</p>
        @endif
    </div>

    <span class="shrink-0 text-[10px] font-bold text-slate-600" title="{{ $accion->created_at }}">{{ $accion->created_at?->diffForHumans(short: true) }}</span>
</div>
