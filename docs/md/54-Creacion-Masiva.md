# Creación masiva de entidades

**Fecha:** 3 de septiembre de 2026
**Alcance:** la pantalla de crear un lote de entidades. La gestión masiva
—editar muchas a la vez— queda para el siguiente paso; el porqué está en la
sección 7.

---

## 1. El problema

La pantalla funcionaba y era potente —pegar de Excel, emparejar imágenes por
el nombre del archivo, valores comunes, borrador automático— pero **no lo
contaba**. Había cuatro botones sueltos (`+1`, `+5`, `CSV`, `Imágenes en
masa`) y ninguna forma de saber qué hacía cada uno sin pulsarlo, ni dónde
estaban los límites.

Además eran 6.234 líneas en un solo archivo, de las cuales 2.319 son el motor
de Alpine. Tocar el diseño obligaba a navegar por el motor, y al revés.

---

## 2. Cuatro formas de llenar el lote, dibujadas

Lo primero que se ve ahora es un bloque con las cuatro maneras de meter
entidades, cada una con **un esquema en SVG de cuatro trazos** y su límite
debajo en letra pequeña —el límite es lo que arruina el intento número dos—:

| | forma | el dibujo | el límite |
|---|---|---|---|
| 01 | **A mano** | filas que se apilan, la última punteada | 200 filas por lote |
| 02 | **Pegar de una hoja** | una cuadrícula que cae en filas | tabulador, coma o punto y coma |
| 03 | **Copiar de una que ya tienes** | una ficha que reparte su forma | copia la forma, no los valores |
| 04 | **Imágenes de golpe** | archivos que encuentran su fila | JPG/PNG/WEBP, 4 MB cada una |

No son ilustraciones: son esquemas del mismo juego que los iconos —caja fija,
trazo fino, `currentColor`—. Y se pueden mezclar: pegar los nombres, añadir
alguna a mano y soltar las imágenes al final.

---

## 3. La pantalla, reordenada

Como se trabaja de verdad:

1. **Lo que van a compartir** — nombre del lote, tipo, estado, visibilidad,
   permitir copia y qué hacer si el nombre ya existe.
2. **Dónde van a vivir** — colecciones, en tarjetas con su imagen.
3. **Qué características tendrán** — buscador, y cada una elegida con un
   conmutador de dos posiciones: **«igual para todas»** o **«una por fila»**.
   Eso es lo que decide si se rellena una vez o doscientas.
4. **El valor que comparten** — solo aparece si hay alguna común.
5. **Las entidades** — la lista.

### 3.1 La lista, de dos maneras

- **Tabla** para escribir rápido: número, cara, nombre, descripción, tipo y
  una columna por característica individual.
- **Fichas** para mirar: la imagen grande. Sirve para lo que la tabla no
  permite —comprobar de un vistazo que cada imagen cayó en la entidad
  correcta después de soltarlas en masa—.

Cada celda de característica tiene un **«↓ copiar abajo»**, el gesto de hoja
de cálculo que la pantalla ya soportaba y no ofrecía.

### 3.2 La barra de acciones

Marcar todas, duplicar las marcadas, quitarlas, y **aplicar un valor a todas
las marcadas de golpe** —lo que convierte «doscientas filas» en un minuto—.

---

## 4. El resumen, siempre a la vista

Una columna fija a la derecha que responde a «¿puedo darle ya?»:

- **Cuántas están listas** con su barra: una fila está lista cuando tiene
  nombre.
- **Lo que conviene mirar antes**, en tres avisos que solo aparecen si existen:
  cuántas repiten un nombre que ya tienes (y qué va a pasar con ellas según la
  estrategia elegida), cuántas van sin imagen, y cuántos avisos dejó la
  importación. Si no hay ninguno, lo dice.
- **Qué se va a crear**, en cinco líneas: tipo, estado, visibilidad,
  colecciones y características.
- El botón, que **dice cuántas**: «Crear 3 entidades», y se desactiva si no
  hay ninguna lista.

Debajo, el **borrador**: la pantalla ya lo guardaba sola en el navegador y no
lo decía. Ahora se explica y se puede guardar o descartar a mano.

---

## 4 bis. El lote nuevo empieza vacío

Había un fallo de verdad, y no era de diseño: **al entrar a crear un lote
aparecían las entidades del lote anterior**, que ya estaban creadas.

La cadena era ésta:

1. `prepareSubmit()` guardaba el borrador antes de enviar, «por si el servidor
   devuelve un error».
2. El `beforeunload` que guarda el borrador al cerrar también se dispara al
   enviar el formulario, así que lo volvía a escribir.
3. Después de crear el lote **nadie lo borraba**, y `init()` lo restauraba sin
   preguntar en la siguiente visita.

Corregido en tres sitios:

- **Al enviar se borra el borrador, no se guarda.** Como red de seguridad no
  hacía falta: si el servidor rechaza el lote, Laravel devuelve las filas en
  `old` y `init()` ya las repone por esa vía.
- **El `beforeunload` no escribe mientras se está enviando**, ni cuando el lote
  está vacío —entrar y recargar sin escribir nada machacaba con diez filas en
  blanco el borrador que quedara guardado—.
- **`init()` ya no aplica el borrador solo.** Lo deja a un lado y la pantalla
  lo ofrece en un aviso ámbar arriba: *«Dejaste un lote a medias en este
  navegador (12 filas). Este lote empieza vacío; recupéralo solo si lo quieres
  continuar»*, con **Recuperarlo** y **Descartarlo**.

De paso, guardar y descartar el borrador dejaron de abrir un `alert()` del
sistema: interrumpir con un cuadro para decir «hecho» molesta justo a quien
estaba escribiendo deprisa. Ahora se dice en la propia pantalla.

---

## 5. Un editor de valores, no tres

El valor de una característica hace falta en tres sitios —el valor común del
lote, el que se aplica en masa, y la celda de cada fila— y los tres tienen que
ofrecer lo mismo para los ocho tipos de dato. Estaba escrito tres veces.

Ahora es **un solo archivo** (`partials/value-editor.blade.php`) que recibe
dos expresiones: dónde vive el valor y cómo se llama al enviarse. Tenerlo
triplicado garantizaba que un día uno de ellos se quedara sin soportar un
tipo.

De paso, el editor de color pasó a ser el cuadrado **y** el código: escribir
un hexadecimal a ciegas no es elegir un color.

---

## 6. Lo que no se tocó

El motor —`bulkEntityBuilder`, 2.319 líneas— **está intacto**. Llena filas,
parsea CSV, empareja imágenes por nombre de archivo, detecta nombres
repetidos, copia hacia abajo y guarda el borrador. Lo único que se le añadió
desde el marcado es `rowView`, la propiedad que decide si la lista se ve como
tabla o como fichas.

Y los nombres de los campos son exactamente los que espera
`BulkStoreEntityRequest`: `batch_name`, `entity_type_id`, `status`,
`visibility`, `allow_cloning`, `duplicate_strategy`, `collection_ids[]`,
`selected_attribute_ids[]`, `common_attribute_ids[]`, `common_attributes`,
`rows[clave][…]`, `images[clave]` y `bulk_images[]`.

---

## 7. Lo que queda pendiente

La **gestión masiva** (`entities/bulk-edit`) son otras 6.010 líneas con su
propio motor. Va en el siguiente paso, con el mismo método: separar el motor
del marcado, rediseñar el marcado, y comprobar que el motor sigue vivo.

---

## 8. Archivos

| archivo | qué es |
|---|---|
| `resources/views/entities/bulk/create.blade.php` | reescrito: de 6.234 a 2.505 líneas, de las cuales 2.319 son el motor intacto |
| `.../bulk/partials/body.blade.php` | nuevo: todo el marcado de la pantalla |
| `.../bulk/partials/methods.blade.php` | nuevo: las cuatro formas, con sus esquemas |
| `.../bulk/partials/value-editor.blade.php` | nuevo: el editor de valor por tipo, uno para los tres sitios |

---

## 9. Verificación

- La pantalla responde **200** y el motor arranca con sus datos: 6
  características, 4 tipos, 10 filas iniciales.
- Escribiendo tres nombres, los contadores reaccionan a la vez: **3 listas, 3
  sin imagen, 3 repetidas**, la barra pasa a 3/10 y el botón dice «Crear 3
  entidades».
- Los dos avisos reales aparecen con su color: *«3 con un nombre que ya
  tienes. Se crearán igual»* y *«3 sin imagen. Se pueden poner después»*.
- Cambiar a **fichas** deja 0 filas de tabla y 10 fichas con su selector de
  imagen.
- El **modal de pegar** dibuja el orden de las columnas y funciona: pegando
  dos líneas separadas por tabulador el lote pasa de 10 a 12 filas, con
  `Gaara / Kazekage de Suna` y `Temari / Hermana de Gaara` repartidos en
  nombre y descripción, y el modal se cierra solo.
- **El lote nuevo empieza vacío.** Plantando en el navegador un borrador de 12
  entidades ya creadas y entrando: 10 filas en blanco, **0 con nombre**, sin
  nombre de lote, y el aviso ofreciendo las 12.
- **Recargar sin escribir no destruye ese borrador**: sigue con sus 12 filas y
  su nombre.
- **Recuperarlo** repone las 12 filas y el nombre del lote, y retira el aviso.
- **Al enviar se borra**: `prepareSubmit()` deja el borrador en `null`, marca
  el envío en curso, y el `beforeunload` posterior ya no lo vuelve a escribir.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
