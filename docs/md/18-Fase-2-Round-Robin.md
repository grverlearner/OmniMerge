# FASE 2 — ROUND ROBIN

> Documento de planificación e implementación (Fase 2 del [MASTER_PLAN.md](../../MASTER_PLAN.md), posterior al cierre de Single Elimination). Autocontenido: no requiere releer el código para entender qué se hizo.
>
> **Estado: IMPLEMENTADO Y VERIFICADO EN NAVEGADOR** (generación de calendario, jornadas, resultados, standings en vivo, desempates con empate real, resolución de puertas de salida, reinicio). Ver **"13. Notas de implementación"** al final para las 2 correcciones adicionales que surgieron durante la verificación (un bug de re-entrada en JS que afectaba también al simulador de Single Elimination, y un bug preexistente en el formulario compartido de Phase Exits que bloqueaba crear salidas para cualquier motor).

## 1. Estado actual de Round Robin

### Inventario exacto

| Capa | Archivo | Qué hace |
|---|---|---|
| Config (BD) | `database/migrations/2026_08_13_015721_create_phase_round_robin_settings_table.php` | `cycles` (1-10), `initial_order_mode`, `schedule_mode` (solo `BALANCED` implementado), `allow_draws`, `win_points`/`draw_points`/`loss_points` (decimal, configurable libremente), `default_best_of`, `cutoff_tie_policy` |
| Config (BD) | `..._create_phase_round_robin_tiebreakers_table.php` | Cadena de desempate: `criterion`, `direction` (AUTO/ASC/DESC), `sort_order`, único por `(phase_template_id, criterion)` |
| Modelos | `PhaseRoundRobinSetting.php`, `PhaseRoundRobinTiebreaker.php` | Casts + labels. Sin lógica de negocio. |
| Definición de dominio | `RoundRobinRankingDefinitionService.php` | Catálogo de criterios (`WINS`, `FEWEST_LOSSES`, `HEAD_TO_HEAD`, `SCORE_DIFFERENCE`, `SCORE_FOR`, `GAME_DIFFERENCE`, `GAME_WINS`, `SEED`), políticas de corte (`USE_TIEBREAKERS`/`MANUAL_RESOLUTION`/`RANDOM_RESOLUTION`/`INCLUDE_ALL_TIED`/`REQUIRE_PLAYOFF`), columnas de standings. Puramente declarativo. |
| Calendario (preview) | `RoundRobinScheduleCalculator.php` | Circle Method completo, maneja par/impar (slot `null` = descanso), múltiplos ciclos con alternancia de orientación. **Es solo para vista previa** (limitado a `previewRoundLimit`, no persiste nada). Tiene un método `calculateStructure()` reutilizable ya pensado para que Group Stage lo use sin necesitar una `PhaseTemplate` de tipo ROUND_ROBIN — patrón a imitar, no a romper. |
| Validación | `RoundRobinValidator.php` | Min/max/exact/múltiplo de `PhaseTemplate`, rango de ciclos, rechaza `schedule_mode` distinto de `BALANCED`. |
| Servicios de configuración | `RoundRobinSettingsService.php`, `RoundRobinTiebreakerService.php` | CRUD estándar + creación automática de 4 tiebreakers por defecto (`WINS`, `SCORE_DIFFERENCE`, `SCORE_FOR`, `HEAD_TO_HEAD`) la primera vez. |
| **Motor de ejecución real** | `RoundRobinLabEngine.php` (1004 líneas) | **Esto es lo más importante y menos esperado del análisis** — ver sección 2. |
| Motor compartido | `CutoffPolicyResolver.php` (`app/Services/Tournaments/CompetitionLab/Runtime/`) | Servicio genérico (no específico de RR) que resuelve las 5 políticas de empate en el corte, incluida `MANUAL_RESOLUTION`/`REQUIRE_PLAYOFF` con generación de una decisión manual compatible con `LabPhaseEngineManager::resolveDecision()`. Ya es de uso general. |
| Controladores | `RoundRobinController.php` (show/update), `RoundRobinTiebreakerController.php` (store/update/destroy/moveUp/moveDown) | CRUD estándar, autorización vía Form Requests, patrón idéntico al resto del proyecto. |
| Requests | `PreviewRoundRobinRequest`, `UpdateRoundRobinSettingsRequest`, `Store/UpdateRoundRobinTiebreakerRequest` | Correctos, `authorize()` bien implementado en los 4. |
| Vista | `resources/views/tournaments/phase-templates/round-robin.blade.php` (única página) + partials `round-robin-settings-form`, `round-robin-preview`, `round-robin-tiebreaker-form` | Una sola pantalla "Reglas": hero, quick stats, formulario de configuración + preview en vivo, explicación de puntuación, tabla de standings **vacía/decorativa** (`range(1,4)` con guiones), gestión de tiebreakers, y una sección "Outputs" final que **enlaza a una página que no gestiona nada** (ver sección 1.4). |
| Rutas | `routes/web.php:592-666` | Solo `round-robin.show`/`update` + 5 rutas de tiebreakers. **No existen** `round-robin.structure.*`, `round-robin.io.*` ni `round-robin.simulator.*`. |
| Navegación | `workspace-navigation.blade.php:121-137` | El bloque que agrega pestañas "Estructura"/"Entradas y salidas"/"Simulador" está condicionado literalmente a `$phaseTemplate->phase_type === 'SINGLE_ELIMINATION'`. **Round Robin hoy solo tiene 3 pestañas: Resumen, Definición, Reglas.** |
| Tests | `tests/Unit/Tournaments/RoundRobinScheduleCalculatorTest.php` (5 tests reales, solo cubre el calculador de preview) | `tests/Unit/Tournaments/CompetitionLab/RoundRobinLabEngineTest.php` **contiene únicamente `test_example()`** — el motor de ejecución real (1004 líneas, la lógica más compleja de todo Round Robin) tiene **cero cobertura real**. |

### Respuestas directas a las 18 preguntas del punto 3

1-3. Existe: configuración completa (ciclos, orden inicial, calendario, empates, puntuación, best of, política de corte) + motor de ejecución real bastante sofisticado.
4. Se guardan en `phase_round_robin_settings` (una fila por fase) + `phase_round_robin_tiebreakers` (N filas, cadena ordenada).
5. **Dos generadores distintos y no unificados**: `RoundRobinScheduleCalculator` (preview, Circle Method, limitado) para la pantalla de Reglas, y `RoundRobinLabEngine::schedule()` (Circle Method real, sin límite) para la ejecución en el Lab. Ambos implementan el mismo algoritmo por separado — no es duplicación peligrosa (son capas con propósitos distintos: diseño vs ejecución) pero sí redundancia de código que vale la pena señalar.
6. Vía `RoundRobinLabEngine::submit()`, delegando primero en `MatchSeriesRuntime::submitGame()` (reutilizado del motor compartido, exactamente igual que Single Elimination) para resolver series BO1-9 antes de registrar el resultado final del enfrentamiento.
7. `RoundRobinLabEngine::rank()`: puntos → cadena de tiebreakers configurados → SEED como último criterio, con `uasort` real sobre PJ/PG/PE/PP/Pts/SF/SC/DIF/partidas internas.
8. Participantes: mismo formato genérico que usa todo Competition Lab (`preview_id`/`name`/`seed`, sin `Entity`) — no hay nada específico de RR aquí, es 100% reutilizable.
9. Vía el contrato estándar de `PhaseTemplate` (`min_participants`/`max_participants`/`exact_participants`/`participant_multiple`), igual que cualquier otra fase.
10. Slot `null` en la rotación = descanso (`rest_seed`/`rest_participant` en el preview; en el motor real, el participante emparejado con `null` simplemente no genera match esa jornada). Correcto y ya probado (`test_five_participants_generate_rest_each_round`).
11. `cycles` (1 = una vuelta, 2 = ida y vuelta, hasta 10) con alternancia de orientación por ciclo (documentado explícitamente como "todavía no significa local/visitante", solo orden de presentación).
12. Estructura visual: **la tabla de standings en `round-robin.blade.php` es decorativa/vacía** (`range(1,4)` con guiones, comentario explícito "Todavía no existen resultados reales"). El preview de calendario (`round-robin-preview.blade.php`) sí es real pero no lo leí línea por línea (bajo riesgo, patrón visible en el resto del código).
13. Entradas: nada específico de RR — usa el contrato genérico de `PhaseTemplate`. Salidas: **el backend ya consume `PhaseExit` con selectores `TOP_N`/`BOTTOM_N`** (`RoundRobinLabEngine::prepare()` líneas 193-205), pero **no existe ninguna pantalla para crear/editar esas puertas** — el único enlace ("Configurar puertas de salida →") apunta a `tournaments.phase-templates.show#exits`, que es la página de Resumen genérica; su sección de gestión de puertas está condicionada a Single Elimination (enlaza a `single-elimination.structure.io`). **Confirmado: Round Robin no tiene ninguna UI de Entrada y salida hoy**, pese a que el motor ya sabe usarlas.
14. Simulación: no existe nada específico de RR. Solo existe a través del Competition Lab general (`/tournaments/lab`, requiere `TournamentTemplate`), igual que estaba Single Elimination antes de este Fase 1.5.
15. Ver Matriz de funcionalidades (sección 5) y hallazgos de la sección 2.
16. Reutilizable sin tocar: `RoundRobinLabEngine`, `CutoffPolicyResolver`, `MatchSeriesRuntime` (vía `LabPhaseEngineManager`), `RoundRobinRankingDefinitionService`, `RoundRobinScheduleCalculator`, toda la capa de configuración/tiebreakers.
17. Preparado en interfaz: patrón de tabs de fase (`workspace-navigation.blade.php`, solo hay que sumar una condición), patrón de formularios/tarjetas ya usado en Reglas, y **el simulador de Single Elimination recién construido resulta, por diseño, casi enteramente reutilizable para Round Robin** (ver sección 6).
18. Debe crearse: pestaña + página "Entrada y salida" (CRUD de `PhaseExit` para RR), pestaña + página "Simulador", tabla de standings real (ligada al simulador, ya que hoy no hay ningún lugar donde mostrarla con datos reales), y cerrar 2 gaps funcionales confirmados (sección 2).

