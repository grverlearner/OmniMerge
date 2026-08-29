@php
    /*
     * 01 · ESTA EDICIÓN — lo básico, y de dónde parte.
     *
     * Una edición nueva casi nunca se diseña de cero: se copia la anterior
     * y se cambia lo que cambió —más gente, otra fase previa, otro juego—.
     * Por eso lo primero que ofrece la pantalla no es un formulario en
     * blanco: es la lista de lo que ya se jugó.
     */
@endphp

<section x-show="isOpen('identity')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">01</span>
        <span class="text-[11px]">◈</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-slate-300">Esta edición</h2>
        <span class="ml-auto text-[10px] text-slate-600">{{ $universeTournament->name }}</span>
    </div>

    <div class="space-y-3 p-4">

        {{-- ============ DE DÓNDE PARTE ============ --}}

        @if (! $competition && count($previousEditions))
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Partir de una edición anterior
                </p>

                <p class="mt-0.5 text-[10px] leading-relaxed text-slate-600">
                    Trae su forma, su juego, cómo se peleaba y los premios que su
                    dueño marcó para arrastrarse. Lo que <span class="font-bold text-slate-400">no</span>
                    trae son sus competidores: eso se decide cada año.
                </p>

                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($previousEditions as $edicion)
                        <a href="{{ route('universes.competitions.create', $universe) }}?universe_tournament_id={{ $universeTournament->id }}&copy={{ $edicion['id'] }}"
                            class="flex items-center gap-2 rounded-lg border px-2.5 py-1.5 transition {{ ($source?->id ?? null) === $edicion['id']
                                ? 'border-sky-400/60 bg-sky-500/10'
                                : 'border-slate-800 bg-slate-950 hover:border-slate-600' }}">

                            <span class="font-mono text-[9px] text-slate-600">{{ $edicion['code'] }}</span>

                            <span class="text-[10px] font-black {{ ($source?->id ?? null) === $edicion['id'] ? 'text-sky-300' : 'text-slate-300' }}">
                                {{ $edicion['name'] }}
                            </span>

                            <span class="text-[9px] text-slate-600">
                                {{ $edicion['participants'] }} · {{ $edicion['created_at'] }}
                            </span>
                        </a>
                    @endforeach

                    @if ($source)
                        <a href="{{ route('universes.competitions.create', $universe) }}?universe_tournament_id={{ $universeTournament->id }}"
                            class="rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-500 transition hover:border-rose-500/40 hover:text-rose-300">
                            × empezar de cero
                        </a>
                    @endif
                </div>

                @if ($source)
                    <p class="mt-2 rounded-lg bg-sky-500/10 px-2 py-1 text-[10px] text-sky-300">
                        Copiando «{{ $source->name }}». Cambia lo que haga falta antes de crearla.
                    </p>
                @endif
            </div>
        @endif


        {{-- ============ NOMBRE Y CARTEL ============ --}}

        <div class="grid gap-3 lg:grid-cols-[1fr_auto]">

            <div class="space-y-3">

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Cómo se llama esta edición
                    </span>

                    <input type="text" name="name" x-model="name" maxlength="150" required
                        placeholder="{{ $universeTournament->name }} — edición 3"
                        class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-3 py-2 text-[13px] font-black text-slate-100 placeholder:text-slate-700 focus:border-slate-500 focus:ring-slate-500">

                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </label>

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        De qué va, si hace falta decirlo
                    </span>

                    <textarea name="description" rows="2" maxlength="2000"
                        placeholder="La primera que se juega con la fase previa."
                        class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-3 py-2 text-[11px] leading-relaxed text-slate-200 placeholder:text-slate-700 focus:border-slate-500 focus:ring-slate-500">{{ $designerValues['description'] }}</textarea>

                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </label>

                @if (count($seasons))
                    <label class="block">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            En qué temporada se juega
                        </span>

                        <select name="universe_season_id"
                            class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-3 py-1.5 text-[11px] text-slate-200 focus:border-slate-500 focus:ring-slate-500">
                            <option value="">— sin temporada —</option>
                            @foreach ($seasons as $season)
                                <option value="{{ $season->id }}"
                                    @selected((int) $designerValues['universe_season_id'] === (int) $season->id)>
                                    {{ $season->name }}
                                </option>
                            @endforeach
                        </select>

                        <x-input-error :messages="$errors->get('universe_season_id')" class="mt-1" />
                    </label>
                @endif
            </div>


            {{-- El cartel --}}

            <div x-data="{
                    preview: @js($designerValues['image_url']),
                    pick(event) {
                        const file = event.target.files?.[0];
                        if (!file) return;
                        this.preview = URL.createObjectURL(file);
                    }
                }"
                class="w-full lg:w-52">

                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Su cartel
                </span>

                <label class="mt-1 flex aspect-[3/4] cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-700 bg-slate-950 transition hover:border-slate-500">

                    <template x-if="preview">
                        <img :src="preview" alt="" class="h-full w-full object-cover">
                    </template>

                    <template x-if="!preview">
                        <span class="px-3 text-center text-[10px] leading-relaxed text-slate-600">
                            Subir una imagen<br>para esta edición
                        </span>
                    </template>

                    <input type="file" name="image" accept="image/*" class="hidden" @change="pick">
                </label>

                <x-input-error :messages="$errors->get('image')" class="mt-1" />
            </div>

        </div>

    </div>
</section>
