# Tipos de entidad

**Fecha:** 3 de septiembre de 2026
**Alcance:** la biblioteca de tipos, ver un tipo, crearlo y editarlo, y dónde
se llega a ellos.

---

## 1. El problema

La ficha de un tipo enseñaba «las 12 últimas» entidades y poco más. Y el tipo
es justo lo contrario de eso: **es todo lo que lo lleva puesto**. Con
diecisiete entidades de un tipo, doce no son una muestra: son un recorte
arbitrario.

Además solo se llegaba a los tipos desde dentro de un formulario o desde el
dashboard: había que **saber que existían** para encontrarlos.

---

## 2. Ver un tipo

### 2.1 Su colección entera, en cuatro modos

Ya no son doce: es la colección completa, paginada, **filtrable por estado,
buscable y ordenable** (por fecha, nombre o cuántas características tienen). Y
se mira en cuatro modos con control de tamaño, recordados entre visitas:
**galería**, **cuadrícula**, **lista** y **tabla** — los mismos partials que la
biblioteca de entidades, así que enseñan exactamente lo mismo en los dos
sitios.

### 2.2 Lo que describe al tipo

Dos preguntas que antes no se podían contestar sin abrir las entidades una a
una:

- **Qué suelen llevar.** El reparto de características entre sus entidades,
  con su barra y su porcentaje: si el 100 % lleva *Anime* y el 12 % lleva
  *aldea*, la primera describe al tipo tanto como su nombre. Cada barra lleva
  al atributo.
- **Dónde acaban.** Las colecciones donde viven sus entidades, con su imagen y
  cuántas de este tipo hay en cada una.

Más: **tus otros tipos** en chips con su color y su cuenta, para saltar entre
ellos sin volver al índice, y una zona de peligro que dice la verdad — al
borrar un tipo **las entidades no se borran, se quedan sin tipo**.

### 2.3 El color del tipo, en toda la pantalla

El resplandor de la cabecera, el borde, el botón principal y el modo de vista
activo usan el color real del tipo. Va en `style` y como variable CSS: es un
dato del usuario, no un token del diseño, y una clase compuesta con
`'border-' . $color` no existiría en el CSS.

---

## 3. Crear y editar

La misma pantalla, en tres bloques: **qué es**, **cómo se ve** y **su sitio**.

### 3.1 La vista previa es la pantalla

Un tipo no se mira en su propia ficha: se mira **donde aparece**. Por eso la
columna derecha no enseña una tarjeta genérica sino **los cuatro sitios
reales**, en vivo:

1. **Su ficha**, con el resplandor de su color.
2. **En la ficha de una entidad**, la insignia que lleva su icono y su nombre.
3. **En la galería**, tres carteles con su icono sobre su color.
4. **En los filtros**, la línea del desplegable con su punto de color.

Elegir un color a ciegas y descubrir el resultado en otra pantalla es lo que
hacía que nadie los cambiara.

### 3.2 Lo que se añadió

- **Paleta de iconos** (dieciséis sugeridos) y **paleta de colores** (doce),
  además del campo libre y el selector de color de siempre. Sigue admitiendo
  cualquier icono, incluidos emojis: es un atajo, no una jaula.
- **Estado en tarjetas** que explican qué significa cada uno, y un aviso al
  pie de la vista previa: *«No aparecerá en los desplegables»* cuando no está
  activo.
- **Orden en las listas** (`sort_order`). La columna existía en la base y se
  usaba para ordenar los tipos, pero **no se preguntaba en ninguna parte**:
  los tipos se ordenaban por un número que nadie podía cambiar. Ahora se
  edita, validado entre 0 y 9999.
- En editar, un aviso ámbar cuando el tipo ya está en uso: *«Lo llevan 17
  entidades. Cambiar su color o su icono cambia cómo se ven todas ellas»*.

---

## 3 bis. La biblioteca de tipos

El índice enseña **lo que cada tipo lleva puesto**, no solo su nombre: dentro
de cada ficha van las caras de sus últimas entidades. Si un tipo llamado
«Personaje» está lleno de banderas, se ve sin abrirlo.

Tres maneras de mirar, recordadas entre visitas:

| modo | qué enseña |
|---|---|
| **mosaico** | la ficha grande, teñida de su color, con sus caras dentro |
| **lista** | una línea por tipo, con las caras en fila |
| **tabla** | color, estado, cuántas, orden y fecha, para comparar |

