# La comunidad del taller y el panel de creador

**Fecha:** 2 de septiembre de 2026
**Alcance:** una comunidad propia para el módulo de Torneos, el panel de
creador, y la reorganización del sidebar.

---

## 1. El problema

El enlace «Comunidad» del taller de torneos llevaba a la comunidad de la
Biblioteca de entidades: entidades, colecciones, atributos y catálogos. Nada
de lo que se diseña aquí aparecía allí, y no había ninguna forma de ver ni de
copiar las plantillas que había montado otra persona.

Publicar tampoco servía de nada, porque no había dónde mirar lo publicado. Y
no existía ninguna pantalla que dijera **qué ve** alguien que se encuentra una
plantilla tuya.

---

## 2. Una comunidad propia

`tournaments/community` solo tiene dos cosas: **plantillas de torneo** y
**plantillas de fase**. Y una sola acción que importa: llevarse una a tu
espacio.

Los dos tipos se mezclan a propósito. Quien busca «una fase de grupos de 16» y
quien busca «una copa entera» está haciendo lo mismo: buscar una pieza que le
ahorre montarla. Separarlas en dos pantallas obligaría a mirar dos veces.

### 2.1 Cuatro maneras de mirar

Las mismas que en las bibliotecas propias —cuadrícula, **detalle**, lista y
tabla— con control de tamaño, y recordadas entre visitas
(`omnimerge.community.view`).

El modo **detalle** es el que justifica su existencia aquí: enseña el
recorrido de un torneo (entradas → fases numeradas → finales) o las puertas y
salidas de una fase **antes** de llevársela. Copiar algo sin saber qué hace por
dentro es copiar un problema.

### 2.2 Filtrar y ordenar

Búsqueda por nombre, código o descripción; tipo (torneos y fases / solo unos /
solo otras); motor de fase; tipo de torneo; y cinco ordenaciones: recién
publicadas, **más copiadas**, más vistas, más completas, y por nombre.

«Más copiadas» usa `clones_count`, que es la única señal honesta que hay aquí:
una plantilla que diez personas se llevaron les sirvió de algo.

### 2.3 Cómo se pagina

Cuando se miran los dos tipos a la vez, cada uno trae su propio tramo de 12 con
un enlace «ver los N». Al elegir un tipo concreto, ese se pagina entero.
Fusionar dos consultas paginadas en una sola lista exige recorrerlas enteras, y
aquí no compra nada.

### 2.4 Las fichas

Cada pieza tiene su página: portada, autor con su titular, el recorrido o las
puertas completos, sus cifras, sus etiquetas, y el botón de llevársela. Desde
un torneo se puede saltar a **la fase que usa**, si también está publicada: a
veces lo que uno quiere no es la copa entera sino una de sus piezas.

Las visitas se cuentan **una vez por sesión** y nunca las del dueño. Sin eso el
contador mide recargas, no interés, y el propio autor sería su mejor público.

### 2.5 Llevársela

Reutiliza el `duplicate` que ya existía: la política ya permitía copiar una
plantilla ajena si `canBeCloned()`. La copia entra en tu espacio como
**borrador privado**, con su estructura entera, y a partir de ahí es tuya: el
original no se entera de lo que hagas.

---

## 3. El perfil de creador

`tournaments/community/creators/{user}` enseña a una persona **desde el
taller**: quién es, y todo lo que ha publicado de torneos y de fases.

Deliberadamente parcial. Su biblioteca de entidades vive en la otra comunidad y
se **enlaza**, no se copia: mezclarlas convertiría la pantalla en un perfil
general y dejaría de responder a la única pregunta que se hace aquí —«¿me sirve
algo de lo que monta?»—.

---

## 4. El panel de creador

`tournaments/creator`. Es la pantalla que faltaba, y su bloque más importante
es el que responde a **«¿por qué no encuentran lo mío?»**.

Una pieza aparece en la comunidad si cumple tres cosas —**activa**, **pública**
y con **fecha de publicación**— y sirve de algo si cumple una cuarta:
**permitir la copia**. El panel enseña cuántas se quedan fuera por cada motivo,
con el enlace para ir a arreglarlo:

| gravedad | qué detecta |
|---|---|
| 🔴 | Tu perfil está oculto — nadie puede abrir tu página de creador |
| 🟠 | Públicas pero no activas — marcadas como públicas, siguen en borrador |
| 🔵 | Activas pero privadas — las candidatas a publicar |
| 🔵 | Publicadas sin permitir copia — escaparate, no pieza |

Además: **tu ficha pública** (titular, biografía, dónde estás, tu sitio y si tu
perfil se ve) con **la vista previa en vivo** de cómo te verán al lado, y **lo
que ya está publicado** con sus visitas y sus copias.

El nombre, el usuario, el avatar y el correo siguen en la pantalla de perfil de
siempre: no son «cómo te ven», son quién eres para el sistema.

---

## 5. El sidebar

Fuera **Laboratorio** y **Recompensas**. El laboratorio se abre desde el taller
junto a la plantilla que se va a probar —que es cuando se necesita— y las
recompensas no existen todavía como para ocupar sitio.

Dentro un grupo **Comunidad** con dos entradas: **Explorar** y **Creador**. Y
«Dashboard» pasa a llamarse **Taller**, que es lo que es.

---

## 6. Archivos

| archivo | qué es |
|---|---|
| `app/Http/Controllers/Tournaments/TournamentCommunityController.php` | nuevo · índice, fichas y perfil |
| `app/Http/Controllers/Tournaments/CreatorPanelController.php` | nuevo · el panel y su formulario |
| `resources/views/tournaments/community/index.blade.php` | nuevo · los cuatro modos y los filtros |
| `.../community/partials/collection.blade.php` | nuevo · un conjunto en los cuatro modos, para los dos tipos |
| `.../community/{tournament,phase,creator}.blade.php` | nuevos · las fichas y el perfil |
| `resources/views/tournaments/creator.blade.php` | nuevo · el panel de creador |
| `resources/views/partials/tournaments/sidebar.blade.php` | reorganizado |
| `routes/web.php` | seis rutas nuevas dentro del grupo de torneos |

Un solo archivo dibuja los dos tipos en los cuatro modos
(`partials/collection`). Es deliberado: en esta pantalla los dos se comparan
entre sí, y con dos renderizadores acabarían enseñando cosas distintas en
sitios equivalentes.

---

## 7. Verificación

- Las seis pantallas responden **200**: comunidad, comunidad filtrada por
  fases, panel de creador, perfil de creador y la ficha de una fase.
- La ficha de un torneo se verificó con una **sonda en memoria**, sin tocar la
  base de datos —no hay ningún torneo publicado todavía—: monta la plantilla
  con sus entradas, su fase y sus dos finales, y comprueba que la página
  contiene el nombre, el recorrido, el final «Campeón» y la capacidad. Vista
  por su dueño dice «Duplicar en mi espacio» y «es tuya»; vista por otra
  persona, «Llevármela a mi espacio».
- En el panel de creador, escribir titular, biografía, ubicación y sitio
  actualiza la vista previa en vivo, y el campo oculto de visibilidad recoge
  el valor elegido.
- La validación del formulario, probada en memoria: acepta todo vacío salvo la
  visibilidad, y rechaza una URL inválida, una visibilidad inventada y un
  titular de más de 120 caracteres.
- El sidebar ya no tiene Laboratorio ni Recompensas, y sí Explorar y Creador.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
