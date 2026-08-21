# Simulador de Single Elimination

> Documento de planificación e implementación (Fase 1.5, posterior al cierre de Single Elimination — ver [16-Fase-1-Single-Elimination.md](16-Fase-1-Single-Elimination.md) y [MASTER_PLAN.md](../../MASTER_PLAN.md)). Autocontenido: no requiere releer el código para poder implementarse.
>
> **Estado: IMPLEMENTADO.** Este plan se ejecutó tal como está descrito, sin desviaciones en la arquitectura. La sección **"22. Notas de implementación"**, al final del documento, registra las únicas precisiones que surgieron al construirlo (ninguna cambia el diseño, solo lo aterrizan a nombres de archivo y comportamientos exactos verificados en el código real).

## 1. Objetivo

Agregar una sección **"Simulador"** dentro de la fase Single Elimination (`Phase Template → Single Elimination → Simulador`) que permita probar cómo se comporta la configuración actual de esa fase — bracket, seeding, pairing, BYE, series, K→Q, puertas de salida — con participantes ficticios, sin crear un torneo, sin agregar la fase a un grafo, y sin tocar ningún dato real. Debe reutilizar el motor real de ejecución (los mismos servicios que usa Competition Lab), no un motor paralelo simplificado.

## 2. Problema actual

Hoy, para comprobar si una configuración de Single Elimination funciona, hay que: crear una `TournamentTemplate`, agregar la fase al Tournament Graph (`TournamentPhaseNode`), configurar `TournamentStart`, entrar al Competition Lab, inicializar el grafo completo, y solo entonces se puede ejecutar esa fase. Esto es correcto para probar un torneo completo, pero es demasiado lento para la pregunta simple "¿esta configuración de Single Elimination hace lo que espero?" — que es exactamente lo que un diseñador de fases necesita responder mientras edita Reglas/Estructura/Entrada y salida.

## 3. Objetivo UX

```
Configuración de la fase (Reglas / Estructura / Entrada y salida)
                    ↓
              Simulador
                    ↓
        Resultado de la simulación
```

Debe sentirse como una herramienta de verificación rápida, no como una segunda pantalla de configuración ni como una copia visual del Competition Lab. Flujo objetivo: entrar a la fase → pestaña Simulador → generar N participantes ficticios en un clic → ver el bracket real → resolver encuentros → ver quién queda eliminado, quién avanza, y qué sale por cada puerta de salida.

## 4. Alcance

- Nueva pestaña "Simulador" dentro de la navegación de una `PhaseTemplate` de tipo `SINGLE_ELIMINATION`.
- Generación rápida de participantes ficticios (sin `Entity`, sin `TournamentStart`, sin persistencia).
- Ejecución real de la fase (modo Básico y Avanzado) reutilizando `LabPhaseEngineManager`/`SingleEliminationLabEngine`/`SingleEliminationGraphRuntime`/`LabManualDecisionManager` sin modificarlos.
- Visualización del bracket con resultados, BYE, series, K→Q.
- Registro de resultados (score o selección de clasificados según el tipo de encuentro).
- Resolución y visualización de puertas de salida al completarse la fase.
- Reinicio de la simulación y regeneración tras cambiar configuración.
- Aislamiento total de datos reales (ningún dato persiste en base de datos).

## 5. Fuera de alcance

- Round Robin, Group Stage, Swiss (simuladores futuros, no esta tarea).
- Ejecución de un Tournament Graph completo (múltiples fases conectadas) — eso ya lo hace el Competition Lab y no se duplica aquí.
- `ON_ELIMINATION` como enrutamiento en tiempo real hacia **otra fase** (no tiene sentido sin un grafo — ver sección 29).
- Participantes reales de la Biblioteca (`Entity`) — el simulador es exclusivamente de participantes ficticios; conectar Entidades reales es la Fase 7 del Master Plan (Torneos reales), no esta tarea.
- Persistencia de historial de simulaciones.
- Cualquier cambio a la lógica de negocio existente de Single Elimination (seeding, pairing, BYE, series, K→Q, routing) — todo eso ya fue auditado en la Fase 1 y no se toca.

## 6. Arquitectura actual relevante

```
PhaseTemplate (SINGLE_ELIMINATION)
   ├── singleEliminationSetting (configuration_mode, seeding_mode, pairing_mode, bye_assignment, series...)
   ├── singleEliminationRoundRules (series por ronda)
   ├── singleEliminationRounds → encounters → slots / results   (solo si configuration_mode=ADVANCED)
   ├── singleEliminationConnections
   ├── inputGates
   └── exits (PhaseExit)

Motores de ejecución (ya existen, ya operan sobre PhaseTemplate suelta):
   LabPhaseEngineManager::prepare($phase, $participantIds, $participants)
        → SingleEliminationLabEngine::prepare()          (modo BASIC)
        → SingleEliminationGraphRuntime::prepare()        (modo ADVANCED, si hay estructura activa)
   LabPhaseEngineManager::submit() / submitSelection() / simulateSelection()
   LabManualDecisionManager::preparationDecision($phase, $participantIds)   (seeding/BYE manual — solo BASIC)
   MatchSeriesRuntime (BO1-9 / FIXED_GAMES, compartido por ambos motores)
   RuntimeOutcomeResolver::select()   (resuelve puertas de salida sobre el runtime ya completado)
   LabStateTokenService::encode()/decode()   (estado cifrado, sin persistencia en BD)

Orquestación actual (SÍ acoplada a TournamentTemplate — NO se reutiliza tal cual):
   CompetitionLabService::prepareNode()   → exige $template->graphNodes()->find($nodeId)
   LabStateFactory::create($template, ...)   → arma estado desde graphStarts/graphNodes/graphTerminals
   PreviewParticipantFactory::generate(TournamentStart $start, ...)   → exige un TournamentStart real
   TournamentLabController   → todas sus rutas usan {tournamentTemplate}
   TournamentGraphRuntimeService   → orquesta el grafo completo (Starts→Nodes→Terminals), no aplica a una fase aislada
```

**Hallazgo clave de la investigación** (confirmado archivo:línea): los motores de ejecución (`LabPhaseEngineManager`, `SingleEliminationLabEngine`, `SingleEliminationGraphRuntime`, `LabManualDecisionManager`) **ya reciben `PhaseTemplate` como parámetro nativo** y no importan ni referencian `TournamentTemplate` en ningún punto. El acoplamiento al torneo vive exclusivamente en la capa de orquestación que los rodea (`CompetitionLabService`, `LabStateFactory`, `PreviewParticipantFactory`, `TournamentLabController`), no en los motores mismos. Esto es lo que hace viable el simulador sin tocar código existente: se construye una orquestación paralela y más delgada, y se reutilizan los motores tal cual.

## 7. Cómo funciona actualmente Single Elimination

**Backend**: una fase se configura vía `SingleEliminationController` (Reglas), `SingleEliminationStructureController` (Estructura/IO). Para ejecutarla, hoy solo existe el camino a través de `TournamentLabController` → `CompetitionLabService::execute()`, que despacha 9 acciones (`PREPARE_NODE`, `SUBMIT_MATCH_RESULT`, `SUBMIT_ENCOUNTER_RESULT`, `SIMULATE_MATCH`, `SIMULATE_ROUND`, `RESOLVE_MANUAL_DECISION`, `START_TOURNAMENT`, `STEP_RUNTIME`, `RUN_TOURNAMENT`) — las 3 últimas son de grafo completo, las 6 primeras operan sobre una fase individual dentro del grafo.

**Frontend**: cada fase tiene 5 secciones navegables por pestañas server-side (no SPA): Resumen, Definición, Reglas, Estructura, Entradas y salidas — implementadas como rutas Blade independientes, con el componente compartido `workspace-navigation.blade.php` resaltando la pestaña activa. El Competition Lab (`/tournaments/templates/{id}/lab`) es una página completamente aparte, a nivel de `TournamentTemplate`, con su propio `x-data="competitionLab(...)"` (Alpine.js) que mantiene el token de estado en `sessionStorage` y despacha todas las acciones a un único endpoint POST.

## 8. Capacidades reales disponibles

| Capacidad | Dónde vive hoy | Estado |
|---|---|---|
| Seeding INPUT_ORDER/RANDOM/RANKING (modo BASIC) | `SingleEliminationLabEngine::prepare()` | EXISTE Y FUNCIONA |
| Seeding MANUAL (modo BASIC) | `LabManualDecisionManager::singleEliminationDecision()` | EXISTE Y FUNCIONA |
| Seeding/Pairing/BYE en modo ADVANCED | — | NO EXISTE (documentado como brecha en Fase 1; interfaz ya avisa) |
| Pairing SEQUENTIAL/RANDOM/STANDARD_SEEDED (BASIC) | `SingleEliminationLabEngine::buildRoundPairings()` | EXISTE Y FUNCIONA |
| BYE automático (cantidades irregulares) | `SingleEliminationLabEngine::selectByeIds()` | EXISTE Y FUNCIONA |
| Series BO1-9 / FIXED_GAMES | `MatchSeriesRuntime` | EXISTE Y FUNCIONA |
| K→Q (modo ADVANCED) | `SingleEliminationGraphRuntime::submitSelection()` | EXISTE Y FUNCIONA |
| Puertas de salida (8 selectores) | `RuntimeOutcomeResolver::select()` | EXISTE Y FUNCIONA |
| ON_ELIMINATION (routing entre fases) | `TournamentGraphRuntimeService::timedEventsForExit()` | EXISTE PERO NECESITA ADAPTACIÓN (es de grafo, no de fase aislada — ver sección 29) |
| Generar participantes ficticios sin Start real | — | NO EXISTE (`PreviewParticipantFactory` exige `TournamentStart`) |
| Ejecutar una fase sin `TournamentTemplate` | — | NO EXISTE (orquestación actual exige `graphNodes()->find()`) |
| Token de estado cifrado sin BD | `LabStateTokenService` | EXISTE Y FUNCIONA (reutilizable tal cual) |
| Bracket visual con líneas de conexión | — | NO EXISTE EN NINGUNA PARTE DEL PROYECTO |
| Captura de resultado (score / selección K→Q) | `lab/partials/manual-engine.blade.php` | EXISTE (patrón reutilizable, atado hoy al Lab de torneo) |

## 9. Diseño funcional del simulador

Un `PhaseSimulatorService` nuevo, delgado, que:
1. Recibe una `PhaseTemplate` + un conteo de participantes ficticios (o una lista con nombres/seeds).
2. Genera esos participantes con un factory nuevo (sin `TournamentStart`).
3. Arma un estado inicial mínimo (participantes + snapshot de configuración) y lo codifica con `LabStateTokenService::encode()` (reutilizado tal cual).
4. Despacha acciones al mismo trío de motores que usa el Lab (`LabPhaseEngineManager` + `LabManualDecisionManager`), sin pasar por `CompetitionLabService`/`TournamentGraphRuntimeService`.
5. Al completarse la fase, resuelve las puertas de salida con `RuntimeOutcomeResolver` (reutilizado tal cual) para mostrar "quién saldría por cada puerta", sin enrutarlos a ningún sitio real.

## 10. Flujo de usuario

```
1. Preparar simulación
   → Fase: Phase Template → Single Elimination → Simulador
   → Se muestra la configuración detectada (modo, seeding, pairing, BYE, series, K→Q, exits)
   → Botón [Crear participantes]

2. Participantes
   → Botones rápidos: 4 / 8 / 16 / 32 (genera N participantes con nombre y seed automáticos)
   → Edición inline opcional: nombre, seed, "BYE manual" (si bye_assignment=MANUAL)
   → Botón [Generar simulación]

3. Estructura / Bracket
   → Se llama a PREPARE_PHASE (equivalente ligero de PREPARE_NODE)
   → Se muestra el bracket completo con el estado inicial (BYEs ya resueltos automáticamente)

4. Ejecutar
   → Por cada encuentro pendiente: capturar resultado (score BO-N) o seleccionar clasificados (K→Q)
   → Avance automático tras cada resultado (igual que hoy en el motor real)
   → Botón [Simular] por encuentro (resultado aleatorio, reutiliza el mismo "número mayor" del Lab) o [Simular todo]

5. Routing / salidas
   → Al completar la fase: panel de puertas de salida con quién sale por cada una
   → Aviso de ON_ELIMINATION: "esta puerta se resolvería en tiempo real dentro de un Tournament Graph real" (ver sección 29)

6. Resultado final
   → Campeón / posiciones / eliminados / participantes restantes / salidas producidas

7. Reiniciar / Regenerar
   → [Reiniciar simulación]: vuelve al paso 2 con los mismos participantes
   → [Nueva simulación]: descarta todo y vuelve al paso 1
   → Si la configuración de la fase cambió mientras había una simulación activa, se muestra un aviso y se exige regenerar
```

## 11. Diseño de interfaz

Interfaz propia, con identidad visual distinta a la del Competition Lab actual (que es de torneo completo), pero coherente con el resto de las pantallas de Single Elimination (mismos tokens de color: ámbar para acciones primarias, el mismo patrón de "card" con badges de estado). Tres zonas:

- **Panel de configuración detectada** (colapsable, arriba): resumen de solo lectura de lo que se va a probar — reutiliza el patrón de "Hero con métricas" ya usado en `single-elimination.blade.php`.
- **Panel de participantes** (visible solo antes de generar/tras reiniciar): grid de tarjetas pequeñas, botones de conteo rápido, edición inline.
- **Panel del bracket + ejecución** (vista principal una vez generada la simulación): columnas por ronda, cada encuentro como card expandible con el formulario de resultado inline cuando está pendiente.

## 12. Pantallas / estados

| Estado | Qué se muestra |
|---|---|
| `NO_SIMULATION` | Panel de configuración detectada + botón "Crear participantes" |
| `BUILDING_PARTICIPANTS` | Grid de participantes editable + botón "Generar simulación" |
| `RUNNING` | Bracket con encuentros pendientes/completados/BYE, formularios de resultado activos |
| `AWAITING_DECISION` | Banner de decisión manual pendiente (seeding manual, BYE manual) con su formulario — mismo patrón que `manual-decision.blade.php` |
| `COMPLETED` | Bracket final + panel de puertas de salida + resumen (campeón, eliminados, posiciones) |
| `STALE_CONFIG` | Banner de aviso: "la configuración de la fase cambió desde que generaste esta simulación" + botón "Regenerar" |

## 13. Componentes frontend reutilizables

| Componente actual | Qué aporta | Cómo se reutiliza |
|---|---|---|
| `x-tournament-layout` | Layout base de toda la sección de torneos | Igual, sin cambios |
| `workspace-navigation.blade.php` | Navegación por pestañas de la fase | Se le agrega una entrada más al array `$tabs` (ver sección 35) |
| `lab/partials/manual-engine.blade.php` (inputs de score, toggle de clasificados) | El único patrón existente de "capturar resultado de un encuentro" | Se adapta (no se copia literal, ver sección 14) para el nuevo componente del simulador |
| `lab/partials/manual-decision.blade.php` | UI de resolución de decisiones manuales (seeding/BYE manual) | Se reutiliza el patrón directamente |
| `phase-templates/partials/single-elimination-visualizer-blocks.blade.php` | Patrón "ronda como columna con cards de encuentro expandibles" | Es la base visual del bracket del simulador — hay que extenderlo con resultado real (score/ganador/BYE), que hoy no muestra |
| `resources/js/tournaments/lab/competition-lab.js` (patrón de token + sessionStorage + `execute()` por POST único) | Arquitectura cliente de máquina de estados | Se reutiliza el patrón (no el archivo) en un JS nuevo y más pequeño |
| Botones/badges de estado (`PENDING`/`COMPLETED`/`BYE`, colores) ya usados en `manual-engine.blade.php` | Vocabulario visual consistente | Reutilizar clases/badges tal cual |

## 14. Componentes frontend nuevos

| Componente nuevo | Por qué no existe uno reutilizable |
|---|---|
| Vista `single-elimination-simulator.blade.php` (nueva pestaña) | No existe ninguna vista de fase aislada ejecutable hoy |
| Generador rápido de participantes (botones 4/8/16/32 + edición inline) | No existe ninguna UI de creación de participantes fuera del flujo de `TournamentStart` |
| Bracket visual con resultados en vivo (extensión de `visualizer-blocks`) | El visualizador actual es de solo estructura/diseño, nunca muestra un score o ganador real |
| Alpine component `singleEliminationSimulator(...)` en `resources/js/tournaments/single-elimination/simulator.js` | Análogo reducido a `competition-lab.js`, pero sin lógica de grafo/Starts/Terminals |
| Panel de puertas de salida resueltas (resultado de simulación) | No existe ninguna vista que muestre el resultado de `RuntimeOutcomeResolver` de forma legible para el usuario |

## 15. Servicios backend reutilizables

| Servicio | Se reutiliza | Motivo |
|---|---|---|
| `LabPhaseEngineManager` | Directo, sin adaptar | Ya recibe `PhaseTemplate`, sin dependencia de grafo |
| `SingleEliminationLabEngine` | Directo, sin adaptar | Motor BASIC, ya aislado |
| `SingleEliminationGraphRuntime` | Directo, sin adaptar | Motor ADVANCED, ya aislado |
| `LabManualDecisionManager` | Directo, sin adaptar | Decisiones manuales (seeding/BYE), ya aislado |
| `MatchSeriesRuntime` | Directo, sin adaptar | Series BO-N/FIXED_GAMES, ya aislado |
| `RuntimeOutcomeResolver` | Directo, sin adaptar | Resolución de puertas de salida, opera sobre el runtime ya completado de una fase |
| `LabStateTokenService` | Directo, sin adaptar | Cifrado/descifrado de estado, no depende de torneo |
| `SingleEliminationValidator` / `SingleEliminationConfigurationInspector` | Directo, sin adaptar | Validación de configuración antes de preparar, ya opera sobre `PhaseTemplate` |

## 16. Servicios backend nuevos necesarios

| Servicio nuevo | Responsabilidad | Tamaño estimado |
|---|---|---|
| `SimulatorParticipantFactory` | Generar participantes ficticios (`count`, `prefix`, `seeds` opcionales) sin depender de `TournamentStart` | Pequeño — variante de `PreviewParticipantFactory::generate()` sin el parámetro `TournamentStart`, mismo formato de array de salida |
| `PhaseSimulatorStateFactory` | Armar el estado inicial mínimo `{ phase_template_id, user_id, participants, runtime: null }` | Pequeño |
| `PhaseSimulatorService` | Orquestar acciones (`PREPARE_PHASE`, `SUBMIT_MATCH_RESULT`, `SUBMIT_ENCOUNTER_RESULT`, `SIMULATE_MATCH`, `SIMULATE_ROUND`, `RESOLVE_MANUAL_DECISION`, `RESET`) delegando a `LabPhaseEngineManager`/`LabManualDecisionManager`, y resolver salidas con `RuntimeOutcomeResolver` cuando el runtime queda `COMPLETED` | Mediano — es el equivalente reducido de `CompetitionLabService`, pero sin las 3 acciones de grafo ni la lógica de `graphNodes()->find()` |

No se necesita ningún modelo Eloquent nuevo, ninguna migración, ningún `TournamentInstance`.

## 17. Manejo de estado

Igual que el Competition Lab: el estado completo de la simulación (participantes + runtime del motor) viaja en un token cifrado (`LabStateTokenService`) entre cliente y servidor en cada acción. El cliente lo persiste en `sessionStorage` bajo una clave `omnimerge:se-simulator:{user_id}:{phase_template_id}` (mismo patrón que `omnimerge:competition-lab:...`), para sobrevivir a un refresh de página sin necesitar backend.

## 18. Persistencia

**Decisión: completamente temporal (token cifrado + `sessionStorage`), sin tablas nuevas.** Justificación: el Competition Lab ya prueba que este patrón es suficiente para ejecutar un motor completo de Single Elimination sin tocar la base de datos (confirmado: cero `::create`/`save`/`update`/`DB::` en toda la capa `CompetitionLab`). No hay ninguna necesidad técnica de persistir una simulación — es exactamente el tipo de dato que el propio roadmap del proyecto ya clasifica como "no debe generar registros oficiales" (`docs/md/09-Para Futuro.md`, sección 25). Si en el futuro se quisiera "guardar una simulación para revisarla después", sería una mejora incremental sobre este mismo token (ej. guardarlo como texto en un campo, sin necesidad de tablas relacionales) — no se diseña ahora por no ser requerido.

## 19. Aislamiento de simulaciones

- El token de estado incluye `user_id` y `phase_template_id`; el servidor valida ambos en cada acción (mismo patrón que `validateOwnership()` del Lab actual, adaptado a `phase_template_id` en vez de `tournament_template_id`).
- Los participantes ficticios son arrays PHP puros con `entity_id: null` — nunca se crea una fila `entities`.
- Ninguna acción del simulador escribe en `phase_single_elimination_*` (esas tablas son de **definición** de estructura, no de resultados de ejecución) ni en ninguna tabla de torneo real.
- Las rutas del simulador se autorizan con la misma Policy que ya protege la fase (`can('update', $phaseTemplate)`) — nadie más que el propietario (o quien tenga permiso de edición) puede simular.
- El simulador nunca invoca `TournamentGraphRuntimeService` ni ninguna acción de grafo (`START_TOURNAMENT`/`STEP_RUNTIME`/`RUN_TOURNAMENT`), así que estructuralmente no puede tocar ningún `TournamentTemplate`.

## 20. Flujo de ejecución

```
POST /phases/{phaseTemplate}/single-elimination/simulator/initialize
   body: { count: 8 } o { participants: [{name, seed}, ...] }
   → SimulatorParticipantFactory::generate()
   → PhaseSimulatorStateFactory::create() → { participants }
   → PhaseSimulatorService::prepare() → LabPhaseEngineManager::prepare($phase, $participantIds, $participants)
   → LabStateTokenService::encode($state)
   ← { state, state_token }

POST /phases/{phaseTemplate}/single-elimination/simulator/action
   body: { action: 'SUBMIT_MATCH_RESULT', state_token, match_id, score_a, score_b }
   → PhaseSimulatorService::execute()
   → decode token → validar ownership → despachar a LabPhaseEngineManager::submit()
   → si $runtime['status'] === 'COMPLETED' → RuntimeOutcomeResolver::select() por cada PhaseExit → adjuntar al estado devuelto
   → encode nuevo estado
   ← { state, state_token }
```

## 21. Manejo de resultados

Dos formularios posibles por encuentro, exactamente como ya distingue `manual-engine.blade.php` hoy vía `usesQualifierSelection(match)`:
- **Duelo simple (DUEL, K=2/Q=1)**: dos inputs numéricos (`score_a`/`score_b`) → `SUBMIT_MATCH_RESULT`. Si el encuentro es una serie BO3+, se captura un juego a la vez y el propio `MatchSeriesRuntime` decide cuándo la serie está completa (reutilizado sin cambios).
- **Multi-competidor / K→Q**: botones toggle por participante con contador "X / Q elegidos" → `SUBMIT_ENCOUNTER_RESULT` (que internamente llama a `submitSelection()`).

## 22. Seeding

Se prueba pasando participantes con distintos `seed` en el paso de creación. En modo BASIC, el simulador debe permitir fijar `seed` manualmente por participante (para poder probar RANKING de forma determinista) y mostrar, tras `PREPARE_PHASE`, el orden resultante antes de generar el primer bracket — así el usuario puede confirmar visualmente que INPUT_ORDER/RANDOM/RANKING/MANUAL producen el orden esperado. En modo ADVANCED, el simulador debe mostrar el aviso ya agregado en Fase 1 ("sin efecto en modo Avanzado") también dentro del propio simulador, para que quede claro por qué el orden no cambia.

## 23. Pairing

Se prueba generando 8 participantes con seeds 1-8 y verificando visualmente el emparejamiento de primera ronda: SEQUENTIAL debe dar 1v2/3v4/5v6/7v8; STANDARD_SEEDED debe dar 1v8/4v5/2v7/3v6; RANDOM debe variar entre corridas (recordar: es determinista por `phase_template_id`, así que para ver un pairing distinto hay que iniciar una simulación con participantes distintos, no basta con "Reiniciar").

## 24. BYEs

El generador rápido de participantes debe permitir explícitamente cantidades no potencia de 2 (5, 6, 7, 10, 13 — los botones rápidos son 4/8/16/32, pero el campo de conteo manual acepta cualquier número). El bracket debe mostrar claramente qué participantes avanzan automáticamente por BYE en la ronda 1 (badge distintivo, ya existe el estado `'BYE'` en el runtime del motor).

## 25. Series

El formulario de resultado de un encuentro con `best_of > 1` debe capturar un juego a la vez y mostrar el marcador acumulado de la serie (`game_wins_a`/`game_wins_b` que ya expone `MatchSeriesRuntime`), cerrando automáticamente cuando se alcanza la mayoría — sin que el simulador tenga que calcular nada, solo mostrar lo que el motor ya devuelve.

## 26. K→Q / Advanced

Solo aplica en modo ADVANCED con estructura generada y validada. El simulador debe, antes de permitir iniciar la simulación en este modo, verificar que `structure_status === 'VALID'` (ya existe este campo, ver `single-elimination-settings-form` / matriz de estados en Fase 1) y bloquear con un mensaje claro si no lo está ("Genera y valida la estructura en la pestaña Estructura antes de simular").

## 27. Routing

Dentro de una fase aislada no hay "routing hacia otra fase" (eso es Tournament Graph). Lo que sí aplica y debe mostrarse es el routing **interno** de la estructura Avanzada (`PhaseSingleEliminationConnection`) — quién avanza a qué slot de qué encuentro — que ya se resuelve automáticamente en `SingleEliminationGraphRuntime` y ya se visualiza (sin resultados) en la pestaña Estructura. El simulador reutiliza ese mismo cálculo, mostrando el bracket ya con los participantes reales asignados a cada slot.

## 28. Phase Exits

Al completarse la fase (`runtime['status'] === 'COMPLETED'`), el simulador ejecuta `RuntimeOutcomeResolver::select()` para cada `PhaseExit` configurado y muestra un panel: "Puerta [nombre] ([selector_type]) → [lista de participantes]". Esto permite verificar los 8 selectores (`MATCH_WINNERS`, `MATCH_LOSERS`, `TOP_N`, `BOTTOM_N`, `RANK_POSITION`, `RANK_RANGE`, `ALL`, `REMAINING`) sin necesitar un Tournament Graph real detrás.

## 29. ON_ELIMINATION

Este es el único punto donde el simulador **no puede** reproducir el comportamiento real 1:1, porque `exit_timing = ON_ELIMINATION` está diseñado para enrutar en tiempo real hacia el **siguiente nodo de un Tournament Graph** (`TournamentGraphRuntimeService::timedEventsForExit()`), y una fase aislada no tiene un "siguiente nodo". Decisión: el simulador debe **mostrar el evento igualmente** (qué participante fue eliminado, en qué ronda, y por qué puerta con timing ON_ELIMINATION saldría) en cuanto ocurre — reutilizando la misma lógica de validación de selector (`ELIMINATED`/`ELIMINATED_IN_ROUND`/`MATCH_LOSERS`) — pero etiquetado claramente como **"se resolvería en tiempo real dentro de un Tournament Graph real"**, sin intentar simular el enrutamiento hacia una fase inexistente. Esto es honesto con el usuario (principio "no engañar" del propio roadmap) y no requiere reimplementar `TournamentGraphRuntimeService`.

## 30. Errores y validaciones

- Configuración inválida o estructura no generada/validada (modo Avanzado): bloquear "Crear participantes" con mensaje explicando qué falta, enlazando a la pestaña correspondiente (Reglas/Estructura).
- Participantes duplicados, cantidad fuera de rango (`min_participants`/`max_participants` de la `PhaseTemplate`): mismo mensaje de error que ya lanza `SingleEliminationLabEngine::fail()`, mostrado tal cual (reutilizar el texto del backend, no inventar mensajes nuevos en frontend).
- Decisión manual pendiente sin resolver: banner bloqueante (no se puede seguir sin resolverla), igual que hoy en el Lab.

## 31. Casos borde

Todos los de la sección 16 del pedido original del usuario están cubiertos por el diseño de las secciones 22-29 de este documento: 2/4/8/16 participantes (sección "Participantes" con conteo manual), BYEs irregulares 3/5/6/7/10/13 (sección 24), los 3 modos de seeding y 3 de pairing (secciones 22-23), las 6 variantes de series (sección 25), K=4/Q=2 y otras combinaciones (sección 26), los 8 selectores de salida (sección 28), ON_ELIMINATION (sección 29, con la limitación ya explicada), y configuraciones inválidas (sección 30).

## 32. Matriz de funcionalidades

| Funcionalidad | Simulable | Nota |
|---|---|---|
| Seeding (BASIC) | Sí | Completo |
| Seeding (ADVANCED) | No aplica | Sin efecto en el motor real; el simulador solo lo refleja |
| Pairing (BASIC) | Sí | Completo |
| BYE automático, todas las cantidades | Sí | Completo |
| BYE manual | Sí | Vía decisión manual, reutilizada |
| Series BO1-9 / FIXED_GAMES | Sí | Completo |
| K→Q (ADVANCED) | Sí | Requiere estructura VALID |
| Puertas de salida (8 selectores) | Sí | Completo, vía `RuntimeOutcomeResolver` |
| ON_ELIMINATION | Parcial | Se muestra el evento, no el enrutamiento real (no aplica sin grafo) |
| Multi-fase / Tournament Graph | No | Fuera de alcance por diseño |
| Entidades reales de Biblioteca | No | Fuera de alcance (Fase 7 del Master Plan) |

## 33. Reutilización de código

Ver tablas de las secciones 13 y 15. Resumen: **cero cambios** a los motores de ejecución existentes; toda la lógica de dominio (seeding, pairing, BYE, series, K→Q, routing, exits) se reutiliza sin tocar una sola línea. El código nuevo es exclusivamente de orquestación (factory de participantes sin `TournamentStart`, servicio delgado sin lógica de grafo) y de presentación (vista + Alpine component nuevos, extensión del visualizador de bloques para mostrar resultados).

## 34. Código que NO debe duplicarse

Explícitamente prohibido reimplementar: `SingleEliminationBracketCalculator` (fórmula de BYE), `SingleEliminationLabEngine::selectByeIds/buildRoundPairings/canonicalSeedOrder` (seeding/pairing/BYE del motor BASIC), `SingleEliminationGraphRuntime` (motor ADVANCED completo), `MatchSeriesRuntime` (series), `RuntimeOutcomeResolver` (puertas de salida), `LabStateTokenService` (cifrado de estado). El nuevo `PhaseSimulatorService` debe ser una capa de **orquestación delgada** que delega en estos, nunca una reimplementación paralela.

## 35. Archivos que probablemente habrá que modificar

| Archivo | Cambio |
|---|---|
| `resources/views/tournaments/phase-templates/partials/workspace-navigation.blade.php` | Agregar entrada `simulator` al array `$tabs` (junto al bloque `structure`/`io`, líneas ~121-137), condicionada a `$canUpdatePhase && $phaseTemplate->phase_type === 'SINGLE_ELIMINATION'` |
| `routes/web.php` | Agregar rutas nuevas bajo el mismo grupo `prefix('phases/{phaseTemplate}/single-elimination')` que ya usa Reglas/Estructura/IO |

## 36. Archivos que probablemente habrá que crear

**Backend**:
- `app/Services/Tournaments/CompetitionLab/SimulatorParticipantFactory.php`
- `app/Services/Tournaments/CompetitionLab/PhaseSimulatorStateFactory.php`
- `app/Services/Tournaments/CompetitionLab/PhaseSimulatorService.php`
- `app/Http/Controllers/Tournaments/SingleEliminationSimulatorController.php`
- `app/Http/Requests/Tournaments/InitializeSingleEliminationSimulatorRequest.php`
- `app/Http/Requests/Tournaments/ExecuteSingleEliminationSimulatorActionRequest.php`

**Frontend**:
- `resources/views/tournaments/phase-templates/single-elimination-simulator.blade.php`
- `resources/views/tournaments/phase-templates/partials/simulator/participants-builder.blade.php`
- `resources/views/tournaments/phase-templates/partials/simulator/bracket-viewer.blade.php` (extensión del patrón de `visualizer-blocks` con resultados)
- `resources/views/tournaments/phase-templates/partials/simulator/exits-panel.blade.php`
- `resources/js/tournaments/single-elimination/simulator.js`

**Documentación**: actualizar este mismo documento tras la implementación real (sección de resultado, si difiere del plan).

Nota: `PhaseSimulatorService`/`PhaseSimulatorStateFactory` se nombran genéricamente ("Phase", no "SingleElimination") porque `LabPhaseEngineManager` ya despacha por `phase_type` — implementar Round Robin/Group Stage/Swiss más adelante debería requerir solo un factory de participantes y una vista de bracket específicos por motor, reutilizando el mismo `PhaseSimulatorService` sin cambios. No se construye esa abstracción compartida ahora (sería sobrediseño para un solo motor), pero el nombramiento deja la puerta abierta sin comprometerse a nada.

## 37. Rutas/endpoints necesarios

```php
Route::get(
    '/phases/{phaseTemplate}/single-elimination/simulator',
    [SingleEliminationSimulatorController::class, 'show']
)->name('single-elimination.simulator.show');

Route::post(
    '/phases/{phaseTemplate}/single-elimination/simulator/initialize',
    [SingleEliminationSimulatorController::class, 'initialize']
)->name('single-elimination.simulator.initialize');

Route::post(
    '/phases/{phaseTemplate}/single-elimination/simulator/action',
    [SingleEliminationSimulatorController::class, 'action']
)->name('single-elimination.simulator.action');
```

Mismo grupo de middleware (`auth`) y mismo patrón de autorización (`$this->authorize('update', $phaseTemplate)`) que el resto de rutas de Single Elimination.

## 38. Orden exacto de implementación

1. `SimulatorParticipantFactory` (aislado, testeable manualmente de inmediato).
2. `PhaseSimulatorStateFactory` + `PhaseSimulatorService::prepare()` (solo la acción `PREPARE_PHASE`).
3. Controlador + rutas + Form Requests para `show`/`initialize`.
4. Vista mínima que solo muestre el bracket recién generado (sin interacción todavía) — para verificar visualmente que la integración con los motores reales funciona antes de construir la UI de resultados.
5. `PhaseSimulatorService::execute()` con `SUBMIT_MATCH_RESULT`/`SIMULATE_MATCH` + formulario de resultado simple (duelo).
6. Extender a `SUBMIT_ENCOUNTER_RESULT`/`SIMULATE_ROUND` + UI de selección K→Q (solo si se va a probar en modo Avanzado).
7. `RESOLVE_MANUAL_DECISION` + banner de decisión pendiente (seeding/BYE manual).
8. Panel de puertas de salida (`RuntimeOutcomeResolver`) al completarse.
9. Generador rápido de participantes (botones 4/8/16/32 + edición inline) — puede ir en paralelo con el paso 4 en adelante.
10. Reinicio/regeneración + detección de configuración obsolerta (`STALE_CONFIG`).
11. Pulido visual final y entrada en `workspace-navigation.blade.php`.

## 39. Fases internas de implementación

- **Fase A (núcleo funcional)**: pasos 1-5. Al terminar, ya se puede generar un bracket real y jugarlo con duelos simples.
- **Fase B (Avanzado)**: pasos 6-7. Habilita probar K→Q y decisiones manuales.
- **Fase C (cierre)**: pasos 8-11. UX completa y pulida.

Se puede entregar y probar manualmente al final de cada fase interna sin esperar a que todo esté terminado.

## 40. Criterios de aceptación

- Se puede entrar a `Phase Template → Single Elimination → Simulador` sin haber creado ningún `TournamentTemplate`.
- Generar 8 participantes ficticios y ejecutar una fase completa hasta obtener un campeón, sin ninguna escritura en base de datos (verificable con `git diff`/consultas SQL antes y después — no debe haber cambios en ninguna tabla).
- El bracket mostrado usa exactamente el mismo seeding/pairing/BYE que produciría el motor real (mismo resultado que se obtendría hoy vía Competition Lab con un grafo de una sola fase).
- Las puertas de salida configuradas se resuelven correctamente al completar la fase.
- Cambiar la configuración de la fase (por ejemplo, `best_of`) y volver al simulador debe advertir que la simulación anterior quedó obsoleta.
- Ningún archivo de los motores de ejecución (`SingleEliminationLabEngine.php`, `SingleEliminationGraphRuntime.php`, `MatchSeriesRuntime.php`, `RuntimeOutcomeResolver.php`, `LabManualDecisionManager.php`) fue modificado.

## 41. Guía de pruebas manuales

> Esta guía es para cuando el simulador ya esté implementado — no aplica todavía, este documento es solo de diseño.

### Prueba: bracket básico de 8 participantes

1. Entra a cualquier `PhaseTemplate` de tipo Single Elimination en modo Básico.
2. Ve a la pestaña "Simulador".
3. Pulsa el botón rápido "8 participantes".
4. Pulsa "Generar simulación".
5. Verifica que aparece un bracket de 3 rondas (Cuartos, Semifinal, Final) con 8 participantes distribuidos según el `pairing_mode` configurado.
6. Resultado esperado: el emparejamiento coincide con lo verificado manualmente en la Fase 1 (ver [16-Fase-1-Single-Elimination.md](16-Fase-1-Single-Elimination.md), Prueba 2).

### Prueba: BYE irregular

1. En la misma fase, vuelve al paso de participantes y cambia el conteo manual a 5.
2. Genera la simulación.
3. Resultado esperado: 1 encuentro real en la ronda 1, 3 participantes avanzan por BYE automáticamente (visibles con su badge correspondiente).

### Prueba: aislamiento de datos

1. Antes de generar cualquier simulación, anota el número de filas en las tablas `entities`, `phase_single_elimination_results`, `tournament_templates`.
2. Genera y juega una simulación completa hasta el final (incluida resolución de puertas de salida).
3. Vuelve a contar las filas de esas mismas tablas.
4. Resultado esperado: ningún número cambió. Si cambió alguno, el aislamiento está roto y es un defecto crítico.

### Prueba: cambiar configuración a mitad de una simulación

1. Genera una simulación y déjala a medias (algún encuentro sin resolver).
2. En otra pestaña, cambia `best_of` en la configuración de Reglas de la misma fase y guarda.
3. Vuelve a la pestaña del Simulador — la simulación en curso sigue usando la configuración con la que se generó (no se actualiza sola a mitad de camino, igual que el Competition Lab real).
4. Pulsa "Nueva simulación" y vuelve a generarla: ahora sí debe reflejar el `best_of` nuevo.
5. Resultado esperado: no hay mezcla de configuraciones dentro de una misma simulación; para probar un cambio hay que generar una simulación nueva. (La detección automática de "esta simulación quedó desactualizada" descrita en la sección 12 de este documento no se implementó — ver nota en la sección 22.)

## 22. Notas de implementación

El simulador ya está implementado siguiendo la arquitectura descrita en este documento. Precisiones y una omisión deliberada que surgieron al construirlo:

- **BYE en modo Avanzado no aparece como un "encuentro"**: se confirmó leyendo `SingleEliminationGraphRuntime.php` que, a diferencia del modo Básico (donde un BYE sí es un `match` con `status: 'BYE'`), en modo Avanzado un participante que recibe BYE simplemente nunca aparece en ningún encuentro de esa ronda — avanza estructuralmente sin que el runtime genere un objeto que lo represente. El visualizador del bracket refleja esto tal cual: la tarjeta "avanza por BYE" solo se activa para `match.status === 'BYE'`, que únicamente ocurre en modo Básico. En modo Avanzado, un participante con BYE simplemente reaparecerá en la ronda siguiente sin un encuentro visible en la anterior. No es un defecto del simulador — es el comportamiento real del motor, y no se inventó ninguna visualización adicional para no fingir un dato que el runtime no produce.
- **Omisión deliberada — detección automática de configuración obsoleta (estado `STALE_CONFIG` de la sección 12)**: no se implementó. Requeriría comparar un hash de la configuración actual contra la que se usó al generar la simulación, y no es indispensable para que el simulador funcione correctamente (el usuario simplemente genera una simulación nueva si cambió algo). Se dejó fuera para no agregar código no solicitado explícitamente en la orden de implementación. La guía de pruebas manuales (sección 21) se actualizó para reflejar el comportamiento real en vez de uno no construido.
- **Sin panel de estadísticas por participante** (victorias/derrotas acumuladas): omitido para mantener el código nuevo al mínimo — el flujo pedido (ver quién avanza, quién queda eliminado, quién recibe BYE, qué sale por cada puerta) no lo requiere; toda esa información ya es visible directamente en el bracket y en la clasificación final.
- **`RESOLVE_MANUAL_DECISION` no incluye la rama `group_assignments`** que sí existe en el Competition Lab general: esa rama es exclusiva de Group Stage, fuera de alcance de este simulador (Single Elimination solo usa el tipo de decisión `SINGLE_ELIMINATION_SETUP`, que cubre seeding manual y BYE manual).
- Los archivos finales coinciden exactamente con lo planeado en las secciones 35-37 — ningún archivo adicional no previsto.
