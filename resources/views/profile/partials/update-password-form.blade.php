<section
    class="
        rounded-3xl
        border
        border-white/10
        bg-white
        p-6
        shadow-xl
        shadow-black/10
    ">

    <header>

        <p
            class="
                text-xs
                font-black
                uppercase
                tracking-wider
                text-violet-600
            ">
            Seguridad
        </p>


        <h2
            class="
                mt-2
                text-xl
                font-black
                text-slate-950
            ">
            Cambiar contraseña
        </h2>


        <p
            class="
                mt-2
                text-sm
                leading-6
                text-slate-500
            ">
            Utiliza una contraseña larga y diferente
            a las que uses en otros servicios.
        </p>

    </header>


    <form method="POST" action="{{ route('password.update') }}" class="
            mt-6
            space-y-5
        ">

        @csrf
        @method('PUT')


        <div>

            <label for="update_password_current_password"
                class="
                    block
                    text-sm
                    font-bold
                    text-slate-700
                ">
                Contraseña actual
            </label>


            <input id="update_password_current_password" name="current_password" type="password"
                autocomplete="current-password"
                class="
                    mt-2
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-slate-50
                    px-4
                    py-3
                    text-sm
                    text-slate-900
                    placeholder:text-slate-400
                    focus:text-slate-900
                    focus:border-violet-500
                    focus:ring-violet-500
                ">


            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />

        </div>


        <div>

            <label for="update_password_password"
                class="
                    block
                    text-sm
                    font-bold
                    text-slate-700
                ">
                Nueva contraseña
            </label>


            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                class="
                    mt-2
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-slate-50
                    px-4
                    py-3
                    text-sm
                    text-slate-900
                    placeholder:text-slate-400
                    focus:text-slate-900
                    focus:border-violet-500
                    focus:ring-violet-500
                ">


            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />

        </div>


        <div>

            <label for="update_password_password_confirmation"
                class="
                    block
                    text-sm
                    font-bold
                    text-slate-700
                ">
                Confirmar contraseña
            </label>


            <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                autocomplete="new-password"
                class="
                    mt-2
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-slate-50
                    px-4
                    py-3
                    text-sm
                    text-slate-900
                    placeholder:text-slate-400
                    focus:text-slate-900
                    focus:border-violet-500
                    focus:ring-violet-500
                ">


            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />

        </div>


        <div class="
                flex
                items-center
                gap-4
            ">

            <button type="submit"
                class="
                    rounded-xl
                    bg-slate-900
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                    transition
                    hover:bg-slate-800
                ">
                Actualizar contraseña
            </button>


            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(
                    () => show = false,
                    3000
                )"
                    class="
                        text-xs
                        font-bold
                        text-emerald-600
                    ">
                    ✓ Actualizada
                </p>
            @endif

        </div>

    </form>

</section>
