# La biblioteca de plantillas de torneo y su definición

**Fecha:** 2 de septiembre de 2026
**Alcance:** el índice de plantillas de torneo y las pantallas de crear y
editar su definición.

---

## 1. El problema

Una plantilla de torneo no se entiende por su nombre: se entiende por su
**recorrido** —por dónde entra la gente, qué fases atraviesa y en qué finales
acaba—. La lista enseñaba una tarjeta clara con la foto, el estado y poco más,
así que para saber qué era cada cosa había que abrirla una a una.

Y la pantalla de definición pedía cinco cosas: nombre, descripción, imagen,
límites y publicación. Nada permitía organizar una biblioteca que ya tiene
doce plantillas y crecerá.

---

## 2. La biblioteca

### 2.1 Cuatro maneras de mirar

| modo | qué enseña |
|---|---|
| **cuadrícula** | la cara y las cuatro cifras del recorrido |
| **detalle** | el recorrido entero: entradas → fases numeradas → finales |
| **lista** | una línea por plantilla, con sus fases por nombre |
| **tabla** | once columnas para comparar cifras |

El modo elegido y el tamaño se recuerdan entre visitas (`localStorage`, clave
`omnimerge.tournaments.view`).

**Detalle** es el que responde a «entender bien qué hay en cada torneo»: para
cada plantilla enseña por dónde entra la gente y con cuánta, la lista numerada
de fases con el motor de cada una, y los finales con su tipo —campeón,
clasificados, puestos, eliminados—, cada uno de su color.

### 2.2 Las cuatro cifras

Toda ficha lleva **entradas · fases · enlaces · finales**. Son las que dicen si
una plantilla es un cuadro simple o un sistema con ramas, y se piden por
adelantado (`withCount` + `with`) para que dieciocho fichas no sean setenta
consultas.

Además, una plantilla sin fases o sin finales se marca **«sin terminar»**: no
es un borrador a medio hacer, es un torneo que no se puede jugar, y decirlo en
la lista ahorra abrirlo para descubrirlo.

### 2.3 Filtrar y ordenar

Búsqueda, **tipo**, estado, visibilidad, **uso** (ya en uso / sin usar
todavía) y ocho ordenaciones, todo en el servidor porque la lista está
paginada. Los desplegables se envían al cambiar: un filtro que hay que
confirmar con un botón se usa la mitad.

---

## 3. La definición

### 3.1 Qué se decide aquí y qué no

Esta pantalla responde a **qué es** el torneo y **cómo se reconoce**. El
recorrido —entradas, fases, enlaces, finales— se construye en la Super
Edición, porque hasta que la plantilla no existe no hay grafo que montar. Y
quién juega, cuándo y con qué premios se decide en cada edición, no aquí.

### 3.2 Lo que se añadió

- **Tipo de torneo**: Copa, Liga, Clasificatorio, Amistoso, Ranking, Especial,
  o sin clasificar. Cada uno explica en una línea qué significa, y le da a la
  plantilla su icono y su color por defecto.
- **Etiquetas**: hasta seis palabras propias («verano», «oficial», «pruebas»)
  para agrupar como convenga. Se escriben y se añaden con Enter o con coma; se
  quitan con la ×; las repetidas se descartan solas. Una etiqueta a medio
  escribir se recoge al enviar en vez de perderse por no haber pulsado Enter.
- **Icono** libre o de una lista de sugerencias, y **color** de siete tonos.
- **Frase corta** de 140 caracteres, que es lo que se lee en la ficha.
- **Capacidad con o sin techo**, en vez de dos campos sueltos donde no se
  sabía si dejar el máximo vacío era válido.

Todo lo nuevo vive en `settings` y no lo lee ningún motor: solo la biblioteca.
Un campo que se vacía se **borra** en vez de guardarse en blanco, para que la
vista pueda preguntar «¿tiene icono?» sin comparar contra cadena vacía.

### 3.3 La vista previa

A la derecha se ve la ficha exacta que aparecerá en la biblioteca, en vivo:
imagen, icono, color, tipo, nombre, frase, capacidad y etiquetas. Las cuatro
cifras del recorrido salen a cero porque todavía no hay recorrido.

El componente de Alpine repite a propósito las tablas de icono y color por
defecto que PHP ya tiene. Si divergieran, la vista previa mentiría; el
comentario del código lo dice para que se corrijan las dos a la vez.

---

## 4. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/tournaments/templates/index.blade.php` | reescrito: oscuro, cuatro modos, seis filtros |
| `.../templates/partials/library-card.blade.php` | nuevo: la ficha (cuadrícula y detalle) |
| `.../templates/partials/library-row.blade.php` | nuevo: la línea del modo lista |
| `resources/views/tournaments/partials/template-form.blade.php` | reescrito: oscuro, tipo, etiquetas, presentación y vista previa |
| `.../templates/{create,edit}.blade.php` | cabecera oscura, con acceso directo a la Super Edición |
| `resources/js/tournaments/templates/designer.js` | nuevo: el estado del formulario y las etiquetas |
| `app/Models/TournamentTemplate.php` | `CATEGORIES`, `ACCENTS` y los accesores `display_icon`, `accent`, `summary`, `category`, `tags` |
| `app/Http/Requests/Tournaments/{Store,Update}TournamentTemplateRequest.php` | validan icono, color, frase, tipo y hasta seis etiquetas |
| `app/Services/Tournaments/TournamentTemplateService.php` | `withPresentation()`: limpia, deduplica y guarda en `settings` |
| `app/Http/Controllers/Tournaments/TournamentTemplateController.php` | precarga el grafo, cuenta, y filtra por tipo y por uso |

---

## 5. Verificación

- El índice y las pantallas de crear y editar responden **200**.
- Los cuatro modos alternan lo que se ve: en tabla, 12 filas visibles y 0
  fichas; en detalle, el bloque del recorrido aparece con su contenido real
  —`Entrada ·16 → ZZZ ELIMINACION CON PUESTOS (Eliminación directa) → Ganador,
  #3 lugar, #7 lugar, #13 lugar, Eliminados`—.
- En el formulario: elegir tipo, color, capacidad y estado actualiza la vista
  previa y los campos ocultos que se envían (`category`, `accent`, `status`,
  `visibility`, `capacity_mode` y un `tags[]` por etiqueta).
- Sonda en memoria, sin tocar la base de datos: la validación rechaza más de
  seis etiquetas; el servicio recorta, deduplica y descarta las vacías; el
  modelo lo lee todo de vuelta y, sin icono propio, hereda el de su tipo.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
