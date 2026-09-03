# El sidebar plegable y el juego de iconos

**Fecha:** 2 de septiembre de 2026
**Alcance:** los tres sidebars —Biblioteca, Torneos y Universos—, sus
cabeceras, y las pantallas de fases que se habían quedado a pantalla completa.

---

## 1. El problema

Había tres sidebars escritos a mano, casi iguales pero no del todo: el mismo
armazón copiado tres veces, con lo que cualquier arreglo había que hacerlo
tres veces o quedaba a medias. Solo tenían dos estados —dentro o fuera de la
pantalla en móvil— y en escritorio ocupaban 18rem fijos que no se podían
recuperar.

Y los iconos eran caracteres sueltos: `▦`, `✦`, `⌘`, `⚗`, `☷`, `◇`, mezclados
con emojis (`🏆`, `🌐`, `📚`, `🌌`). Puestos en columna no se leían como una
familia sino como una colección de símbolos encontrados —distinto peso,
distinto tamaño óptico—, y los emojis además cambian de forma según el sistema
operativo y no heredan el color del texto, así que no podían combinar con nada.

---

## 2. Tres estados, no dos

| estado | ancho | qué se ve |
|---|---|---|
| **desplegado** | 18rem | icono y texto |
| **plegado** | 4.5rem | solo los iconos, con el nombre en un globo al pasar |
| **móvil** | fuera de la pantalla | entra deslizándose entero, con fondo oscurecido |

El control de plegado es un botón redondo montado sobre la línea que separa el
sidebar del contenido —porque es justo lo que hace: mover esa línea—. En móvil
no existe; ahí lo que hay es el aspa de cerrar.

Plegado y desplegado son un eje distinto de abierto y cerrado, y por eso son
dos propiedades distintas (`compact` y `sidebarOpen`). El nombre `sidebarOpen`
se conservó porque es el que ya usaban las tres cabeceras para su botón de
menú, y cambiarlo habría obligado a tocarlas sin ganar nada.

### Por qué una cookie y no `localStorage`

El ancho del sidebar decide el margen del contenido. Si el estado viviera solo
en el navegador, el servidor pintaría siempre la página ancha y habría un
salto visible en cuanto arrancase Alpine. Con la cookie, Blade ya dibuja el
ancho correcto y Alpine solo continúa desde ahí.

### Una trampa de Alpine que costó una vuelta

`:class` con **array** suma clases pero no quita las que ya venían escritas en
el atributo `class`. Como el ancho se pinta desde el servidor, `lg:w-72` y
`lg:w-[4.5rem]` acababan puestas a la vez y ganaba la que el CSS tuviera más
abajo: el sidebar decía estar plegado y seguía midiendo 18rem.

La sintaxis de **objeto** sí quita: `{'clase': condición}` añade cuando es
cierta y elimina cuando es falsa, aunque la clase viniera del atributo. Todos
los pares ancho/margen usan esa forma.

---

## 3. El juego de iconos

`<x-omni-icon name="..." />` tiene el juego completo dentro: caja de 24, trazo
de 1.75, extremos redondeados, sin relleno, en `currentColor`. Eso es lo que
hace que combinen —y lo que permite que el sidebar plegado, donde el icono es
lo único que queda, siga siendo legible—.

Los nombres describen la forma, no el sitio donde se usan, para que un icono
pueda reutilizarse sin que su nombre mienta: `cuadricula`, `capas`, `grafo`,
`chispa`, `controles`, `libro`, `trofeo`, `espadas`, `matraz`, `medalla`,
`orbita`, `brujula`, `dado`, `calendario`, `historial`, `barras`, `globo`,
`engranaje`, `mas`, `casa`, `usuario`, `panel`, las flechas y los chevrones,
`menu` y `cerrar`.

**Añadir un icono es añadirlo ahí**, nunca pegar un carácter en una vista: un
juego de iconos disperso deja de ser un juego.

---

## 4. Un armazón, tres módulos

Los tres sidebars pasan a ser una lista de qué hay, no de cómo se dibuja:

```blade
<x-omni-sidebar accent="amber">
    <x-slot:brand>
        <x-omni-sidebar-brand ... />
    </x-slot:brand>

    <x-omni-nav-section title="Diseño">
        <x-omni-nav-item icon="grafo" label="Fases" :href="..." :active="..." />
    </x-omni-nav-section>

    <x-slot:footer>
        <x-omni-sidebar-user> ... </x-omni-sidebar-user>
    </x-slot:footer>
</x-omni-sidebar>
```

Lo único que cambia entre módulos es el color (`indigo` la Biblioteca, `amber`
Torneos, `violet` Universos) y su contenido. El comportamiento —plegarse,
recordarse, comportarse en móvil— es uno solo.

Los grupos, plegados, se separan con una línea fina en lugar del rótulo: sin
ella la columna de iconos se convierte en una lista uniforme donde no se
distingue dónde acaba una cosa y empieza otra.

---

## 5. Las fases vuelven al módulo

El índice de fases y las pantallas de crear y editar su definición estaban
sobre `x-arena-layout`, que ocupa la ventana entera y no tiene navegación. Son
pantallas de trabajo, no arenas: ahora van sobre el layout de Torneos, con su
sidebar, aprovechando igualmente todo el ancho (`surface="dark"`).

La pantalla completa sin navegación se reserva para lo que se juega o se edita
a fondo: la arena de una competición y las dos Super Ediciones.

La barra de guardar del formulario dejó de ser `fixed` a la ventana —con el
sidebar delante habría empezado debajo de él— y pasó a ser `sticky` dentro del
contenido.

---

## 6. Archivos

| archivo | qué es |
|---|---|
| `resources/views/components/omni-icon.blade.php` | nuevo · el juego de iconos |
| `resources/views/components/omni-sidebar.blade.php` | nuevo · el armazón y los tres estados |
| `resources/views/components/omni-sidebar-brand.blade.php` | nuevo · la identidad del módulo |
| `resources/views/components/omni-sidebar-user.blade.php` | nuevo · el pie con quién ha entrado |
| `resources/views/components/omni-nav-section.blade.php` | nuevo · un grupo con su rótulo |
| `resources/views/components/omni-nav-item.blade.php` | nuevo · un enlace, con su globo al plegar |
| `resources/js/layout/sidebar.js` | nuevo · el estado, y la cookie que lo recuerda |
| `resources/views/partials/{,tournaments/,universes/}sidebar.blade.php` | reescritos sobre el armazón |
| `resources/views/partials/{,tournaments/,universes/}header.blade.php` | el botón de menú deja de ser un carácter |
| `resources/views/layouts/{app,tournaments,universes}.blade.php` | el margen del contenido sigue al sidebar |
| `resources/views/tournaments/phase-templates/{index,create,edit}.blade.php` | vuelven al layout del módulo |

---

## 7. Verificación

- Las siete pantallas de los tres módulos responden **200** y dibujan sus
  iconos como SVG.
- Medido en el navegador: desplegado el sidebar mide **288 px** y el contenido
  se aparta 288 px; plegado mide **72 px** y el contenido se aparta 72 px.
- La cookie `omni_sidebar` alterna entre `full` y `compact` al usar el control.
- Plegado, el globo con el nombre del enlace aparece al pasar por encima.
- En móvil el sidebar entra entero sobre un fondo oscurecido, con su aspa para
  cerrarlo.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
