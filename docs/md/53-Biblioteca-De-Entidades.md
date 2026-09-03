# La biblioteca de entidades

**Fecha:** 3 de septiembre de 2026
**Alcance:** el índice de entidades, la ficha de una entidad, crear y editar,
y la superficie oscura del módulo Biblioteca.

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

## 6. Ver una entidad

Una entidad es la suma de cuatro cosas, y la ficha las enseña las cuatro con
su cara: **qué es** (su tipo), **cómo es** (sus características), **dónde
vive** (sus colecciones) y **en qué se convierte** (sus versiones).

### 6.1 El interruptor de las dos caras

Lo que más confunde de este modelo es que una entidad tiene dos caras: la
**original** —la que se escribió el primer día— y la de su **Base activa**,
que es la que ve el resto de la aplicación. `?view=original` ya existía; lo
que faltaba era **decirlo**.

Ahora hay un interruptor arriba, siempre visible, que dice cuál se está
mirando y salta a la otra, con la frase que lo explica: *«esto es lo que ve el
resto de la aplicación»* frente a *«lo que escribiste el primer día»*. Los
botones de acción cambian con él: sobre la base activa se edita la versión,
sobre la original se edita la entidad.

### 6.2 Todo con su imagen

- El **tipo** lleva su imagen o su icono, en su color, y enlaza a su ficha.
- Cada **valor de catálogo** lleva su propia imagen, icono o color, y enlaza
  al elemento del catálogo: no es un texto, es una cosa con cara.
- Las **colecciones** llevan su imagen y su color.
- Las **versiones** se enseñan como una galería, con la que hace de base
  marcada con ★.

Las características se agrupan por su grupo de atributos, y cuando se mira la
base activa llegan ya resueltas por el resolver —con la herencia aplicada—; la
vista las normaliza para no tener que saberlo.

---

## 7. Crear y editar

La misma pantalla para las dos cosas, en cinco bloques que responden en orden
a **quién es**, **qué es**, **cómo es**, **dónde vive** y **publicación**.

Lo que cambió:

- **El tipo se elige en tarjetas** con la imagen, el icono y el color reales
  de cada tipo, y la elegida se tiñe de su propio color. Antes era una lista.
- **Las colecciones igual**, con su imagen y una marca de selección que toma
  el color de la colección. La casilla real sigue viajando escondida dentro de
  la etiqueta: el cuadro entero es el área de clic, pero lo que se envía sigue
  siendo un `checkbox` con su `name` de siempre.
- **Visibilidad y estado** pasan de desplegable a tarjetas que explican en una
  línea qué significa cada opción.
- **La vista previa en vivo** a la derecha enseña la ficha tal y como quedará
  en la biblioteca: imagen, tipo con su color, nombre, descripción,
  visibilidad y cuántas colecciones.
- La **subida de imagen** aceptó una superficie oscura (`surface="dark"`). Es
  un componente compartido por ocho pantallas, así que `light` sigue siendo lo
  que hace por defecto y nada de lo existente cambia.
- La **barra de guardar** se queda pegada abajo y avisa de cambios sin
  guardar.

### 7.1 Lo que se respetó

El **constructor de características** se incluye tal cual, sin tocar su
componente de Alpine: solo cambiaron sus clases de color para que no fuera una
tarjeta blanca en medio de un formulario oscuro. La sustitución se limitó a
tokens de color, y todos ellos viven en el marcado —el objeto `x-data` termina
antes de la primera etiqueta con `class=`—, así que la parte frágil no se
tocó.

Y los nombres de los campos son exactamente los que espera el controlador:
`name`, `entity_type_id`, `image`, `remove_image`, `description`,
`collection_ids[]`, `visibility`, `allow_cloning` y `status`. El rediseño
cambia cómo se ven, no cómo se llaman.

### 7.2 Un fallo que el rediseño introdujo, y se corrigió

Al rehacer el formulario le puse a **estado** los tres valores de las
plantillas de torneo —Activa, **Borrador**, Archivada—. Pero una entidad no
tiene borrador: sus estados son **ACTIVE, INACTIVE y ARCHIVED**. La pantalla
habría ofrecido un valor que la validación rechaza, y guardar habría fallado
sin explicar por qué.

Está corregido en las tres pantallas donde había copiado la lista mal (el
formulario, el índice y la ficha), y la verificación lo comprueba
explícitamente.

---

## 8. Archivos

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
| `resources/views/entities/show.blade.php` | reescrita: las cuatro caras de una entidad y el interruptor |
| `resources/views/entities/partials/form.blade.php` | reescrito: cinco bloques, tarjetas visuales y vista previa |
| `resources/views/entities/{create,edit}.blade.php` | cabeceras oscuras; editar avisa de la Base activa |
| `resources/views/entities/partials/characteristics-builder.blade.php` | solo sus colores; su Alpine intacto |
| `resources/views/components/omni-image-upload.blade.php` | acepta `surface="dark"`, sin cambiar lo existente |

Los partials antiguos (`index-card`, `index-gallery-card`, `index-list-item`)
siguen en su sitio: los usa la vista anterior en otros puntos del módulo y
retirarlos sin comprobarlo sería romper algo por limpiar.

---

## 9. Verificación

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
- La ficha, crear y editar responden **200**, también la ficha con
  `?view=original` y la edición de una entidad con Base activa.
- Los dos componentes de Alpine del formulario arrancan y cargan sus datos: el
  del formulario y **el constructor de características**, con sus 2
  características existentes.
- Pulsando los controles reales (no el estado), lo que se enviaría queda
  correcto: `entity_type_id=5`, `visibility=PUBLIC`, `status=DRAFT`
  seleccionado por radio, `allow_cloning` con su `0` oculto delante del `1`
  marcado, y la vista previa reflejando todo.
- Sonda en memoria contra la petición real —con su `prepareForValidation`, sin
  escribir nada—: lo que envía el formulario **se acepta**, los tres estados
  reales se aceptan, «sin tipo» se acepta, y el `DRAFT` que había puesto por
  error **se rechaza**. Esa es la comprobación que encontró el fallo.
- El constructor de características ya no tiene ningún fondo blanco (0 de 63
  bloques) y sus títulos se leen en claro sobre oscuro.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
