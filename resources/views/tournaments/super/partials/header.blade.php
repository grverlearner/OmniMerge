@php
    /*
     * La identidad del torneo: lo que es, no lo que hace.
     *
     * Se edita aquí mismo y en un solo formulario, porque son cuatro campos
     * y mandarlos a otra pantalla para cambiar un nombre es justo lo que
     * esta Super Edición viene a quitar.
     *
     * La imagen se previsualiza con un blob local antes de subirla: así se
     * ve el cambio al instante, y si no se guarda no ha pasado nada.
     */
@endphp

<header class="shrink-0 border-b border-slate-800 bg-slate-900/70"
    x-data="{
        open: false,
        preview: null,

        pick(event) {
            const file = event.target.files?.[0];

            if (!file) return;

            if (this.preview) URL.revokeObjectURL(this.preview);

            this.preview = URL.createObjectURL(file);
        },
    }">

    <form method="POST" enctype="multipart/form-data"
        action="{{ route('tournaments.super.update', $tournamentTemplate) }}">

        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-center gap-3 px-3 py-2">

            {{-- ============ VOLVER ============ --}}

            <a href="{{ route('tournaments.templates.show', $tournamentTemplate) }}"
                class="shrink-0 rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100"
                title="Volver al torneo">←</a>


            {{-- ============ CARA ============ --}}

            <label class="group relative h-11 w-11 shrink-0 cursor-pointer overflow-hidden rounded-xl border border-slate-700 bg-slate-950">
                <template x-if="preview">
                    <img :src="preview" alt="" class="h-full w-full object-cover">
                </template>

                <template x-if="!preview">
                    <span class="flex h-full w-full items-center justify-center">
                        @if ($tournamentTemplate->image_url)
                            <img src="{{ $tournamentTemplate->image_url }}" alt=""
                                class="h-full w-full object-cover">
                        @else
                            <span class="text-lg text-amber-400">⛯</span>
                        @endif
                    </span>
                </template>

                <span class="absolute inset-0 flex items-center justify-center bg-slate-950/70 text-[9px] font-black text-slate-200 opacity-0 transition group-hover:opacity-100">
                    ✎
                </span>

                <input type="file" name="image" accept="image/*" class="hidden" @change="pick($event)">
            </label>


            {{-- ============ NOMBRE ============ --}}

            <div class="min-w-0 flex-1">

                <div class="flex items-center gap-1.5">
                    <span class="font-mono text-[9px] font-bold text-slate-600">
                        {{ $tournamentTemplate->code }}
                    </span>

                    <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-[0.16em] text-amber-300">
                        Super Edición · Torneo
                    </span>
                </div>

                <input type="text" name="name" required maxlength="160"
                    value="{{ old('name', $tournamentTemplate->name) }}"
                    class="mt-0.5 w-full max-w-lg truncate border-0 border-b border-transparent bg-transparent px-0 py-0 text-base font-black text-slate-100 focus:border-amber-500 focus:ring-0">

            </div>


            {{-- ============ CIFRAS DEL GRAFO ============ --}}

            <div class="flex shrink-0 items-center gap-1.5">

                @foreach ([
                    ['clave' => 'starts', 'etiqueta' => 'Entradas', 'color' => 'text-emerald-300'],
                    ['clave' => 'nodes', 'etiqueta' => 'Fases', 'color' => 'text-sky-300'],
                    ['clave' => 'connections', 'etiqueta' => 'Rutas', 'color' => 'text-violet-300'],
                    ['clave' => 'terminals', 'etiqueta' => 'Finales', 'color' => 'text-rose-300'],
                ] as $cifra)
                    <div class="rounded-lg border border-slate-800 bg-slate-950/60 px-2 py-1 text-center">
                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">
                            {{ $cifra['etiqueta'] }}
                        </p>
                        <p class="font-mono text-sm font-black {{ $cifra['color'] }}"
                            x-text="stats.{{ $cifra['clave'] }} ?? 0"></p>
                    </div>
                @endforeach

            </div>


            {{-- ============ ESTADO Y GUARDADO ============ --}}

            <div class="flex shrink-0 items-center gap-1.5">

                <span class="rounded-full px-2 py-1 text-[9px] font-black uppercase tracking-wider"
                    :class="isValid
                        ? 'bg-emerald-500/15 text-emerald-300'
                        : 'bg-rose-500/15 text-rose-300'"
                    x-text="isValid ? '✓ válido' : (stats.errors ?? 0) + ' problemas'"></span>

                <button type="button" @click="open = !open"
                    class="rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100"
                    title="Más ajustes">⚙</button>

                <button class="rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                    Guardar
                </button>

            </div>

        </div>


        {{-- ============ AJUSTES QUE NO CABEN ARRIBA ============ --}}

        <div x-show="open" x-cloak
            class="grid gap-2 border-t border-slate-800 bg-slate-950/50 px-3 py-2 sm:grid-cols-2 lg:grid-cols-6">

            <label class="sm:col-span-2 lg:col-span-2">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Descripción</span>
                <textarea name="description" rows="2" maxlength="2000"
                    class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">{{ old('description', $tournamentTemplate->description) }}</textarea>
            </label>

            <label>
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Mínimo</span>
                <input type="number" name="min_participants" min="2" max="1024"
                    value="{{ old('min_participants', $tournamentTemplate->min_participants) }}"
                    class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-center text-[11px] font-bold text-slate-100 focus:border-amber-500 focus:ring-amber-500">
            </label>

            <label>
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Máximo</span>
                <input type="number" name="max_participants" min="2" max="1024"
                    value="{{ old('max_participants', $tournamentTemplate->max_participants) }}"
                    class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-center text-[11px] font-bold text-slate-100 focus:border-amber-500 focus:ring-amber-500">
            </label>

            <label>
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Estado</span>
                <select name="status"
                    class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                    @foreach (['DRAFT' => 'Borrador', 'ACTIVE' => 'Activo', 'ARCHIVED' => 'Archivado'] as $valor => $texto)
                        <option value="{{ $valor }}" @selected(old('status', $tournamentTemplate->status) === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Visibilidad</span>
                <select name="visibility"
                    class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                    @foreach (['PRIVATE' => 'Privado', 'UNLISTED' => 'Con enlace', 'PUBLIC' => 'Público'] as $valor => $texto)
                        <option value="{{ $valor }}" @selected(old('visibility', $tournamentTemplate->visibility) === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </label>

            <label class="flex items-center gap-1.5 sm:col-span-2 lg:col-span-6">
                <input type="hidden" name="allow_byes" value="0">
                <input type="checkbox" name="allow_byes" value="1"
                    @checked(old('allow_byes', $tournamentTemplate->allow_byes))
                    class="h-3 w-3 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">
                <span class="text-[9px] leading-relaxed text-slate-400">
                    Permitir descansos
                    <span class="text-slate-600">— si el número no encaja, alguien pasa sin jugar.</span>
                </span>
            </label>

            @if ($tournamentTemplate->image_url)
                <label class="flex items-center gap-1.5 sm:col-span-2 lg:col-span-6">
                    <input type="checkbox" name="remove_image" value="1"
                        class="h-3 w-3 rounded border-slate-600 bg-slate-950 text-rose-500 focus:ring-rose-500">
                    <span class="text-[9px] text-slate-400">Quitar la imagen</span>
                </label>
            @endif

        </div>

    </form>


    @if (session('success'))
        <p class="border-t border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[10px] font-bold text-emerald-300">
            {{ session('success') }}
        </p>
    @endif

    @if ($errors->any())
        <p class="border-t border-rose-500/30 bg-rose-500/10 px-3 py-1 text-[10px] font-bold text-rose-300">
            {{ $errors->first() }}
        </p>
    @endif

</header>
