# El orden general de una fase de grupos

Una fase de grupos produce **varias tablas, no una**. Pero casi siempre hace
falta una sola lista: para repartir plazas, para sembrar el cuadro que viene,
para entregar premios por puesto.

Y cuál es «la primera» de esa lista no es un hecho, es una **decisión**:

> El 1.º del Grupo A hizo 3 puntos. El 2.º del Grupo C hizo 2.
>
> Comparando a todos → va delante el del Grupo A (hizo más).
> Comparando por puesto → va delante el del Grupo A igualmente… pero el 3.º del
> Grupo A, con 3 puntos, cae **por detrás** del 2.º del Grupo C, con 2.

Ninguna de las dos es «la correcta». Por eso ahora se elige, y por eso se ve.

## Los tres modos

| Modo | Cómo ordena |
|---|---|
| **Todos contra todos** | Una sola tabla, sin mirar el grupo. Manda el rendimiento. |
| **Por puesto en su grupo** | Primero todos los 1.º, luego todos los 2.º… Dentro de cada bloque, por rendimiento. Ganar tu grupo vale más que hacer puntos. |
| **Por puesto y orden de grupo** | Como el anterior, pero dentro del bloque manda el orden de los grupos: 1.º de A, 1.º de B, 1.º de C… Da un cuadro siempre igual, sin depender de resultados. |

El **desempate** dentro de cada caso no lo decide esto: es el de la fase —puntos,
diferencia, anotados, enfrentamiento directo…— con los criterios que la
plantilla tenga configurados. Por eso la lista general nunca puede contradecir a
las tablas de cada grupo.

## Dónde se elige y dónde se ve

- **Se elige** en el panel izquierdo de la Super Edición, con las tres opciones y
  lo que hace cada una escrito al lado.
- **Se ve** arriba del todo de la estructura central: la lista completa, con el
  puesto general, la cara, y de qué grupo y en qué posición viene cada uno.
  Cambiar el modo la reordena al momento.
- **Se guarda** en la plantilla de fase, así que viaja al torneo real dentro del
  snapshot y sale igual en la competición.
- **Se muestra** en la competición real, encima de los grupos, con el nombre del
  modo al lado —sin eso el orden parece arbitrario—.

## Una sola implementación

`GroupStageOverallRanking` tiene la aritmética y **no sabe comparar**: recibe la
comparación del motor y solo decide en qué orden se aplica. El motor la usa al
recalcular y deja el resultado en `overall_standings`, así que la pantalla de la
competición, el reparto de plazas y cualquier fase que siembre con ella leen la
misma lista. Calcularla en cada sitio serían tres listas que pueden discrepar.

El editor la reproduce en el cliente para la vista previa, con el mismo
`rank()` que ya usaba para las tablas.

## Comprobado

En el editor, sobre una fase de 4 grupos ya simulada, los tres modos producen
listas distintas y coherentes. Y sobre la competición real —12 competidores en
4 grupos, con resultados de verdad— la diferencia entre los dos primeros modos
sale exactamente donde tiene que salir:

| # | Todos contra todos | Por puesto |
|---|---|---|
| 7 | D2 · 3p | D2 · 3p |
| 8 | **A3 · 3p** | **C2 · 2p** |
| 9 | **C2 · 2p** | **A3 · 3p** |

El tercero del Grupo A hizo más puntos que el segundo del Grupo C, así que va
antes por rendimiento y después por puesto. Es justo la decisión que el modo
representa.

Suite de tests: 88 pasan / 99 fallan, igual que antes. `npm run build` compila.

## Lo que hay que saber

Una competición **ya en marcha** no tiene la lista en su estado guardado: aparece
en cuanto el motor recalcule, es decir, en la siguiente acción —jugar una
batalla, abrir una fase—. Y las que se crearon antes de este cambio salen con
**Todos contra todos**, que es el modo por defecto.

---

# La edición puede cambiarlo, y la arena lo enseña

## 1. Dentro de «ajustar», solo en fase de grupos

Al crear o editar una edición, en **05 · Fase por fase**, cada fase de grupos
trae ahora su propio bloque —y solo ellas: en un cuadro o en una liga la
pregunta no existe, y ofrecer un control que no hace nada es peor que no
ofrecerlo—.

Dice **en qué estado está**, que era la mitad de lo pedido:

> ≡ **Orden general de la fase** · `heredado de la plantilla` · Todos contra todos

y al elegir otro pasa a `esta edición`. La opción vacía —«heredar de la
plantilla»— es el valor por defecto y el caso normal: nulo significa «lo que
diga la plantilla», igual que el juego o el formato de batalla.

