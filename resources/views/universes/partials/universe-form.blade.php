@php
    /*
     * La identidad del universo: nombre, descripcion, portada y estado.
     *
     * La comparten crear y editar, asi que el estado de Alpine NO vive aqui:
     * vive en la pantalla que lo incluye, porque en crear ese mismo estado
     * alimenta ademas el resumen de lo que se va a crear.
     *
     * El contrato que la pantalla debe declarar en su x-data:
     *
     *   nombre        string
     *   descripcion   string
     *   estado        'DRAFT' | 'ACTIVE' | 'ARCHIVED'
     *   portada       string|null   (url de vista previa)
     *   quitarPortada boolean
     *   cargarPortada(evento)
     *   limpiarPortada()
     */

    $editando = isset($universe);

    $estados = [
        'DRAFT' => ['#60a5fa', 'Borrador', 'Se queda a un lado mientras lo montas. Sigue siendo tuyo y puedes jugar en él.'],
        'ACTIVE' => ['#34d399', 'En marcha', 'El mundo está vivo: aparece el primero y se espera que se juegue.'],
        'ARCHIVED' => ['#64748b', 'Archivado', 'Terminado. Se conserva entero, pero deja de pedir tu atención.'],
    ];
@endphp

<input type="hidden" name="remove_image" :value="quitarPortada ? 1 : 0">


<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
            <x-omni-icon name="globo" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Quién es este mundo</h2>
            <p class="text-[10px] text-slate-500">
                Lo único obligatorio es el nombre. Todo lo demás se puede cambiar después.
            </p>
        </div>

        <span class="shrink-0 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-mono text-[10px] font-black text-slate-500"
            title="OmniMerge lo genera solo">
            {{ $editando ? $universe->code : $previewCode }}
        </span>
    </header>


    <div class="grid gap-4 p-4 lg:grid-cols-[180px_minmax(0,1fr)]">

        {{-- ---------- LA PORTADA ---------- --}}

        <div>
            <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">Portada</p>

            <label class="group relative block aspect-square cursor-pointer overflow-hidden rounded-xl border border-dashed border-slate-700 bg-slate-950 transition hover:border-violet-500">

                <template x-if="portada">
                    <img :src="portada" alt="" class="h-full w-full object-cover">
                </template>

                <template x-if="! portada">
                    <span class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-slate-700">
                        <x-omni-icon name="globo" size="h-8 w-8" />
                        <span class="text-[10px] font-black">Sin portada</span>
                    </span>
                </template>

                <span class="absolute inset-x-0 bottom-0 bg-slate-950/80 py-1.5 text-center text-[10px] font-black text-slate-300 opacity-0 transition group-hover:opacity-100">
                    <span x-text="portada ? 'Cambiar' : 'Elegir imagen'"></span>
                </span>

                <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                    @change="cargarPortada($event)" class="hidden">
            </label>

            <button type="button" @click="limpiarPortada()" x-show="portada" x-cloak
                class="mt-1.5 w-full rounded-lg border border-rose-500/30 bg-rose-500/10 px-2 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                Quitar la portada
            </button>

            <p class="mt-1.5 text-[9px] leading-3 text-slate-600">
                JPG, PNG o WEBP. Hasta 4 MB. Es la cara del mundo en la estantería.
            </p>

            <x-input-error :messages="$errors->get('image')" class="mt-1.5" />
        </div>


        {{-- ---------- NOMBRE, DESCRIPCIÓN Y ESTADO ---------- --}}

        <div class="space-y-3">

            <label class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Nombre <span class="text-rose-400">*</span>
                </span>

                <input type="text" name="name" x-model="nombre" required maxlength="150"
                    placeholder="Universo Shonen, Copa de Naciones, Mundo de pruebas…"
                    class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[13px] font-bold text-white placeholder:font-normal placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">

                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </label>


            <label class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    De qué va
                </span>

                <textarea name="description" x-model="descripcion" rows="3" maxlength="5000"
                    placeholder="Qué reúne este mundo y qué clase de torneos vivirán en él…"
                    class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500"></textarea>

                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </label>


            {{--
                El estado como tres opciones que se explican, no como un
                desplegable: «Borrador» y «Archivado» no significan lo mismo
                para todo el mundo, y aqui deciden si el mundo pide tu
                atencion o no.
            --}}
            <div>
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Cómo nace</p>

                <div class="mt-1 grid gap-1.5 sm:grid-cols-3">
                    @foreach ($estados as $valor => [$tono, $texto, $ayuda])
                        <label class="block cursor-pointer rounded-xl border p-2 transition"
                            :style="estado === '{{ $valor }}'
                                ? 'border-color: {{ $tono }}; background-color: {{ $tono }}14'
                                : 'border-color: #1e293b'">

                            <input type="radio" name="status" value="{{ $valor }}" x-model="estado" class="sr-only">

                            <span class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full" style="background-color: {{ $tono }}"></span>
                                <span class="text-[11px] font-black"
                                    :class="estado === '{{ $valor }}' ? 'text-white' : 'text-slate-400'">
                                    {{ $texto }}
                                </span>
                            </span>

                            <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                        </label>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('status')" class="mt-1" />
            </div>
        </div>
    </div>
</section>
