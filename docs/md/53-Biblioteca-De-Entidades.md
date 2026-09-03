# La biblioteca de entidades

**Fecha:** 2 de septiembre de 2026
**Alcance:** el índice de entidades y la superficie oscura del módulo
Biblioteca. Las pantallas de crear, ver y editar una entidad quedan
pendientes; el porqué está en la sección 6.

---

## 1. El problema

Una entidad no se reconoce por su nombre: se reconoce por su **cara** y por lo
que lleva encima —de qué tipo es, qué características tiene y con qué valor,
en qué colecciones está, cuántas versiones se le han hecho—. La lista lo
enseñaba a medias y en claro, con la mitad de sus filtros escondidos.

El filtro más potente que ya existía —«enséñame las entidades cuyo *continente*
sea *Sudamérica*»— estaba en el fondo del formulario y prácticamente no se
usaba.

---

## 2. Cinco maneras de mirar

| modo | qué enseña |
|---|---|
| **galería** | solo la cara y el nombre, en una pared de caras |
| **cuadrícula** | la cara en vertical y las tres cifras |
| **a fondo** | además: características **con su valor**, colecciones y base activa |
| **lista** | una línea por entidad, con la cara pequeña y sus valores |
| **tabla** | ocho columnas para comparar |

La **galería** es el modo para mirar, no para trabajar: sin cifras, sin
botones y sin bordes que separen una ficha de la siguiente, solo la imagen a
sangre en 3:4 y el nombre encima de un degradado que lo hace legible caiga
sobre lo que caiga. El color del tipo aparece únicamente al pasar por encima,
como un halo alrededor de la ficha —si estuviera siempre, la pared de caras
dejaría de ser una pared de caras—. Y cabe más apretada que la cuadrícula:
hasta diez columnas, porque sin texto debajo una cara pequeña se sigue
reconociendo.

Ese halo viaja como variable CSS (`--halo`, puesta en el `style` de cada
ficha) y el hover la consume con `ring-[color:var(--halo)]`. Así el color, que
es un dato del usuario, no obliga ni a JavaScript en línea ni a clases de
Tailwind compuestas que no existirían en el CSS.

El modo y el tamaño (de 2 a 6 columnas) se recuerdan entre visitas
(`omnimerge.entities.view`).

La imagen está **siempre**, aunque sea pequeña. Usa `base_display_image_url`,
que es la de la Base activa si la hay y la del original si no: la cara que la
entidad tiene **hoy**, no la que se le puso el primer día. Y cuando la cara
viene de una Base activa, la ficha lo dice con una marca ★ y el nombre de esa
base —es la diferencia entre «así es» y «así es ahora mismo»—.

---

## 3. Filtrar, ordenar, buscar

Todo lo que ya existía en el controlador, ahora visible y en dos filas:

- **Arriba**, lo que más se usa: búsqueda, tipo (con su icono y cuántas tiene),
  colección (con cuántas tiene), estado, visibilidad y doce ordenaciones.
- **Abajo**, lo fino: con o sin imagen, con o sin características, cuántas por
  página, y el **filtro por característica y valor**.

Ese último se rescató del fondo del formulario y se puso en su propio bloque
violeta, con las opciones del segundo desplegable dependiendo del primero. Esa
dependencia vive en el cliente: ir al servidor para rellenar un desplegable
sería una recarga por elegir. Cuando hay uno puesto, se dice en palabras
encima de la lista.

Las cifras de la cabecera son enlaces: «Sin tipo» abre la lista ya filtrada por
las que no tienen tipo. Una cifra que no lleva a ninguna parte es decoración.

Todo el filtrado sigue en el **servidor** porque la lista está paginada:
filtrar en el cliente ordenaría una página, no la biblioteca.

---

## 4. Los colores son datos, no diseño

Los tipos de entidad y las colecciones tienen color propio, elegido por el
usuario, en hexadecimal. Esos van en `style` y no en clases de Tailwind: no son
tokens del diseño, son contenido. El resto de la paleta sí son clases
literales, como en el resto de la aplicación.

---

## 5. La superficie oscura del módulo

`AppLayout` acepta ahora `surface="dark"`, igual que `TournamentLayout` y
`UniverseLayout`. Es el mismo mecanismo a propósito: tres módulos con tres
formas distintas de decir lo mismo acabarían divergiendo. En oscuro la página
ocupa `max-w-[1600px]` y respira menos, porque lo que enseña son fichas y
tablas que quieren ancho.

La cabecera del módulo acompaña a la superficie con la misma bandera `$dark`.

---

## 6. Lo que queda pendiente, y por qué

Faltan las otras tres pantallas que se pidieron: **crear**, **ver** y **editar**
una entidad.

No es un olvido. Esas tres viven sobre `partials/form.blade.php` (1.242
líneas), `partials/characteristics-builder.blade.php` (1.740) y
`show.blade.php` (1.502): unas 4.500 líneas de Blade y Alpine entrelazadas,
donde el constructor de características ya rompió una vez por dos llaves mal
puestas —y cuando un `x-data` se rompe, se cae el componente entero y todos sus
campos dejan de existir sin decir nada—.

Rehacerlas de golpe sin verificar cada una una por una tiene bastantes
probabilidades de dejar formularios que parecen funcionar y no guardan. Van en
el siguiente paso, una a una y comprobadas.

---

## 7. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/entities/index.blade.php` | reescrito: oscuro, cinco modos, filtros en dos filas |
| `.../entities/partials/library-poster.blade.php` | nuevo: la ficha del modo galería |
| `.../entities/partials/library-card.blade.php` | nuevo: la ficha (cuadrícula y a fondo) |
| `.../entities/partials/library-row.blade.php` | nuevo: la línea del modo lista |
| `app/Http/Controllers/Entities/EntityController.php` | el índice precarga características con su valor, colecciones y cuenta versiones |
| `resources/views/components/omni-icon.blade.php` | icono `galeria` nuevo |
| `app/View/Components/AppLayout.php` | acepta `title` y `surface` |
| `resources/views/layouts/app.blade.php` | superficie clara u oscura |
| `resources/views/partials/header.blade.php` | acompaña a la superficie |

Los partials antiguos (`index-card`, `index-gallery-card`, `index-list-item`)
siguen en su sitio: los usa la vista anterior en otros puntos del módulo y
retirarlos sin comprobarlo sería romper algo por limpiar.

---

## 8. Verificación

- El índice responde **200** con datos reales: 22 fichas en cuadrícula, 22
  filas en lista y 22 en tabla, y con filtro aplicado
  (`attributes_state=yes&sort=attributes_desc&per_page=12`) baja a 12.
- Los cinco modos alternan lo que se ve: en lista hay 22 filas y 0 fichas; en
  tabla, 22 filas de tabla y 0 fichas; en galería, 22 caras y 0 de lo demás.
- En galería, el control de tamaño llega a diez columnas, y cada ficha lleva
  el color real de su tipo en `--halo` (por ejemplo `#bb4b25`).
- El modo «a fondo» abre el bloque real: para una entidad concreta enseña
  `CARACTERÍSTICAS · ◆ Anime · Naruto`, `COLECCIONES · En ninguna` y
  `BASE ACTIVA · Ninguna: se enseña la original`.
- La fila de la lista enseña la característica con su valor:
  `Ishiki · PÚBLICA · ENT000022 · Personaje · Anime Boruto: Naruto Next
  Generation · 1☷ 0◈ 0★`.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