Debajo, la explicación del modo seleccionado cambia con él, para no tener que
recordar qué hacía cada uno.

### Cómo llega al motor

Columna nueva `overall_ranking_mode` en `tournament_instance_phases`, nulable.
`CompetitionPhasePlan` la escribe en el runtime del nodo **en cada acción** —no
solo al preparar—, así que retocarla con la competición ya empezada se nota en
cuanto el motor recalcule. Sin excepción propia no toca nada y manda lo que el
motor leyó de la plantilla.

| Situación | Lo que usa el motor |
|---|---|
| Sin excepción | el modo de la plantilla |
| Con `BY_POSITION` en la edición | `BY_POSITION` |

## 2. El ranking, en fila y arriba

Mientras se juega hacen falta dos respuestas, y ninguna estaba a mano: **cómo va
esta competición** y **cómo va el universo**. Son preguntas distintas, así que
son dos pestañas y no una tabla mezclada — el campeón de hoy puede ser el 13.º
del universo, y ver las dos cifras juntas es justo lo interesante.

La tira vive **fuera del lienzo**, así que se ve en las cinco etapas: jugando,
mirando la estructura o repartiendo premios. Se pliega, y se recuerda plegada.

### Esta competición

Tarjeta por competidor con su foto, el puesto en oro/plata/bronce para los tres
primeros, 🏆 en el campeón, su línea de victorias·empates·derrotas, sus puntos
y —abajo— **su posición en el universo**. Ese último dato es el que hace hablar
a la tira.

Quien todavía no tiene puesto sale con «—» en vez de un número inventado.

### El universo

El ranking completo, con títulos, torneos jugados y porcentaje de victorias. Y
lo que convierte una lista ajena en una lista que te habla: **los que compiten
aquí van marcados** —borde cian y una etiqueta «compite aquí»— y el resto queda
atenuado. Se ve de un vistazo si tu campeón viene de arriba o del fondo.

Un enlace al ranking completo del universo cierra la tira: esto es un vistazo,
no una consulta.

## Comprobado

| Qué | Resultado |
|---|---|
| Bloque en «ajustar» | aparece solo en la fase `GROUP_STAGE` |
| Estado | «heredado de la plantilla» → «esta edición» al elegir |
| Campo que viaja | `phases[103][overall_ranking_mode]=BY_POSITION` |
| El motor con excepción | usa `BY_POSITION`; sin ella, el de la plantilla |
| Tira · esta competición | 16 tarjetas, con puesto, cifras y posición de universo |
| Tira · universo | 22 tarjetas, con títulos y los de esta competición marcados |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |

---

# Y el ranking de cada fase

Faltaba la escala de en medio. La tira de arriba responde «cómo va la
competición» y «cómo va el universo»; ninguna responde **«cómo va esta fase»**,
que es justo lo que se mira mientras se juega. Un cuadro de dieciséis y una liga
de diez van cada uno por su cuenta, y su orden no es ninguno de los otros dos.

Va **dentro de cada fase**, encima de su cuerpo, así que aparece para todos los
motores por igual y siempre junto a lo que describe.

## Deliberadamente pequeño

Puesto, cara y nombre. Nada más, como pediste — y por una razón: el detalle
—jugados, ganados, diferencia— ya está en la tabla de abajo, a un palmo.
Repetirlo aquí sería ruido, y la gracia es leerlo de un vistazo sin dejar de
mirar las batallas. El primero va en ámbar; el resto, en gris.

## De dónde salen las filas

Depende del motor, y por eso lo decide la pantalla y no el trozo que las pinta:

| Fase | Fuente |
|---|---|
| De grupos | la **lista única** que calcula el motor con el modo elegido |
| Las demás | sus propias posiciones, que ya son el orden de la fase |

Y una fase de grupos **sin** lista única todavía **no enseña nada**. Sus
posiciones son de cada grupo —1, 1, 1, 1, 2, 2, 2, 2…— y puestas en fila
parecen un ranking sin serlo: cuatro «primeros» seguidos confunden más que la
ausencia. Aparece en cuanto el motor recalcule.

## Comprobado

Sobre el torneo de 4 fases:

| Fase | Tira |
|---|---|
| TVT 1 · liga | 10 competidores, 1 → 10 |
| TVT 2 · liga | 10 competidores, 1 → 10 |
| Grupos · sin lista única aún | **no se dibuja** |
| Elim · sin empezar | no se dibuja |

En pantalla: «▤ Cómo va esta fase · Todos contra todos · 10 competidores», y
debajo la fila — 1 Jura, 2 Hiruzen Sarutobi, 3 Ishiki, 4 Akamaru…

Suite de tests: 88 pasan / 99 fallan, igual que antes.
