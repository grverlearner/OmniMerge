<section x-data="{
    preview: @js($user->avatar_url),
    removed: false,

    previewAvatar(event) {

        const file =
            event.target.files[0];

        if (!file) {
            return;
        }

        this.removed = false;

        const reader =
            new FileReader();

        reader.onload = (event) => {
            this.preview =
                event.target.result;
        };

        reader.readAsDataURL(file);
    },

    removeAvatar() {

        this.preview = null;

        this.removed = true;

        if (this.$refs.avatarInput) {
            this.$refs.avatarInput.value = '';
        }
    }
}"
    class="
        rounded-3xl
        border
        border-white/10
        bg-white
        p-6
        shadow-xl
        shadow-black/10
        sm:p-8
    ">

    {{-- ========================================================= --}}
    {{-- ENCABEZADO --}}
    {{-- ========================================================= --}}

    <header>

        <p
            class="
                text-xs
                font-black
                uppercase
                tracking-[0.16em]
                text-indigo-600
            ">
            Perfil OmniMerge
        </p>

        <h2
            class="
                mt-2
                text-2xl
                font-black
                text-slate-950
            ">
            Información del perfil
        </h2>

        <p
            class="
                mt-2
                max-w-2xl
                text-sm
                leading-6
                text-slate-500
            ">
            Esta información permite identificarte dentro de
            OmniMerge y, si tu perfil es público, también será
            visible para otros usuarios en la Comunidad.
        </p>

    </header>


    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
        class="
            mt-8
            space-y-7
        ">

        @csrf
        @method('PATCH')


        <input type="hidden" name="remove_avatar" :value="removed ? 1 : 0">


        {{-- ===================================================== --}}
        {{-- AVATAR --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-2xl
                border
                border-slate-200
                bg-slate-50
                p-5
            ">

            <div
                class="
                    flex
                    flex-col
                    gap-5
                    sm:flex-row
                    sm:items-center
                ">

                <div
                    class="
                        h-24
                        w-24
                        shrink-0
                        overflow-hidden
                        rounded-full
                        bg-gradient-to-br
                        from-indigo-500
                        via-violet-500
                        to-fuchsia-500
                        shadow-lg
                    ">

                    <template x-if="preview">

                        <img :src="preview" alt=""
                            class="
                                h-full
                                w-full
                                object-cover
                            ">

                    </template>


                    <template x-if="! preview">

                        <div
                            class="
                                flex
                                h-full
                                w-full
                                items-center
                                justify-center
                                text-2xl
                                font-black
                                text-white
                            ">
                            {{ $user->initials }}
                        </div>

                    </template>

                </div>


                <div class="flex-1">

                    <p
                        class="
                            text-sm
                            font-black
                            text-slate-900
                        ">
                        Foto de perfil
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            leading-5
                            text-slate-500
                        ">
                        JPG, PNG o WEBP. Máximo 3 MB.
                        Esta imagen aparecerá en OmniMerge
                        y en tus creaciones públicas.
                    </p>


                    <div
                        class="
                            mt-4
                            flex
                            flex-wrap
                            gap-3
                        ">

                        <label
                            class="
                                cursor-pointer
                                rounded-xl
                                bg-indigo-600
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                                transition
                                hover:bg-indigo-700
                            ">
                            Elegir foto

                            <input x-ref="avatarInput" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp"
                                class="hidden"
                                @change="
                                    previewAvatar(
                                        $event
                                    )
                                ">
                        </label>


                        <button type="button" x-show="preview" @click="removeAvatar()"
                            class="
                                rounded-xl
                                border
                                border-slate-300
                                px-4
                                py-2.5
                                text-xs
                                font-bold
                                text-slate-600
                                transition
                                hover:border-red-200
                                hover:bg-red-50
                                hover:text-red-600
                            ">
                            Quitar foto
                        </button>

                    </div>


                    @error('avatar')
                        <p
                            class="
                                mt-3
                                text-sm
                                font-semibold
                                text-red-600
                            ">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- NOMBRE + USERNAME --}}
        {{-- ===================================================== --}}

        <div class="
                grid
                gap-5
                sm:grid-cols-2
            ">

            <div>

                <label for="name"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Nombre
                </label>


                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                    autocomplete="name"
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
                        focus:border-indigo-500
                        focus:bg-white
                        focus:text-slate-900
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


            <div>

                <label for="username"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Username
                </label>


                <div class="relative mt-2">

                    <span
                        class="
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
                    </span>


                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}"
                        required autocomplete="username"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-slate-50
                            py-3
                            pl-9
                            pr-4
                            text-sm
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:bg-white
                            focus:text-slate-900
                            focus:ring-indigo-500
                        ">

                </div>


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

        </div>


        {{-- ===================================================== --}}
        {{-- EMAIL --}}
        {{-- ===================================================== --}}

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


            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                autocomplete="email"
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
                    focus:border-indigo-500
                    focus:bg-white
                    focus:text-slate-900
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


            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div
                    class="
                        mt-3
                        rounded-xl
                        bg-amber-50
                        p-4
                        text-sm
                        text-amber-800
                    ">

                    Tu correo todavía no está verificado.

                    <button form="send-verification"
                        class="
                            ml-1
                            font-black
                            underline
                        ">
                        Reenviar verificación
                    </button>

                </div>
            @endif

        </div>


        {{-- ===================================================== --}}
        {{-- HEADLINE --}}
        {{-- ===================================================== --}}

        <div>

            <div
                class="
                    flex
                    items-center
                    justify-between
                ">

                <label for="headline"
                    class="
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Presentación corta
                </label>


                <span
                    class="
                        text-xs
                        text-slate-400
                    ">
                    Máx. 120 caracteres
                </span>

            </div>


            <input id="headline" name="headline" type="text" maxlength="120"
                value="{{ old('headline', $user->headline) }}" placeholder="Ej. Creador de mundos de fantasía"
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
                    focus:border-indigo-500
                    focus:bg-white
                    focus:text-slate-900
                    focus:ring-indigo-500
                ">


            @error('headline')
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


        {{-- ===================================================== --}}
        {{-- BIO --}}
        {{-- ===================================================== --}}

        <div>

            <div
                class="
                    flex
                    items-center
                    justify-between
                ">

                <label for="bio"
                    class="
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Biografía
                </label>


                <span
                    class="
                        text-xs
                        text-slate-400
                    ">
                    Máx. 500 caracteres
                </span>

            </div>


            <textarea id="bio" name="bio" rows="5" maxlength="500"
                placeholder="Cuéntale a la comunidad qué tipo de cosas te gusta crear..."
                class="
                    mt-2
                    w-full
                    resize-y
                    rounded-xl
                    border-slate-300
                    bg-slate-50
                    px-4
                    py-3
                    text-sm
                    text-slate-900
                    placeholder:text-slate-400
                    leading-6
                    focus:border-indigo-500
                    focus:bg-white
                    focus:text-slate-900
                    focus:ring-indigo-500
                ">{{ old('bio', $user->bio) }}</textarea>


            @error('bio')
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


        {{-- ===================================================== --}}
        {{-- UBICACIÓN + WEB --}}
        {{-- ===================================================== --}}

        <div class="
                grid
                gap-5
                sm:grid-cols-2
            ">

            <div>

                <label for="location"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Ubicación
                </label>


                <input id="location" name="location" type="text" value="{{ old('location', $user->location) }}"
                    placeholder="Ej. Tacna, Perú"
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
                        focus:border-indigo-500
                        focus:bg-white
                        focus:text-slate-900
                        focus:ring-indigo-500
                    ">


                @error('location')
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


            <div>

                <label for="website"
                    class="
                        block
                        text-sm
                        font-bold
                        text-slate-700
                    ">
                    Sitio web
                </label>


                <input id="website" name="website" type="text" value="{{ old('website', $user->website) }}"
                    placeholder="github.com/usuario"
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
                        focus:border-indigo-500
                        focus:bg-white
                        focus:text-slate-900
                        focus:ring-indigo-500
                    ">


                @error('website')
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

        </div>


        {{-- ===================================================== --}}
        {{-- PRIVACIDAD --}}
        {{-- ===================================================== --}}

        <div>

            <p
                class="
                    text-sm
                    font-bold
                    text-slate-700
                ">
                Visibilidad del perfil
            </p>


            <div
                class="
                    mt-3
                    grid
                    gap-3
                    sm:grid-cols-2
                ">

                <label
                    class="
                        cursor-pointer
                        rounded-2xl
                        border
                        border-slate-200
                        p-4
                        transition
                        hover:border-indigo-300
                        hover:bg-indigo-50/40
                    ">

                    <div
                        class="
                            flex
                            items-start
                            gap-3
                        ">

                        <input type="radio" name="profile_visibility" value="PUBLIC"
                            class="
                                mt-1
                                border-slate-300
                                text-indigo-600
                                focus:ring-indigo-500
                            "
                            @checked(old('profile_visibility', $user->profile_visibility) === 'PUBLIC')>


                        <div>

                            <p
                                class="
                                    text-sm
                                    font-black
                                    text-slate-900
                                ">
                                🌐 Público
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-xs
                                    leading-5
                                    text-slate-500
                                ">
                                Otros usuarios pueden abrir
                                tu perfil desde Comunidad.
                            </p>

                        </div>

                    </div>

                </label>


                <label
                    class="
                        cursor-pointer
                        rounded-2xl
                        border
                        border-slate-200
                        p-4
                        transition
                        hover:border-slate-400
                        hover:bg-slate-50
                    ">

                    <div
                        class="
                            flex
                            items-start
                            gap-3
                        ">

                        <input type="radio" name="profile_visibility" value="PRIVATE"
                            class="
                                mt-1
                                border-slate-300
                                text-indigo-600
                                focus:ring-indigo-500
                            "
                            @checked(old('profile_visibility', $user->profile_visibility) === 'PRIVATE')>


                        <div>

                            <p
                                class="
                                    text-sm
                                    font-black
                                    text-slate-900
                                ">
                                🔒 Privado
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-xs
                                    leading-5
                                    text-slate-500
                                ">
                                Tu contenido público puede existir,
                                pero los demás no podrán abrir
                                tu página de creador.
                            </p>

                        </div>

                    </div>

                </label>

            </div>


            @error('profile_visibility')
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


        {{-- ===================================================== --}}
        {{-- GUARDAR --}}
        {{-- ===================================================== --}}

        <div
            class="
                flex
                flex-col
                gap-3
                border-t
                border-slate-200
                pt-6
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">

            <p class="
                    text-xs
                    text-slate-400
                ">
                Los cambios se aplicarán inmediatamente
                en todo OmniMerge.
            </p>


            <button type="submit"
                class="
                    rounded-xl
                    bg-indigo-600
                    px-6
                    py-3
                    text-sm
                    font-black
                    text-white
                    shadow-lg
                    shadow-indigo-600/20
                    transition
                    hover:-translate-y-0.5
                    hover:bg-indigo-700
                ">
                Guardar cambios
            </button>

        </div>

    </form>

</section>
