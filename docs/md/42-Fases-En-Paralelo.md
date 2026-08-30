# Dos fases a la vez

La arena siempre trató una competición como una fila: fase 1, fase 2, fase 3.
Con eso bastaba mientras los torneos fueran una fila. Un recorrido con dos ligas
arrancando a la vez no lo es, y se notaba en tres sitios distintos.

## 1. Solo se veía una fase — y no era la pantalla

El síntoma: «TVT 1» aparecía con **0 batallas** y «TVT 2» con 45. Parecía que el
motor solo abría una.

No era eso. El motor tenía **45 batallas en cada una**. Lo que fallaba estaba en
la proyección: cada motor numera sus enfrentamientos con su propia cuenta, así
que la primera jornada de una liga es `RR-R1-M1` **venga de la fase que venga**.
Las dos fases generaban los mismos 45 nombres, y la tabla los consideraba únicos
por competición:

```
tmatch_instance_match_unique (tournament_instance_id, runtime_match_id)
```

Las 45 batallas de la segunda fase **pisaban** a las de la primera. Quedaban 45
filas en vez de 90, todas apuntando a un solo nodo, y una fase entera se veía
vacía porque literalmente no existía.

La fase pasa a formar parte de la identidad —índice `(competición, fase,
enfrentamiento)`—. El identificador sigue siendo el del motor, que es lo que el
motor entiende; ninguna fila existente se toca, porque añadir una columna a un
índice único solo lo hace más permisivo.

Y con ello, la clave que viaja a la pantalla pasa a ser **`94:RR-R1-M1`**: sin
la fase delante, pulsar una batalla podía abrir la de la fase de al lado.

## 2. Solo se preparaba una fase por acción

`advanceToPlayable` paraba en cuanto encontraba **una** batalla jugable. Con dos
fases arrancando a la vez, preparaba la primera y dejaba la segunda en la cola,
sin un solo enfrentamiento, hasta que otra acción moviera esa cola.

Ahora para cuando hay batalla jugable **y** la cola está vacía. Drenarla no
juega nada: solo reparte participantes y prepara las fases que ya pueden
empezar, que es justo lo que hace falta para que las paralelas existan a la vez.

## 3. Al acabar una fase, saltaba a la otra

Terminar la última batalla de una fase metía directamente en una batalla de la
otra. El grafo avanzaba, encontraba algo jugable, y lo abría; desde dentro
parecía que la batalla se cambiaba sola.

Continuar dentro de la **misma** fase sí es lo pedido —es la serie que se estaba
jugando—. Cambiar de fase es una decisión del usuario, y se toma desde la
estructura, donde se ve el recorrido entero. Ahora, si lo siguiente jugable está
en otra fase, el motor no lo abre.

## La pantalla, con la forma real

`CompetitionPhaseGraph` lee del snapshot qué fases van a la vez y cuáles esperan
a cuáles: nivel, de quién depende cada una, a quién alimenta, y si su entrada
exige que **todas** las anteriores terminen (`WAIT_ALL`) o le basta con que
llegue alguien.

- **La barra agrupa por nivel.** Las que se juegan a la vez van juntas bajo un
  «⇉ 2 fases a la vez», y la flecha solo separa niveles —que es donde sí hay un
  «después»—. Antes había una flecha entre todas, diciendo algo falso.
- **Cada fase enseña sus hermanas**, con su estado y su progreso, y se salta a
  ellas de un clic. Saber que hay otra fase abierta es lo que permite ir a ella
  sin creer que hay que terminar esta primero.
- **Lo que viene después sale del recorrido, no de la lista.** Antes era
  `$phaseTabs[$index + 1]`: con dos fases en paralelo eso señalaba a la hermana
  —que no va después, va al lado— y ofrecía abrirla como si esta la alimentara.
- **Y si falta una fase, se dice cuál.** «*GRPOS 12-8* se alimenta de 2 fases y
  espera a que todas terminen. Falta que termine: **TVT 2** →», con botón para
  ir a ella.
