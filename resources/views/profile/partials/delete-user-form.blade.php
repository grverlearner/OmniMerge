<section
    class="
        rounded-3xl
        border
        border-red-200
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
                text-red-500
            ">
            Zona crítica
        </p>


        <h2
            class="
                mt-2
                text-xl
                font-black
                text-slate-950
            ">
            Eliminar cuenta
        </h2>


        <p
            class="
                mt-2
                text-sm
                leading-6
                text-slate-500
            ">
            Esta acción elimina tu acceso a OmniMerge.
            Confirma utilizando tu contraseña actual.
        </p>

    </header>


    <button type="button" x-data=""
        x-on:click.prevent="
            $dispatch(
                'open-modal',
                'confirm-user-deletion'
            )
        "
        class="
            mt-6
            rounded-xl
            border
            border-red-200
            bg-red-50
            px-5
            py-3
            text-sm
            font-black
            text-red-600
            transition
            hover:bg-red-100
        ">
        Eliminar mi cuenta
    </button>


    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>

        <form method="POST" action="{{ route('profile.destroy') }}" class="p-6">

            @csrf
            @method('DELETE')


            <h2
                class="
                    text-xl
                    font-black
                    text-slate-900
                ">
                ¿Eliminar tu cuenta?
            </h2>


            <p
                class="
                    mt-2
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Esta operación no debe realizarse por accidente.
                Introduce tu contraseña actual para continuar.
            </p>


            <div class="mt-6">

                <label for="delete_account_password"
                    class="
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Contraseña
                </label>


                <input id="delete_account_password" name="password" type="password" autocomplete="current-password"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        px-4
                        py-3
                        text-sm
                        focus:border-red-500
                        focus:ring-red-500
                    ">


                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />

            </div>


            <div
                class="
                    mt-6
                    flex
                    justify-end
                    gap-3
                ">

                <button type="button" x-on:click="
                        $dispatch('close')
                    "
                    class="
                        rounded-xl
                        border
                        border-slate-300
                        px-5
                        py-3
                        text-sm
                        font-bold
                        text-slate-600
                    ">
                    Cancelar
                </button>


                <button type="submit"
                    class="
                        rounded-xl
                        bg-red-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                        hover:bg-red-700
                    ">
                    Sí, eliminar cuenta
                </button>

            </div>

        </form>

    </x-modal>

</section>