## 2. Qué está bien (y es más de lo esperado)

- El **motor de ejecución real** (`RoundRobinLabEngine`) ya está construido con una sofisticación mayor a la que tenía Single Elimination en varios aspectos: calendario Circle Method correcto para par/impar/múltiples ciclos, cálculo de standings completo (PJ/PG/PE/PP/Pts/SF/SC/DIF + métricas de partidas internas), cadena de desempates real (no solo etiquetas), y **resolución de empates en el corte con 5 políticas reales, incluida resolución manual/playoff** vía `CutoffPolicyResolver` — esto es exactamente lo que Single Elimination solo tenía documentado como aspiración (ver Fase 1, hallazgo sobre políticas de empate de serie). Round Robin no necesita "inventar" este mecanismo — ya existe y funciona.
- Reutiliza `MatchSeriesRuntime` (BO1-9) sin ninguna lógica propia — series ya funcionan gratis.
- Reutiliza `PhaseExit`/el contrato de entrada de `PhaseTemplate` sin ninguna tabla nueva.
- El generador de calendario ya está aislado como método reutilizable (`calculateStructure()`) pensando en Group Stage — buen precedente arquitectónico a respetar.

## 3. Qué falta (hallazgos concretos, no genéricos)

### Bloqueantes para considerar Round Robin "completo" (nivel Single Elimination)

1. **`HEAD_TO_HEAD` es seleccionable como criterio de desempate (y es uno de los 4 criterios por defecto que se crean automáticamente) pero no tiene ningún efecto real.** `RoundRobinLabEngine::rank()` (línea ~741, `$fieldMap`) y `competitivelyTied()` (línea ~954) no incluyen `'HEAD_TO_HEAD'` en su mapa de campos — el `uasort` simplemente hace `continue` cuando encuentra ese criterio y pasa al siguiente. Es el mismo patrón de funcionalidad engañosa que se corrigió en Single Elimination durante la Fase 1 (P0: "la interfaz nunca debe prometer algo que el backend no cumple"). **Esto debe corregirse en Fase 2**, implementando la comparación real de resultados directos entre los participantes empatados.
2. **No existe ninguna interfaz para gestionar Phase Exits de Round Robin**, pese a que el motor ya las consume (`TOP_N`/`BOTTOM_N`). Sin esto, Round Robin no puede usarse en un torneo real (solo en el Lab/Simulador, donde sin exits configuradas todos los participantes terminan como "supervivientes" genéricos).
3. **El texto de la propia interfaz (`round-robin.blade.php`, sección "Outputs") promete selectores `RANK_POSITION` y `RANK_RANGE` que el motor no implementa** — `RoundRobinLabEngine::prepare()` solo filtra `whereIn('selector_type', ['TOP_N', 'BOTTOM_N'])`. Mismo patrón de brecha engañosa que el punto 1.
4. **Cero cobertura de test del motor real** (`RoundRobinLabEngineTest.php` solo tiene `test_example()`). No es bloqueante para implementar, pero es un riesgo real dado que ahí vive toda la lógica de clasificación/desempate/corte — igual que el hallazgo del Master Plan original sobre `VersionResolverService`.
5. **No hay pestaña "Entrada y salida" ni "Simulador"** en la navegación de la fase — bloqueado a nivel de `workspace-navigation.blade.php` (condición dura a `SINGLE_ELIMINATION`).
6. **Tabla de standings es decorativa** en la única vista existente — no hay ningún lugar del proyecto hoy donde se muestre una clasificación de Round Robin con datos reales.

### No bloqueantes, documentar y posponer

- Duplicación leve entre `RoundRobinScheduleCalculator` (preview) y `RoundRobinLabEngine::schedule()` (ejecución) — mismo algoritmo, dos implementaciones. No se toca en Fase 2 salvo que al implementar se detecte un riesgo real de divergencia; no es indispensable para cerrar Round Robin.
- `schedule_mode` solo soporta `BALANCED` — el propio código ya declara que la arquitectura queda abierta a más estrategias en el futuro; no es una carencia de Fase 2.

## 4. Qué debe ser Round Robin en OmniMerge (modelo funcional objetivo)

