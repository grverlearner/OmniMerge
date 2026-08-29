# La edición de un torneo

Un torneo es una **marca** —«la Copa»—. Una competición es la **edición**
que se juega este año. No todas son iguales: cambia el juego, cambia
cuántos juegos dura un enfrentamiento, y a veces cambia hasta dentro de la
misma edición —los grupos a un juego, la final al mejor de cinco—.

Lo que no cambia son las reglas del torneo. Una edición **no puede abrir lo
que el torneo cerró**, y esa frase es toda la arquitectura de esta pantalla.

---

## 1. Los tres niveles

| | Qué decide | Dónde se configura |
|---|---|---|
| **El torneo** | Lo que heredan todas sus ediciones, y **qué permite** que decidan | Diseñador del torneo |
| **La edición** | Con qué se juega esta, cómo se pelea, quién entra | Esta pantalla |
| **La fase** | La excepción dentro de esta edición | Bloque 05 de esta pantalla |

Cada nivel solo puede abrir lo que el de arriba permitió. `CompetitionPhasePlan`
es quien resuelve eso, y devuelve además **de dónde viene** cada valor, que es
lo que permite que la pantalla diga «esta fase manda» en vez de repetir el
mismo número en veinte sitios sin explicar cuál gana.

### Los dos permisos nuevos del torneo

Faltaba poder decir que algo varía **dentro** de una edición. Se añadieron:

- `allow_phase_game` — cada edición puede usar un juego distinto por fase.
- `allow_phase_battle` — cada edición puede pelear distinto por fase.

Son distintos de `game_mode`. Aquel dice si el juego cambia **entre**
ediciones; estos, si puede cambiar **dentro** de una. Un torneo puede querer
lo segundo sin lo primero: «la Copa es siempre a Highest Number, pero los
grupos se juegan a otra cosa».

### Un valor dormido no es un valor borrado

Si una fase tiene guardado un juego de cuando la competición permitía
variarlo y después se cierra esa puerta, ese valor **deja de aplicarse pero
no se borra**: volver a abrirla lo devuelve tal cual. Por eso resolver esto
no es un simple `?? $padre`.

---

## 2. La pantalla

Siete bloques plegables, uno abierto cada vez. Cada uno lleva en su cabecera
un resumen de lo que hay dentro, así que plegado sigue diciendo algo: es la
única forma de que plegar no signifique esconder.

| | Bloque | Qué resuelve |
|---|---|---|
| 01 | Esta edición | Nombre, cartel, temporada — y **de qué edición parte** |
| 02 | La forma | Con qué plantilla se juega, **viendo su estructura** |
| 03 | El juego | Cuál, y si se baja la decisión a las fases |
| 04 | La batalla | Cuántos, cuántos juegos, qué decide, si cabe empate |
| 05 | Fase por fase | Las excepciones |
| 06 | Quién entra | Y por qué puerta |
| 07 | Trofeos y premios | Qué se lleva quien gane |

Crear y editar comparten el mismo diseñador. Dos formularios para la misma
entidad acaban ofreciendo cosas distintas sin que nadie lo decida.

---

## 3. Elegir la forma viendo la forma

Era un desplegable con el nombre. Un nombre no dice si hay grupos, ni cuántas
rondas, ni por dónde entra la gente: dos plantillas llamadas «Copa 2024» y
«Copa 2025» pueden no parecerse en nada.

Ahora `CompetitionTemplateBrief` arma la ficha de cada candidata: las puertas
de entrada, las fases **repartidas en las columnas en las que de verdad se
juegan** y las salidas.

Las columnas salen de las conexiones, no del orden en que se crearon. Una
lista mentiría sobre lo que pasa a la vez: en una plantilla real de 19 fases
el dibujo sale en 5 pasos, con «7 a la vez» en el primero y «9 a la vez» en
el segundo.

Es deliberadamente barato —ni analiza el flujo ni reparte competidores—
porque aquí se pintan diez plantillas de golpe.

---

## 4. Quién entra por cada puerta

Un recorrido puede tener varias entradas: «los campeones entran en
semifinales, el resto desde la primera ronda». Repartir eso era marcar
competidor por competidor en cada caja.

Dos formas, y las dos valen:

- **Con una regla** — «los que lleven doujutsu → sharingan». Se guarda con la
  edición (`start_rules`), así que la siguiente se copia sin volver a marcar
  a nadie.
