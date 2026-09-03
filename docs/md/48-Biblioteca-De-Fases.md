# La biblioteca de fases y la definición de una fase

**Fecha:** 2 de septiembre de 2026
**Alcance:** el índice de plantillas de fase y las pantallas de crear y editar
su definición.

---

## 1. El problema

Una fase no se reconoce por su nombre. Se reconoce por su forma: cuánta gente
admite, por dónde entra esa gente y por dónde sale. Nada de eso se veía en la
lista, así que la biblioteca servía para poco más que localizar algo cuyo
nombre ya se recordaba; para saber qué era cada cosa había que abrirla.

Y la pantalla de definición —la de crear o editar— arrastraba dos cosas más:
un diseño claro que ya no se parecía al resto de la aplicación, y un campo
**BEST OF** que no debería estar ahí.

---

## 2. La biblioteca

### 2.1 Cuatro maneras de mirar

El índice tiene ahora cuatro modos de vista, que se eligen arriba a la derecha
y se recuerdan entre visitas (`localStorage`, clave `omnimerge.phases.view`):

| modo | qué enseña |
|---|---|
| **cuadrícula** | la cara y lo esencial, para abarcar muchas de golpe |
| **estructura** | además, el recorrido: entradas → motor → salidas, con sus nombres |
| **lista** | una línea por fase, con todo en horizontal |
| **tabla** | para comparar cifras |

Junto a ellos hay un control de tamaño (de 2 a 5 columnas), que también se
recuerda.

La cuadrícula y la estructura son **la misma ficha**
(`partials/library-card.blade.php`) con un bloque más desplegado, no dos
componentes: duplicarla garantizaría que un día enseñaran datos distintos. Ese
bloque se oculta con `x-show` y no con `x-if`, porque cambiar de modo veinte
veces no debería reconstruir el DOM veinte veces.

### 2.2 Filtrar y ordenar los hace el servidor

Búsqueda, motor, estado, visibilidad y orden se envían al servidor. No es un
descuido: la lista está paginada, y filtrar en el cliente ordenaría *una
página*, no la biblioteca. Lo que sí vive en el cliente es la forma de mirar,
que no cambia los datos.

### 2.3 Qué cuenta cada ficha

Imagen (o el icono de la fase), motor, estado, visibilidad, si algún torneo ya
la está usando y cuántos, modo de participación, y el número de entradas y
salidas. En modo estructura, además, los **nombres** de esas puertas y salidas
—que es lo que distingue dos fases que por cifras parecen iguales—.

Para que las cifras no cuesten una consulta por ficha, el controlador carga por
adelantado las puertas y salidas activas y cuenta con `withCount`.

---

## 3. La definición de una fase

### 3.1 Qué se decide aquí y qué no

Esta pantalla responde a **qué es** la fase y **cómo se reconoce**. No a cómo
se juega. Emparejamientos, calendario, grupos, desempates y salidas se
configuran en la Super Edición.

Por eso se retiró el **BEST OF**. Cuántos juegos tiene un enfrentamiento no es
una propiedad de la plantilla: cambia entre dos ediciones del mismo torneo
usando la misma plantilla, y por tanto pertenece al torneo real. La columna
`best_of` sigue existiendo y los motores siguen leyéndola —al crear una fase
se siembra con 3—, pero ya no se pregunta en el formulario.

### 3.2 Lo que se añadió: cómo se reconoce

Tres campos nuevos, todos de presentación:

- **Icono** — una lista de sugerencias y un campo libre (máximo 8 caracteres).
- **Color** — siete tonos, o «el de su motor».
- **Frase corta** — hasta 120 caracteres; es lo que se lee en la ficha de la
  biblioteca. Si se deja vacía, la ficha enseña la descripción.

Viven en `settings` y no en columnas propias porque **no los lee ningún
motor**: solo la biblioteca. Un campo que se vacía se borra en vez de
guardarse en blanco, para que la vista pueda preguntar «¿tiene icono?» sin
comparar contra cadena vacía.

Si no se elige ninguno, la fase no se queda sin cara: hereda el icono y el
color de su motor (`PhaseTemplate::display_icon` y `::accent`).

### 3.3 La vista previa

A la derecha, mientras se rellena, se ve **la ficha exacta** que aparecerá en
la biblioteca: imagen, icono, color, nombre, frase, modo de participación y
contrato de capacidad, todo en vivo.

Es deliberado. Elegir un color a ciegas y descubrir el resultado en otra
pantalla es lo que hacía que nadie los cambiara.

El componente de Alpine repite a propósito las tablas de icono y color por
defecto que PHP ya tiene. Si divergieran, la vista previa mentiría; el comentario
del código lo dice para que se corrijan las dos a la vez.

### 3.4 Las clases de Tailwind no se componen

Tailwind lee los archivos fuente: una clase armada con `'border-' . $color` no
existiría en el CSS. El mapa de tonos se escribe literal en Blade y viaja
entero al cliente, donde el componente solo **elige** cuál usar
(`tone('borde')`), nunca la arma.

---

## 4. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/tournaments/phase-templates/index.blade.php` | reescrito: oscuro, cuatro modos, tamaño, filtros |
| `.../partials/library-card.blade.php` | nuevo: la ficha (cuadrícula y estructura) |
| `.../partials/library-row.blade.php` | nuevo: la línea del modo lista |
| `.../partials/form.blade.php` | reescrito: oscuro, sin BEST OF, con presentación y vista previa |
| `.../create.blade.php`, `.../edit.blade.php` | cabecera oscura sobre `x-arena-layout` |
| `resources/js/tournaments/phase-templates/designer.js` | `icon`, `accent`, `summary`, `effectiveIcon()`, `effectiveAccent()`, `tone()`; fuera `bestOf` |
| `app/Models/PhaseTemplate.php` | accesores `display_icon`, `accent`, `summary` |
| `app/Http/Requests/Tournaments/{Store,Update}PhaseTemplateRequest.php` | `best_of` pasa a `nullable`; se validan `icon`, `accent`, `summary` |
| `app/Services/Tournaments/PhaseTemplateService.php` | siembra `best_of` en 3 al crear; guarda la presentación en `settings` |
| `app/Http/Controllers/Tournaments/PhaseTemplateController.php` | el índice precarga puertas y salidas y cuenta con `withCount` |

---

## 5. Verificación

- El índice, crear y editar responden **200** y renderizan fichas, filas y
  tabla reales.
- Los cuatro modos de vista alternan lo que se ve; el bloque de estructura
  aparece solo en su modo.
- En la pantalla de crear: elegir motor, color, icono, participación,
  capacidad y estado actualiza la vista previa y los campos ocultos que se
  envían (`phase_type`, `participant_mode`, `accent`, `status`, `visibility`).
- Ni el formulario de crear ni el de editar envían ya `best_of`.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían antes de este cambio—.
