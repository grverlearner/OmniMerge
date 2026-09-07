# El índice de colecciones

**Fecha:** 4 de septiembre de 2026
**Alcance:** el índice de colecciones, sus filtros, sus modos de vista y el
cambio rápido de visibilidad.

---

## 1. El problema

Una colección es lo único de la biblioteca que **no se define por sus datos
sino por lo que agrupa**, y la pantalla no enseñaba nada de dentro: cuatro modos
de vista que se diferenciaban en el tamaño de la misma tarjeta, y un número de
entidades como único indicio de qué había en cada una.

Tampoco usaba el **color** que cada colección ya tiene guardado, ni respondía la
única pregunta accionable del panel: qué se ha quedado fuera de todas.

---

## 2. Cinco maneras de mirar

| modo | qué enseña |
|---|---|
| **galería** | la portada y el nombre, para reconocerlas de un vistazo |
| **contenido** | las **caras de lo que hay dentro** — la vista nueva |
| **cuadrícula** | la ficha con sus etiquetas y sus acciones |
| **lista** | una línea por colección, con las cuatro primeras caras |
| **tabla** | para comparar tamaño, visibilidad, vistas y copias |

La de **contenido** es la que faltaba: una cuadrícula de hasta nueve miembros
por colección, con su nombre al pasar por encima y un «+N» que lleva a la ficha.
Hasta ahora había que abrir una colección para saber qué agrupaba.

Todas con control de tamaño (2 a 5 columnas) y memoria entre visitas.

---

## 3. El color, que era un dato y no se usaba

Cada colección guarda un `color`. Ahora tiñe su borde, su cifra de entidades, su
icono y su etiqueta de visibilidad.

Va en `style`, no en clases: una clase de Tailwind compuesta a partir de un valor
de la base de datos **no existe en el CSS generado**, porque Tailwind escanea el
código fuente y solo genera lo que encuentra escrito literalmente.

---

## 4. Qué queda fuera

La cifra accionable que no estaba: **cuántas entidades no están en ninguna
colección**, con su barra y su porcentaje. Y se dice lo que no es: *«no es un
error —una entidad no tiene por qué pertenecer a nada—, pero conviene saberlo»*.

---

## 5. Filtros

A los cuatro que había —búsqueda, visibilidad, estado, portada— se suman tres
que hacen falta en cuanto tienes más de cinco colecciones:

- **llenas o vacías** — `whereHas('entities')`
- **propias o clonadas** — por `source_collection_id`
- **copiables o cerradas** — por `allow_cloning`

Y las cifras de la cabecera son enlaces: pulsar «Vacías» filtra por vacías.

---

## 6. Cambiar la visibilidad sin salir

`PATCH collections/{collection}/quick`, nuevo.

Cambiar si una colección es pública, retirarla de circulación o permitir que la
copien obligaba a abrir el **formulario de edición entero** —con su imagen, su
color y su lista de entidades— para mover un desplegable.

Ahora es un desplegable en la propia tarjeta: las tres visibilidades con la
frase que las distingue («Público: cualquiera puede encontrarla en Comunidad»),
la actual marcada y deshabilitada, y el interruptor de copiable. Cada opción es
su propio formulario, así que funciona sin JavaScript de por medio.

El endpoint solo acepta **tres campos** y valida el valor contra la lista de cada
uno: pedirle que cambie el nombre se rechaza.

---

## 7. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/collections/index.blade.php` | reescrita: cinco modos, color, cobertura |
| `resources/views/collections/partials/library-card.blade.php` | **nueva**: la ficha con sus miembros |
| `resources/views/collections/partials/quick-visibility.blade.php` | **nueva**: el desplegable |
| `app/Http/Controllers/Collections/CollectionController.php` | entidades de dentro, tres filtros, dos cifras, `quickUpdate` |
| `routes/web.php` | `collections.quick-update` |

---

## 8. Verificación

- El índice responde 200, también filtrado por contenido y ordenado por tamaño,
  y con el filtro de vacías (que deja la lista a cero y enseña el estado vacío).
- Con datos reales: «Franquicia Naruto» sale con su naranja `#ee8420` en el
  borde y en la cifra, con la cara de su única entidad, y la cobertura calcula
  «1 de 3 entidades están en alguna colección · 33 %» avisando de las 2 que
  quedan fuera.
- El desplegable de visibilidad abre con las tres opciones, la actual
  deshabilitada, y el interruptor de copiable con el valor contrario al actual.
- El endpoint, probado en una transacción revertida: cambia visibilidad, estado
  y copiable con su mensaje en castellano; rechaza un valor inventado sin tocar
  nada y rechaza un campo no permitido. Al terminar, la colección volvió a
  `PUBLIC` y copiable.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

### Un fallo encontrado verificando

El desplegable mide 224 px y vive dentro de una tarjeta de 227 con
`overflow-hidden` —que estaba ahí para recortar la portada a las esquinas
redondeadas—, así que **la tarjeta lo cortaba**. El recorte pasó a la propia
portada, que era lo único que lo necesitaba.

---

# Anexo — Crear y editar una colección

**Fecha:** 4 de septiembre de 2026

## 9. Lo que era

Una columna de campos sueltos, y para elegir entidades una rejilla de casillas
con un buscador y un desplegable de tipo. **Nada decía en qué colecciones estaba
ya cada entidad** —el dato que decide si vale la pena meterla también aquí— ni
cuántas llevabas elegidas. El color se escribía a mano, en hexadecimal.

## 10. La identidad se ve mientras se escribe

A la derecha, pegada al hacer scroll, está **la ficha real** tal como saldrá en
el índice: su portada o su símbolo sobre su color, su nombre, su etiqueta de
visibilidad y las caras de lo que lleva elegido. No es una maqueta: es el mismo
marcado.

El **color** se elige entre nueve muestras con nombre, más un selector libre
para cualquier otro; el **símbolo** entre doce, y se tiñe del color elegido. Los
dos existían en la base de datos y se pedían escribiendo texto.

## 11. El selector de entidades

Tres maneras de mirar —**galería** (solo la cara), **cuadrícula** (con su tipo y
dónde está ya) y **lista**— con tamaño de 4 a 9 columnas y memoria entre
visitas.

Filtros: búsqueda por nombre, tipo de entidad y, el que faltaba, **dónde está
ya**:

- estén donde estén
- **las que no están en ninguna colección** — las huérfanas, que es justo lo que
  se suele querer agrupar
- las que ya están en alguna
- solo las elegidas

Cada entidad enseña, con el color de cada colección, **en cuáles está ya**, o un
«En ninguna» apagado. Y hay una **bandeja** encima de la lista con todo lo
elegido, donde cada una se quita de un clic sin tener que buscarla otra vez.

Los ids se envían **una sola vez**, desde la selección: los tres modos de vista
conviven en el DOM —`x-show` oculta, no desmonta— y un `name` por modo enviaría
cada entidad tres veces.

## 12. La visibilidad, explicada

Tres tarjetas en vez de un desplegable de tres palabras, cada una diciendo qué
implica: *«no sale en las búsquedas: solo quien tenga el enlace»*. Y la casilla
de copiable dice qué pasa exactamente: se copia la colección y sus entidades,
las tuyas no se tocan.

## 13. Qué es una colección, dibujado

En «nueva», un bloque plegable con un esquema: un **tipo** es una caja —una
entidad es de uno solo—; las **colecciones** son marcos que se solapan sobre las
mismas entidades. Por eso meter algo aquí no lo saca de ningún sitio.

## 14. La barra de guardar

Pegada abajo, dice qué va a pasar: *«Se creará «Equipo 7» con 3 entidades
dentro»*, o *«vacía. Podrás añadirle entidades cuando quieras»*. El botón se
deshabilita mientras no haya nombre, diciendo por qué.

## 15. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/collections/partials/form.blade.php` | reescrito entero, con su motor |
| `resources/views/collections/create.blade.php` | reescrita, con el explicador dibujado |
| `resources/views/collections/edit.blade.php` | reescrita, con la zona de borrado |
| `app/Http/Controllers/Collections/CollectionController.php` | `create()` y `edit()` cargan en qué colecciones está cada entidad |
| `app/Http/Requests/Collections/{Store,Update}CollectionRequest.php` | mensajes en castellano |

## 16. Verificación

- Crear, editar e índice responden 200, sin fondos claros salvo el desplegable
  compartido de la cabecera.
- Con datos reales: la paleta, los doce símbolos y la vista previa se pintan; en
  el selector, «Mitsuki» sale con la etiqueta naranja **Franquicia Naruto** y
  las otras dos con «En ninguna».
- Cambiando el estado desde el navegador, la vista previa sigue el nombre y el
  color (`border-color: #06b6d455`), y el filtro **«las que no están en
  ninguna»** deja Jura y Naruto, mientras **«las que ya están en alguna»** deja
  Mitsuki.
