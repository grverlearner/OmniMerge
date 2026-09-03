/*
 * El estado del sidebar, compartido por los tres módulos.
 *
 * Son dos ejes distintos y por eso son dos propiedades:
 *
 *   sidebarOpen   en móvil el sidebar está fuera de la pantalla y entra
 *                 deslizándose. Es el nombre que ya usaban las cabeceras,
 *                 y se conserva para no tener que tocarlas.
 *
 *   compact       en escritorio el sidebar siempre se ve, pero puede
 *                 estrecharse hasta dejar solo los iconos. No es "cerrado":
 *                 la navegación sigue ahí, ocupando 4rem en vez de 18.
 *
 * El estado se recuerda en una COOKIE y no en localStorage a propósito. El
 * ancho del sidebar decide el margen del contenido, así que si el servidor
 * no supiera en qué estado está, la página se pintaría ancha y daría un
 * salto al arrancar Alpine. Con la cookie, Blade ya dibuja el ancho correcto
 * y aquí solo se continúa.
 */

const COOKIE = 'omni_sidebar';

function recordar(compacto) {
    try {
        document.cookie =
            COOKIE +
            '=' +
            (compacto ? 'compact' : 'full') +
            ';path=/;max-age=31536000;samesite=lax';
    } catch (e) {
        /* sin cookies: el sidebar sigue funcionando, solo no se recuerda */
    }
}

export default function omniSidebar(compactoInicial = false) {
    return {
        sidebarOpen: false,

        compact: Boolean(compactoInicial),

        init() {
            /*
             * Al pasar a móvil el modo compacto no significa nada -ahí el
             * sidebar entra entero o no entra-, así que se cierra el panel
             * para no dejarlo abierto sobre el contenido.
             */
            this.$watch('compact', (valor) => recordar(valor));
        },

        toggleCompact() {
            this.compact = !this.compact;
        },

        /* Lo que mide el sidebar y, por tanto, lo que se aparta el contenido */
        get anchoSidebar() {
            return this.compact ? 'lg:w-[4.5rem]' : 'lg:w-72';
        },

        get margenContenido() {
            return this.compact ? 'lg:pl-[4.5rem]' : 'lg:pl-72';
        },
    };
}
