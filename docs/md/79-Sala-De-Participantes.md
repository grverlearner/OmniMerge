# 79 · La sala de participantes

## El problema

Decidir quién juega un torneo del universo existía por piezas, pero no
funcionaba de punta a punta. Al revisarlo aparecieron fallos que afectaban a
muchos torneos a la vez.

1. **Los valores de catálogo a veces eran números.** Una entidad importada
   desde su versión base guardaba `aldea: [12]` en lugar de `aldea: [hoja]`.
   Las reglas se escriben por nombre, así que «aldea → Hoja» nunca casaba con
   ella. Las versiones tenían el mismo problema.
2. **Las ediciones ignoraban las reglas del torneo.** El diseñador de
   ediciones ofrecía el universo entero, y el reparto por puertas del
   servidor también partía del universo entero. Un torneo «solo de la Hoja»
   podía llenarse con cualquiera.
3. **La cara correcta no se podía elegir.** En la Biblioteca, «Naruto
   clásico» se activa con «Anime → Naruto», pero ese vínculo no se copiaba
   al universo. La cara se elegía comparando atributos, y como todas las
   versiones heredan los mismos, no distinguía nada.
4. **La cara elegida no se veía.** Tablas, cuadro, resultado, campeón y
   batalla pintaban la imagen de siempre de la entidad, no la de la versión
   congelada.
5. **El catálogo en árbol no contaba.** `parent_option_id` existía, pero
   pedir «País del Fuego» no traía a los de «Hoja».
6. **No había reparto automático entre puertas**, y el reparto solo se
   podía definir edición a edición.

## Qué hay ahora

### La sala: `/universes/{u}/tournaments/{t}/participants`

La sala es `UniverseTournamentParticipantsController` con
`ParticipantRoomBuilder`, la vista `universes/tournaments/participants`, sus
partials en `partials/sala/*` y el componente Alpine `participant-room.js`.
Ocupa la pantalla completa y tiene fondo oscuro.

Se entra desde tres sitios:
- la tarjeta del torneo;
- su ficha;
- el bloque «Quién compite» del diseñador, que la abre en otra pestaña para
  no perder lo que se esté escribiendo.

**Cabecera**
- Cifras: dentro, fuera, colocados en puertas frente a plazas, y cuántos
  salen sin imagen.
- Una barra de proporción.
- «Sin guardar», Descartar y Guardar.
- Un aviso si la pantalla y el servidor no calculan lo mismo.
- Un aviso si nadie cumple las condiciones.

**Panel «Quién entra»**
- Cómo se combinan las condiciones: todas, alguna, ninguna o solo una.
- Condiciones: un atributo solo o con valores. Los valores se muestran en
  árbol, y un interruptor decide si un valor arrastra a sus sub-elementos.
- Grupos, que cuentan como una sola condición.
- El catálogo del universo, con la cobertura de cada atributo y sus valores
  en árbol (imagen o color del elemento y cuántos lo llevan).
- «Decidido a mano», con acciones en bloque:
  - meter o sacar a los buscados;
  - sacar a los que salen sin imagen;
  - olvidar lo decidido a mano.

**Panel «Puertas»**

| Caso | Qué ofrece |
|---|---|
| Sin puertas | Lo dice, con el nombre de la plantilla. |
| Una puerta | Qué hacer si no caben todos: los primeros o al azar (barajable). |
| Varias puertas, **Solo** | Equilibrado, en orden, al azar con semilla, o por atributo, eligiendo a qué puerta va cada valor (por ejemplo, Hoja → puerta 1). |
| Varias puertas, **Por reglas** | Una condición por puerta, con orden de preferencia (↑↓), y la opción de repartir el resto. |
| Varias puertas, **A mano** | Un pincel: se elige una puerta y se pulsan caras. Se puede partir del reparto automático. |

Debajo, cómo queda cada puerta: completa, faltan N o sobran N. También se
listan quienes cumplen pero no tienen puerta.

**Panel «Caras»**
- Qué cara se pone: «la que toca» o «la de siempre».
- De dónde sale cada cara, con filtros por fuente.
- Quienes tienen varias versiones, para fijar una con un clic.
- Quienes salen sin imagen.

**Escenario**, con cuatro vistas:
- **Dentro y fuera.** El tamaño de las caras es ajustable.
- **Por puerta.** Una caja por entrada, con color, plazas y estado.
- **Por valor.** Una caja por cada valor de un atributo, con quién juega y
  quién no, y un botón para llevar ese valor a la regla.
- **Lista.** Por qué entra, puerta, cara y fuente, y los atributos que pide
  la regla resaltados.

La búsqueda y el filtro «Problemas» (sin imagen o sin puerta) valen para las
cuatro vistas.

