<x-guest-layout>

    <div x-data="{
        showPassword: false,
        showConfirmation: false
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
                Únete a OmniMerge
            </p>

            <h1
                class="
                    mt-3
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-950
                ">
                Crea tu cuenta
            </h1>

            <p
                class="
                    mt-3
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Empieza a construir tu biblioteca de entidades,
                atributos y colecciones.
            </p>

        </div>


        <form method="POST" action="{{ route('register') }}" class="mt-8">
            @csrf


            {{-- NOMBRE COMPLETO --}}
            <div>

                <label for="name"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Nombre completo
                </label>

                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                    autocomplete="name" placeholder="Tu nombre"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        bg-slate-50
                        px-4
                        py-3.5
                        text-sm
                        text-slate-900
                        placeholder:text-slate-400
                        focus:border-indigo-500
                        focus:bg-white
                        focus:ring-indigo-500
                    ">

                @error('name')
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


            {{-- USERNAME --}}
            <div class="mt-5">

                <label for="username"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Nombre de usuario
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
                            font-bold
                            text-slate-400
                        ">
                        @
                    </div>

                    <input id="username" type="text" name="username" value="{{ old('username') }}" required
                        autocomplete="username" placeholder="grverlearner"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-slate-50
                            py-3.5
                            pl-9
                            pr-4
                            text-sm
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:bg-white
                            focus:ring-indigo-500
                        ">
                </div>

                <p
                    class="
                        mt-2
                        text-xs
                        text-slate-400
                    ">
                    Usa letras, números, guiones o guiones bajos.
                </p>

                @error('username')
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


            {{-- EMAIL --}}
            <div class="mt-5">

                <label for="email"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Correo electrónico
                </label>

                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    autocomplete="email" placeholder="tu@email.com"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        bg-slate-50
                        px-4
                        py-3.5
                        text-sm
                        text-slate-900
                        placeholder:text-slate-400
                        focus:border-indigo-500
                        focus:bg-white
                        focus:ring-indigo-500
                    ">

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


            {{-- PASSWORD --}}
            <div class="mt-5">

                <label for="password"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Contraseña
                </label>


                <div class="relative mt-2">

                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                        autocomplete="new-password" placeholder="Crea una contraseña segura"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-slate-50
                            px-4
                            py-3.5
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
                            px-4
                            text-xs
                            font-bold
                            text-slate-400
                            hover:text-indigo-600
                        ">
                        <span x-text="showPassword ? 'Ocultar' : 'Ver'"></span>
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


            {{-- CONFIRMAR PASSWORD --}}
            <div class="mt-5">

                <label for="password_confirmation"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Confirmar contraseña
                </label>


                <div class="relative mt-2">

                    <input id="password_confirmation" :type="showConfirmation ? 'text' : 'password'"
                        name="password_confirmation" required autocomplete="new-password"
                        placeholder="Repite la contraseña"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-slate-50
                            px-4
                            py-3.5
                            pr-12
                            text-sm
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:bg-white
                            focus:ring-indigo-500
                        ">

                    <button type="button" @click="showConfirmation = !showConfirmation"
                        class="
                            absolute
                            inset-y-0
                            right-0
                            px-4
                            text-xs
                            font-bold
                            text-slate-400
                            hover:text-indigo-600
                        ">
                        <span
                            x-text="
                                showConfirmation
                                    ? 'Ocultar'
                                    : 'Ver'
                            "></span>
                    </button>

                </div>

                @error('password_confirmation')
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


            {{-- INFORMACIÓN --}}
            <div
                class="
                    mt-6
                    rounded-xl
                    border
                    border-indigo-100
                    bg-indigo-50
                    p-4
                ">
                <div class="
                        flex
                        gap-3
                    ">
                    <div
                        class="
                            flex
                            h-8
                            w-8
                            shrink-0
                            items-center
                            justify-center
                            rounded-lg
                            bg-indigo-100
                            text-sm
                            text-indigo-600
                        ">
                        ✓
                    </div>

                    <p
                        class="
                            text-xs
                            leading-5
                            text-indigo-800
                        ">
                        Tu biblioteca comenzará siendo privada.
                        Tú decides posteriormente qué contenido quieres
                        publicar en la comunidad.
                    </p>
                </div>
            </div>


            {{-- SUBMIT --}}
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
                Crear mi cuenta

                <span>→</span>
            </button>


            {{-- LOGIN --}}
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
                    ¿Ya tienes una cuenta?

                    <a href="{{ route('login') }}"
                        class="
                            ml-1
                            font-black
                            text-indigo-600
                            hover:text-indigo-800
                        ">
                        Iniciar sesión
                    </a>
                </p>
            </div>

        </form>
    </div>

</x-guest-layout>