Cada ficha lleva la cara del tipo, su descripción, cuántas entidades tiene en
un contador de su propio color, y cuatro accesos: ver, editar, **filtrar la
biblioteca por él** y crear una entidad de ese tipo.

Un tipo **sin nada** no se queda en un cero silencioso: se marca. Pierde el
borde de color —el color se lo gana usándose— y en lugar de las caras dice
«todavía no lo lleva ninguna entidad», con el enlace para crear la primera.

Arriba, las cifras por estado (todas enlazadas a su filtro) y una más:
**cuántas entidades hay repartidas** entre los tipos de la página.

---

## 4. Llegar a crear un tipo

El botón está ahora en los cuatro sitios donde tiene sentido buscarlo:

- La **ficha de un tipo**, en sus acciones («+ Otro tipo») y en el panel de
  «tus otros tipos».
- La pantalla de **editar**, en la cabecera.
- El **formulario de una entidad**, cuando no tienes ningún tipo todavía.
- Y sobre todo, **el índice de Entidades**, que es donde se usan: una tira
  horizontal «Por tipo» con la cara de cada uno, su color y su cuenta.

### 4.1 La tira de tipos

No es solo un acceso: **filtra**. Un clic en un tipo deja la lista con lo suyo
—`type=5` deja 17 fichas—, el elegido se marca con su propio color, y están
también «Todas» y «Sin tipo» (en ámbar, y solo si hay alguna sin tipo). Al
final de la tira, **+ Nuevo tipo**, y arriba a la derecha **Gestionar tipos
→**.

Los tipos **no tienen entrada en el sidebar**. El sidebar enseña los sitios
donde uno trabaja, no todas las tablas que existen; y los tipos se usan
mirando entidades, así que es ahí donde están.

---

## 5. Archivos

| archivo | qué cambió |
|---|---|
| `app/Http/Controllers/EntityTypes/EntityTypeController.php` | `show()` reescrito: colección paginada y filtrable, cifras, reparto de características, colecciones y tipos hermanos |
| `resources/views/entity-types/show.blade.php` | reescrita: oscura, cuatro modos de vista y los dos paneles que describen al tipo |
| `.../entity-types/partials/form.blade.php` | reescrito: tres bloques, paletas, y la vista previa de los cuatro sitios |
| `.../entity-types/{create,edit}.blade.php` | cabeceras oscuras; editar avisa de cuántas lo llevan |
| `app/Http/Requests/EntityTypes/{Store,Update}EntityTypeRequest.php` | validan `sort_order` |
| `resources/views/entity-types/index.blade.php` | reescrito: oscuro, tres modos, fichas con las caras de sus entidades |
| `.../entity-types/partials/library-card.blade.php` | nuevo: la ficha del mosaico |
| `.../entity-types/partials/library-row.blade.php` | nuevo: la línea del modo lista |
| `resources/views/entities/index.blade.php` | la tira «Por tipo», que filtra y da acceso a crear |
| `resources/views/partials/sidebar.blade.php` | fuera «Tipos»; Colecciones con entrada propia |

---

## 6. Verificación

- Las tres pantallas responden **200**, también la ficha con filtros
  (`?status=ACTIVE&sort=name_asc`) y un tipo sin ninguna entidad.
- Con datos reales, el reparto de características sale bien: para «Personaje»,
  *Anime* al 100 % y *aldea* al 12 %.
- En crear: cambiar nombre, icono y color actualiza **los cuatro** bloques de
  la vista previa a la vez, y el botón de guardar toma el color elegido.
- Los siete campos están presentes y con su nombre —`name`, `description`,
  `icon`, `color`, `status`, `sort_order`, `image`—; en editar llegan con sus
  valores reales (Personaje · ◇ · #6366f1 · ACTIVE · orden 10) y aparece el
  aviso de «ya está en uso».
- Sonda en memoria contra las reglas reales, sin escribir nada: lo que envía
  el formulario **se acepta**, el orden es opcional, y se rechazan un orden
  negativo, uno mayor de 9999, un color sin almohadilla y un estado
  inventado.
- El índice de tipos responde **200**, también con filtros
  (`?sort=entities_desc&status=ACTIVE`), y las fichas traen las caras reales:
  «Personaje» enseña seis y un «+11».
- La tira del índice de entidades **filtra de verdad**: `type=5` deja 17
  fichas y `type=none` deja 1, y en los dos casos el elegido queda marcado.
  Sus ocho enlaces apuntan donde deben, incluidos «Gestionar tipos» y «Nuevo
  tipo».
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