- **A mano** — cuando no hay ningún atributo que los distinga.

### Quién reparte de verdad

Lo calcula el **servidor** (`CompetitionStartRouting`), no la pantalla, y al
guardar se vuelve a calcular en vez de confiar en lo que la pantalla mandó:
una pantalla abierta hace media hora puede llevar competidores que ya no
están.

Reglas del reparto:

- **Un competidor entra por una sola puerta.** Cuando dos reglas lo reclaman
  gana la primera, que es el orden en que están escritas.
- **Una regla sin condiciones es la puerta general**: todo el que quede
  libre. Es lo que hace que el caso normal —un solo inicio— no necesite
  configurar nada.
- **Sobrar no se traga en silencio.** Con 21 competidores y una puerta de 5
  plazas: entran 5, y la pantalla dice que 16 no caben.

---

## 5. Trofeos y premios

Dos capas, y no se mezclan:

| | Quién lo tiene | Se puede |
|---|---|---|
| **Del torneo** | Todas sus ediciones | verse, no tocarse |
| **De esta edición** | Solo esta | crearse, corregirse, retirarse |

Corregir uno del torneo desde una edición cambiaría también las que ya se
jugaron y ya lo entregaron. Se dice, y no se hace en silencio: intentarlo
devuelve «este trofeo es del torneo, no de esta edición».

### Premios por fase

Un premio de edición puede colgar de un nodo (`node_id`), y entonces «puesto
1» significa primero **de esa fase**, no del torneo. Un premio de torneo no
sabe decir eso, porque el torneo no sabe con qué plantilla se jugará cada
año.

### Repartirlos desde el bloque de fases

Los premios por fase también se ven y se crean desde «Fase por fase»: cada
fase dice en su línea resumida qué se lleva quien la gane, y un botón añade
uno colgado de ella. Se **rellenan** en el bloque de premios, no ahí: son los
mismos premios, y dos formularios para lo mismo acabarían discrepando.

Ese bloque ya no se apaga cuando todas las fases se juegan igual — repartir
premios por fase no depende de ningún permiso del torneo.

### Arrastrarse a la siguiente

Cada premio lleva `carry_forward`. Al copiar una edición vienen **solo los
marcados**: un premio de aniversario no debería aparecer solo al año
siguiente.

### Lo que da el torneo se ve aquí, siempre

El bloque de premios de una edición enseña **primero lo que ya da el
torneo**, y lo enseña también cuando está vacío. Antes solo aparecía si había
algo, así que al abrir la pantalla no se veía nada y parecía que lo
configurado en el torneo se hubiera perdido. Un bloque vacío que explica por
qué está vacío dice más que ningún bloque.

Cada trofeo lleva de dónde viene: «lo da el torneo», «solo en esta edición» o
«del universo». Solo los propios llevan lápiz — uno del torneo lo comparten
todas las ediciones, incluidas las ya jugadas que ya lo entregaron.

### Por qué es una tabla aparte

`tournament_instance_rewards`, y no una columna nullable en
`universe_tournament_rewards`: los del torneo los heredan todas las
ediciones, y editarlos desde una sola las cambiaría todas.

---

## 6. Cómo llega esto al motor

El motor no consulta la base de datos: **lee el estado**. `CompetitionPhasePlan`
escribe el plan de cada fase en `state.competition_plan[nodeId]` antes de cada
acción, y de ahí lo recogen los motores.

### Una rama de primer nivel, y no dentro del nodo

Esto costó una tarde. El plan empezó colgando de
`state.nodes[id].runtime.competition`, y el motor decide si una fase ya se
preparó preguntando `isset($state['nodes'][$id]['runtime'])`. Crear ese
`runtime` para colgar el plan hacía que **todas** las fases pareciesen ya
preparadas, y ninguna llegaba a empezar: la competición se quedaba en
`BLOCKED` con cero participantes en el nodo.

Ahora va en `competition_plan`, de primer nivel. Se refleja además dentro del
nodo, pero **solo si ese `runtime` ya existe**, porque `MatchSeriesRuntime`
recibe el runtime del nodo y no el estado entero. Para entonces la fase ya
arrancó, así que reflejarlo no inventa nada.

Se reescribe en **cada acción** y no solo al crear: cambiar el formato de una
fase que todavía no empezó tiene que surtir efecto.

### Marcador frente a anotaciones, funcionando

