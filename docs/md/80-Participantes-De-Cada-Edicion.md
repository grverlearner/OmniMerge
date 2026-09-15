# 80 · Los participantes de cada edición

## Qué faltaba

La sala de participantes del torneo (ver 79) decidía quién entra, por qué
puerta y con qué cara, pero al crear una edición no se veía nada de eso. El
bloque «Quién entra» del diseñador de ediciones seguía siendo el antiguo:
reglas por puerta o marcar uno a uno. No decía qué había configurado el
torneo ni permitía decidir, solo para esa edición, algo distinto.

## Qué hay ahora

### Dos formas, siempre a la vista

El bloque 06 del diseñador de ediciones
(`competitions/partials/designer-participants`) ofrece dos opciones:

| Opción | Qué hace |
|---|---|
| **Como dice el torneo** | Usa lo configurado en la sala del torneo: sus condiciones y su mano, su reparto por puertas (si es para la misma forma) y sus caras. Si el torneo cambia antes de crear la edición, la edición lo recoge. |
| **Distinta en esta edición** | Tiene lo suyo, solo para esta edición. La primera vez parte del reparto y del modo de cara del torneo. El torneo no se toca. |

Debajo se ve el resultado:
- cuántos juegan;
- cuántos salen con la cara de una versión y cuántos sin imagen;
- cada puerta con su color, sus plazas, su estado y sus caras;
- quienes cumplen pero no caben;
- un aviso si no entra nadie.

Botones:
- **Abrir la sala de esta edición**: la abre a pantalla completa encima del
  diseñador, sin cambiar de página ni perder lo escrito.
- **Sala del torneo**: la abre en otra pestaña.
- Pulsar una puerta abre la sala directamente en la vista «por puerta».

### La sala de la edición

Usa las mismas piezas que la del torneo: pestañas, paneles «Quién entra»,
«Puertas» y «Caras», escenario con cuatro vistas y ficha de cada
competidor. Lo que añade:

- **En la cabecera**, el cambio entre «Como el torneo» y «Distinta en esta
  edición».
- **Con «Distinta»**, desde dónde se parte:
  - *de lo que permite el torneo*: las condiciones de la edición estrechan a
    los que ya deja entrar el torneo;
  - *de todo el universo*: ignora las reglas del torneo solo en esta
    edición.
- **Con «Como el torneo»**, el panel izquierdo muestra qué dice el torneo
  (condiciones, mano, puertas y caras) y un botón para personalizar. Si
  decides algo en la ficha de un competidor, la edición pasa sola a
  «Distinta» y lo avisa.
- **«Usar esto»** cierra la sala con los cambios. **«Cancelar»** (o Escape)
  deja lo que había al abrirla. Nada se guarda hasta crear o guardar la
  edición.

### Qué se guarda

La nueva columna `tournament_instances.participant_design` (JSON, nullable)
guarda el diseño. La migración
`2026_09_15_100000_add_participant_design_to_tournament_instances` solo
añade la columna y no toca ninguna fila.

```
{ source: TOURNAMENT|CUSTOM, scope: TOURNAMENT|UNIVERSE,
  mode, rules, groups, include, exclude, faces, face_mode, doors }
```

El formulario lo envía en `participant_design`. El servidor ignora lo que
calculó la pantalla y lo recalcula con `EditionParticipants`:

- **`resolve()`**: quién entra y por qué puerta.
  - `TOURNAMENT`: el plan del torneo, con su reparto si es para esta
    plantilla; si no, reparto automático equilibrado.
  - `CUSTOM`: parte de los que deja el torneo o del universo, aplica las
    condiciones y la mano de la edición, y reparte con
    `CompetitionStartRouting::planWithin()`.
- **`faceContext()`**: con qué reglas se elige la cara. Las de la edición si
  escribió alguna, si no las del torneo. Las caras elegidas a mano en la
  edición ganan a las del torneo, y el modo de cara es el de la edición.
- **`doorRules()`**: la regla de cada puerta cuando el reparto es por
  reglas.

La pantalla (`participant-room.js`, contexto `EDITION`) calcula lo mismo con
`effRules`, `effFaces`, `effFaceMode` y `effDoors`.

### Dónde se usa

- **Crear una edición**: calcula el reparto desde el diseño y lo congela con
  sus caras en el estado inicial (`face_context` y `door_rules`). Después
  guarda el diseño.
- **Editar una edición que no ha empezado**: guarda el diseño y rehace el
  cuadro con él. Si ya empezó, el bloque solo muestra quién juega, con la
  cara con la que juega.
- **Rehacer el cuadro** (`reassign`): usa el diseño de la edición si lo
  tiene.
- **Copiar una edición**: la nueva trae su diseño. Si la copiada es anterior
  a esto y no tiene diseño, su reparto se convierte en uno propio a mano,
  para no perderlo.
- **Tras un error de validación**: el diseño vuelve tal como estaba.
- **Sincronizar entidades**: las condiciones propias de una edición cuentan
  como atributos de los que depende.
- **Índice del diseñador**: la pastilla «Quién entra» dice
  «N de M · como el torneo» o «· propia».

## Verificación

Todo lo que escribe se probó dentro de una transacción deshecha.

- **Pantallas:** crear una edición de dos torneos y copiar una responden
  200, igual que la sala del torneo.
- **Paridad con el servidor:**
  - «Como el torneo» en «SOLO GRUPOS TORNEO» reparte igual en la pantalla y
    en el servidor: `68:[134,139,142] 69:[132,130,128] …`;
  - «Distinta», desde todo el universo, con «anime → Naruto», al azar con
    semilla 4 y Naruto fijado como «Naruto niño», también coincide:
    `68:[124,136,132] …`, con 5 que no caben.
- **Crear:**
  - la heredada se guarda con `source TOURNAMENT` y 12 participantes;
  - la propia, con `CUSTOM`, 12 participantes y Naruto como «Naruto niño».
- **Rehacer y copiar:**
  - rehacer la heredada con el diseño propio deja `CUSTOM`, rehace el cuadro
    con 12 y responde «Edición actualizada, y su cuadro rehecho con 12
    competidores.»;
  - copiarla trae `CUSTOM` con su condición.
- **La sala en el navegador:**
  - cambiar a «Como el torneo» muestra su panel;
  - «Cancelar» devuelve lo que había al abrir.

## Límites conocidos

- Las condiciones de una edición «de lo que permite el torneo» se aplican
  encima del torneo. No hay forma de sacar una excepción del torneo solo
  para la edición, salvo partir de todo el universo.
- El bloque antiguo (`designer-doors`) sigue en el repositorio, pero ya no
  se incluye.