**Ficha** (al pulsar una cara)
- Dentro o fuera y por qué, y su puerta.
- Quién decide: las condiciones, dentro siempre o fuera siempre.
- Su puerta, cuando el reparto es a mano.
- Sus atributos. Pulsar un valor lo lleva a la regla.
- Todas sus versiones, con la imagen, las marcas base y defecto, y los
  elementos de catálogo que las activan. Se puede elegir «auto», una versión
  concreta o «de siempre».

### Qué se guarda

Todo se guarda en `universe_tournaments.eligibility` (no hay migración):

```
{ mode, rules: [{attribute, values[], descendants}], groups, include, exclude,
  faces: {universeEntityId: entityVersionId | 0}, face_mode: AUTO|BASE,
  doors: { mode: AUTO|RULES|MANUAL, strategy, attribute, value_doors, seed,
           fill_rest, rules: [{start_id, …regla}], manual: {startId: [ids]},
           template_id } }
```

Al guardar se descartan las entidades de otro universo y las puertas que no
son de la plantilla.

Guardar desde el diseñador del torneo conserva `faces`, `face_mode` y
`doors`.

### Cómo se decide la cara

Lo decide `UniverseEntityVersionResolver::choose()`, en este orden:

1. `MANUAL`: la elegida a mano.
2. `CATALOG`: la versión cuyos vínculos de la Biblioteca activa lo que pide
   la regla. Los grupos se leen como alternativas y dentro de cada grupo se
   aplica su operador, igual que en la Biblioteca.
3. `ATTRIBUTES`: la versión cuyos atributos cumplen la regla, pero solo si
   eso las distingue.
4. `BASE`, luego `DEFAULT`, luego `ENTITY`.

Se prueba primero con la regla de la puerta por la que entra, cuando el
reparto es por reglas, y después con la del torneo.

Los vínculos se copian al importar, en `version_snapshot[].activation`. Para
las entidades importadas antes se leen de la Biblioteca por el id de la
versión, solo en lectura, y la cara queda congelada en la edición.

### Lo que cambió por detrás

- **`UniverseCatalogIndex`** (nuevo): convierte los ids de catálogo en
  nombres al leer, sin tocar datos, y guarda el árbol padre/hijo.
- **`UniverseTournamentEligibility`**:
  - lee los valores traducidos;
  - admite `descendants` en cada regla;
  - el catálogo sale en árbol, con `total` (contando hijos), imagen y color;
  - añade `positiveSelections()`, `reason()` y `ownedFrom()`.
- **`CompetitionStartRouting`**:
  - `route()` parte de los que el torneo deja competir;
  - `plan()` reparte en los tres modos;
  - el azar usa FNV-1a con semilla, igual que la pantalla.
- **`UniverseEntityImporter`**: guarda los valores de catálogo por nombre y
  los vínculos de activación de cada versión.
- **Ediciones**:
  - el diseñador solo ofrece a los que cumplen;
  - una edición nueva parte del reparto del torneo;
  - al guardar se rechaza a quien no cumple;
  - la vista previa por puertas envía el torneo.
- **Estado inicial**: `TournamentInstanceStateFactory` recibe la regla de
  cada puerta y congela `image_url`, `version_from` y `version_reason` de
  cada participante.
- **`CompetitorFaces`** y el atributo `face_url` en los dos modelos de
  participante: tablas, cuadro, resultado, campeón, premios y batalla pintan
  la cara con la que se jugó.

## Verificación

Todo lo que escribe se probó dentro de transacciones deshechas.

- **Datos reales:**
  - Naruto (124) sale con «Naruto clásico» bajo «Anime → Naruto» y con su
    versión Shippuden bajo «Naruto: Shippūden»;
  - con cara a mano sale como «Naruto niño»;
  - con «la de siempre» sale con su base.
- **Reparto:** los cuatro repartos automáticos respetan las 3 plazas de cada
  una de las 4 puertas de «SOLO GRUPOS TORNEO».
- **Paridad pantalla–servidor:**
  - `serverMismatch` es falso al abrir;
  - el hash `7:124` da 2804143945 en PHP y en JS.
- **Guardar la sala:**
  - descarta un id inventado y una puerta ajena;
  - conserva «hoja → puerta 69»;
  - devuelve el mensaje con cuántos cumplen y cuántos no caben.
- **Pantallas:** responden 200 la sala en dos universos, la ficha, el
  diseñador y el índice de torneos, y crear, ver y jugar una edición.

## Límites conocidos

- El reparto «por atributo» usa el primer valor de cada competidor.
- La sala edita las reglas de puerta con condiciones y modo. Los grupos y la
  mano por puerta los entiende el servidor, pero la sala todavía no los
  ofrece.
