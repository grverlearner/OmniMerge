# SUPER EDICIÓN DE TORNEOS

Una sola pantalla completa para el recorrido entero de un torneo: por dónde
entra la gente, qué fases juega, cómo se conectan y dónde acaba cada uno.

---

## 1. Por qué existe

Una fase ya se podía editar entera en una pantalla. Un torneo no: había que
ir al constructor de grafos, colocar cajas en un lienzo, y para saber qué
significaba cada caja abrir la fase en otra pestaña. El grafo se veía, pero
no se **entendía**.

Esta pantalla contesta tres preguntas que el constructor no contestaba:

| Pregunta | Dónde se contesta |
|---|---|
| ¿Cómo es este torneo de principio a fin? | El mapa |
| ¿Qué le pasa a quien llega a esta fase? | El recorrido |
| ¿Qué se lleva quien acaba aquí? | Los premios de cada final |

---

## 2. Lo que NO se ha escrito

Casi todo el dominio ya existía, y esa es la parte importante del diseño.

`TournamentGraphPayloadService` ya armaba el grafo completo —nodos, inicios,
terminales, conexiones, niveles, bifurcaciones, convergencias, validación
estructural y de flujo, y hasta la URL del CRUD de cada pieza—. Y todos los
controladores del grafo responden con `back()`.

Consecuencia: **los formularios de esta pantalla apuntan a las rutas de
siempre y funcionan sin que se haya tocado ni un controlador del grafo.**
Crear una fase, un inicio, un terminal o una ruta desde aquí es exactamente
la misma operación que desde el constructor, y vuelve aquí sola.

Lo único nuevo del servidor es `TournamentSuperEditorService`, que añade
cuatro cosas que el constructor no necesitaba:

| Añade | Para qué |
|---|---|
| `map` | El grafo repartido en columnas, de izquierda a derecha |
| `links` | Las conexiones aplanadas a «de esta llave a esta otra» |
| `neighbours` | Quién está antes y quién después de cada pieza |
| `outlines` | La forma de cada fase, para reconocerla dentro del mapa |

---

## 3. Las llaves

Una conexión del modelo sale de un inicio **o** de un nodo, y llega a una
puerta de entrada **o** a un terminal: cuatro columnas distintas. Para
dibujar un recorrido eso es incómodo, porque lo único que importa es de qué
pieza sale y a qué pieza llega.

Se aplana a dos llaves de texto:

```
START:3  →  NODE:7  →  TERMINAL:4
```

Con llaves, «de qué a qué» es una sola pregunta en vez de cuatro, y el
cliente puede pintar, agrupar y buscar vecinos sin volver a mirar de qué
tipo era cada extremo.

Las puertas de entrada se traducen a su nodo al calcular vecinos: al
recorrido le importa que se viene de la fase A, no por cuál de sus tres
puertas.

---

## 4. La silueta de una fase

`outline()` es la costura entre un torneo y sus fases. Devuelve cuatro
números —qué clase de forma, su etiqueta, sus columnas y sus huecos— y con
eso se dibuja algo reconocible:

| Motor | Se dibuja como |
|---|---|
| Eliminación directa | Un embudo: barras que van menguando |
| Fase de grupos | Cajas, una por grupo |
| Liga | Filas apiladas, como una tabla |

Es deliberadamente pobre: no reparte competidores, no calcula
emparejamientos y no toca el reparto prestado. **Enseñar la forma de cinco
fases en un mapa no puede costar cinco estructuras completas**, que es lo
que habría costado reutilizando `payload()`.

Los motores que todavía no tienen Super Edición —Swiss, League— no traen
esquema, y entonces no se dibuja nada. Una caja vacía dice la verdad; una
forma prestada, no.

---

## 5. Las dos vistas

### El mapa

El torneo entero, en columnas: **Entran → nivel 1 → nivel 2 → … → Salen**.

Las columnas del medio son los NIVELES del grafo, no las fases una a una:
dos fases que se juegan a la vez comparten columna y color, y eso es
exactamente lo que hay que ver de un vistazo. El nivel lo calcula el
análisis de flujo, no la vista.

Dos símbolos dicen la forma del camino:

| Símbolo | Significa |
|---|---|
| ⑃ | De aquí sale gente hacia varios sitios — el camino se abre |
| ⑂ | Aquí llega gente de varios sitios — el camino se junta |

Las fases que no alcanza nadie salen en una columna aparte, «Sin conectar»,
en vez de desaparecer justo cuando más falta hace verlas.

### El recorrido

Una fase en el centro, lo que viene antes a la izquierda y lo que viene
después a la derecha. Si vienen de varios sitios se ven todos apilados, y si
reparte a varios se ven todos: es justo el caso donde una lista plana deja
de explicar nada.

Entre columna y columna van las rutas, y una ruta no es una flecha suelta:
dice por qué salida se va, a qué entrada llega y cuántos pasan. Es la única
forma de ver que «Ganador → Entrada general, todos» y «Eliminados → De baja,
los que sobren» son cosas distintas.

La fase del centro enseña además sus puertas abiertas, y **marca en ámbar
las salidas que no llevan a ningún sitio**: una salida sin ruta es una
plaza que nadie recoge.

---

## 5bis. El taller

La tercera vista. Es el recorrido con un botón en cada cosa.

El recorrido enseña qué le pasa a la gente que llega a una fase. El taller lo
cambia, sin salir de la pantalla y sin volver al panel: cada puerta trae sus
rutas debajo, y cada ruta se retoca o se borra donde está.

El reparto es el mismo —entradas a la izquierda, la fase en el centro,
salidas a la derecha— a propósito: quien ya sabe leer una vista sabe usar la
otra. Lo único que cambia es que aquí todo se puede tocar.

Lo que se hace sin irse a ningún sitio:

| Acción | Dónde |
|---|---|
| Conectar una salida a donde sea | En la salida, con un extremo ya puesto |
| Conectar algo a una puerta | En la puerta, igual |
| Cambiar cuántos pasan y en qué orden | En la propia fila de la ruta |
| Borrar una ruta | La × de su fila |
| Crear, editar y borrar puertas de entrada | Columna izquierda |
| Renombrar la fase dentro del torneo | En el centro |

Arriba, en rojo y ámbar, lo que está suelto: **una salida sin ruta deja gente
en el limbo** y **una puerta sin ruta deja una fase que nadie alimenta**. Son
los dos errores fáciles de cometer y difíciles de ver, y por eso se cuentan
antes de nada.

### Un extremo ya viene puesto

El formulario del panel pregunta los dos extremos de una ruta desde cero.
Aquí sobra la mitad: se conecta *desde esta salida* o *hacia esta puerta*, y
esa mitad ya tiene respuesta antes de abrir el formulario. El otro extremo se
elige de una lista de piezas que existen, con lo que no hay forma de escribir
una ruta imposible.

### Cuántos pasan y en qué orden

El formulario rápido pedía el reparto pero no servía para lo que más se usa:
**repartir una entrada entre varias fases**.

Dos motivos, y ninguno era el que parecía:

- **El número estaba, pero muerto.** Con «todos» seleccionado se veía una
  caja gris rotulada «Valor», que no dice para qué es. Ahora aparece solo
  cuando el reparto lo usa, y con la etiqueta de lo que significa en cada
  caso: «cuántos exactamente» no es lo mismo que «qué porcentaje». Debajo,
  una frase que explica el modo elegido.

- **La prioridad no estaba.** Iba fija en 10, en un campo oculto. Importa
  justo en este caso: cuando varias rutas se reparten la misma gente, se
  sirve antes la del número más bajo, y con eso se decide quién se queda con
  los mejores. Ahora es un campo, y cada ruta enseña su `#prioridad` cuando
  hay más de una compitiendo —con una sola sería ruido—.

### «Todos» bloquea el reparto, y ahora se dice antes

Un origen que ya reparte **todos** no admite una segunda rama: mandar a todo
el mundo a una fase y además repartir a otras no puede cumplirse. El
servidor lo rechaza con razón, pero enterarse al pulsar Conectar es tarde —
para entonces ya elegiste destino y cantidad.

Ahora el formulario lo avisa **al elegir el origen**, nombrando a dónde va
ya esa ruta y qué hacer: cambiarla primero a una cantidad o un porcentaje.

El alcance del aviso es el mismo que usa el servidor: una entrada concreta,
o un par fase+salida concreto.

Comprobado de punta a punta: con el «todos» suelto, una entrada reparte
12 exactos a una fase (prioridad 1), 4 a otra (prioridad 5) y el resto a una
tercera (prioridad 9).

### Una salida es de la PLANTILLA, no de la fase del torneo

El fallo más feo de todos, y el más fácil de cometer.

`PhaseExit` pertenece a la **plantilla de fase**, no al nodo del torneo. Dos
fases en paralelo que usan la misma plantilla —dos llaves iguales, lo más
normal del mundo— **comparten los ids de sus salidas**.

El taller filtraba las rutas solo por el id de la salida. Consecuencias, con
un escenario de dos llaves hermanas alimentando a una final:

- Conectabas «Llave A» y **la ruta aparecía también bajo «Llave B»**, como
  si se hubieran conectado todas las fases paralelas a la vez.
- La fase que las recibe, si usaba una plantilla con esos mismos ids,
  **aparecía con su salida ya conectada** sin haberla tocado. De ahí la
  sensación de bucle.
- El desplegable de origen generaba **dos opciones con el mismo valor**
  (`EXIT:26` para las dos llaves), así que elegir una podía crear la ruta
  desde la otra: `find` devuelve la primera.
- Y `unconnectedExits` daba por conectada una salida porque la de su hermana
  lo estaba.

**Una ruta pertenece al par (fase, salida). Siempre.** Ahora se filtra por
los dos, y la llave de las opciones lleva la fase dentro:
`EXIT:79:26` frente a `EXIT:80:26`.

De paso, el formulario dejó de trocear la llave para leer sus partes y lee
el objeto de la opción elegida — eso no se rompe cuando el formato de la
llave cambia, que es justo lo que acababa de pasar.

Medido con dos llaves hermanas y una final: cada una muestra solo su ruta,
la final recibe las dos con sus prioridades, cero valores duplicados en el
selector.

### Cuántos entran y cuántos salen, en cada pieza

Conectar era a ciegas. Una ruta decía «Todo» y una puerta decía «16
participantes exactos», y juntos no contestaban la única pregunta que se
hace al conectar: **cuántos me quedan por meter**.

El pronóstico de flujo ya lo calculaba el validador —es el mismo que produce
los avisos del diagnóstico— pero solo se usaba para quejarse. Ahora se
enseña donde se trabaja. Cada pieza dice tres cosas:

| | |
|---|---|
| **cabe** | Lo que pide su contrato |
| **llegan** | Lo que de verdad le mandan las rutas conectadas |
| **faltan** | La resta — «faltan 8», «lleno», «se pasa de 3» |

Las cuentas no se rehacen: salen del mismo pronóstico, así que la pantalla y
el diagnóstico no pueden contradecirse.

**Una puerta sin límite propio hereda el de su fase**, pero solo si es la
única: con dos puertas el cupo de la fase se reparte entre ellas, y
atribuírselo entero a cada una diría que caben el doble. Cuando lo hereda,
se dice —«el cupo lo pone la fase»—.

Y donde no hay cupo contra el que restar no se inventa un número: una puerta
sin máximo admite lo que le echen, y decir «faltan 0» ahí sería mentir.

Las rutas del mapa dejaron de mostrar su modo de reparto y muestran **cuánta
gente llevan de verdad**: no es lo mismo saber que una ruta reparte «los N
primeros» que ver que por ahí pasan 4.

Comprobado: con la entrada bajada a 12 sobre una fase de 20, dice «faltan
8»; las fases de detrás pasan a «faltan hasta 16» porque su origen se vuelve
variable; y pedir 40 de una entrada que solo tiene 20 se limita solo.

### Los destinos llenos no se ofrecen

Elegir a dónde va una ruta ofrecía todos los destinos, llenos incluidos.
Conectar uno lleno crea una ruta que el diagnóstico rechaza acto seguido: un
viaje de ida y vuelta que no lleva a ningún sitio.

Ahora el desplegable solo enseña los que **admiten gente**, y cada opción
dice cuántos le faltan:

```
Entrada principal · faltan 8
Entrada principal · faltan hasta 16
Eliminados · faltan hasta 19
```

Un destino **sin cupo declarado no está lleno**: admite lo que le echen, así
que se queda. Y los que ya se pasaron tampoco se ofrecen, por el mismo
motivo que los llenos.

**Pero no desaparecen del todo.** Cuando hay alguno oculto aparece un
«ver también N que ya están llenos». Esconder una opción sin dejar forma de
llegar a ella convierte un atajo en un callejón, y hay motivos legítimos
para querer una: cambiar el reparto de lo que ya llega, por ejemplo.

Si TODOS los destinos están llenos y no se ha pedido verlos, se dice —en vez
de dejar un desplegable vacío sin explicación—.

### Lo que el taller NO deja cambiar

**Los extremos de una ruta que ya existe.** Cambiar a dónde va una ruta es
cambiar el recorrido, no afinarlo, y hacerlo con dos desplegables escondidos
en una fila pequeña es la mejor forma de romper un torneo sin darse cuenta.
Para eso está borrar y volver a conectar, que se ve.

**Las salidas de una fase.** Se crean en la Super Edición de esa fase, porque
son suyas: el torneo decide a dónde llevan, no cuáles hay.

### Moverse entre fases sin salir de la estructura

Empezó siendo un desplegable en la esquina de arriba. Funcionaba, pero
obligaba a subir a una esquina para algo que se hace todo el rato, y sobre
todo **no decía dónde estabas**: un desplegable con cinco nombres no enseña
que la que miras es la tercera de cinco.

Ahora hay una tira de fichas dentro de la estructura, en el recorrido y en
el taller. Cada fase es una ficha con su color de nivel y su tipo; la que se
mira va marcada, hay un contador «3/5», y las flechas ◀ ▶ saltan a la de al
lado. Las fases con cabos sueltos llevan un ▲ ámbar, así que se ve cuál hay
que revisar sin entrar en ninguna.

Ese orden es el de la lista, no el del grafo: con bifurcaciones «la
siguiente» no existe como una sola cosa. Para seguir el camino de verdad
están las fichas de antes y después, que sí salen del grafo — y, en el
taller, **un botón de salto en cada ruta**: ↰ va a la fase de la que viene
esa gente, ↳ a la que va. Solo aparecen cuando al otro lado hay una fase;
de una entrada del torneo o hacia un final no hay a dónde saltar.

### La fase que miras se recuerda

Todo el CRUD del grafo responde con redirecciones, así que **cada formulario
recarga la página**. Sin recordar la fase, conectar una ruta en la cuarta te
devolvía a la primera: el trabajo seguía hecho, pero había que volver a
buscar dónde estabas cada vez.

Se guarda en el navegador, con el id del torneo en la llave —dos torneos no
comparten fases, y sin eso una fase de uno «existiría» en el otro solo
porque el número coincide—. Si la fase recordada ya no existe, se vuelve a
la primera en vez de quedarse en blanco.

### El panel se pliega

El taller siempre se abre con el panel de la izquierda plegado: lo que se
edita está en el centro y el panel le quitaba un tercio de ancho justo cuando
más falta hace. Se puede reabrir mientras se está dentro, y al salir y volver
el taller vuelve a dar la pantalla entera. Recargar estando en el taller hace
lo mismo, así que lo que se ve y lo que se recuerda no se contradicen.

El botón ⇤/⇥ lo pliega en cualquiera de las tres vistas.

---

## 5ter. La ficha del torneo

Lo que ves al abrir una plantilla de torneo. Es el torneo **presentado**, no
editado: aquí no hay ni un botón de guardar.

Se lee de arriba abajo:

| Zona | Contesta |
|---|---|
| Portada | Qué es, su contrato, y si el recorrido está completo |
| El recorrido | Qué camino tiene, con sus colores y etiquetas |
| El simulador | Qué pasaría si se jugara hoy |

Comparte el payload con la Super Edición —el mismo grafo, los mismos
niveles, las mismas siluetas de fase— porque es la misma información leída
para otra cosa. Lo único que sobra son los botones, y esos no están en el
payload sino en la vista.

### El simulador

Mete N participantes por las entradas y los deja recorrer el grafo entero.
No hay motor nuevo: lo ejecuta `TournamentFlowPreviewService`, que ya hacía
exactamente esto.

Lo que importa de una simulación **no es quién gana** —los resultados son
aleatorios— sino si el recorrido cuadra:

- ¿llegan todos a algún sitio, o se pierde gente por el camino?
- ¿alguna fase se queda sin nadie?
- ¿algún final se queda vacío?

Por eso **los perdidos salen en rojo y grandes**. Un participante perdido
significa que salió de una fase por una salida que no lleva a ningún sitio:
es un agujero del recorrido, no del azar, y es la razón principal de que
este simulador exista.

Cuando hay resultado, la estructura de arriba **se llena con lo que pasó**:
cada fase enseña `▼ recibe` y `▲ manda`, cada ruta cuántos la cruzaron, y
cada final quién llegó. Pulsando a un participante se le sigue: su viaje se
lista paso a paso y el recorrido marca por dónde pasó, apagando lo demás.

Si el grafo tiene problemas bloqueantes, el servicio se niega a ejecutar y
se dice por qué. Es lo correcto: simular un recorrido roto daría un
resultado inventado.

Nada de esto se guarda. Los participantes son sintéticos.

---

## 6. Lo que NO va en una plantilla

Los premios estuvieron aquí y se retiraron.

Una plantilla describe la ESTRUCTURA de un torneo: por dónde se entra, qué
se juega, cómo se conecta y dónde acaba. Qué se lleva quien llega a un final
depende de la edición concreta que se juegue —el mismo recorrido puede
repartir cosas distintas en dos ocasiones—, así que pertenece al torneo
real, no a la plantilla que lo describe.

Es la misma frontera que ya sacó el formato de batalla de la Super Edición
de fases: `series_format` y `default_best_of` siguen en la tabla y el motor
los lee, pero cuántos juegos tiene un enfrentamiento se decide al montar el
torneo.

**Regla práctica:** si un dato cambia entre dos ediciones del mismo torneo
usando la misma plantilla, no va en la plantilla.

Lo que sí quedó en cada final es cuántos caben, que sí es estructura.

---

## 7. Las caras

El recorrido se enseña con gente dentro y no con cajas vacías: caras
prestadas de tus universos y tu biblioteca, con el mismo `PreviewCastService`
que usan las fases. No son inscritos y no se guardan.

---

## 8. Qué se conserva

El constructor de grafos de siempre **sigue vivo y enlazado**. No es
duplicación: hace algo que la Super Edición no hace, que es colocar las
piezas a mano en un lienzo con coordenadas. La ficha del torneo ofrece las
dos puertas, con la Super Edición como la principal.

---

## 9. Casos probados

| Caso | Resultado |
|---|---|
| Crear entrada, fase y final desde el panel | Las tres se crean y se vuelve a la pantalla |
| Conectar entrada → fase | Ruta creada, `ALL` |
| Conectar salida de fase → final | Ruta creada, `TAKE_N` 4 |
| Guardar identidad del torneo | Nombre, rango y descansos persisten |
| El mapa tras los cambios | 2 entradas · 2 fases · 3 finales · 5 rutas |
| Siluetas de dos motores a la vez | «Cuadro de 16» y «8 en una tabla» |
| Salida sin ruta | Marcada en ámbar: «nadie sale por aquí» |
| Vista estrecha | Sin desbordamiento horizontal |
| Editar un final, una entrada y una puerta | Guardan — antes daban 403 |
| Taller · conectar una salida suelta a un final | Ruta creada, `Tomar 2` |
| Taller · ajustar esa ruta a 50% y prioridad 5 | Persiste |
| Taller · crear puerta con solo un máximo | Se crea — antes lo rechazaba |
| Taller · editar y borrar puerta y ruta | Todo persiste |
| Taller · el panel se pliega al entrar | Y se puede reabrir |
| Taller · reparto «todos» | El número se oculta, no queda una caja muerta |
| Taller · «N primeros» y «porcentaje» | El número aparece con su etiqueta |
| Taller · prioridad | Campo visible, y `#n` en cada ruta que compite |
| Una entrada a tres fases: 12 · 4 · resto | Persiste con sus prioridades 1 · 5 · 9 |
| Elegir un origen que ya manda «todos» | Se avisa antes de enviar, con el destino |
| Dos fases hermanas (misma plantilla) → una final | Cada una muestra solo su ruta |
| La fase que las recibe | No aparece con salidas conectadas de más |
| Valores del selector de origen | Únicos: `EXIT:79:26` ≠ `EXIT:80:26` |
| Fase de 20 recibiendo 12 | «faltan 8» |
| Fase de 20 recibiendo 20 | «lleno», en verde |
| Fase cuyo origen es variable | «faltan hasta 16» |
| Puerta sin cupo propio, fase con contrato | Lo hereda y lo dice |
| Mapa y ficha | Muestran `llegan / caben` en cada pieza |
| Torneo lleno · desplegable de destino | Oculta 4, ofrece 1, y avisa de los 4 |
| Torneo a medio llenar | Ofrece los 5, cada uno con sus «faltan N» |
| Ningún destino lleno | El interruptor no aparece |
| Recargar tras guardar | Se queda en la misma fase y la misma vista |
| La fase recordada ya no existe | Cae a la primera, sin colgarse |
| Flechas ◀ ▶ y fichas del navegador | Saltan y el contador acompaña |
| Botón de salto en una ruta fase→fase | Aparece con su nombre y salta |
| Ruta que acaba en un final | No ofrece salto, que es lo correcto |
| Las tres vistas a 375px | Sin desbordamiento horizontal |
| Ficha · oscura, con sidebar, cero botones de editar | Correcto |
| Ficha · simular con el grafo bloqueado | Se niega y dice por qué |
| Ficha · simular con el grafo válido | 16 entran · 1 llega · 15 perdidos |
| Ficha · seguir a un participante | Su viaje y su rastro en el recorrido |
| Ficha a 375px | Sin desbordamiento |

Suite completa: 88 pasan / 99 fallan — idéntico al baseline anterior.

---

## 10. Tres formularios que nunca funcionaron

`UpdateTournamentTerminalRequest`, `UpdateTournamentStartRequest` y
`UpdatePhaseEntryPortRequest` estaban **sin implementar**: `authorize()`
devolvía `false` y `rules()` estaba vacío, tal y como los deja `artisan
make:request`.

Eso no daba un formulario a medias: daba un **403 «This action is
unauthorized»** al pulsar guardar, sin ninguna pista de que el problema no
era de permisos sino de una clase que nadie había escrito. Y crear
funcionaba mientras editar no, que es el síntoma más desconcertante posible.

No era un fallo de esta pantalla —editar un final no había funcionado nunca,
desde ninguna— pero la Super Edición fue lo primero que puso los tres al
alcance de un clic.

Las tres ahora **heredan de su gemela de creación**. Editar valida lo mismo
que crear, y heredar en vez de copiar evita que dos listas de reglas para la
misma entidad diverjan en silencio.

---

## 10bis. Una regla que rechazaba lo válido

`StorePhaseEntryPortRequest` validaba `max_participants` con
`gte:min_participants` **siempre**. Crear una puerta que solo dice «caben
hasta 8», sin mínimo, fallaba con un mensaje que apuntaba al sitio
equivocado: «el máximo debe ser mayor o igual que el mínimo», cuando no
había mínimo que comparar.

Dos causas encadenadas: un formulario manda cadena vacía cuando no rellenas
un número, y `nullable` no la trata como ausente. Ahora los tres numéricos se
normalizan a `null` antes de validar, y el `gte` solo se aplica cuando hay
mínimo con el que comparar.

Salió al construir el taller, que es la primera pantalla que ofrece los tres
campos por separado.

---

## 10ter. array_unique colapsaba listas de participantes

`EntryPortMergePolicy::normalizeIds()` quitaba repetidos con `array_unique`,
que compara sus elementos **convertidos a texto**. Con ids sueltos funciona;
pero por ahí pasan también participantes completos —arrays—, y todos los
arrays se convierten a la misma cadena, `"Array"`.

No es que avisara y ya: **colapsaba la lista entera a un solo participante**.
Tres que llegaban por una conexión salían uno, y los otros dos desaparecían
sin dejar rastro ni error. Medido: tres participantes por dos conexiones
entraban y salían dos.

Ahora se compara en estricto, que no convierte nada: dos arrays son el mismo
si tienen las mismas claves con los mismos valores.

Salió construyendo el simulador de la ficha, que es lo primero que ejecuta
ese camino y enseña el resultado en números.

---

## 10quater. El pronóstico de flujo mentía en tres sitios

El diagnóstico avisaba de cosas que no pasaban. Un torneo bien montado —20
entran, 16 clasifican, 4 caen, y un destino de eliminados que declara 19—
se quejaba de que «necesita exactamente 19, pero el flujo calculado es
15–35», y no había forma de contentarlo: la configuración era correcta y el
que se equivocaba era el pronosticador.

**Uno · «el resto» no se restaba.** `REMAINING` se calculaba como «entre 0 y
todos los que entran», mirando solo su propia salida. Pero el resto es una
resta: si entran 20 y otra salida se lleva 16 exactos, por el resto salen 4.
Exactamente 4.

Ahora las salidas se pronostican en **dos pasadas**: primero las que se
bastan solas, después las de resto restando lo ya reclamado. Si algo de lo
que se llevan las hermanas no se sabe, se vuelve al rango pesimista — restar
de un desconocido no da un exacto.

**Dos · `WINNER` no estaba en la lista.** Caía en el `default` y devolvía «no
se sabe», cuando un campeón es evidentemente una persona. `RUNNER_UP` igual.

**Tres · el aviso del campeón no miraba el flujo.** Avisaba de que un destino
de tipo Campeón no declara «cabe uno» aunque el recorrido ya garantizara que
llega exactamente uno. Ahora solo avisa cuando de verdad hay algo raro que
señalar; antes no podía distinguirlo, porque el pronóstico de un `WINNER`
salía desconocido por el punto dos.

Efecto medido sobre los torneos existentes: tres dejaron de quejarse sin
tocarles ni un dato, otro pasó de «puede producir 0–20» a «8 exactos», y no
apareció ningún aviso nuevo.

---

## 11. Qué falta

1. **La simulación reparte, pero no juega.** El preview recorre el grafo
   aplicando los selectores de salida de cada fase; no disputa los
   enfrentamientos uno a uno. Para eso está el Competition Lab, que vive en
   otra pantalla.
2. **Swiss y League no tienen silueta** porque no tienen Super Edición.
4. **No se pueden reordenar las fases arrastrando.** El orden lo dictan las
   rutas, que es lo correcto, pero mover una fase de nivel obliga a
   reconectar a mano.