**Decisión central: Round Robin NO necesita una "Estructura" al estilo Single Elimination.** SE necesita una pantalla de Estructura porque su bracket admite modo Avanzado con grafo interno editable a mano (K→Q, slots, conexiones persistidas). Round Robin es **enteramente determinístico**: dados N participantes + configuración, el calendario completo ya se puede calcular sin ninguna decisión de diseño adicional (no hay "modo avanzado" ni edición manual de jornadas). Por lo tanto:

- **No se crea ninguna tabla de estructura persistida** para Round Robin (no `phase_round_robin_rounds`/`_matches`). El calendario se sigue calculando en memoria (como ya hace `RoundRobinLabEngine::schedule()`), nunca se diseña "a mano".
- **No se crea una pestaña "Estructura".** Lo que en SE es "Estructura" aquí ya vive parcialmente en la vista previa de la propia pestaña Reglas (`round-robin-preview.blade.php`) — se mantiene ahí, no se separa innecesariamente.
- **Sí se crea "Entrada y salida"**: gestión de `PhaseExit` (reutilizando el modelo/controlador ya genérico `PhaseExitController`, extendiendo su alcance de selectores soportados por el motor RR).
- **Sí se crea "Simulador"**: para poder generar un calendario real, jugarlo y ver clasificación/cortes sin un torneo — igual objetivo que el Simulador de SE, arquitectura de bajo nivel muy reutilizable (ver sección 6).

### Configuración — qué aplica y qué no

| Concepto pedido en el enunciado | ¿Aplica a Round Robin? | Estado |
|---|---|---|
| Min/max participantes | Sí | Ya existe (contrato genérico de `PhaseTemplate`) |
| Una vuelta / ida y vuelta | Sí | Ya existe (`cycles`) |
| Generación de calendario | Sí | Ya existe (Circle Method, ambas capas) |
| Jornadas/rounds | Sí | Ya existe (`rounds`, `rounds_per_cycle`, `current_round`) |
| Orden de encuentros | Sí | Ya existe (`initial_order_mode`: INPUT_ORDER/RANDOM/RANKING/MANUAL) |
| Descanso/BYE en impar | Sí | Ya existe (slot `null`, correcto) |
| Criterios de clasificación | Sí | Ya existe (Puntos fijo + cadena configurable) |
| Puntos victoria/empate/derrota | Sí | Ya existe (decimal libre) |
| Criterios de desempate | Sí, con 1 corrección | Ya existe salvo HEAD_TO_HEAD (bug confirmado) |
| Cantidad de clasificados | Sí | Vía Phase Exits TOP_N/BOTTOM_N (falta UI) |
| Eliminación | **No aplica** | Round Robin no elimina during la fase — todos juegan todas las jornadas. La "eliminación" ocurre únicamente como resultado de no ser seleccionado por una Phase Exit al finalizar. No se implementa ningún concepto de eliminación intra-fase. |
| Entradas | Sí | Contrato genérico, sin necesidad de nada nuevo |
| Salidas | Sí, con extensión | TOP_N/BOTTOM_N ya funcionan; se evalúa sumar RANK_POSITION/RANK_RANGE (ver sección 7) |
| Comportamiento al terminar | Sí | Ya existe (`finalizeCutoffs()`, `status = COMPLETED`, `survivor_ids`) |

## 5. Matriz de funcionalidades

| Funcionalidad | Estado actual | Objetivo Fase 2 | Acción |
|---|---|---|---|
| Configuración | Completa | Completa | Ninguna (ya cumple el objetivo) |
| Generación de calendario | Completa en 2 capas (preview + runtime) | Sin cambios | Ninguna |
| Jornadas | Completo | Sin cambios | Ninguna |
| BYE/descanso | Completo y correcto | Sin cambios | Ninguna |
| Resultados | Completo (reutiliza `MatchSeriesRuntime`) | Sin cambios | Ninguna |
| Standings (motor) | Completo | Sin cambios | Ninguna |
| Standings (interfaz) | Decorativa, sin datos reales | Real, dentro del Simulador | Construir panel de standings del simulador |
| Desempates | Funcional salvo HEAD_TO_HEAD | Los 8 criterios con efecto real | Corregir `RoundRobinLabEngine::rank()`/`competitivelyTied()` |
| Cortes/política de empate | Completo (5 políticas + decisión manual) | Sin cambios | Ninguna |
| Entradas | Completo (contrato genérico) | Sin cambios | Ninguna |
| Salidas (motor) | TOP_N/BOTTOM_N | + RANK_POSITION/RANK_RANGE | Extender `cutoff_exits` en el engine |
| Salidas (interfaz) | Inexistente | Gestión real desde la fase | Nueva pestaña "Entrada y salida" |
| Estructura (interfaz) | No existe, no debería copiarse de SE | Se mantiene dentro de Reglas | Ninguna acción nueva (decisión de diseño, no una carencia) |
| Simulador | No existe | Sí, específico de RR | Nueva pestaña "Simulador" + adaptación de `PhaseSimulatorService` |
| Interfaz (navegación) | 3 pestañas | 5 pestañas | Extender `workspace-navigation.blade.php` |
| Tests del motor runtime | Prácticamente nulos | Fuera de alcance salvo lo estrictamente necesario | Ver sección 11 (reglas de implementación) |

