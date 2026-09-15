# Resumen — el puesto de mando del universo

## 1. Qué había

Una pantalla clara y correcta: portada del mundo, cuatro cifras, las
competiciones en juego, el top 5 de la clasificación, los últimos campeones, lo
que toca jugar y el registro de actividad.

Le faltaba lo único que convierte un resumen en un puesto de mando: **saber qué
espera por ti**. El universo ya guardaba que una competición se había quedado
bloqueada, que otra terminó sin repartir sus premios y que un torneo llevaba
meses definido sin jugarse nunca — y nada de eso llegaba aquí. Había que entrar
panel por panel a descubrirlo.

Y estaba en claro, cuando el resto del workspace del universo ya es oscuro.

## 2. Lo que espera por ti

Lo primero después de la portada. Siete comprobaciones, cada una sobre datos que
ya existían:

| Aviso | De dónde sale | Adónde lleva |
|---|---|---|
| Competiciones paradas | `runtime_status` en `BLOCKED` o `AWAITING_DECISION` | Competiciones |
| Listas y sin empezar | `status = DRAFT` | Competiciones, **ya filtradas** |
| Terminaron sin repartir premios | `COMPLETED` + `rewards_processed_at` nulo | Se resuelve aquí mismo |
| Ninguna temporada en marcha | no hay temporada activa | Temporadas |
| El mundo no tiene tiempo | no hay ninguna temporada | Temporadas |
| Torneos nunca jugados | torneo sin ninguna edición | Torneos |
| Vitrina hecha y vacía | trofeos creados, cero entregas | Trofeos |
| Competidores que no han jugado nunca | entidades sin participaciones | El mapa, ya repartido por «¿ha competido?» |

Las dos reglas que sigue este bloque:

**Cada punto lleva a donde se resuelve.** Un aviso que no se puede atender desde
el aviso no es un aviso, es una queja. Por eso el de las competiciones
preparadas no abre el panel entero: lo abre ya filtrado a `DRAFT`.

**Lo que frena el juego va primero y se marca.** Una competición bloqueada lleva
una etiqueta roja —«frena el juego»— y sube al principio de la lista. Un torneo
sin estrenar es una sugerencia; una competición parada es un atasco.

Cuando no hay nada pendiente, el bloque no desaparece: dice que el mundo está al
día, y enumera qué se ha comprobado.

### Los premios se reparten desde aquí

La acción de reprocesar recompensas ya existía —`RewardProcessor`, idempotente,
con su ruta— y vivía escondida dentro de la ficha de cada edición. Ahora el
aviso trae su propio botón por competición, con un paso de confirmación: aunque
el procesador solo aplique lo que falte, concede trofeos de verdad.

### El aviso que hubo que corregir

Al probar el botón —dentro de una transacción que se deshizo— el procesador
devolvió `applied: 0, trophies: 0`: aquel torneo **no tenía ningún premio
configurado**. El aviso decía «hay campeón, pero sus trofeos y bonus no se han
concedido», y era falso: no había nada que conceder. Pulsar el botón solo habría
marcado la edición como repartida.

Así que el aviso ahora exige que el torneo o la edición **tengan premios
definidos**. En el universo 6 eso bajó los avisos de tres a dos, y los dos que
quedan son ciertos.

## 3. El resto de la pantalla

**El pulso** — seis cifras, cada una con un pie que la explica y un enlace a su
panel: competidores (con cuántos han competido de verdad), temporadas, torneos,
competiciones, en juego, trofeos dados.

**En juego ahora** — cada competición viva con su **estado de recorrido**, no
solo el administrativo. «En curso» y «Bloqueada» son las dos cosas a la vez, y
la segunda es la que importa: se dice «está parada: no avanzará sola».

**La temporada que corre** — lo que esta pantalla no sabía decir: si la
temporada va por la mitad o está sin empezar. Sale de cruzar la recurrencia de
cada torneo —«cada dos temporadas», «solo en la primera»— con las ediciones ya
creadas, y lo dibuja como una barra de avance. Debajo, los torneos que le tocan
y todavía no se han creado, cada uno con su botón de «crear edición».

**El mundo, temporada a temporada** — una columna por temporada con la altura de
lo que se jugó en ella, y la parte clara de la barra es lo que sigue vivo. No
hace ninguna consulta nueva: son las mismas competiciones ya cargadas, contadas
por temporada. Sirve para lo que ninguna cifra dice: si el mundo está vivo o
tuvo un arranque fuerte y se paró.

**Qué ha pasado** — el registro de actividad con filtro por tipo que se aplica
en el sitio. Son doce líneas: no hace falta volver al servidor para esconder
unas cuantas. El filtro solo ofrece los tipos que de verdad aparecen.

**Quién manda** — top 5 con barras comparadas con el líder.

**Los últimos en ganar** — agrupados por competición, con el mismo criterio que
la Clasificación: un torneo que se queda en fase de grupos corona a todos los
que clasifican, así que pedir cuatro ganadores sueltos devolvía cuatro caras de
una sola edición.

**Con qué se juega** — qué motor ha resuelto cada competición. No es un detalle
técnico: un universo que solo usa Rounded Number es un mundo distinto de uno que
reparte entre varios. Las competiciones antiguas sin motor guardado salen como
«Sin juego», diciendo que no consta con qué se resolvieron.

**El mapa del mundo** — una tira de caras que lleva a Explorar.

## 4. Una trampa: el calendario salía del revés

La línea del tiempo aparecía con la temporada 6 a la izquierda y la 1 a la
derecha, pese a pedir `->orderBy('number')`.

La causa: la relación `Universe::seasons()` ya trae su propio
`orderByDesc('number')`, y `orderBy` **añade** una cláusula en vez de
sustituirla. La consulta salía como `ORDER BY number DESC, number ASC`, y la
primera gana. La salida es `reorder('number')`, que limpia el orden anterior
antes de poner el nuevo.

Merece la pena recordarlo: cualquier relación de este proyecto puede traer un
orden por defecto, y encadenarle otro no lo cambia.

## 5. Lo que se comprobó

- Las tres portadas devuelven 200, incluida la del universo vacío, y las diez
  rutas de cada universo siguen a 200 después de tocar el layout.
- Los avisos salen de datos reales: en el universo 7, una competición bloqueada
  y una preparada; en el 6, una preparada y la vitrina vacía.
- Los formularios de repartir premios apuntan a
  `/universes/6/tournaments/4/editions/20/reprocess` con método `PUT` y token
  CSRF, y el primer clic abre la confirmación en vez de enviar.
- El procesador de recompensas se ejecutó **dentro de una transacción que se
  deshizo**: marcó la edición como repartida, no concedió nada (porque el torneo
  no tiene premios), y al revertir volvieron a quedar las dos ediciones sin
  repartir y cero entregas de trofeo, exactamente como estaban.
- El filtro del historial pasa de 12 líneas a 4 al dejar solo «Terminan», a 7 al
  dejar «Empiezan», y vuelve a 12 al quitarlo.
- La línea del tiempo sale ordenada T1 → T6, con T1 alta (16 competiciones, la
  franja verde son las que siguen vivas) y T2 marcada «ahora».
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

El bloque de avisos recorre las competiciones ya cargadas en memoria, que es lo
correcto con diecisiete. Un universo con miles de ediciones querría estas
comprobaciones como consultas contadas, no como filtros sobre una colección.