- Lo que se enviaría es correcto y completo: `name`, `color`, `icon`,
  `visibility`, `status`, el par `allow_cloning` oculto+casilla, y
  `entity_ids[]` con **tres ids sin duplicar** pese a los tres modos de vista.
- La validación real acepta ese envío y el de una colección vacía, y rechaza en
  castellano la que va sin nombre y la del color inventado.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

**Un detalle que se repite y conviene no olvidar:** `border-{{ $tono }}-500` no
existe en el CSS. Tailwind escanea el código fuente y solo genera lo que
encuentra escrito literalmente, así que las tres tarjetas de visibilidad llevan
sus clases enteras y el color de la colección viaja en `style`.

---

# Anexo II — La ficha de una colección

**Fecha:** 4 de septiembre de 2026

## 17. La pantalla más pobre del módulo

La portada, el nombre y una rejilla plana con las entidades de dentro. Una
colección **es** lo que agrupa, así que casi todo lo interesante estaba sin
contar —y todo salía de datos que ya existían—.

## 18. De qué está hecha

El reparto por tipo, con su barra teñida del color de la colección y las caras de
cada grupo. Veinte personajes y tres aldeas no es lo mismo que veintitrés
personajes, y con un número suelto no se distinguía.

## 19. Lo que tienen todas en común

Los valores de catálogo que comparten **todos** los miembros, con su imagen. Es
la firma de la colección: si las doce son de Konoha, eso es lo que la define más
allá de su nombre.

Se calcula contando entidades distintas por opción y quedándose con las que
llegan al total de miembros. Solo aparece con **dos o más** dentro: con una, «lo
que todas comparten» son simplemente sus datos.

## 20. Quién más podría entrar

El pago de lo anterior, y la funcionalidad nueva más útil de la pantalla: las
entidades de fuera que **cumplen la firma entera**, con su cara y un botón para
meterlas de un clic.

Se pide una condición por valor —«tiene este» **y** «tiene aquel»—, no «tiene
alguno», que devolvería media biblioteca. Y se dice lo que es: *«una sugerencia,
no una regla: decides tú»*.

## 21. Lo que hay dentro, de cinco maneras

| modo | para qué |
|---|---|
| **galería** | solo las caras |
| **cuadrícula** | con su tipo, en qué otras colecciones está, y sacarla |
| **lista** | una línea por entidad |
| **tabla** | para comparar y ver quién está solo aquí |
| **por tipo** | agrupadas, que es como se lee una colección mixta |

Con búsqueda dentro, filtro por tipo, cuatro órdenes —incluido **el orden de la
colección**, que es el `sort_order` del pivote y por eso no se recalcula— y
tamaño de 3 a 8 columnas.

## 22. Meter y sacar sin abrir el formulario

Dos endpoints nuevos:

| ruta | qué hace |
|---|---|
| `POST collections/{collection}/entities` | mete varias a la vez |
| `DELETE collections/{collection}/entities/{entity}` | saca una |

Sacar **solo suelta el vínculo**: la entidad sigue en la biblioteca y en las
demás colecciones, y eso se dice en el aviso y en el mensaje. Meter comprueba
antes cuáles estaban ya —`attach()` sin comprobar crearía filas duplicadas en la
tabla intermedia— y respeta el `sort_order` continuando desde el máximo.

## 23. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/collections/show.blade.php` | reescrita entera, con su motor |
| `app/Http/Controllers/Collections/CollectionController.php` | `show()` reescrito; `attachEntities` y `detachEntity` |
| `routes/web.php` | `collections.entities.attach` y `.detach` |

## 24. Verificación

- La ficha responde 200 y no deja fondo claro salvo el desplegable compartido.
- Con la colección real: cifras, «Qué agrupa», y «De qué está hecha» con la
  barra naranja de su color (Personaje · 1 · 100 %). Las secciones de firma y
  sugerencias **no** aparecen, que es lo correcto con un solo miembro.
- Probado en una transacción revertida con una colección de tres miembros con
  catálogos: la firma encuentra **Anime = Naruto** compartido por las tres y las
  sugerencias proponen **12 entidades** que la cumplen entera y no están dentro,
  todas con su cara.
- Los endpoints, también en transacción revertida: meter dos pasa de 1 a 3;
  repetir el mismo envío **no duplica** y lo dice; sacar una pasa de 3 a 2 con el
  mensaje que aclara que la entidad no se borra; y enviar sin elegir nada se
  rechaza en castellano. Al terminar, la colección volvió a su único miembro.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.
