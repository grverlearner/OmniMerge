@php
    /*
     * Quien eres: los datos que viajan con cada cosa que publicas.
     *
     * La visibilidad del perfil se decide aqui, y con la consecuencia escrita
     * al lado de cada opcion: «público» y «privado» no significan lo mismo para
     * todo el mundo, y aqui deciden si alguien puede llegar a lo que has hecho.
     */

    $usuario = $user;
@endphp

<section x-data="{
        avatar: @js($usuario->avatar_url),
        quitarAvatar: false,

        cargarAvatar(evento) {
            const fichero = evento.target.files[0];
            if (! fichero) return;
            this.avatar = URL.createObjectURL(fichero);
            this.quitarAvatar = false;
        },

        limpiarAvatar() {
            this.avatar = null;
            this.quitarAvatar = true;
        },

        visibilidad: @js(old('profile_visibility', $usuario->profile_visibility ?? 'PRIVATE')),
    }"
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
            <x-omni-icon name="usuario" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Quién eres</h2>
            <p class="text-[10px] text-slate-500">
                Estos datos viajan con cada cosa que publicas.
            </p>
        </div>
    </header>


    @if (session('status') === 'profile-updated')
        <p class="border-b border-emerald-500/20 bg-emerald-500/10 px-4 py-2 text-[11px] font-bold text-emerald-200">
            Guardado.
        </p>
    @endif


    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
        class="p-4">

        @csrf
        @method('PATCH')

        <input type="hidden" name="remove_avatar" :value="quitarAvatar ? 1 : 0">


        <div class="grid gap-4 lg:grid-cols-[160px_minmax(0,1fr)]">

            {{-- ---------- LA FOTO ---------- --}}

            <div>
                <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">Tu foto</p>

                <label class="group relative block aspect-square cursor-pointer overflow-hidden rounded-2xl border border-dashed border-slate-700 bg-slate-950 transition hover:border-indigo-500">

                    <template x-if="avatar">
                        <img :src="avatar" alt="" class="h-full w-full object-cover">
                    </template>

                    <template x-if="! avatar">
                        <span class="flex h-full w-full items-center justify-center text-[30px] font-black text-slate-700">
                            {{ $usuario->initials }}
                        </span>
                    </template>

                    <span class="absolute inset-x-0 bottom-0 bg-slate-950/80 py-1.5 text-center text-[10px] font-black text-slate-300 opacity-0 transition group-hover:opacity-100">
                        <span x-text="avatar ? 'Cambiar' : 'Elegir foto'"></span>
                    </span>

                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp"
                        @change="cargarAvatar($event)" class="hidden">
                </label>

                <button type="button" @click="limpiarAvatar()" x-show="avatar" x-cloak
                    class="mt-1.5 w-full rounded-lg border border-rose-500/30 bg-rose-500/10 px-2 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                    Quitar la foto
                </button>

                <p class="mt-1.5 text-[9px] leading-3 text-slate-600">
                    JPG, PNG o WEBP. Hasta 3 MB. Sin foto se usan tus iniciales.
                </p>

                <x-input-error :messages="$errors->get('avatar')" class="mt-1.5" />
            </div>


            {{-- ---------- LOS DATOS ---------- ---------- --}}

            <div class="space-y-3">

                <div class="grid gap-3 sm:grid-cols-2">

                    <label class="block">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Nombre <span class="text-rose-400">*</span>
                        </span>
                        <input type="text" name="name" value="{{ old('name', $usuario->name) }}"
                            required maxlength="100"
                            class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[13px] font-bold text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </label>

                    <label class="block">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Nombre de usuario <span class="text-rose-400">*</span>
                        </span>
                        <span class="relative mt-1 block">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-mono text-[13px] font-black text-slate-600">@</span>
                            <input type="text" name="username" value="{{ old('username', $usuario->username) }}"
                                required minlength="3" maxlength="50"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 pl-7 font-mono text-[13px] font-bold text-white focus:border-indigo-500 focus:ring-indigo-500">
                        </span>
                        <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">
                            Es tu dirección: /perfil/{{ $usuario->username }}
                        </span>
                        <x-input-error :messages="$errors->get('username')" class="mt-1" />
                    </label>
                </div>


                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Presentación corta
                    </span>
                    <input type="text" name="headline" value="{{ old('headline', $usuario->headline) }}"
                        maxlength="120" placeholder="Construyo mundos de anime y los hago pelear"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">
                        Es la línea que sale debajo de tu nombre. Máximo 120 caracteres.
                    </span>
                    <x-input-error :messages="$errors->get('headline')" class="mt-1" />
                </label>


                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Sobre ti
                    </span>
                    <textarea name="bio" rows="3" maxlength="500"
                        placeholder="Qué haces aquí, qué te gusta crear…"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">{{ old('bio', $usuario->bio) }}</textarea>
                    <x-input-error :messages="$errors->get('bio')" class="mt-1" />
                </label>


                <div class="grid gap-3 sm:grid-cols-2">

                    <label class="block">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Dónde estás</span>
                        <input type="text" name="location" value="{{ old('location', $usuario->location) }}"
                            maxlength="100" placeholder="Perú"
                            class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">
                        <x-input-error :messages="$errors->get('location')" class="mt-1" />
                    </label>

                    <label class="block">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Tu sitio</span>
                        <input type="text" name="website" value="{{ old('website', $usuario->website) }}"
                            maxlength="255" placeholder="github.com/tu-usuario"
                            class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">
                        <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">
                            Si te olvidas del https://, se pone solo.
                        </span>
                        <x-input-error :messages="$errors->get('website')" class="mt-1" />
                    </label>
                </div>


                {{-- ---------- CORREO ---------- --}}

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Correo <span class="text-rose-400">*</span>
                    </span>
                    <input type="email" name="email" value="{{ old('email', $usuario->email) }}"
                        required maxlength="150"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">
                        No se enseña a nadie. Sirve para entrar y para recuperar la cuenta.
                    </span>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </label>


                @if ($usuario instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $usuario->hasVerifiedEmail())
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-2.5">
                        <p class="text-[11px] font-bold text-amber-200">Tu correo no está verificado.</p>

                        <form method="POST" action="{{ route('verification.send') }}" class="mt-1">
                            @csrf
                            <button type="submit"
                                class="text-[11px] font-black text-amber-300 underline transition hover:text-white">
                                Enviar otra vez el correo de verificación
                            </button>
                        </form>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-1 text-[10px] font-bold text-emerald-300">
                                Enviado. Mira tu bandeja.
                            </p>
                        @endif
                    </div>
                @endif


                {{-- ---------- VISIBILIDAD ---------- --}}

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Tu página de perfil
                    </p>

                    <div class="mt-1 grid gap-1.5 sm:grid-cols-2">
                        @foreach ([['PUBLIC', '#34d399', 'Pública', 'Cualquiera con cuenta puede abrir tu página y ver, desde ahí, todo lo que hayas publicado.'], ['PRIVATE', '#fbbf24', 'Privada', 'Nadie puede abrir tu página. Lo que publiques seguirá apareciendo en la comunidad, pero suelto.']] as [$valor, $tono, $texto, $ayuda])

                            <label class="block cursor-pointer rounded-xl border p-2.5 transition"
                                :style="visibilidad === '{{ $valor }}'
                                    ? 'border-color: {{ $tono }}; background-color: {{ $tono }}14'
                                    : 'border-color: #1e293b'">

                                <input type="radio" name="profile_visibility" value="{{ $valor }}"
                                    x-model="visibilidad" class="sr-only">

                                <span class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $tono }}"></span>
                                    <span class="text-[12px] font-black"
                                        :class="visibilidad === '{{ $valor }}' ? 'text-white' : 'text-slate-400'">
                                        {{ $texto }}
                                    </span>
                                </span>

                                <span class="mt-0.5 block text-[10px] leading-3 text-slate-600">{{ $ayuda }}</span>
                            </label>
                        @endforeach
                    </div>

                    <x-input-error :messages="$errors->get('profile_visibility')" class="mt-1" />
                </div>


                <div class="flex flex-wrap justify-end gap-2 pt-1">
                    <button type="submit"
                        class="rounded-xl bg-indigo-500 px-5 py-2.5 text-[12px] font-black text-white transition hover:bg-indigo-400">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
