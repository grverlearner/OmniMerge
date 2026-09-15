@php
    /*
     * Crear una cuenta.
     *
     * Tres cosas cambian además del oscuro:
     *
     *   · El usuario enseña la dirección que va a generar —/perfil/tu-usuario—
     *     y se comprueba mientras se escribe con la misma regla que valida el
     *     servidor (3 caracteres, letras, números, guiones). Y se avisa de que se
     *     guarda en minúsculas, porque el controlador lo hace.
     *
     *   · La contraseña dice su único requisito real —al menos 8 caracteres,
     *     Password::defaults() sin configurar— y si la repetición coincide.
     *
     *   · La nota de privacidad era verdad a medias: decía «tu biblioteca
     *     comenzará siendo privada». Lo es —entidades, colecciones y atributos
     *     nacen PRIVATE—, pero el perfil nace PUBLIC. Ahora se dicen las dos.
     */
@endphp

<x-guest-layout>

    <x-slot name="titulo">Crear cuenta</x-slot>

    <div x-data="{
        usuario: @js(old('username', '')),
        clave: '',
        repetida: '',

        get usuarioValido() {
            return this.usuario.length >= 3
                && this.usuario.length <= 50
                && /^[\p{L}\p{M}\p{N}_-]+$/u.test(this.usuario);
        },

        get claveValida() {
            return this.clave.length >= 8;
        },

        get coinciden() {
            return this.repetida !== '' && this.repetida === this.clave;
        },
    }">

        <div>
            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400/80">Únete</p>

            <h1 class="mt-2 text-3xl font-black tracking-tight text-white">Crea tu cuenta</h1>

            <p class="mt-2 text-[14px] leading-relaxed text-slate-400">
                Gratis. En un minuto estás creando tu primera entidad.
            </p>
        </div>


        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf

            @include('auth.partials.campo', [
                'nombre' => 'name',
                'etiqueta' => 'Tu nombre',
                'icono' => 'usuario',
                'valor' => old('name'),
                'autocomplete' => 'name',
                'placeholder' => 'Cómo quieres que te vean',
                'autofocus' => true,
            ])


            {{-- El usuario, con la dirección que genera --}}
            <div>
                @include('auth.partials.campo', [
                    'nombre' => 'username',
                    'etiqueta' => 'Nombre de usuario',
                    'prefijo' => '@',
                    'valor' => old('username'),
                    'autocomplete' => 'username',
                    'placeholder' => 'tu-usuario',
                    'extra' => 'x-model="usuario"',
                ])

                @unless ($errors->has('username'))
                    <p class="mt-1.5 text-[11px] leading-4">
                        <span x-show="usuario === ''" class="text-slate-600">
                            Letras, números, guiones o guiones bajos. Será tu dirección.
                        </span>

                        <span x-show="usuario !== '' && usuarioValido" x-cloak class="text-emerald-300/80">
                            Tu perfil será
                            <span class="font-mono font-bold text-emerald-200">/perfil/<span x-text="usuario.toLowerCase()"></span></span>
                        </span>

                        <span x-show="usuario !== '' && ! usuarioValido" x-cloak class="text-amber-300/80">
                            <span x-show="usuario.length < 3">Al menos 3 caracteres.</span>
                            <span x-show="usuario.length >= 3">Solo letras, números, guiones y guiones bajos.</span>
                        </span>
                    </p>
                @endunless
            </div>


            @include('auth.partials.campo', [
                'nombre' => 'email',
                'etiqueta' => 'Correo',
                'tipo' => 'email',
                'icono' => 'correo',
                'valor' => old('email'),
                'autocomplete' => 'email',
                'placeholder' => 'tu@correo.com',
                'ayuda' => 'No se enseña a nadie. Sirve para entrar y recuperar la cuenta.',
            ])


            {{-- La contraseña, con su único requisito real --}}
            <div>
                @include('auth.partials.clave', [
                    'nombre' => 'password',
                    'etiqueta' => 'Contraseña',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Al menos 8 caracteres',
                    'extra' => 'x-model="clave"',
                ])

                @unless ($errors->has('password'))
                    <p class="mt-1.5 flex items-center gap-1.5 text-[11px]"
                        :class="clave === '' ? 'text-slate-600' : (claveValida ? 'text-emerald-300/80' : 'text-amber-300/80')">
                        <span class="h-1.5 w-1.5 rounded-full"
                            :class="clave === '' ? 'bg-slate-700' : (claveValida ? 'bg-emerald-400' : 'bg-amber-400')"></span>
                        <span x-text="clave === '' || claveValida
                            ? 'Al menos 8 caracteres.'
                            : `Te faltan ${8 - clave.length} caracteres.`"></span>
                    </p>
                @endunless
            </div>

            <div>
                @include('auth.partials.clave', [
                    'nombre' => 'password_confirmation',
                    'etiqueta' => 'Repítela',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'La misma otra vez',
                    'extra' => 'x-model="repetida"',
                ])

                <p x-show="repetida !== ''" x-cloak class="mt-1.5 flex items-center gap-1.5 text-[11px]"
                    :class="coinciden ? 'text-emerald-300/80' : 'text-amber-300/80'">
                    <span class="h-1.5 w-1.5 rounded-full" :class="coinciden ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                    <span x-text="coinciden ? 'Coinciden.' : 'Todavía no coinciden.'"></span>
                </p>
            </div>


            {{-- Qué empieza siendo visible, dicho entero --}}
            <div class="rounded-xl border border-indigo-500/25 bg-indigo-500/5 p-3">
                <div class="flex gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
                        <x-omni-icon name="candado" size="h-4 w-4" />
                    </span>

                    <p class="text-[12px] leading-5 text-slate-400">
                        <strong class="text-slate-200">Todo lo que crees empieza privado</strong>: nadie ve
                        una entidad, colección o atributo hasta que tú la publicas.
                        <span class="text-slate-500">Tu página de perfil sí es visible desde el
                            principio, aunque vacía; puedes cerrarla cuando quieras en Mi perfil.</span>
                    </p>
                </div>
            </div>


            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3.5 text-[14px] font-black text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-900">
                Crear mi cuenta
                <x-omni-icon name="flecha-derecha" size="h-4 w-4" />
            </button>
        </form>


        <div class="mt-6 border-t border-white/10 pt-5 text-center">
            <p class="text-[13px] text-slate-500">
                ¿Ya tienes una?
                <a href="{{ route('login') }}" class="ml-1 font-black text-indigo-400 transition hover:text-indigo-200">
                    Entrar
                </a>
            </p>
        </div>
    </div>

</x-guest-layout>
