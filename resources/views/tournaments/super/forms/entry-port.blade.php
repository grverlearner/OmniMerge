@php
    /*
     * Las puertas de entrada de una fase, dentro del torneo.
     *
     * Una fase puede tener más de una: la típica es «los que vienen de
     * arriba y los que vienen de la repesca entran por sitios distintos»,
     * y entonces la política de combinación decide qué pasa cuando llegan
     * por las dos.
     *
     * Es lo único de esta pantalla que edita algo de la fase y no del
     * torneo, y está bien que esté aquí: una puerta no significa nada sin
     * saber quién llama a ella, y eso solo se ve desde el torneo.
     */
    $editando = ($entry ?? null) === 'alpine';
@endphp

<form method="POST" class="rounded-lg border border-emerald-500/40 bg-slate-950/70 p-2"
    @if ($editando)
        :action="entry.update_url"
    @else
        :action="@js(route('tournaments.graph.entry-ports.store', [$tournamentTemplate, '__ID__'])).replace('__ID__', focused.id)"
    @endif>

    @csrf
    @if ($editando) @method('PUT') @endif

    <input type="text" name="name" required maxlength="120"
        @if ($editando) :value="entry.name" @else value="" @endif
        placeholder="Nombre de la puerta"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">

    <label class="mt-1.5 block">
        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
            Si llegan por varias rutas
        </span>

        <select name="merge_policy"
            @if ($editando) x-model="entry.merge_policy" @endif
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
            <option value="APPEND">Se juntan según llegan</option>
            <option value="WAIT_ALL">Se espera a todas</option>
            <option value="FIRST_AVAILABLE">Entra la primera que llegue</option>
            <option value="PRIORITY">Por orden de prioridad</option>
        </select>
    </label>

    <div class="mt-1.5 grid grid-cols-3 gap-1">

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Mín</span>
            <input type="number" name="min_participants" min="1" max="512"
                @if ($editando) :value="entry.min_participants" @endif
                placeholder="—"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-[10px] text-slate-100">
        </label>

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Máx</span>
            <input type="number" name="max_participants" min="1" max="512"
                @if ($editando) :value="entry.max_participants" @endif
                placeholder="—"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-[10px] text-slate-100">
        </label>

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Exactos</span>
            <input type="number" name="exact_participants" min="1" max="512"
                @if ($editando) :value="entry.exact_participants" @endif
                placeholder="—"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-[10px] text-slate-100">
        </label>

    </div>

    <label class="mt-1.5 flex items-center gap-1.5">
        <input type="hidden" name="is_required" value="0">
        <input type="checkbox" name="is_required" value="1"
            @if ($editando) :checked="entry.is_required" @endif
            class="h-3 w-3 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500">
        <span class="text-[9px] leading-relaxed text-slate-400">
            Obligatoria
            <span class="text-slate-600">— sin gente aquí, la fase no arranca.</span>
        </span>
    </label>

    <input type="hidden" name="accepts_multiple_connections" value="1">

    <button class="mt-1.5 w-full rounded-md bg-emerald-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-emerald-500">
        {{ $editando ? 'Guardar puerta' : 'Crear puerta' }}
    </button>

</form>
