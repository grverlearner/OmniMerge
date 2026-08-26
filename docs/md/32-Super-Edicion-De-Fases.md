# SUPER EDICIÓN DE FASES

**Primera implementación: Round Robin.**
Arquitectura pensada para que Single Elimination, Group Stage y Swiss se
enchufen después sin rehacer el armazón.

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
        ├── SingleEliminationSuperEditor    ← previsto
        ├── GroupStageSuperEditor           ← previsto
        └── SwissSuperEditor                ← previsto
```

Para añadir un motor: escribir la clase que implemente el contrato, y una
línea en `PhaseSuperEditorRegistry::EDITORS`. El controlador, las rutas, el
armazón, la cabecera, el panel de puertas y la zona de jornadas **no se
tocan**.

### El contrato

```php
phaseType()          string          ROUND_ROBIN, SINGLE_ELIMINATION...
configView()         string          vista Blade del panel izquierdo
stageView()          string          vista Blade del escenario central
payload($phase, $user, $overrides)   todo lo que la pantalla necesita
persist($phase, $data)               guarda lo que pertenece a la fase
persistenceRules()   array           reglas para el FormRequest
```

### Vistas

```
super/editor.blade.php                armazón: reparte la ventana
super/partials/header.blade.php       identidad + estado + guardar
super/partials/gates.blade.php        entradas y salidas
super/partials/schedule.blade.php     jornadas
super/round-robin/config.blade.php    panel izquierdo   (hueco del motor)
super/round-robin/stage.blade.php     escenario central (hueco del motor)
```

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

## 9. Navegación

Round Robin pierde dos pestañas, porque su contenido vive ahora dentro:

| Pestaña | Antes | Ahora |
|---|---|---|
| Resumen | Sí | Sí, y con el acceso a Super Edición |
| Definición | Sí | Sí |
| **Super Edición** | — | **Nueva** |
| Reglas | Sí | Absorbida |
| Estructura | Sí | Absorbida |
| Entrada y salida | Sí | Absorbida — su ruta redirige al editor |
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

1. **El motor todavía no aplica `BY_GATE`.** `RoundRobinLabEngine` entiende
   `RANDOM` y `RANKING`; el modo por puerta funciona en el editor pero el
   runtime aún lo trata como orden de llegada. `RoundRobinSeedRuleResolver::seatArrivals()`
   ya está escrito para que el motor lo use — falta enchufarlo y probarlo
   en el simulador.
2. **El recorte de jornadas tampoco lo aplica el motor todavía.** Se guarda
   y el editor lo respeta; falta que el runtime lo lea.
3. **Los otros tres motores.** El armazón está listo; falta la clase de cada uno.
4. **Sacar el formato de batalla de la fase de verdad**, cuando exista la
   capa de configuración del torneo real que lo reciba.
5. **Arrastrar para reordenar** en modo manual. Hoy son flechas, que
   funcionan y no dependen de una librería.