- **«Se jugó todo» solo si de verdad se jugó todo.** Sin esa comprobación, la
  primera fase que terminaba ya ofrecía cerrar la competición y repartir los
  premios con la hermana a medias.

## Comprobado

Sobre la competición real de 4 fases —TVT 1 y TVT 2 en paralelo, luego grupos,
luego eliminación:

| Qué | Resultado |
|---|---|
| Forma leída del grafo | nivel 1: TVT 1 + TVT 2 · nivel 2: grupos · nivel 3: elim |
| Reproyección (en transacción deshecha) | **90 filas**: 45 por fase, antes 45 en total |
| Clave de batalla en pantalla | `95:RR-R1-M1`, y se parte bien en fase + enfrentamiento |
| La barra | «⇉ 2 fases a la vez» sobre el grupo paralelo |
| Cada fase | enseña su hermana con estado y progreso |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |

## Lo que no pude comprobar

El cambio de pestaña entre fases se verificó a nivel de datos —pulsar mueve el
índice— pero no visualmente: la captura estática que uso para inspeccionar deja
los `x-show` de esta pantalla congelados, y le pasa igual a las pestañas de
etapa que existían desde antes y que no toqué. Es del entorno de captura, no del
código, pero conviene mirarlo en la aplicación de verdad.

---

# Los grupos que el recorrido ya había repartido

Al terminar las dos ligas paralelas, la fase de grupos pedía un reparto manual:

> El recorrido está esperando tu decisión en GRPOS 12 - 8.

Y el reparto ya estaba hecho. El usuario lo había dibujado al conectar las
fases: de cada liga salían 6, tres por una puerta de entrada y tres por otra.
Cuatro puertas, cuatro grupos, tres en cada uno.

## Por qué preguntaba

El grafo guarda **quién llegó por cada puerta** —`entry_ports[*].participant_ids`—
pero justo antes de llamar al motor los aplanaba en una sola lista:

```php
$participantIds = collect($node['entry_ports'])
    ->flatMap(fn($port) => $port['participant_ids'])
```

Ahí se perdía el reparto. El motor recibía doce competidores en montón, veía
`distribution_mode = MANUAL`, y hacía lo único que podía hacer: preguntar.

## Lo que ahora ocurre

La lista por puertas viaja junto a la lista aplanada. Si la fase tiene tantas
puertas con gente como grupos, y en cada puerta llegaron justo los que caben en
su grupo, **el reparto está hecho** y no se pregunta nada.

La correspondencia es por **orden**: la primera puerta llena el primer grupo, la
segunda el segundo. Es lo que se ve al dibujarlo, y no hay ninguna otra pista en
el modelo — una puerta de entrada no sabe a qué grupo va.

Solo se aplica cuando encaja **exacto**. En cuanto algo no cuadra —una puerta
con cuatro y otra con dos— se vuelve a preguntar, porque colocar a alguien en un
grupo por aproximación es peor que preguntar.

Y se dice en pantalla, sobre la fase: **«⇥ repartido por el recorrido · Grupo A
← Entrada X · Grupo B ← Entrada Y…»**. Callarlo dejaría sin explicar por qué
unos acabaron en un grupo y otros en otro.

## Comprobado

Sobre la fase real «BBB Grupos» —4 grupos, 4 puertas, `MANUAL`— con los 12 que
salen de las dos ligas:

| Reparto que llega | Resultado |
|---|---|
| 3 · 3 · 3 · 3 | **sin preguntar**: 4 grupos llenos y 12 jornadas |
| 4 · 2 · 3 · 3 | sigue preguntando |
| sin información de puertas | sigue preguntando, como antes |

Los cuatro grupos quedaron exactamente con los de su puerta.

---

# La clasificación tenía dos verdades

En TVT 1, por encima de la línea de corte había alguien marcado **Eliminado**, y
por debajo alguien marcado **Clasificado**. Ninguna de las dos etiquetas estaba
mal. Lo que estaba mal era el orden en el que salían las filas.

## Dos criterios distintos en la misma tabla

La **etiqueta** lee al motor: `status = ADVANCED` o `ELIMINATED`, que es quien de
verdad pasa a la fase siguiente.

El **orden** lo calculaba la propia pantalla: puntos y, en caso de empate, la
diferencia de las columnas *A favor / En contra*.

Y esos números son otra cosa. `PhasePointsService` los suma de `game_encounters`
para enseñar el rendimiento bruto al lado de la tabla —su propia documentación
lo dice: «esto se muestra al lado como lo que es»—. No son los que el motor usa
para clasificar.

En TVT 1 la diferencia de la pantalla decía +6 / +4 / +3 y la del motor 22 / 10 /
3. Con los mismos puntos, cada una ordenaba distinto:

| | Motor | Pantalla |
|---|---|---|
| 2.º | Hiruzen Sarutobi | Kakashi Hatake |
| 5.º | Kakashi Hatake | Ishiki |
| 6.º | Iruka Umino ✔ | *fuera del corte* |

Con el corte en 6, alguien que el motor había clasificado caía por debajo de la
línea y alguien eliminado subía por encima.

## Ahora manda el puesto de la fase

El motor deja su veredicto en `position`, y es ese el que ordena. Orden, línea de
corte y etiquetas salen de la misma fuente, así que ya no pueden contradecirse.
La ordenación propia de la pantalla queda solo como respaldo para una fase recién
abierta, donde todavía no hay puestos —y donde nadie está clasificado ni
eliminado, así que da igual—.

Las columnas *A favor / En contra / DIF* se quedan: son información útil. Pero el
pie ya no dice que desempaten, porque no lo hacen. Ahora dice lo que pasa: que el
orden lo decide la fase y que estos números se enseñan al lado.

Mismo arreglo en la tabla de cada grupo, que tenía exactamente el mismo doble
criterio.

## Comprobado

| Puesto | Antes | Ahora |
|---|---|---|
| 2.º | Kakashi Hatake | Hiruzen Sarutobi |
| 6.º | — | Iruka Umino ▲ Clasificado |
| 7.º | Iruka Umino ▲ Clasificado | Ino Yamanaka ▼ Eliminado |

Los seis por encima del corte salen **Clasificado** y los cuatro por debajo,
**Eliminado**. Suite de tests: 88 pasan / 99 fallan, igual que antes.

## Y las cifras que se enseñaban no eran las que ordenan

Corregido el orden, la tabla seguía desconcertando: el 6.º tenía peor diferencia
que el 8.º, y a alguien le salía «—» en A favor y En contra.

Las columnas venían de `PhasePointsService`, que suma `game_encounters` — solo
los enfrentamientos que pasaron por el motor de juego—. En esa fase hay **45
batallas y 18 encuentros registrados**, y 9 de 10 competidores con datos. Era una
suma parcial presentada como si fuera el total.

La clasificación de la fase ya trae `score_for`, `score_against` y
`score_difference`, calculados sobre todas las batallas, y son los que el motor
usa para desempatar. Así que son los que se enseñan.

| | Antes (parcial) | Ahora (lo que ordena) |
|---|---|---|
| Iruka Umino, 6.º | 12 : 19 · −7 | 58 : 60 · **−2** |
| Ino Yamanaka, 7.º | 5 : 7 · −2 | 38 : 42 · **−4** |
| Chōji Akimichi, 8.º | 4 : 4 · +0 | 46 : 53 · **−7** |
| Ebisu, 9.º | — | 35 : 42 · −7 |

Con las cifras completas, el orden es exactamente el que dicta la regla de la
fase —puntos, diferencia, anotados a favor— y la tabla ya explica su propio
orden en vez de contradecirlo.