`POINTS_ONLY` no es una etiqueta. Medido sobre una edición real de 8
competidores a 3 juegos fijos:

| | Series completadas | Decididas por acumulado | Ganadas por quien perdió el marcador |
|---|---|---|---|
| `SERIES_THEN_POINTS` | 7 | 0 | 0 |
| `POINTS_ONLY` | 7 | 7 | **1** |

Esa última columna es la prueba: un competidor ganó 1 de 3 juegos y se llevó
el enfrentamiento por 12,47 a 9,26 en anotaciones.

**El empate tiene guardarraíl.** Con solo anotaciones, dos empatados en
acumulado dentro de un cuadro dejarían la ronda siguiente sin nadie a quien
colocar. Se cae al marcador —el criterio que esta edición aparcó pero no
borró— y solo si también empata se pide desempate.

**Las eliminatorias siempre exigen ganador**, diga lo que diga `allow_draws`:
un cuadro necesita que alguien pase.

---

## 7. Lo que se puede cambiar después, y cuándo

Lo que bloquea no es «estoy editando»: es **haber empezado a jugar**.

| Estado | La configuración | Quién compite |
|---|---|---|
| `DRAFT` + runtime `READY` | se cambia | **se cambia** |
| `DRAFT` pero ya arrancado | se cambia | congelado |
| `RUNNING` en adelante | congelado | congelado |

La primera fila era el error: se bloqueaban los competidores en cuanto se
entraba a editar, dijese lo que dijese el estado. Una edición en borrador que
nadie ha empezado tiene su cuadro dibujado en limpio y volver a dibujarlo no
estropea nada — que es justo lo que hace falta cuando se te olvidó meter a
alguien.

`reassign()` lo rehace desde el **mismo snapshot** —la forma no cambia—:
reconstruye el estado inicial entero, borra los participantes viejos (dejarlos
convertiría «quito a uno» en «ahora hay uno de sobra») y vuelve a proyectar.
La revisión del estado sube, así que una pestaña que tuviera la edición
abierta ve rechazada su siguiente acción en vez de jugar sobre un cuadro que
ya no existe.

**La forma sí sigue congelada** aunque esté en borrador: el cuadro se dibujó
con esa plantilla. Para otra forma, copiar.

### Elegir competidores

Al editar viene **relleno con el reparto que la edición ya tiene** —sale del
estado, que es quien sabe por qué puerta entró cada uno—; empezar de una caja
vacía obligaría a volver a marcar a los veinte.

**Tres formas de mirar**, porque no siempre se elige por lo mismo:

| | Qué enseña | Para qué |
|---|---|---|
| **Cuadritos** | solo la cara | abarcar muchos de golpe |
| **Galería** | la cara y sus atributos | reconocerlos |
| **Lista** | una línea con **todo** su catálogo | decidir por lo que uno es, no por su cara |

El tamaño se ajusta con `−` y `+`: de 4 a 20 columnas en cuadritos, de 4 a 13
en galería. La preferencia se recuerda en el navegador — es cómo miras, no un
dato de la competición.

Los atributos salen del mismo `roster()` que alimenta la galería del torneo,
que trae por cada uno **la clave** con la que casa una regla y **el texto**
con el que se lee. Antes esta pantalla construía su propia lista y se había
quedado sin los textos, así que las fichas no podían enseñar nada.

### Dos cosas que hacían dudar de si se había marcado

**La ficha saltaba.** Los ya marcados van primero, pero eso se recalculaba en
cada clic: la que acababas de tocar se iba a la cabeza y perdías de vista
dónde estabas —de la posición 7 a la 1—. Ahora el orden se **congela al abrir
la puerta**, y reordenar es un botón.

**El recuento estaba lejos.** El balance general vive arriba del bloque, y al
marcar a alguien mirabas la ficha, no la cabecera. Ahora el número de esa
puerta está en la propia barra del selector, al lado de la búsqueda.

El nombre **no se copia**. Dos ediciones llamadas igual son indistinguibles
en cualquier lista, y copiar es justamente la vía por la que eso pasaría
siempre.

---

## 8. Comprobado