## 6. Simulador de Round Robin — qué debe incluir y qué no

### Reutilización directa (sin tocar) de lo construido en la Fase 1.5

- `LabStateTokenService` — igual, sin cambios.
- `SimulatorParticipantFactory` — genérico, ya no depende de `TournamentStart` ni de Single Elimination. Se usa tal cual.
- `LabPhaseEngineManager` — ya despacha por `phase_type`, ya soporta `ROUND_ROBIN` (`$this->roundRobin` inyectado). Sin cambios.
- `PhaseSimulatorStateFactory` — genérica (arma `{participants, runtime: null}`), sin cambios.
- El patrón de rutas/controlador/Form Requests (`SingleEliminationSimulatorController` como plantilla a clonar con su propio namespace `RoundRobinSimulatorController`).
- El patrón de token + `sessionStorage` + endpoint único de acción en el frontend (`resources/js/tournaments/single-elimination/simulator.js` como referencia de arquitectura, no de contenido).

### Adaptación necesaria (no duplicación, ajuste puntual)

- **`PhaseSimulatorService::applyRuntimeState()` debe dejar de asumir que siempre hay que llamar a `RuntimeOutcomeResolver::resolve()` al completarse la fase.** Hallazgo concreto: `RoundRobinLabEngine::finalizeCutoffs()` **ya resuelve sus propias salidas internamente** (usa `CutoffPolicyResolver`, no `RuntimeOutcomeResolver`) y dejam el resultado en `$runtime['outcomes']` con una forma distinta. Si el simulador vuelve a invocar `RuntimeOutcomeResolver` genérico sobre un runtime de Round Robin, se obtiene una **segunda resolución redundante y potencialmente distinta** a la que el motor real ya decidió — un bug de coherencia, no solo cosmético. La solución correcta: en `PhaseSimulatorService`, cuando `phase_type === 'ROUND_ROBIN'` (y por extensión cualquier motor que resuelva sus propias salidas, como se descubra en Group Stage/Swiss más adelante), usar directamente `$runtime['outcomes']` ya calculado; solo invocar `RuntimeOutcomeResolver` para motores que no resuelven internamente (hoy, solo Single Elimination). Cambio pequeño, con justificación clara, no es "un segundo motor".
- **Manejo de decisión manual de corte** (`AWAITING_DECISION` con `manual_decision.type` en `CUTOFF_SELECTION`/`PLAYOFF_SELECTION`): el simulador debe reutilizar el mismo patrón de banner de decisión pendiente que ya se construyó para SE (`partials/simulator/manual-decision.blade.php`), pero **debe soportar un tercer tipo de decisión** que SE no tiene (SE solo usa `SINGLE_ELIMINATION_SETUP`). La UI debe distinguir "seed/BYE manual" (SE) de "selección de corte/playoff" (RR) — mismo componente base, rama de contenido distinta según `decision.type`.

### Contenido específico del simulador de Round Robin

Debe incluir:
- Constructor de participantes (idéntico al de SE, reutilizado tal cual).
- Botón "Generar simulación" → `PREPARE_PHASE` (reutilizado).
- **Vista de jornadas** (no bracket): cada jornada como sección expandible con sus encuentros — patrón "ronda como columna" ya construido para SE, pero sin conexiones de bracket (Round Robin no tiene rutas entre partidos).
- Formulario de resultado por encuentro (score, reutilizado tal cual del simulador de SE — Round Robin nunca usa selección K→Q).
- **Tabla de standings en vivo**, recalculada tras cada resultado (`runtime.standings`, ya la calcula el motor) — pieza nueva, no existe en ningún simulador previo.
- Indicador de quién descansa cada jornada (`rest_participant`/slot `null` → mostrar "Descansa" en vez de dejarlo vacío).
- Banner de decisión manual de corte (adaptado, ver arriba).
- Panel de salidas resueltas — reutiliza el panel visual ya construido para SE, pero alimentado desde `runtime.outcomes` directamente (no desde `RuntimeOutcomeResolver`, ver adaptación arriba).
- Reinicio/nueva simulación — idéntico al de SE.

NO debe incluir:
- Ningún editor de estructura o modo avanzado/manual de calendario (Round Robin no lo necesita, ver sección 4).
- Ningún concepto de "BYE manual" en el sentido de SE (Round Robin no tiene BYEs de bracket; el "descanso" es automático y determinado por la rotación, no seleccionable).
- Selección de clasificados tipo K→Q (Round Robin siempre resuelve por score).

## 7. Interfaz — navegación específica de Round Robin

```
Resumen | Definición | Reglas | Entrada y salida | Simulador
```
(sin "Estructura" — decisión justificada en sección 4)

| Pestaña | Contenido específico de Round Robin |
|---|---|
| Resumen | Igual patrón que hoy (genérico de `PhaseTemplate`), sin cambios de alcance en Fase 2. |
| Definición | Sin cambios (genérico). |
| Reglas | La página actual, sin rehacer nada — solo se retira la sección "Outputs" del final (se reemplaza por la nueva pestaña dedicada) para no duplicar el mismo contenido en dos sitios. |
| **Entrada y salida** (nueva) | CRUD de `PhaseExit` reutilizando el patrón de formulario ya construido para SE (`single-elimination-io-manager.blade.php` como referencia de UI, no de lógica — los campos son genéricos de `PhaseExit`, no específicos de SE). Selectores ofrecidos: `TOP_N`, `BOTTOM_N`, `RANK_POSITION`, `RANK_RANGE`, `ALL`, `REMAINING` — se excluyen deliberadamente `MATCH_WINNERS`/`MATCH_LOSERS` (no tienen sentido conceptual en Round Robin, donde no hay un único "partido decisivo" por participante) y `ON_ELIMINATION` (Round Robin no elimina en tiempo real). |
| **Simulador** (nueva) | Ver sección 6. |

Extensión necesaria en `workspace-navigation.blade.php`: el bloque condicional de las líneas 121-137 debe generalizarse. Recomendación concreta: reemplazar `$phaseTemplate->phase_type === 'SINGLE_ELIMINATION'` por una tabla de configuración por `phase_type` (qué pestañas adicionales le corresponden a cada motor), en vez de otra condición hardcodeada — esto además deja lista la extensión natural para Group Stage/Swiss sin volver a tocar este archivo cada vez.

## 8. Arquitectura propuesta (resumen de decisiones)

1. **Backend de ejecución: cero motor nuevo.** `RoundRobinLabEngine` ya existe y es correcto — solo se corrige el bug de `HEAD_TO_HEAD` y se extiende el filtro de `cutoff_exits`.
2. **Simulador: reutilización de ~90% de la Fase 1.5**, con una adaptación puntual y justificada en `PhaseSimulatorService` (fuente de outcomes según motor) y un tercer tipo de decisión manual en el frontend.
3. **Interfaz nueva**: 2 pestañas (Entrada y salida, Simulador) siguiendo el lenguaje visual ya establecido, sin inventar componentes nuevos de bajo nivel (reutilizar cards, badges, patrón de formularios).
4. **Sin tablas nuevas.** Todo lo necesario ya existe (`phase_round_robin_settings`, `phase_round_robin_tiebreakers`, `phase_exits` genérico).
5. **Sin tocar Single Elimination.** El único archivo compartido que se toca es `PhaseSimulatorService` (para el punto 2) y `workspace-navigation.blade.php` (para el punto 3) — ambos cambios son aditivos/condicionales por `phase_type`, no alteran el comportamiento existente de SE.

## 9. Plan de implementación

### Paso 1 — Corregir bugs confirmados del motor (backend, bajo riesgo)
- **Qué se hará**: agregar `'HEAD_TO_HEAD' => 'head_to_head'` (o el campo que corresponda) al `$fieldMap` de `RoundRobinLabEngine::rank()` y a `competitivelyTied()`, implementando el cálculo real de enfrentamiento directo (comparar resultados de los partidos ya jugados entre los participantes empatados dentro de `$runtime['rounds']`).
- **Archivos**: `app/Services/Tournaments/CompetitionLab/Engines/RoundRobinLabEngine.php`.
- **Reutiliza**: la misma estructura de `$runtime['rounds']`/`matches` ya existente.
- **Nuevo**: función auxiliar de head-to-head (probablemente recorrer los matches ya completados entre exactamente los IDs empatados).
- **Dependencias**: ninguna.
- **Riesgo**: bajo — es lógica aislada y acotada, pero al no haber tests del motor, verificar manualmente con un caso de 3+ participantes empatados en puntos.

### Paso 2 — Extender selectores de salida soportados por el motor
- **Qué se hará**: ampliar el filtro `whereIn('selector_type', [...])` en `RoundRobinLabEngine::prepare()` para incluir `RANK_POSITION`/`RANK_RANGE` (ya calculables con el mismo `CutoffPolicyResolver` — solo cambia cómo se calcula `take`/el rango inicial a partir del selector).
- **Archivos**: mismo archivo del Paso 1.
- **Reutiliza**: `CutoffPolicyResolver` sin cambios.
- **Riesgo**: bajo-medio — `RANK_RANGE` implica tomar un sub-rango, no un prefijo desde el top; revisar si `CutoffPolicyResolver::resolve()` (pensado para "top N") necesita un ajuste de firma o si conviene resolver el rango antes de pasarlo. Decidir en el momento de implementar, no ahora.

### Paso 3 — Pestaña y vista "Entrada y salida"
- **Qué se hará**: nuevo controlador (o reutilización del `PhaseExitController` genérico ya existente, agregándole un método `index`/`show` que hoy no tiene) + nueva vista `round-robin-io.blade.php` con el CRUD de `PhaseExit` filtrado a los selectores relevantes de la sección 7.
- **Archivos nuevos**: `resources/views/tournaments/phase-templates/round-robin-io.blade.php`, posible `resources/views/tournaments/phase-templates/partials/round-robin-exit-form.blade.php`.
- **Archivos modificados**: `app/Http/Controllers/Tournaments/PhaseExitController.php` (agregar `index`, o crear un controlador propio si `PhaseExitController` no debe volverse "genérico de vista" — decidir en implementación cuál ensucia menos la arquitectura existente), `routes/web.php` (nueva ruta `round-robin.io.show` o reutilizar una ruta genérica `phase-exits.index`), `workspace-navigation.blade.php`.
- **Reutiliza**: modelo `PhaseExit`, requests de exits ya existentes (`StorePhaseExitRequest`/`UpdatePhaseExitRequest`, confirmar que no están acopladas a SE).
- **Riesgo**: medio — es la pieza con más decisiones de diseño abiertas (¿vista genérica reutilizada por RR y futuros motores, o vista propia de RR?). Recomendación: vista propia de Round Robin por ahora (más simple, menos riesgo de acoplar prematuramente), dejando la generalización para cuando Group Stage/Swiss lo necesiten también.

### Paso 4 — Backend del Simulador
- **Qué se hará**: adaptar `PhaseSimulatorService::applyRuntimeState()` para leer `$runtime['outcomes']` directamente cuando el motor ya los resuelve internamente (Round Robin), en vez de invocar `RuntimeOutcomeResolver` incondicionalmente.
- **Archivos**: `app/Services/Tournaments/CompetitionLab/PhaseSimulatorService.php` (modificado, no reescrito).
- **Nuevo**: `app/Http/Controllers/Tournaments/RoundRobinSimulatorController.php`, `app/Http/Requests/Tournaments/InitializeRoundRobinSimulatorRequest.php`, `ExecuteRoundRobinSimulatorActionRequest.php` — clones directos de sus equivalentes de SE, cambiando solo `phase_type`/nombres de ruta.
- **Reutiliza**: `SimulatorParticipantFactory`, `PhaseSimulatorStateFactory`, `LabPhaseEngineManager` sin cambios.
- **Dependencias**: Pasos 1-2 (para que el motor esté correcto antes de exponerlo en el simulador).
- **Riesgo**: bajo — es principalmente repetición de un patrón ya construido y verificado en el navegador durante la Fase 1.5.

### Paso 5 — Frontend del Simulador
- **Qué se hará**: nueva vista `round-robin-simulator.blade.php` + partials (`participants-builder` reutilizado tal cual del de SE, `matchdays-viewer` nuevo con el patrón "jornada expandible", `standings-panel` nuevo, `manual-decision` extendido con la rama de corte/playoff, `exits-panel` reutilizado con la fuente de datos ajustada). Nuevo `resources/js/tournaments/round-robin/simulator.js` (adaptación de `single-elimination/simulator.js`, quitando lo específico de bracket/K→Q, agregando `standings()`/`restingParticipant(round)`).
- **Archivos**: ver arriba.
- **Reutiliza**: el 100% del patrón de token/`sessionStorage`/fetch único de `simulator.js` de SE, el componente de constructor de participantes tal cual, el panel de salidas con solo el cambio de fuente de datos.
- **Riesgo**: bajo — es la parte más mecánica del plan dado que ya existe una implementación de referencia funcionando.

### Paso 6 — Navegación
- **Qué se hará**: generalizar el bloque condicional de `workspace-navigation.blade.php` para que Round Robin obtenga sus propias pestañas ("Entrada y salida", "Simulador") sin la condición `=== SINGLE_ELIMINATION`.
- **Archivos**: `resources/views/tournaments/phase-templates/partials/workspace-navigation.blade.php`.
- **Riesgo**: bajo, pero es el único punto que toca código compartido con SE — probar que SE conserva exactamente sus mismas 6 pestañas después del cambio.

### Paso 7 — Documentación
- **Qué se hará**: actualizar este mismo documento con el resultado real (igual que se hizo con el de Single Elimination/Simulador) y registrar cualquier desviación.
- **Archivo**: este documento (`docs/md/18-Fase-2-Round-Robin.md`).

## 10. Archivos principales afectados (resumen)

