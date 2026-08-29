@php
    /*
     * 01 · IDENTIDAD — qué es este torneo.
     *
     * El nombre es lo único que la gente recordará, así que va grande y sin
     * caja: se escribe encima del propio título.
     *
     * El "contexto" no es decoración: es dónde ocurre este torneo dentro
     * del universo —una región, una organización— y sirve para distinguir
     * dos copas que se llaman parecido.
     */
    $t = $universeTournament ?? null;
@endphp

<section x-show="isOpen('identity')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-slate-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">01</span>
        <span class="text-[11px]">◈</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-slate-300">Identidad</h2>
        <span class="ml-auto text-[10px] text-slate-600">Qué es este torneo</span>
    </div>

    <div class="grid gap-4 p-4 lg:grid-cols-[auto_1fr]">

        {{-- ============ LA CARA ============ --}}

        <div x-data="{ preview: null }">

            <label class="group relative block h-32 w-32 cursor-pointer overflow-hidden rounded-2xl border border-slate-700 bg-slate-950">

                <template x-if="preview">
                    <img :src="preview" alt="" class="h-full w-full object-cover">
                </template>

                <template x-if="!preview">
                    <span class="flex h-full w-full items-center justify-center">
                        @if ($t?->image_url)
                            <img src="{{ $t->image_url }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="text-4xl text-amber-400/60">🏆</span>
                        @endif
                    </span>
                </template>

                <span class="absolute inset-x-0 bottom-0 bg-slate-950/80 py-1 text-center text-[9px] font-black text-slate-300 opacity-0 transition group-hover:opacity-100">
                    cambiar
                </span>

                <input type="file" name="image" accept="image/*" class="hidden"
                    @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
            </label>

            @if ($t?->image_url)
                <label class="mt-1.5 flex items-center gap-1.5">
                    <input type="checkbox" name="remove_image" value="1"
                        class="h-3 w-3 rounded border-slate-600 bg-slate-950 text-rose-500 focus:ring-rose-500">
                    <span class="text-[9px] text-slate-500">Quitar</span>
                </label>
            @endif

            <x-input-error :messages="$errors->get('image')" class="mt-1" />
        </div>


        {{-- ============ NOMBRE Y DEMÁS ============ --}}

        <div class="min-w-0 space-y-3">

            <div>
                <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Nombre del torneo
                </label>

                <input type="text" name="name" required maxlength="150"
                    value="{{ old('name', $t->name ?? '') }}"
                    placeholder="La Copa del Fuego"
                    class="mt-1 w-full border-0 border-b border-slate-700 bg-transparent px-0 py-1 text-xl font-black text-slate-100 placeholder:text-slate-700 focus:border-amber-500 focus:ring-0">

                <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
                    Es la marca, no una edición. «La Copa del Fuego», no
                    «La Copa del Fuego 2024».
                </p>

                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Descripción
                </label>

                <textarea name="description" rows="3" maxlength="2000"
                    placeholder="Qué es, quién lo organiza, por qué importa dentro del universo…"
                    class="mt-1 w-full rounded-xl border-slate-700 bg-slate-950 px-3 py-2 text-[12px] leading-relaxed text-slate-200 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">{{ old('description', $t->description ?? '') }}</textarea>

                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">

                <div>
                    <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Contexto
                    </label>

                    <input type="text" name="context" maxlength="150"
                        value="{{ old('context', $t->context ?? '') }}"
                        placeholder="País del Fuego · Academia · Liga menor"
                        class="mt-1 w-full rounded-xl border-slate-700 bg-slate-950 px-3 py-1.5 text-[12px] text-slate-200 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">

                    <p class="mt-1 text-[9px] text-slate-600">
                        Dónde ocurre dentro del universo. Distingue dos copas parecidas.
                    </p>

                    <x-input-error :messages="$errors->get('context')" class="mt-1" />
                </div>

                <div>
                    <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Estado
                    </label>

                    <div class="mt-1 grid grid-cols-2 gap-1.5">
                        @foreach (['ACTIVE' => ['Activo', 'emerald'], 'INACTIVE' => ['Inactivo', 'slate']] as $value => [$label, $tone])
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="{{ $value }}" class="peer sr-only"
                                    @checked(old('status', $t->status ?? 'ACTIVE') === $value)>

                                <span @class([
                                    'block rounded-xl border px-2 py-1.5 text-center text-[10px] font-black transition',
                                    'border-slate-800 bg-slate-950/60 text-slate-500 hover:border-slate-700',
                                    'peer-checked:border-emerald-400/60 peer-checked:bg-emerald-500/15 peer-checked:text-emerald-300' => $tone === 'emerald',
                                    'peer-checked:border-slate-500/60 peer-checked:bg-slate-700/40 peer-checked:text-slate-200' => $tone === 'slate',
                                ])>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <p class="mt-1 text-[9px] text-slate-600">
                        Inactivo no borra nada: deja de proponerse en temporadas nuevas.
                    </p>

                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>

            </div>


            {{-- ============ LA FORMA ============ --}}

            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">

                <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Forma por defecto
                </label>

                <select name="tournament_template_id"
                    class="mt-1 w-full rounded-xl border-slate-700 bg-slate-950 px-3 py-1.5 text-[12px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                    <option value="">— sin plantilla todavía —</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}"
                            @selected(old('tournament_template_id', $t->tournament_template_id ?? null) == $template->id)>
                            {{ $template->name }} · {{ $template->graph_nodes_count }} fases
                        </option>
                    @endforeach
                </select>

                <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
                    El recorrido con el que suele jugarse. Cada edición puede usar
                    otro: las temporadas crecen y a veces hace falta una fase previa
                    que antes no existía.
                </p>

                <x-input-error :messages="$errors->get('tournament_template_id')" class="mt-1" />
            </div>

        </div>

    </div>

</section>