| Qué | Resultado |
|---|---|
| Crear una edición con excepción de fase | La fase guarda `FIXED_GAMES 4` sobre un `BEST_OF 3` de la edición |
| Una fila de premio vacía | De 3 enviadas se guardan 2; la que no da nada se descarta sola |
| Premio colgado de una fase | «Quien acabe en el puesto 2 de «Fase #1 Grupos» recibe +3 Rango mínimo» |
| Editar una edición empezada | Redirige, no abre |
| Reparto por reglas, 21 competidores | 1 por la regla, 20 por la general, 0 repetidos, suma exacta |
| Puerta de 5 plazas con 21 candidatos | Entran 5, se avisa de 16 que no caben |
| Copiar una edición | Viene 1 de 2 premios: solo el marcado para arrastrarse |
| Editar un trofeo del universo desde una edición | Se rechaza con explicación |
| Premio plegado | 373 px → 52 px, y sus 10 campos siguen en el formulario |
| Estructura de 19 fases | 5 columnas, con 7 y 9 fases en paralelo |
| Editar un borrador sin empezar | Deja cambiar competidores; de 4 pasa a 8 y el cuadro se rehace |
| Editar un borrador ya arrancado | Bloquea los competidores, deja el resto |
| Los competidores en la galería | 21 de 21 con su imagen cargada |
| Marcar uno en el selector | 6 → 7 en los dos contadores, y la ficha **no se mueve** |
| Los tres modos a 1280 px | cuadritos 68×68 · galería 117×157 · lista 854×46 |
| El tamaño en cuadritos | de 4 a 20 columnas |
| A 375 px de ancho | 6 / 3 / 1 columnas, sin desbordar |
| «todos» y «ninguno» | 21 y 0, con sus campos en el formulario |
| Premios del torneo en la edición | Se ven, con su trofeo marcado «lo da el torneo» |
| Añadir un premio desde una fase | Nace con su `node_id` y lleva al bloque de premios |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |

---

## 9. Lo que queda fuera

- **`battle_participants` se guarda y se avisa, pero los motores no lo
  imponen.** La pantalla ya alerta si el juego elegido no admite ese número,
  pero un cuadro sigue cruzando de dos en dos aunque se pidan cuatro:
  cambiarlo es rehacer el emparejamiento de los cinco motores.
- **Los premios de edición no se reparten todavía.** Se guardan, se leen y se
  explican; `CompetitionAwardsService` solo mira los del torneo. Conectarlos
  es la continuación natural.

---

## 9. «La estructura avanzada cambió después de su última validación»

Un bloqueo que impedía jugar cualquier edición cuyo formato de batalla no
fuese el de la plantilla. Fue culpa mía.

Antes de ejecutar una fase de eliminación directa, el motor comprueba que la
**huella de la estructura** —un hash de su forma— sigue siendo la que se
validó. Esa huella incluye `series_format`, `default_best_of` y `fixed_games`.

Y esos son exactamente los tres campos que `CompetitionBattleFormat::applyTo()`
pisa en memoria al hidratar, porque **la competición decide cuántos juegos
dura un enfrentamiento**. Resultado: huella distinta, y el motor se negaba.

Medido sobre la edición que fallaba:

| | Huella |
|---|---|
| Guardada en el snapshot (con `bo1`) | `13f14b30…` |
| Recalculada al hidratar (con `bo9`) | `50e724f2…` |

Era una **falsa alarma**: cuántos juegos dura un enfrentamiento no cambia la
forma del cuadro. Las rondas, los cruces y las salidas son idénticos.

### Por qué no se quitó el formato de la huella

Habría sido lo más directo, pero cambia el hash de **todas** las estructuras
ya validadas —y una edición en curso lleva la huella vieja congelada en su
snapshot, así que revalidar la plantilla no la desbloquearía—.

En su lugar, `applyTo()` guarda el formato original en
`structure_format_before` antes de pisarlo, y la huella usa ese. Ninguna
estructura validada pierde su validez, y la competición sigue jugando con su
propio formato.

Comprobado: la huella vuelve a coincidir (`13f14b30…` = `13f14b30…`) con la
competición jugando a `bo9` y la plantilla intacta en `bo1`. La edición de 16
participantes que se quedaba en «la fase todavía no está repartida» ahora
llega hasta `COMPLETED`.

---

## 10. Los puestos en eliminación directa

Al terminar, **todos** los participantes reciben puesto: en una edición de 16,
del 1 al 16. Se ven en la pantalla final, bajo «Clasificación».

Pero no todos los puestos valen lo mismo, y la pantalla lo distingue a
propósito:

| | Cómo se decide |
|---|---|
| **1.º** | lo marcó el motor: es el superviviente |
| **2.º** | quien perdió la última batalla que ganó el campeón — exacto |
| **3.º en adelante** | por ronda alcanzada, puntos, victorias y seed |

El motor marca cada plaza como `RANKED` —se disputó— o `TIED_BAND` —un rango
sin desempatar—. En un cuadro puro, cuatro competidores pierden en la misma
ronda y **nadie jugó para separarlos**: por eso salen como «5.º–8.º» y sin
medalla, en vez de inventar un quinto puesto que nadie ganó.

### Lo que falta para decidirlos de verdad

Que el 5.º sea un puesto disputado y no un rango exige **partidos de
clasificación**: los cuatro que perdieron en cuartos jugando entre ellos. Eso
no existe todavía.

Los mecanismos para encaminarlos sí: una salida de fase admite
`RANK_POSITION` y `RANK_RANGE`, así que en cuanto haya partidos que decidan
esas plazas, el grafo ya sabe hacia dónde mandarlas.


---

## 11. «Empieza con 16 y en la fase salen 8»

Otro que parecía del motor y era de la proyección.

`tournament_instance_matches` solo tenía sitio para **dos** participantes:
`participant_a_*` y `participant_b_*`. Bastaba mientras todo fuese un duelo.
No lo es: una fase puede cruzar **de cuatro en cuatro**, y entonces de cada
encuentro llegaban los dos primeros y los otros dos se perdían.

De ahí el 8: cuatro encuentros de round 1 × dos visibles.

El motor nunca se equivocó — 20 huecos, 16 ocupados, 5 encuentros — y su
`participant_ids` traía los cuatro. Lo que se quedaba corto era la tabla que
lee la pantalla.

### Qué se cambió

- **`participants`** (json) en `tournament_instance_matches`: la lista
  completa con clave, nombre, entidad, si pasó y si cayó, en el orden en que
  el motor los colocó.
- Las columnas `A`/`B` se quedan: media aplicación las usa y para un duelo
  dicen lo mismo.
- La ficha de batalla reparte el ancho entre los que hay —dos son mitades,
  cuatro son cuartos— en vez del `w-1/2` fijo que tenía.
- Las caras de los participantes 3 y 4 salen de la lista de participantes de
  la competición, que la pantalla ya tiene cargada: el modelo solo tiene
  accesor de entidad para A y B.

Comprobado: los 16 nombres aparecen, 4 fichas con 4 lados cada una, 16
imágenes, sin desbordar.

---

## 12. Los puestos, jugados de verdad

La misma edición, jugada hasta el final, reparte los 16 puestos. Pero el
motor dice **cómo** decidió cada uno:

| Plaza | Cómo |
|---|---|
| 1.º | `RANKED` — es el superviviente |
| 2.º–4.º | `TIED_BAND` |
| 5.º–16.º | `TIED_BAND` |

Y tiene razón. Con cuatro por enfrentamiento y un clasificado, **tres pierden
la final a la vez**: nadie jugó para separar al 2.º del 4.º. Igual con los
doce que cayeron en la primera ronda.

Los números 1..16 existen y se ven —se ordenan por ronda alcanzada, puntos,
victorias y seed—, pero solo el 1.º está disputado. La pantalla final lo
distingue: medalla para lo disputado, rango para lo demás.

**Para que el 3.º y el 5.º sean puestos de verdad hacen falta partidos de
clasificación** entre los que cayeron en la misma ronda. Eso sigue sin
existir; los mecanismos para encaminarlos —salidas `RANK_POSITION` y
`RANK_RANGE`— sí.


---


---

## 13. Los puestos se juegan

Una fase podía configurar salidas «#3 lugar», «#7», «#13». Se guardaban, y al
jugar no pasaba nada: el cuadro arrancaba idéntico a uno sin puestos
configurados y no había forma de distinguir «todavía no toca» de «no se aplicó
nada».

Había dos motivos, y ninguno era el que parecía.

### Nadie alimentaba esas salidas

`RANK_POSITION` y `RANK_RANGE` los resolvía únicamente el motor de **liga**. En
eliminación directa se creaban, se guardaban y nunca se leían.

### Y aunque se leyeran, el puesto no existía

Un cuadro de 16 reparte plazas así: 1.º, 2.º, dos empatados en 3.º–4.º, cuatro
en 5.º–8.º y ocho en 9.º–16.º. Esas bandas no son un fallo: **es que nadie jugó
para separarlas**. Preguntarle al cuadro quién es tercero no tiene respuesta.