**Backend modificado**: `RoundRobinLabEngine.php`, `PhaseSimulatorService.php`, `PhaseExitController.php` (o alternativa), `routes/web.php`.
**Backend nuevo**: `RoundRobinSimulatorController.php`, `Initialize/ExecuteRoundRobinSimulatorActionRequest.php`.
**Frontend modificado**: `workspace-navigation.blade.php`, `round-robin.blade.php` (retirar sección Outputs duplicada).
**Frontend nuevo**: `round-robin-io.blade.php`, `round-robin-simulator.blade.php` + partials, `resources/js/tournaments/round-robin/simulator.js`.
**Sin cambios**: todo el resto de Single Elimination, todos los modelos/migraciones de Round Robin (ya son suficientes).

## 11. Riesgos y decisiones importantes

- **Decisión ya tomada y justificada**: Round Robin no tendrá pestaña "Estructura" ni modo avanzado — es una decisión de diseño basada en que el calendario es 100% determinístico, no una carencia.
- **Decisión a confirmar en implementación**: si la vista de "Entrada y salida" de Round Robin debe ser propia o generalizar `PhaseExitController` con un `index`. Se recomienda empezar propia y generalizar después, cuando Group Stage/Swiss repitan la misma necesidad (evita sobre-diseñar ahora para un caso hipotético).
- **Riesgo real**: el motor de ranking/corte no tiene tests, y se le va a modificar (HEAD_TO_HEAD) y extender (RANK_POSITION/RANK_RANGE). Cualquier caso borde con desempates múltiples debe verificarse manualmente con cuidado (la guía de pruebas manuales del informe final, tras implementar, debe cubrir explícitamente 3+ participantes empatados en puntos).
- **No romper Single Elimination**: los dos únicos puntos de contacto (`PhaseSimulatorService`, `workspace-navigation.blade.php`) deben modificarse de forma aditiva/condicional, nunca reemplazando el camino que ya usa SE.

## 12. Qué queda fuera de Fase 2

- Group Stage y Swiss (Fases 3 y 4 del Master Plan).
- Unificar `RoundRobinScheduleCalculator` y `RoundRobinLabEngine::schedule()` en un solo generador (deuda técnica documentada, no bloqueante).
- Generalizar la gestión de Phase Exits en una vista compartida entre motores (se hace propia de RR por ahora).
- Tests automatizados del motor, salvo que durante la implementación se determine que son estrictamente necesarios para verificar un cambio riesgoso (regla general de esta fase: pruebas manuales, no baterías automatizadas).
- Cualquier cambio a `schedule_mode` más allá de `BALANCED`.
- Runtime persistente / torneos reales con Round Robin (Fases posteriores del Master Plan).

## 13. Notas de implementación

La Fase 2 se implementó siguiendo este plan tal como está descrito, sin desviaciones de arquitectura. Se verificó de punta a punta en el navegador (generación de calendario con 4 participantes, 3 jornadas por Circle Method, resultados simulados jornada por jornada, standings en vivo con reordenamiento real de empates, resolución de puertas de salida TOP_N, reinicio). Durante esa verificación aparecieron dos bugs reales que **no estaban en el plan original** porque solo se manifiestan al ejecutar la aplicación real, no al leer el código estáticamente. Ambos eran indispensables de corregir para que el simulador de Round Robin funcionara, así que se corrigieron dentro del alcance de esta fase:

- **Bug de re-entrada en `generateSimulation()` (JavaScript)**: la función ponía `this.loading = true` y, sin liberarlo, llamaba a `await this.execute('PREPARE_PHASE')` — pero `execute()` tiene su propio guard de re-entrada (`if (this.loading) return;`), así que esa llamada interna se autobloqueaba en silencio: el calendario nunca se generaba y la pantalla quedaba congelada en "Generando la simulación…" para siempre. Se corrigió liberando `loading` antes de encadenar la llamada. Este mismo patrón ya existía en `resources/js/tournaments/single-elimination/simulator.js` (el simulador de Single Elimination) — se corrigió ahí también, ya que es exactamente la causa raíz más probable del bug que reportaste anteriormente sobre "Simular"/"Simular ronda actual".
- **Bug preexistente en `exit-form.blade.php`** (compartido entre todos los motores, no es específico de Round Robin ni fue introducido en esta fase): el campo `exit_timing` tenía dos elementos con el mismo `name` — un `<select>` visible y un `<input type="hidden">` que solo se ocultaba con `x-show` (no se deshabilitaba). Como `x-show` no impide el envío del formulario, el input oculto viajaba siempre con el valor `ON_ELIMINATION`, y al ser el último en el DOM, sobrescribía silenciosamente cualquier otra opción elegida — bloqueando la creación de cualquier puerta de salida que no fuera de tipo eliminación. Se corrigió añadiendo `:disabled` complementario a ambos campos para que solo uno se envíe a la vez. Esto también beneficia directamente a Single Elimination, que usa el mismo formulario.

Fuera de estas dos correcciones, no hubo ninguna otra desviación: la arquitectura, los archivos y las decisiones de diseño coinciden exactamente con lo planeado en las secciones 1-12.
