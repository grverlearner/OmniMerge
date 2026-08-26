# SUPER EDICIÓN DE FASES

**Implementados: Round Robin y Fase de grupos.**
Arquitectura pensada para que Single Elimination y Swiss se enchufen
después sin rehacer el armazón.

---

## 1. Por qué existe

Una fase se miraba y se editaba en la misma pantalla, y además en varias:
el resumen tenía botones de editar repartidos, «Reglas» configuraba el
motor, «Estructura» enseñaba el calendario y «Entrada y salida» las
puertas. Cuatro sitios para una sola cosa, y ninguno enseñaba a la vez la
configuración y su consecuencia. Cambiar el número de vueltas obligaba a
guardar, salir y volver para ver cuántas jornadas salían.

La Super Edición separa dos actos que antes estaban mezclados:

| | Vista normal | Super Edición |
|---|---|---|
| Qué hace | Describe la fase | La cambia |
| Se entra | Siempre | A propósito, por un solo acceso |
| Puede romper algo | No | Sí, y por eso avisa |

---

## 2. Qué pertenece a una fase y qué no

Esta es la decisión conceptual más importante de la implementación.

### Sí pertenece a la fase

- Identidad: nombre, descripción, imagen, código.
- Contrato de participantes: mínimo, máximo, exacto, múltiplo.
- Ciclo: una vuelta, ida y vuelta, o más.
- Orden inicial: `INPUT_ORDER`, `RANKING`, `RANDOM`, `MANUAL`, `BY_GATE`.
- Fuente de ranking a usar cuando corra el torneo real.
- Puntuación: victoria, empate, derrota, y si se permiten empates.
- Puertas de entrada y de salida.

### NO pertenece a la fase

**Formato de batalla** — best of, series, juegos fijos, duración, reglas de
combate. Una misma liga tiene que poder reutilizarse en dos torneos con
reglas de combate distintas; si la fase las guardara, no podría.

> **Decisión técnica.** Las columnas `default_best_of`, `series_format` y
> `fixed_games` siguen existiendo en `phase_round_robin_settings` y el motor
> las sigue leyendo como valor por defecto. Se han retirado de la interfaz,
> no de la base de datos. Borrarlas hoy rompería la ejecución de Round Robin
> y de Group Stage, que reutiliza el equivalente `internal_best_of`. El
> torneo real las sobrescribirá cuando exista esa capa.

**Criterios de desempate** — la cadena es fija y no se edita:

```
Puntos → Diferencia → Anotadas → Victorias → Enfrentamiento directo → Orden de entrada
```

> **Corrección aplicada.** `defaultCriteria()` tenía `WINS` por delante de
> `SCORE_DIFFERENCE`. Es el mismo defecto que se corrigió en Fase de grupos:
> con victorias primero, dos equipos igualados a puntos se separaban por
> partidos ganados aunque uno hubiera arrasado, y la tabla en pantalla
> contradecía lo que el motor decidía al repartir plazas.

La cadena termina en el orden de entrada, que siempre es único. **Una fase
Round Robin no puede acabar en un empate sin resolver**, así que una puerta
de salida siempre puede decidir qué puestos se lleva. `PhaseRoundRobinTiebreaker`
y su controlador siguen existiendo y funcionando; solo desaparecieron de
esta interfaz.

---

## 3. Contradicciones resueltas frente a la especificación

### 3.1 Los dos rankings no se pueden resolver en una fase

`PhaseTemplate` no tiene `universe_id` ni vínculo con torneo — es una pieza
de biblioteca, reutilizable en cualquier universo. `UniverseRankingService::ranking()`
exige un `Universe`. Mientras editas una fase **no hay torneo ni universo
del que leer una clasificación**.

**Resuelto así:** la fase guarda *cuál* de las dos fuentes usar
(`settings['ranking_source']` = `TOURNAMENT` | `UNIVERSAL`), y el editor
enseña una siembra de demostración con caras prestadas, etiquetada como tal.

### 3.2 Una puerta de entrada reparte puestos, pero de la PARRILLA

Hay dos «puestos» distintos y confundirlos es el error fácil:

| | Puerta de ENTRADA | Puerta de SALIDA |
|---|---|---|
| De qué puesto habla | El **inicial**, la parrilla | El **final**, la clasificación |
| Cuándo existe | Antes de jugar nada | Cuando ya hay tabla |
| Qué decide | Por dónde entra cada uno | Quién avanza |

En una liga **todos se enfrentan a todos**, así que una puerta de entrada
no puede decidir quién pasa ni cuántos juegan. Lo único que le queda por
decidir —y no es poco— es **qué número de la parrilla ocupa** la gente que
llega por ella. Y eso cambia el calendario de verdad: el puesto 1 abre
contra el último y el 2 contra el penúltimo.

Se implementa en `RoundRobinSeedRuleResolver`, con cinco reglas:
`FIRST_N`, `LAST_N`, `RANGE`, `POSITION`, `REMAINING`.

> **Decisión técnica.** La regla vive en `PhaseInputGate.settings['seed_rule']`,
> **sin migración**. Es vocabulario de un solo motor: una puerta de Fase de
> grupos apunta a un grupo y una de Eliminación Directa a un slot del
> cuadro. Darle columnas propias a cada una llenaría la tabla de campos
> nulos.

> **La capacidad se deriva, no se pregunta.** Una puerta que reclama del 1
> al 4 admite exactamente 4. Dejar escribir otra cantidad solo permitiría
> guardar una contradicción.

**Los conflictos no se resuelven en silencio.** Si dos puertas reclaman el
mismo puesto, gana la primera y el diagnóstico lo dice con el número
concreto. Una parrilla repartida a escondidas es una que nadie pidió.

`PhaseEntryPort` sigue siendo otra cosa: vive en el nodo de un grafo
(`tournament_phase_node_id`) y no existe al editar una plantilla.

### 3.3 «Por puerta» no existía

`initial_order_mode` es `string(30)` sin restricción en base de datos:
añadir `BY_GATE` fue solo validación. **Sin migración.**

---

## 4. Arquitectura

```
PhaseSuperEditorController          agnóstico del motor
        │
        ▼
PhaseSuperEditorRegistry            qué editor sabe editar qué tipo
        │
        ▼
PhaseSuperEditorContract            interfaz
        │
        ├── RoundRobinSuperEditor           ← implementado
        ├── GroupStageSuperEditor           ← implementado
        ├── SingleEliminationSuperEditor    ← previsto
        └── SwissSuperEditor                ← previsto
```

Para añadir un motor: escribir la clase que implemente el contrato, sus
cuatro vistas, su módulo de JavaScript, y una línea en
`PhaseSuperEditorRegistry::EDITORS`. El controlador, las rutas, el armazón y
la cabecera **no se tocan**.

### Capacidades opcionales

Un motor puede declarar además `SupportsEditableGroups` para que las rutas
`/super/groups` funcionen con él.

Va en una interfaz aparte y no en el contrato principal porque una liga no
tiene grupos: meterlo dentro obligaría a `RoundRobinSuperEditor` a escribir
tres métodos vacíos, y **un método vacío que nadie llama es una promesa que
no se cumple**. El controlador pregunta con `instanceof` y devuelve 404 para
los motores que no lo implementan.

### El contrato

```php
phaseType()          string          ROUND_ROBIN, GROUP_STAGE...
configView()         string          panel izquierdo
stageView()          string          escenario central
gatesView()          string          panel derecho
scheduleView()       string          zona inferior
saveFieldsView()     string          campos ocultos del guardado
clientEngine()       string          qué módulo de JS cargar
payload($phase, $user, $overrides)   todo lo que la pantalla necesita
persist($phase, $data)               guarda lo que pertenece a la fase
persistenceRules()   array           reglas para el FormRequest
gateRules() / persistGate() / deleteGate()
exitRules() / persistExit() / deleteExit()
```

Los cuatro paneles son huecos del motor, no vistas compartidas, porque una
puerta no significa lo mismo en cada fase: en una liga reparte puestos de la
parrilla y en una fase de grupos reparte grupos. Una sola vista con
condicionales acabaría siendo dos vistas mal separadas dentro del mismo
archivo.

### Vistas

```
super/editor.blade.php              armazón: reparte la ventana
super/partials/header.blade.php     identidad + estado + guardar

super/round-robin/                  config · stage · gates · schedule
super/group-stage/                  config · stage · gates · schedule
                                    + group-form · gate-form · exit-form
```

### JavaScript

```
super/base.js          servidor, semillas, simulación, conteo, orden
super/round-robin.js   una parrilla, una lista de jornadas, una tabla
super/group-stage.js   N grupos, jornadas en paralelo, N tablas
super-editor.js        junta la base con el motor que toque
```

> **Se une con `mergeParts`, no con spread.** El spread copia el *valor* que
> devuelve un getter en ese instante, no el getter: `structure`,
> `classified` o `groups` quedarían congelados en su primer valor y la
> pantalla no reaccionaría a nada. Es exactamente el fallo que ya obligó a
> separar `competitionArena` en su propio componente.

---

## 5. Flujo de datos y reactividad

La decisión clave: **el trabajo se reparte en dos mitades**.

```
SERVIDOR                          CLIENTE
la matemática                     quién ocupa cada semilla
─────────────────                 ─────────────────────────
jornadas                          orden de entrada
emparejamientos                   barajado
descansos                         orden manual
validación del contrato           agrupación por puerta
                                  colores de puerta y salida
```

`RoundRobinScheduleCalculator` empareja **semillas** (1 contra 8, 2 contra 7),
nunca personas. Por eso cambiar el orden es una permutación de un array y no
recalcula nada: el calendario no se entera.

```
order[posición − 1] = índice del reparto
```

Solo se pide al servidor cuando cambia algo que altera la matemática:
**cantidad de participantes** y **número de vueltas**. Con 280 ms de espera,
para que escribir «16» no dispare una petición por el «1».

### Cada motor declara sus controles

`previewOverrideKeys()` dice qué parámetros acepta el preview y de qué tipo.

> **Corrección aplicada.** El controlador tenía la lista escrita a mano con
> tres claves, y las tres eran de Round Robin. Todos los controles de fase de
> grupos —número de grupos, tamaño objetivo, sobrantes, reparto, vueltas— se
> descartaban en silencio: el servidor contestaba con la configuración
> guardada y el control saltaba hacia atrás solo. Subir el número de grupos
> volvía a 4, elegir «orden de entrada» seguía repartiendo en serpiente, y
> crear un grupo personalizado deseleccionaba el modo.
>
> Con el contrato, un motor nuevo no puede olvidarse de declararlos.

Las mismas claves viajan en la redirección después de crear, editar o borrar
una puerta o un grupo, para que volver al editor no pierda lo que estabas
previsualizando.

### Endpoint

```
GET  /tournaments/phases/{phaseTemplate}/super           pantalla
GET  /tournaments/phases/{phaseTemplate}/super/preview   mismo cálculo en JSON
PUT  /tournaments/phases/{phaseTemplate}/super           guardar
```

El preview acepta `?participants=N&cycles=C` para responder a lo que se está
tocando y no a lo último guardado.

> **Por qué un endpoint y no cálculo en el navegador:** duplicar la
> matemática de Round Robin en JavaScript crearía dos verdades que se
> separarían con el tiempo. El preview llama al mismo calculador que usan el
> simulador y Fase de grupos.

---

## 6. Persistencia — qué se guarda y qué no

| Cambio | ¿Se guarda? |
|---|---|
| Vueltas | Sí |
| Modo de orden | Sí |
| Fuente de ranking | Sí, en `settings['ranking_source']` |
| Puntuación y empates | Sí |
| Cantidad de participantes | **No**, salvo que se marque «fijar como exacta» |
| Barajado que se acaba de ver | No |
| Orden arrastrado a mano | No |
| Caras prestadas | **Nunca** |

**Participantes.** Mover el control es previsualizar. Fijarlo escribe
`exact_participants` y estrecha el contrato de una pieza reutilizable, así
que se pide con una casilla aparte.

**Barajado y orden manual.** No se guardan porque **son decisiones del
motor en ejecución**, no de la fase: `RoundRobinLabEngine` baraja al
arrancar y `LabManualDecisionManager` detiene la fase para pedir el orden.
La fase solo guarda *qué se hará*. Guardar aquí un orden concreto sería
inventar un dato que el runtime ignoraría.

**Caras prestadas.** `PreviewCastService::borrow()` toma entidades reales de
los universos y la biblioteca del usuario **solo para mirar**. No se
inscriben, no se guardan y no tienen relación con los participantes de un
torneo real. La pantalla lo dice.

---

## 7. Validación

El diagnóstico se calcula en cada preview y se ve en la cabecera:

```
✓ Válida        ⚠ Con avisos        ✕ Inválida
```

Los **errores** vienen de `RoundRobinValidator`, el de siempre — no hay una
segunda lista de reglas. Los **avisos** son de pantalla: número impar y sus
descansos, jornadas recortadas, fase sin salidas.

Con estado inválido el botón de guardar se desactiva. No hace falta guardar
para descubrir que algo está mal.

---

## 8. Puertas en el escenario

El color no es decoración: es cómo se sigue una puerta con la vista desde el
panel derecho hasta la fila del participante y hasta su enfrentamiento.

**La puerta acompaña a la persona, no a la fila.** Es por donde *entró*, así
que al barajar o al simular, cada uno se lleva su color a su posición nueva.

**Se crean y se editan dentro del editor.** Antes había que irse a otra
pantalla, configurar sin ver la estructura y volver para comprobar el
efecto. En un editor cuya razón de ser es que todo reacciona a la vez, salir
fuera era lo único que no reaccionaba.

La antigua pestaña «Entrada y salida» de Round Robin ya no existe; su ruta
redirige al editor para no romper enlaces guardados.

### 8.1 Sembrar por puerta no siempre cambia algo

Con el calendario **completo**, sembrar por puerta solo cambia el *orden* de
los partidos: todo el mundo acaba enfrentándose a todo el mundo igual. El
editor lo dice donde se elige el modo, en vez de dejar que parezca que hace
más de lo que hace.

Es en cuanto la liga se **recorta** cuando el puesto inicial decide contra
quién llegas a jugar y contra quién no. Por eso los dos controles van
juntos.

---

## 8bis. Jornadas a jugar

Normalmente se juega la liga entera y el control sobra. Existe porque es lo
que le da sentido a sembrar por puerta (ver 8.1).

- Se guarda en `settings['round_limit']`, **sin migración**.
- Nunca puede superar las jornadas que existen: si hay guardado un 9 y el
  calendario tiene 7 —porque bajó el número de participantes—, manda el 7.
  Un número guardado no puede inventar jornadas que no se generan.
- Si no hay recorte no se guarda nada, para que una liga completa no
  arrastre un número que habría que revisar en cada cambio.
- Las jornadas fuera del recorte se siguen viendo, atenuadas y marcadas
  como «no se juega». Se recorta, no se esconde.

> **Advertencia de diseño.** Una liga recortada **no es un round robin
> completo**: no todos se enfrentan entre sí, y la clasificación final
> depende de a quién te tocó. Es legítimo (existen ligas cortas), pero el
> editor lo avisa en el panel para que sea una decisión y no un descuido.

---

## 8ter. Simulación

Botones para inventar resultados: **un partido**, **una jornada**, **todo**.

Existe porque una tabla a cero no dice nada. Hasta que no hay resultados no
se ve cómo ordena la cadena de desempate, ni cómo se reordena la
clasificación, ni a quién se lleva cada puerta de salida. Antes había que
montar un torneo entero para averiguarlo.

**Nunca se guarda.** Los resultados viven solo en la memoria del navegador
y se borran solos cuando dejan de tener sentido:

- al cambiar participantes o vueltas — es otro calendario, otros partidos;
- al cambiar el orden inicial o mover a alguien a mano — con otra parrilla
  esos emparejamientos ni existen;
- al recortar jornadas — se descarta lo que caiga fuera del corte.

**Se guardan por semilla, no por participante.** Así reordenar la parrilla
no arrastra el resultado de una posición a otra.

**La tabla se ordena con los criterios reales.** El payload trae
`catalog.ranking_keys` —las mismas claves y el mismo orden que usa el
motor—, y el navegador las aplica. Llevar aquí una lista propia garantizaría
que las dos se separen a la primera corrección.

El único criterio que la tabla salta es el **enfrentamiento directo**: no se
puede resolver mirando una columna, necesita el partido concreto. El motor
hace lo mismo cuando no hay resultado.

Con resultados, la fila muestra el puesto de la tabla y, entre paréntesis,
de qué puesto de la parrilla salió.

---

## 8quater. Fase de grupos

Una fase de grupos es **una liga pequeña repetida N veces y jugada en
paralelo**, y la implementación lo aprovecha entero: no se escribió ni un
algoritmo nuevo.

- El reparto en grupos sale de `GroupStageAllocator`.
- El calendario de cada grupo, del **mismo** `RoundRobinScheduleCalculator`
  que usa la Super Edición de liga.

La costura vuelve a ser la semilla: el repartidor decide qué semilla cae en
qué grupo, y el calculador empareja semillas *dentro* de cada grupo. El
editor traduce las semillas locales del grupo a las globales de la fase
(`localiseRounds`), que son las que conoce el resto de la pantalla.

### El escenario se lee de abajo arriba

```
▲ SALIDAS      quién avanza y por qué puerta, con cara
  GRUPOS       una tabla por grupo, con su color
▼ ENTRANTES    la fila de llegada, pintada del color del grupo que les toca
```

El color hace todo el trabajo: el mismo tono acompaña a una persona desde su
casilla de entrada, a su grupo, a su jornada y a su salida. Se puede seguir
con la vista sin leer un número.

### Construcción

`group_count_mode` decide de dónde salen los grupos:

| Modo | Qué se configura | Dónde |
|---|---|---|
| `FIXED_GROUP_COUNT` | cuántos grupos | panel izquierdo |
| `TARGET_GROUP_SIZE` | cuántos por grupo | panel izquierdo |
| `CUSTOM_GROUPS` | cada grupo, uno a uno | panel derecho |

Con los dos primeros el panel derecho **muestra** los grupos pero no los
edita: los calcula la fase. Solo `CUSTOM_GROUPS` ofrece crear, editar y
borrar — y `GroupStageGroupService` lo exige también en el servidor, así que
la regla no depende de que la interfaz esconda un botón.

Los sobrantes se reparten con `remainder_policy`: `BALANCED`, `FIRST_GROUPS`,
`LAST_GROUPS` o `MANUAL`.

### Vueltas por grupo

Un grupo puede llevar las suyas, en `PhaseGroupStageGroup.settings['cycles']`
— **sin migración**. En blanco usa las de la fase: guardar ahí un número
igual al de la fase solo crearía una copia que habría que mantener
sincronizada a mano.

### Reparto por orden de entrada: se reparte, no se llena

`INPUT_ORDER` reparte como se reparten cartas: el primero al grupo A, el
segundo al B, el tercero al C, y vuelta a empezar. Con 12 participantes y 4
grupos:

```
A   1   5   9
B   2   6  10
C   3   7  11
D   4   8  12
```

> **Corrección aplicada.** `fillSequential()` llenaba un grupo entero antes
> de pasar al siguiente, así que los cuatro primeros en inscribirse acababan
> juntos en el grupo A. Eso hace que el orden de llegada decida el grupo en
> bloque, que es lo contrario de lo que se espera de un reparto: repartiendo,
> dos inscripciones seguidas caen en grupos distintos. Un grupo que se llena
> deja de recibir y el reparto sigue con los demás, para que los cupos
> desiguales no descuadren la vuelta.

### Reparto manual: lo dictan las puertas

`MANUAL` es el único modo donde las puertas mandan de verdad. Cada una
reclama su tramo de llegada y lo lleva a su grupo; lo que ninguna reclame se
reparte por orden de llegada en los huecos que queden.

> **Corrección aplicada.** Este modo devolvía los grupos con huecos vacíos
> etiquetados «Asignación manual 1, 2, 3…», así que **la estructura salía en
> blanco por mucho que hubieras configurado las puertas**: se podía dejar
> todo apuntado y no ver nada.
>
> Vive en `GroupStageAllocator` y no en la Super Edición a propósito: así el
> motor reparte igual que el editor dibuja, en vez de que uno enseñe una cosa
> y el otro juegue otra.

### Grupos personalizados: la cantidad la mandan los cupos

En `CUSTOM_GROUPS` los participantes **no son un número aparte**: son la
suma de los cupos. El control deja de escribirse y pasa a mostrar esa suma.

> **Corrección aplicada.** Eran dos cosas independientes que tenían que
> cuadrar, así que cambiar el cupo de un solo grupo dejaba la fase inválida
> —«las capacidades suman 15, pero el preview utiliza 16»— hasta retocar
> otro. **No había forma de editar un grupo sin pasar por un estado roto.**
> Derivándola, ese error deja de existir por construcción: bajar un cupo
> simplemente redibuja con una persona menos.
>
> Lo que sigue vigilándose es el contrato de la fase (mínimo, máximo,
> exacto), que es otra cosa y avisa con su propio mensaje.

### La propuesta de reparto cuenta las puertas

Un grupo con puertas apuntándole necesita al menos tantos sitios como gente
le mandan: proponer menos sería proponer algo que no cabe. La sugerencia
reserva primero lo que prometen las puertas y reparte el resto a partes
iguales.

```
Puerta: entrantes 1–6 → Grupo C

sin puertas   4 · 4 · 4 · 4
con la puerta 3 · 3 · 8 · 2      (C lleva sus 6 prometidos + su parte)
```

La calcula el servidor, que es quien conoce las puertas. Si las puertas
prometen más gente de la que entra, la propuesta refleja lo que piden y el
diagnóstico avisa: esconder el choque sería peor.

### Grupos personalizados: el modo se elige al crear

`CUSTOM_GROUPS` exige que cada grupo tenga cupo, y los grupos creados en modo
automático no lo tienen — se creaban sin él porque el reparto lo calculaba la
fase. Al cambiar de modo la estructura desaparecía con un error genérico.

Tres cosas lo resuelven:

1. **El diagnóstico dice cuáles** faltan por nombre, no «alguno».
2. **Adoptar el reparto anterior** escribe los cupos que ya había de un
   clic. Si se llega directo a la pantalla en modo personalizado, propone un
   reparto parejo con el número de grupos del panel.
3. **Crear un grupo a mano fija el modo.** `GroupStageGroupService` exige que
   el modo GUARDADO sea `CUSTOM_GROUPS`, y el editor deja elegirlo sin
   guardar: crear el primer grupo fallaba y la pantalla volvía a «cantidad
   fija». Un grupo con nombre y cupo propios no significa nada en ningún otro
   modo, así que la acción lo fija en vez de rechazarlo.

Los grupos que sobran al adoptar un reparto más corto **se desactivan, no se
borran**: pueden tener criterios de salida apuntándoles, y borrarlos en
silencio los dejaría huérfanos.

### Lo que estás tocando viaja con cada formulario

Crear, editar o borrar recarga la pantalla. Sin llevar el estado, el servidor
contestaba con la configuración **guardada**: cambiabas a personalizado,
creabas un grupo, y volvías con «cantidad fija» seleccionada.

`preview-state.blade.php` mete en campos ocultos lo que devuelve
`previewParams()` del motor, así que vale para todos sin listar ninguno.

### Puertas de entrada: reparten GRUPOS

En una fase de grupos una puerta no dice cuánta gente entra: dice **qué
tramo de los que llegan va a qué grupo**. Se guarda en
`settings['entry_range']` y `settings['target_group_code']`, sin migración.

Por eso se lee junto a la fila de entrantes, y por eso la puerta toma
prestado el color de su grupo destino.

> **Las puertas solo mandan con reparto `MANUAL`.** Con cualquier otro
> reparto decide el algoritmo, y las puertas se guardan pero no se aplican.
> El editor lo dice en el panel, y además marca los entrantes cuyo grupo
> real no coincide con el que pide su puerta (`gateConflicts`) — en vez de
> dejar dos verdades en pantalla.

### Puertas de salida

Reutilizan `PhaseGroupStageAdvancementRule`, que ya existía y que el motor ya
ejecuta. Se crean con su criterio en el mismo paso, y el selector se fija en
`ENGINE_RULES` a propósito: guardar además un número en la puerta sería tener
dos verdades sobre lo mismo, que es lo que dejó un torneo bloqueado con la
fase entera jugada.

Los criterios «de cada grupo» se pintan sobre las tablas; los que comparan
entre grupos (`CROSS_GROUP_*`, `BEST_REMAINING`) necesitan resultados y por
eso solo se resuelven al simular.

### Dos trampas de PHP que costaron caro aquí

**`Collection::groupBy()` reindexa.** El desplegable de criterios se agrupa
por familia, y la clave del grupo interno es el `value` del `<option>`. Sin
el segundo argumento (`preserveKeys: true`), el select mandaba
`rule_type="0"` en vez de `"EACH_GROUP_TOP_N"`: la validación lo rechazaba,
la pantalla se recargaba y **la salida no se creaba nunca**, sin decir por
qué. El mismo defecto estaba en el partial antiguo de criterios.

**El pronóstico usaba el mínimo del contrato, no lo que estás mirando.**
`GroupStageExitForecastService::forecast()` sin cantidad usa
`referenceParticipants()`, que es `exact ?? min ?? 8`. Con 16 en pantalla y
un mínimo de 8, el contador «Salen N» hablaba de un reparto distinto al
dibujado — y si ese mínimo ni siquiera repartía en grupos válidos, se
quedaba mudo. Ahora se pronostica con la cantidad previsualizada, y cuando
no se puede calcular se dice con una raya y no con un cero: **un cero
significa que no sale nadie, que es otra cosa**.

### El color dice a qué familia pertenece cada cosa

Grupos y salidas se separan en **dos ejes**:

| | Tono | Intensidad |
|---|---|---|
| Grupos | azul · ámbar · verde · magenta · rojo · lima · azul oscuro · púrpura | `-400` |
| Salidas | violeta · teal · naranja · rosa · índigo · gris | `-300`, un escalón más claras |

Los tonos van elegidos por separación: los cuatro primeros colores de grupo
están a más de 100° unos de otros en el círculo cromático, y los de salida
caen en los huecos que dejan.

La intensidad hace el resto. Con ocho grupos y seis salidas hacen falta
catorce tonos que de verdad se distingan, y el círculo cromático no da para
tanto: cuando dos quedan cerca, el brillo dice a qué familia pertenece cada
marca.

> **Corrección aplicada.** Compartían una sola paleta, así que la salida #1
> recibía el mismo magenta que el Grupo D —su distintivo era indistinguible
> de un grupo— y la #2 salía roja, que en toda la pantalla significa «algo va
> mal».

> **Y una peor.** `tailwind.config.js` no escaneaba `app/`, donde viven las
> paletas. **La mitad de las clases nunca se generaron**: los anillos, los
> bordes y los fondos suaves existían en el HTML y no pintaban nada. Si una
> clase de color se decide en PHP, Tailwind tiene que mirar ahí.

### Los criterios se resuelven enteros, en su orden

`exitAssignment` recorre los criterios **en su orden** y cada uno se lleva a
quien ningún criterio anterior haya reclamado ya — igual que
`GroupStageAdvancementCalculator` en el servidor. Sin ese «ya reclamado»,
«los que sobren» se llevaría a todo el mundo.

> **Corrección aplicada.** Antes solo se resolvían los criterios por puesto
> dentro de cada grupo, así que `REMAINING`, `BEST_REMAINING`,
> `WORST_REMAINING` y los que comparan entre grupos **no marcaban a nadie**:
> se veía media tabla pintada y media en blanco, sin explicación. Los que
> comparan entre grupos usan `crossTable`, que ordena con la misma cadena que
> el motor; como esa cadena acaba en la semilla, que es única, el orden es
> estable y se puede pintar antes de jugar nada.

### Una salida puede llevar varios criterios

Por eso editar una salida **no** pregunta «el» criterio: se edita el nombre,
la prioridad y el estado de la puerta por un lado, y cada criterio por el
suyo, con su propio botón de editar y quitar, más un «+ criterio» para
añadir otro. Reutiliza las rutas de criterios que ya existían.

### Simulación: cuatro alcances

```
un partido        ¿y si este acaba así?
una jornada       ¿cómo quedan las tablas después de esta?
un grupo entero   ¿cómo acaba este grupo?
todo              ¿cómo acaba la fase?
```

La zona inferior se organiza **por jornada y dentro por grupo**, no al
revés: todos los grupos juegan su jornada 1 el mismo día. Agrupar primero
por grupo enseñaría el torneo en un orden que no ocurre.

---

## 9. Navegación

Round Robin y Fase de grupos pierden sus pestañas de edición, porque su
contenido vive ahora dentro:

| Pestaña | Antes | Ahora |
|---|---|---|
| Resumen | Sí | Sí, y con el acceso a Super Edición |
| Definición | Sí | Sí |
| **Super Edición** | — | **Nueva** |
| Reglas | Sí | Absorbida |
| Estructura | Sí | Absorbida |
| Entrada y salida | Sí | Absorbida — su ruta redirige al editor |
| Estructura (grupos) | Sí | Absorbida — su ruta redirige al editor |
| Simulador | Sí | Sí — es ejecución, no edición |

**Las rutas antiguas siguen vivas y funcionando.** `round-robin.show` y
`round-robin.structure` responden 200; solo dejaron de tener pestaña. Nada
que enlace a ellas se rompe.

Los tipos sin editor propio (Single Elimination, Group Stage, Swiss)
conservan sus pestañas intactas y su `/super` responde 404.

---

## 10. Casos probados

| Caso | Resultado |
|---|---|
| 4 participantes, una vuelta | 3 jornadas, 6 enfrentamientos |
| 8 participantes, una vuelta | 7 jornadas, 28 enfrentamientos |
| 8 participantes, ida y vuelta | 14 jornadas, 56 enfrentamientos |
| 8 → 6 participantes | 5 jornadas, 15 enfrentamientos |
| Entrada → Aleatorio | Orden y emparejamientos cambian |
| Aleatorio → Ranking torneo | Cambia |
| Ranking torneo → universal | Cambia |
| Ranking → Manual | Controles de ranking ocultos, flechas visibles |
| Manual, mover el primero | Emparejamientos de la jornada 1 cambian |
| Por puerta, cambiar capacidad | Reordena la tabla |
| Salidas sobre la tabla | Puestos 1–4 y 5–8 pintados con su color |
| 3 participantes (mínimo 4) | ✕ «La Fase requiere al menos 4 participantes» |
| 40 participantes (máximo 32) | ✕ «La Fase admite como máximo 32 participantes» |

Suite completa: 88 pasan / 99 fallan — idéntico al baseline anterior.

---

## 11. Qué falta

1. **El motor todavía no aplica `BY_GATE`** (liga) **ni el reparto por
   puerta** (fase de grupos). `RoundRobinLabEngine` entiende
   `RANDOM` y `RANKING`; el modo por puerta funciona en el editor pero el
   runtime aún lo trata como orden de llegada. `RoundRobinSeedRuleResolver::seatArrivals()`
   ya está escrito para que el motor lo use — falta enchufarlo y probarlo
   en el simulador.
2. **El recorte de jornadas tampoco lo aplica el motor todavía.** Se guarda
   y el editor lo respeta; falta que el runtime lo lea.
3. **Los otros dos motores** (Single Elimination y Swiss). El armazón está
   listo; falta la clase, las cuatro vistas y el módulo de JavaScript de
   cada uno.
4. **Sacar el formato de batalla de la fase de verdad**, cuando exista la
   capa de configuración del torneo real que lo reciba.
5. **Arrastrar para reordenar** en modo manual. Hoy son flechas, que
   funcionan y no dependen de una librería.