Antes eso terminaba en un error seco —«la posición 3 pertenece a una banda
empatada 3–4»— al cerrar la fase.

## Lo que ahora ocurre

Cuando el cuadro termina, el motor **genera las batallas que faltan**. Es el
mismo mecanismo que usa cualquier torneo real para el tercer puesto, aplicado
tantas veces como haga falta: se emparejan los empatados, quien gana se queda
la mitad alta de la banda y quien pierde la baja. Repitiendo, la banda se parte
hasta que el puesto pedido cae en un borde.

Pidiendo #3, #7 y #13 sobre 16 competidores salen **11 batallas** en tres olas:

| Ola | Bandas | Batallas |
|---|---|---|
| 1 | 3–4, 5–8, 9–16 | 1 + 2 + 4 |
| 2 | 7–8, 13–16 | 1 + 2 |
| 3 | 13–14 | 1 |

### Solo se parte lo que hace falta

La banda 9.º–12.º no se toca: nadie pidió distinguir dentro de ella, y jugar
esas batallas sería hacer competir a la gente para nada. Lo mismo con 5.º–6.º y
15.º–16.º, que quedan empatadas y así se muestran.

### Un desempate no elimina a nadie

Quien lo juega ya cayó del cuadro; solo está decidiendo en qué orden queda.
Anotarlo como eliminación haría saltar el control de doble eliminación —con
razón— y movería la clasificación base sobre la que se apoyan las propias
bandas. Con número impar de empatados, uno pasa arriba sin jugar: el mismo BYE
de siempre, preferible a inventarle un rival.

## La pantalla

- **Se anuncia desde el minuto cero.** Una banda sobre la fase dice qué puestos
  pediste y que se disputarán al terminar el cuadro. Sin eso, la fase arranca
  igual que una sin puestos y parece que la configuración se perdió.
- **Los desempates van fuera del cuadro**, en su propia sección «Definición de
  puestos». Un cuadro es un embudo —cada ronda tiene la mitad de gente que la
  anterior— y cuatro batallas para separar 9.º–16.º rompen esa forma. Se
  reconocen por su `group_label`, que en eliminación directa solo llevan ellas.
- **Tamaño ajustable** —Compacto / Normal / Grande—, porque un cuadro de 16 con
  desempates ocupa diez columnas. Se recuerda por competición.

## Un choque que esto destapó

Con los puestos ya servidos, la fase se negaba a cerrar: «las Phase Exits
intentan consumir al mismo participante más de una vez». Y era verdad —el
tercero perdió, luego también está en «Eliminados»—.

`REMAINING`, `ALL` y `ELIMINATED` no describen un puesto, describen un **resto**.
Ahora se evalúan al final y sobre quien no haya salido ya por otra puerta.
«Eliminados» sigue significando lo mismo; lo único que cambia es que ya no
reclama a quien salió por la del tercer puesto. `SURVIVORS` se queda fuera a
propósito: es la salida con la que una fase alimenta a la siguiente, y
convertirla en un resto podría vaciar en silencio un torneo que hoy funciona.

## Una sola implementación

`PlacementPlanner` tiene la aritmética —dónde cortar, quién queda en qué banda,
cómo se emparejan— y no sabe qué es un encuentro. Los dos motores de eliminación
directa la usan: el clásico genera rondas y emparejamientos, el de grafo genera
encuentros y slots. Tenerlo escrito dos veces garantizaba que un día dijeran
cosas distintas.

## Las competiciones ya empezadas

`placement_wanted` lo escribe el motor al preparar la fase, así que una
competición iniciada antes de esto no lo tendría. `CompetitionPhasePlan` lo
rellena leyendo el **snapshot** —nunca la plantilla viva, que cambiaría la forma
de una partida en curso—, de modo que no hay que empezar de cero.

## Comprobado

Sobre la competición real de 16 competidores, jugada entera:

| Qué | Resultado |
|---|---|
| Batallas proyectadas | 26 — 15 del cuadro + 11 de puestos |
| Rondas de desempate | 6, todas con su `group_label` |
| Puestos individuales | 3.º, 4.º, 7.º, 8.º, 13.º, 14.º **decididos jugando** |
| Bandas que nadie pidió | 5.º–6.º, 9.º–12.º, 15.º–16.º intactas |
| Salidas | #3, #7, #13 servidas; «Eliminados» con los 12 restantes |
| Competición ya en marcha | recupera sus puestos sin reiniciarse |
| Página de juego | 200, con aviso, sección de puestos y selector de tamaño |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |

## Lo que sigue faltando

Los desempates existen solo en **eliminación directa**. Liga, grupos y suizo
resuelven `RANK_POSITION` con su propia clasificación —donde los puntos ya
separan— pero no generan batallas si quedan empates de verdad.


---

## 14. Los premios deciden qué puestos se juegan

Un puesto se disputa porque alguien lo pide. Hasta ahora solo sabía pedirlo una
**salida** de la fase. Ahora también un **premio por posición** — y era el que
faltaba: un premio que entrega algo al 5.º está diciendo que el 5.º tiene que
existir, y sin jugarlo o no se entregaba, o se le daba a uno de los cuatro
empatados elegido a dedo.

Cuentan las dos procedencias:

| De dónde | A qué fase se le pide |
|---|---|
| Salida de la fase | esa fase |
| Premio del **torneo** | las fases finales |
| Premio de la **edición**, sin fase | las fases finales |
| Premio de la **edición**, atado a una fase | esa fase |

Una fase es final cuando sus salidas no alimentan a otra. Un puesto de premio es
un puesto **final** del torneo, y una fase solo puede decidir entre los suyos;
en un torneo de una fase —lo habitual— es esa misma.

Un mismo puesto pedido por una salida y por un premio se juega **una vez**, y la
pantalla dice los dos motivos: «Puesto 3.º · por la salida «#3 lugar» y por el
premio del torneo «#3 puesto»». Sin eso, ver batallas que nadie configuró a mano
desconcierta: sí se configuraron, solo que desde la pestaña de premios.

Se recalcula en **cada acción** y al abrir la pantalla, no solo al preparar la
fase. Añadir un premio con la competición ya en marcha se nota cuando la fase
llegue ahí. Una fase que ya cerró no vuelve a mirarlo: el premio llegó tarde.

## Tres cosas que esto obligó a arreglar

### 1. Una batalla de puestos no es una ronda del cuadro

`round_reached` —hasta dónde llegó cada uno— se calculaba con el número de ronda
más alto en el que aparecía. Los desempates van después de la final, así que
quien disputó el 13.º figuraba como si hubiera llegado **más lejos que el
campeón**. Y de ese orden cuelgan los premios por posición.

Se marca en la fila (`is_placement`) y se excluye del cálculo. No se deduce de
la etiqueta de grupo, que ya significa otra cosa en fase de grupos.

### 2. El orden final ignoraba lo jugado

`TournamentPlacementResolver` ordenaba por profundidad, puntos, victorias y
seed. Sería absurdo hacer jugar a dos por el 7.º puesto y después repartir el
premio por número de victorias.

Ahora, justo después de la profundidad, manda **el puesto que se ganó en la
última fase**. Cuando ese desempate no se jugó, la fase deja a los dos en la
misma posición, las claves empatan y se sigue como siempre: se afina donde hay
un hecho y no se inventa nada donde no lo hay. Solo la última fase, porque una
posición de grupos y una del cuadro no son comparables.

### 3. Los premios de la edición no se repartían

Se podían configurar desde hacía tiempo y no entregaban nada: el reparto solo
leía los del torneo. Hacer que un premio de edición fuerce una batalla y después
no entregar nada habría sido una trampa.

Lo que lo impedía era el registro de cambios de stat, que solo sabía apuntar a
un premio de torneo. Ahora tiene la columna hermana y el índice de idempotencia
mira las dos, así que dos premios distintos que compartan número no se
confunden. Los trofeos no referencian la regla, así que funcionaban ya.

## Comprobado

Sobre la competición real —16 competidores, premios de torneo al 1.º, 2.º, 3.º,
7.º y 13.º, y premios de edición al 4.º y al 5.º— jugada en memoria:

| Qué | Resultado |
|---|---|
| Puestos a decidir | 1, 2, 3, 4, 5, 7, 13 — con su motivo cada uno |
| Batallas de puestos | 12, en 7 rondas de desempate |
| Los siete puestos pedidos | **decididos jugando**, ninguno adjudicado a dedo |
| Bandas que nadie pidió | 9.º–12.º y 15.º–16.º intactas |
| Pantalla | anuncia cada puesto y por qué se juega |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |
