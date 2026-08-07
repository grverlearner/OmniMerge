<x-guest-layout>

    <div x-data="{
        showPassword: false
    }">

        {{-- ENCABEZADO --}}
        <div>
            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.18em]
                    text-indigo-600
                ">
                Bienvenido de nuevo
            </p>

            <h1
                class="
                    mt-3
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-950
                ">
                Inicia sesión
            </h1>

            <p
                class="
                    mt-3
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Accede a tu biblioteca, tus entidades, atributos,
                colecciones y contenido de la comunidad.
            </p>
        </div>


        {{-- SESSION STATUS --}}
        <x-auth-session-status
            class="
                mt-6
                rounded-xl
                border
                border-emerald-200
                bg-emerald-50
                p-4
                text-sm
                font-semibold
                text-emerald-700
            "
            :status="session('status')" />


        {{-- FORMULARIO --}}
        <form method="POST" action="{{ route('login') }}" class="mt-8">
            @csrf


            {{-- EMAIL --}}
            <div>
                <label for="email"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Correo electrónico
                </label>

                <div class="relative mt-2">

                    <div
                        class="
                            pointer-events-none
                            absolute
                            inset-y-0
                            left-0
                            flex
                            items-center
                            pl-4
                            text-slate-400
                        ">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>

                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        autocomplete="username" placeholder="tu@email.com"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-slate-50
                            py-3.5
                            pl-11
                            pr-4
                            text-sm
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:bg-white
                            focus:ring-indigo-500
                        ">
                </div>

                @error('email')
                    <p
                        class="
                            mt-2
                            text-sm
                            font-semibold
                            text-red-600
                        ">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- CONTRASEÑA --}}
            <div class="mt-5">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                    ">
                    <label for="password"
                        class="
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Contraseña
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                            class="
                                text-xs
                                font-bold
                                text-indigo-600
                                hover:text-indigo-800
                            ">
                            ¿La olvidaste?
                        </a>
                    @endif
                </div>


                <div class="relative mt-2">

                    <div
                        class="
                            pointer-events-none
                            absolute
                            inset-y-0
                            left-0
                            flex
                            items-center
                            pl-4
                            text-slate-400
                        ">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M12 11c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 0V9a6 6 0 00-12 0v2m-1 0h14v9H5v-9z" />
                        </svg>
                    </div>


                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                        autocomplete="current-password" placeholder="Tu contraseña"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-slate-50
                            py-3.5
                            pl-11
                            pr-12
                            text-sm
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:bg-white
                            focus:ring-indigo-500
                        ">


                    <button type="button" @click="showPassword = !showPassword"
                        class="
                            absolute
                            inset-y-0
                            right-0
                            flex
                            items-center
                            px-4
                            text-slate-400
                            transition
                            hover:text-indigo-600
                        ">
                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />

                            <circle cx="12" cy="12" r="2.5" />
                        </svg>

                        <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3l18 18M10.6 10.6A2 2 0 0012 14a2 2 0 001.4-.6M9.9 5.2A10 10 0 0112 5c6 0 9.5 7 9.5 7a15 15 0 01-2 2.8M6.4 6.4C3.8 8.3 2.5 12 2.5 12s3.5 7 9.5 7a9.8 9.8 0 004-.8" />
                        </svg>
                    </button>
                </div>

                @error('password')
                    <p
                        class="
                            mt-2
                            text-sm
                            font-semibold
                            text-red-600
                        ">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- RECORDAR --}}
            <div
                class="
                    mt-5
                    flex
                    items-center
                ">
                <label for="remember"
                    class="
                        flex
                        cursor-pointer
                        items-center
                        gap-2
                    ">
                    <input id="remember" type="checkbox" name="remember"
                        class="
                            rounded
                            border-slate-300
                            text-indigo-600
                            focus:ring-indigo-500
                        ">

                    <span
                        class="
                            text-sm
                            font-medium
                            text-slate-600
                        ">
                        Mantener mi sesión iniciada
                    </span>
                </label>
            </div>


            {{-- BOTÓN --}}
            <button type="submit"
                class="
                    mt-7
                    flex
                    w-full
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-indigo-600
                    px-5
                    py-3.5
                    text-sm
                    font-black
                    text-white
                    shadow-lg
                    shadow-indigo-600/20
                    transition
                    hover:-translate-y-0.5
                    hover:bg-indigo-700
                    focus:outline-none
                    focus:ring-2
                    focus:ring-indigo-500
                    focus:ring-offset-2
                ">
                Iniciar sesión

                <span>→</span>
            </button>


            {{-- REGISTRO --}}
            <div
                class="
                    mt-8
                    border-t
                    border-slate-200
                    pt-6
                    text-center
                ">
                <p class="
                        text-sm
                        text-slate-500
                    ">
                    ¿Todavía no tienes una cuenta?

                    <a href="{{ route('register') }}"
                        class="
                            ml-1
                            font-black
                            text-indigo-600
                            hover:text-indigo-800
                        ">
                        Crear cuenta
                    </a>
                </p>
            </div>
        </form>
    </div>

</x-guest-layout>
