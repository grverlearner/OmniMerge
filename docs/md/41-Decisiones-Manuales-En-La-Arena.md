# La fase que espera a que decidas

Empezar una competición con fase de grupos manual terminaba aquí:

> **La fase todavía no está repartida.** Ábrela para ver los enfrentamientos.
>
> → *El Tournament Graph Runtime no está en ejecución.*

Y ahí se acababa el camino. No había nada roto: **el motor estaba esperando a
propósito.**

## Qué pasaba

Una fase se puede configurar para que reparta **a mano**:

| Tipo de decisión | Qué pregunta |
|---|---|
| `GROUP_ASSIGNMENT` | quién va a cada grupo |
| `PARTICIPANT_ORDER` | en qué orden entran al cuadro |
| `SINGLE_ELIMINATION_SETUP` | el orden, y quién descansa |

Cuando la hay, el motor deja el recorrido en `AWAITING_DECISION` y no avanza.
Eso es correcto —repartir por su cuenta sería ignorar lo que configuraste— y el
backend siempre supo resolverlo: la acción `RESOLVE_MANUAL_DECISION` existe
desde el Competition Lab.

Lo que faltaba era la pantalla. Ofrecía **«Abrir la fase»**, que llama a
`ADVANCE_TO_PLAYABLE`, que exige un recorrido **en marcha**. De ahí el mensaje:
exacto, y sin una sola pista de qué hacer.

## Lo que ahora se ve

Un panel que sustituye a «abrir la fase» y pregunta lo que hay que preguntar,
**con las caras delante** — repartir doce competidores leyendo claves
`UC-000123` no lo hace nadie.

### Repartir en grupos

Se pulsa un competidor y luego su grupo. Cada grupo enseña **cuántos van de
cuántos caben**, quién está dentro con su imagen, y una × para sacarlo. El
mismo lenguaje que el reparto por puertas: `✓ completo`, `faltan 2`,
`sobran 1`.

Hay un **⇄ repartir por mí** que llena respetando las capacidades. No es una
decisión disfrazada: es el punto de partida que casi siempre se quiere, y se
mueve a mano después. Con doce competidores y cuatro grupos, empezar de cero a
clics es un castigo.

### Orden y descansos

Lista ordenada con ↑ ↓, y cuando la fase pide BYEs, un botón «descansa» por
competidor con su cuenta.

### Y no deja enviar a medias

El botón está apagado hasta que la respuesta es válida, y al lado dice qué
falta exactamente:

- «Faltan 1 competidor por colocar.»
- «Grupo A necesita 3 · Grupo D necesita 3»

El servidor vuelve a validarlo igualmente —sigue siendo la autoridad—, pero
llegar hasta el error habiendo podido decirlo antes no tiene sentido.

## El mensaje de error, también

`requireRuntime()` decía lo mismo para un recorrido roto y para uno parado
esperando. Ahora distingue:

> El recorrido está esperando tu decisión en **GRUPOS PURO**. Esa fase se
> configuró a mano, así que el motor no reparte solo: resuélvela y la fase se
> abrirá.

## Un detalle de Alpine

El panel hace su propia llamada en vez de pedírsela al componente de la arena.
Un `x-data` anidado hereda el ámbito para **evaluar expresiones**, pero no para
el `this` de sus propios métodos: `this.execute` sería `undefined`. Por eso la
URL y la revisión viajan en su configuración.

## Comprobado

Sobre la competición real —12 competidores, 4 grupos de 3, fase «BBB Grupos»
con `distribution_mode = MANUAL`:

| Qué | Resultado |
|---|---|
| La pantalla | ya no ofrece «abrir la fase»; muestra el panel |
| ⇄ repartir por mí | 3 + 3 + 3 + 3, «Todo listo» |
| Sacar a uno | «Faltan 1 competidor por colocar», botón apagado |
| Dejar A con 2 y D con 4 | «Grupo A necesita 3 · Grupo D necesita 3» |
| El envío | `RESOLVE_MANUAL_DECISION` con los 12 en `GROUP_1..4` |
| El motor, con ese reparto | `status=RUNNING`, 4 grupos de 3, **12 jornadas** |
| El motor, con reparto torcido | rechazado: «Grupo A necesita exactamente 3» |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |
