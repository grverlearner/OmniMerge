# Fase 5 — Tournament Graph completamente ejecutable

## Estado de este documento

**Solo auditoría y plan. No se modificó código, no se crearon migraciones, no
se tocó frontend, no se ejecutó implementación.** Este documento se guarda en
`docs/` para que sirva de referencia cuando el usuario indique "EJECUTA EL
PLAN".

## 1. Resumen ejecutivo

La auditoría de esta fase parte de una premisa distinta a la que sugiere el
encargo: **Tournament Graph no es "solo una estructura configurable/
visualizable"** — ya es un sistema ejecutable de verdad, y así quedó
demostrado empíricamente en la Fase 4 (documento
[20-Tournament-Graph-Entradas-Salidas-RR-GS.md](20-Tournament-Graph-Entradas-Salidas-RR-GS.md)):
se construyeron y ejecutaron dos Tournament Graph reales end-to-end
(`Group Stage → Single Elimination → Terminal` y `Round Robin → 2
Terminales`), ambos llegaron a `COMPLETED` con el conteo exacto de
participantes en cada destino.

Esta fase auditó en profundidad las capas que la Fase 4 no había revisado
todavía (validación estructural, prevención de ciclos, servicio de
conexiones, políticas de fusión de entradas, autorización) y encontró el
mismo patrón: **arquitectura ya genérica y ya bastante madura para los 3
motores solicitados (SE, RR, GS)**, sin sesgo hacia Single Elimination. No se
encontró ningún bloqueador nuevo del tamaño del bug de la Fase 4.

Lo que sí queda pendiente, y es real:
1. Verificación empírica de **bifurcación + convergencia combinadas en un
   mismo grafo con los 3 motores** (hoy solo se probó bifurcación simple de
   2 salidas hacia 2 terminales, y cadenas lineales de 2 nodos).
2. Algunas mejoras de **comunicación de errores en el frontend** cuando el
   Runtime se bloquea (`diagnostics`), que hoy existen en el estado pero no
   siempre se explican con suficiente claridad al usuario.
3. **Autorización de ejecución** no está diferenciada de autorización de
   edición (cualquiera que puede `update` el Tournament Template también
   puede ejecutar el Lab — aceptable hoy, pero vale documentarlo).

Lo que **no** se recomienda incluir en esta fase, porque es un cambio de
escala mayor y ya estaba planificado como una fase aparte desde el
`MASTER_PLAN.md` original: **runtime persistente en base de datos**. Hoy el
Competition Lab completo (incluyendo el Tournament Graph Runtime) vive
enteramente en un token cifrado (`LabStateTokenService`), sin ninguna tabla
que registre partidos, resultados o progreso. Sobrevive a un refresh de
página (el token se guarda en `sessionStorage`), pero no a cerrar el
navegador, limpiar almacenamiento, ni a cambiar de dispositivo. Esto es
"Fase 6 — Runtime persistente" en el Master Plan, no "Fase 5".

## 2. Estado actual de Tournament Graph

### 2.1 Lo que ya existe y ya funciona (verificado por lectura + ejecución real)

| Capa | Estado | Evidencia |
|---|---|---|
| Modelo de grafo (`TournamentTemplate`, `TournamentPhaseNode`, `TournamentPhaseConnection`, `TournamentStart`, `TournamentTerminal`, `PhaseEntryPort`) | Completo | Migraciones + modelos ya existen y se usan en producción |
| Entrada por Node (`PhaseEntryPort`) | Completo y genérico | `TournamentGraphNodeService::create()` — puerta automática para cualquier motor sin `PhaseInputGate`s (RR/GS/Swiss); proyección real desde `PhaseInputGate` solo para SE |
| Salida por Fase (`PhaseExit`) | Completo y genérico | Ya verificado en Fases 1-3; `RuntimeOutcomeResolver` distingue motores que resuelven sus propias salidas (RR, GS) de los que no (SE) sin duplicar resolución |
| Routing entre nodos (`RuntimeConnectionRouter`) | Completo y genérico | `select()` soporta ALL/TAKE_N/PERCENTAGE/REMAINDER; entrega a puertos o terminales; sin ningún `if ($phaseType === ...)` |
| Runtime del grafo (`TournamentGraphRuntimeService`) | Completo y genérico | `evaluateNode()`/`routeNode()`/`routeTimedOutputs()` despachan a `LabPhaseEngineManager::prepare()` para cualquier motor soportado; `loadGraph()` ya eager-carga relaciones de los 4 motores |
| Fusión de entradas convergentes (`EntryPortMergePolicy`) | Completo | `APPEND` (unión deduplicada), `WAIT_ALL` (vía `allIncomingConnectionsClosed()`), `FIRST_AVAILABLE`/`PRIORITY` (primer payload no vacío por orden) — las 4 políticas declaradas en el modelo están realmente implementadas |
| Validación estructural (`TournamentGraphValidationService`) | Completo y más rico de lo esperado | Detecta: sin Start/Node/Terminal activo, Start sin salida, entrada requerida sin conexión, entrada que no admite múltiples pero las tiene, Node sin salida conectada, salida sin usar (warning), terminal sin entrada, **ciclos** (`TournamentGraphTopologyService::hasCycle`, DFS de 3 colores), **nodos inalcanzables desde cualquier Start** (BFS de alcanzabilidad), conflictos de fan-out (ALL con hermanos, más de un REMAINDER, porcentajes que superan 100%, porcentajes parciales como warning), y pronóstico de participantes de los Starts contra el mínimo/máximo del torneo |
| Prevención de ciclos en creación (`TournamentGraphConnectionService::validateCycle`) | Completo | Se ejecuta **antes** de persistir una conexión nueva, no solo al validar el grafo completo — un ciclo nunca llega a guardarse |
| Validación de fan-out en creación (`validateFanOut`) | Completo | Mismas reglas que el validador de grafo completo, aplicadas también en `create()` y `update()` de conexiones |
| Bug de "fase sin configuración" al ejecutar desde el grafo | **Corregido en Fase 4** | Los 5 motores (`SingleEliminationLabEngine`, `SingleEliminationGraphRuntime`, `RoundRobinLabEngine`, `GroupStageLabEngine`, `SwissLabEngine`) ahora garantizan su fila de configuración con `ensure()` en vez de fallar |
| Compatibilidad entre motores | Sin restricciones artificiales | Ni `TournamentGraphNodeService::create()` ni `TournamentGraphConnectionService::create()` verifican combinaciones de `phase_type` — cualquier motor soportado por `LabPhaseEngineManager` puede conectarse con cualquier otro. Confirmado además que no existe ninguna lista blanca/negra de combinaciones en el código |
| Competition Lab / simulación | Un solo Runtime real, sin duplicación | `CompetitionLabService` reutiliza exactamente `LabPhaseEngineManager` y `TournamentGraphRuntimeService` — el "simulador" de un Tournament Graph completo **es** el motor de ejecución real, no una maqueta aparte. Mismo patrón que el simulador de una Fase suelta (`PhaseSimulatorService`), que reutiliza el mismo `LabPhaseEngineManager` |
| Frontend del Laboratorio (`tournaments/lab/*`) | Funcional | Estados READY/RUNNING/BLOCKED/AWAITING_DECISION/COMPLETED, progreso %, timeline de eventos, tarjetas de terminal con conteo de participantes — verificado en vivo en la Fase 4 |
| Frontend del constructor del grafo (`tournaments/graph/builder.blade.php`, 1983 líneas) | Funcional para diseño | Errores/advertencias en vivo al crear/editar Starts, Nodes, conexiones, terminales; formulario de conexión con selects dependientes (fuente → salida disponible; destino → puerto disponible) |
| Autorización | Básica pero consistente con el resto del proyecto | `TournamentTemplatePolicy`: ownership-based (`view`/`update`/`delete`/`duplicate`), sin política dedicada de "ejecutar" |

### 2.2 Lo que falta o no está probado

1. **No hay verificación empírica de un grafo con bifurcación + convergencia
   + los 3 motores combinados en una sola ejecución.** Se probó bifurcación
   (una Fase con 2 salidas activas hacia 2 terminales distintos) y cadenas
   lineales de 2 nodos, pero no un caso como "Group Stage reparte a Round
   Robin Y a Single Elimination según posición, y luego ambos convergen en
   una Fase final". El código de `EntryPortMergePolicy` y
   `allIncomingConnectionsClosed()` indica que debería funcionar, pero no se
   ha ejecutado.
2. **Comunicación de errores en el frontend del Laboratorio.** Cuando el
   grafo se bloquea (`BLOCKED`) o encuentra un problema de enrutamiento
   (`UNROUTED_PARTICIPANTS`, `ENTRY_PORT_OVERFLOW`, etc.), esos diagnósticos
   ya viven en `state.graph_runtime.diagnostics[]`, pero conviene confirmar
   que la vista los está mostrando siempre con claridad (no solo cuando el
   usuario abre la consola del navegador, como tuvo que hacerse en la Fase
   4 para diagnosticar el bug real).
3. **Runtime persistente.** Ningún resultado de un Tournament Graph
   sobrevive más allá del token cifrado de la sesión. No hay tabla
   `tournament_instances` ni equivalente. Confirmado como ausente; se
   documenta aquí porque el usuario pidió explícitamente analizar
   persistencia/reanudación (sección 8 de su pedido), pero se recomienda
   NO implementarlo en esta fase (ver sección 11 de este documento).
4. **Autorización de ejecución no diferenciada.** No es un bug — es una
   decisión implícita razonable hoy (el dueño del torneo es el único que
   puede editarlo o probarlo) — pero si en el futuro se permite colaborar
   en un torneo sin poder editarlo, haría falta una policy dedicada.

## 3. Cómo se integran Single Elimination, Round Robin y Group Stage

Los tres ya se integran **de la misma forma**, sin trato especial para
ninguno, a través de tres contratos genéricos:

- **Entrada**: `PhaseEntryPort` por Node (SE puede tener varias, mapeadas a
  slots concretos vía `PhaseInputGate`; RR/GS reciben una única puerta
  flexible, que es la forma correcta dado que ninguno de los dos reparte a
  posiciones — ver Fase 4, sección 3, para la justificación completa de por
  qué esto no es una carencia).
- **Ejecución**: `LabPhaseEngineManager::prepare()/submit()` despacha al
  motor correspondiente (`SingleEliminationLabEngine`, `RoundRobinLabEngine`,
  `GroupStageLabEngine`) sin que `TournamentGraphRuntimeService` necesite
  saber qué motor es.
- **Salida**: `PhaseExit` + `RuntimeOutcomeResolver`, que ya distingue entre
  motores que resuelven sus propias salidas (RR, GS) y los que no (SE).

No hace falta ningún cambio de contrato para que los tres motores
"hablen el mismo idioma" con el grafo — ya lo hacen.

## 4. Arquitectura propuesta

**No se propone una arquitectura nueva.** La arquitectura actual ya cumple
el objetivo declarado de la fase ("que Tournament Graph deje de ser
solamente una estructura configurable/visualizable y se convierta en un
sistema completamente ejecutable, capaz de representar y ejecutar un torneo
compuesto por diferentes fases conectadas entre sí, aceptando correctamente
los tres motores"). Proponer una arquitectura alternativa sería exactamente
el tipo de "sistema paralelo" que el propio pedido del usuario prohíbe en su
sección "Qué no hacer".

Lo único que se propone son verificaciones adicionales y mejoras de
comunicación de estado — detalladas en el plan de la sección 8.

## 5. Flujo completo de ejecución (confirmado, no propuesto)

```
CONFIGURACIÓN (Fase suelta, pestaña "Reglas")
   → PhaseTemplate + su *Setting (ensure() garantiza la fila, Fase 4)
PERSISTENCIA (parcial — solo definición, no runtime)
   → PhaseTemplate, PhaseExit, TournamentTemplate, TournamentPhaseNode,
     TournamentPhaseConnection, PhaseEntryPort, TournamentStart,
     TournamentTerminal (todos persistidos en BD)
GRAPH (constructor visual)
   → TournamentGraphNodeService / TournamentGraphConnectionService
VALIDACIÓN
   → TournamentGraphValidationService (estructural) +
     TournamentGraphFlowValidationService (capacidad/flujo)
RUNTIME (Competition Lab, efímero)
   → LabStateFactory crea el estado inicial (token cifrado)
   → CompetitionLabService::execute() despacha acciones
   → TournamentGraphRuntimeService::initialize()/step()/run()
PARTICIPANTES
   → sintéticos (SimulatorParticipantFactory), identificados por
     lab_id único durante toda la ejecución
EJECUCIÓN
   → LabPhaseEngineManager::prepare()/submit() por Node
RESULTADOS
   → runtime.standings / runtime.outcomes por Node
ROUTING
   → RuntimeOutcomeResolver + RuntimeConnectionRouter
SIGUIENTE FASE
   → EntryPortMergePolicy fusiona en el puerto de entrada del siguiente Node
   → ciclo se repite hasta que todos los participantes llegan a un Terminal
```

## 6. Flujo de entradas y salidas (confirmado)

Ya documentado en detalle en la Fase 4 (sección 4-6 de ese documento). Sin
cambios adicionales encontrados en esta auditoría más profunda de
`TournamentGraphConnectionService`: cada conexión valida en el momento de
creación que el origen y destino pertenezcan al mismo `TournamentTemplate`,
que una puerta que no acepta múltiples conexiones no reciba una segunda, y
que un Node no se conecte consigo mismo.

## 7. Validaciones necesarias (auditadas, no todas nuevas)

Ya existen (ver tabla 2.1): estructura básica, Starts sin salida, entradas
requeridas sin conexión, entradas de conexión única violadas, Nodes sin
salida conectada, salidas sin usar (warning), terminales sin entrada,
**ciclos**, **inalcanzabilidad**, conflictos de fan-out (ALL/REMAINDER
duplicados, porcentaje >100%), pronóstico de participantes contra
min/max del torneo.

**No se identificó ninguna validación de la lista del usuario que falte**
salvo una, menor: no hay una validación explícita de "cantidades imposibles
de participantes" a nivel de **cada Node individual** (ej. una Fase que
exige exactamente 6 participantes pero su entrada, según el fan-out
configurado, solo puede recibir 4) — hoy esto se descubre recién en tiempo
de ejecución (`portContractErrors()` en `TournamentGraphRuntimeService`,
que sí existe y bloquea el Node con diagnóstico claro, pero no se anticipa
en la validación estática del grafo). Se incluye como ítem opcional de bajo
riesgo en el plan (Fase 5.2).

## 8. Estados necesarios (auditados)

Los estados que ya existen son suficientes; no se recomienda agregar
ninguno:

- `TournamentPhaseNode.status`: `ACTIVE` / `INACTIVE` (diseño, no runtime).
- `graph_runtime.status` (dentro del token): `RUNNING` / `BLOCKED` /
  `AWAITING_DECISION` / `COMPLETED`.
- `node.status` (dentro del token, por Node): `WAITING_INPUTS` / `RUNNING`
  (vía `runtime.status`) / `BLOCKED` / `SKIPPED` / `ROUTED` / `COMPLETED`.
- `connection.status` (dentro del token): `PENDING` / `ROUTING` / `ROUTED` /
  `CLOSED_EMPTY`.
- `terminal.status` (dentro del token): `EMPTY` / `COMPLETED` /
  `OVER_CAPACITY`.

Esto ya cubre exactamente los conceptos que el usuario sugirió (draft/ready/
running/waiting/completed/failed/cancelled), con nombres adaptados a lo que
cada capa realmente necesita — no se proponen sinónimos nuevos.

## 9. Persistencia y reanudación

**Análisis, sin implementar (según lo pedido):**

- Un refresh de página **sí sobrevive** hoy: el token se guarda en
  `sessionStorage` (`competitionLab.js`) y se reenvía en cada acción.
- Cerrar el navegador, limpiar `sessionStorage`, o cambiar de dispositivo
  **pierde todo el progreso**: no existe ninguna fila en base de datos que
  registre qué participante jugó qué, qué Node está en qué estado, o qué
  conexiones ya se enrutaron.
- Una fase parcialmente ejecutada (con encuentros pendientes) vive
  exclusivamente en el token; no hay forma de "continuar mañana" sin ese
  token exacto.
- Para soportar esto de verdad haría falta el trabajo ya identificado en
  `MASTER_PLAN.md` como Fase 6: tablas nuevas (`tournament_instances` o
  equivalente + relacionadas), snapshot de la configuración de la plantilla
  al iniciar (para que editar la plantilla no altere un torneo en curso), y
  decidir si el Runtime se recalcula desde eventos guardados o si se guarda
  el estado completo serializado (variante persistida del mismo `array
  $state` que hoy vive en el token).
- **Recomendación**: no mezclar esto con Fase 5. Es un cambio de mayor
  superficie (nuevo esquema, nueva estrategia de snapshot, posiblemente
  colas/jobs) que merece su propia auditoría y plan cuando llegue su turno.

## 10. Cambios de frontend (propuestos, menores)

No se propone ningún rediseño. Los cambios reales identificados son:

- Confirmar (y, si hace falta, mejorar) que `graph_runtime.diagnostics[]`
  se muestra siempre visible en la vista del Laboratorio cuando el estado
  es `BLOCKED`, no solo cuando el usuario inspecciona el estado por consola.
- Igual para `portContractErrors()` (Node bloqueado por contrato de puerto
  incumplido) — verificar que ya se refleja con el mismo nivel de claridad
  que los diagnósticos generales.

## 11. Archivos principales que probablemente se toquen (si el plan se aprueba)

**Backend — solo si la verificación de la sección 8 del plan encuentra algo:**
- `app/Services/Tournaments/CompetitionLab/Runtime/TournamentGraphRuntimeService.php`
- `app/Services/Tournaments/Graph/TournamentGraphValidationService.php` (validación de contrato por Node, si se decide agregar)

**Frontend:**
- `resources/js/tournaments/lab/competition-lab.js`
- `resources/views/tournaments/lab/workspace.blade.php` y sus partials

**Documentación:**
- Este documento, con una sección "Notas de implementación" añadida al cierre.

**Explícitamente NO se tocarán** (fuera de alcance, per lo ya auditado como
correcto): `PhaseInputGate`, `PhaseExit`, `RuntimeConnectionRouter`,
`RuntimeOutcomeResolver`, `TournamentGraphConnectionService`,
`TournamentGraphTopologyService`, `EntryPortMergePolicy`, ni ninguno de los
4 `*LabEngine`.

## 12. Plan de implementación por etapas

### FASE 5.1 — Verificación empírica de bifurcación + convergencia con los 3 motores
**Objetivo**: confirmar con una ejecución real (no solo lectura de código)
que un grafo con bifurcación y convergencia combinadas, usando SE + RR + GS
en el mismo Tournament Graph, se ejecuta correctamente de punta a punta.
**Qué se modifica**: nada en código de producción. Se construye un grafo de
prueba vía interfaz (mismo método que en Fase 4) y se ejecuta en el
Laboratorio.
**Archivos/componentes**: ninguno (solo datos de prueba, eliminados al final).
**Dependencias**: ninguna.
**Riesgos**: si aparece un bug real, esta etapa se convierte en el punto de
partida de una corrección puntual (mismo patrón que el bug de Fase 4).
**Resultado esperado**: confirmación explícita (o, si aparece un problema,
un hallazgo concreto y acotado) de que la convergencia (`EntryPortMergePolicy`)
y la bifurcación funcionan juntas con los 3 motores.

### FASE 5.2 — Validación de contrato de participantes por Node (opcional, bajo riesgo)
**Objetivo**: anticipar en la validación estática del grafo (antes de
ejecutar) los casos donde el fan-out configurado no puede satisfacer el
contrato de participantes de la Fase destino.
**Qué se modifica**: `TournamentGraphValidationService::validate()`, con una
nueva verificación que ya tiene toda la información necesaria disponible
(conexiones activas + `min_participants`/`max_participants`/
`exact_participants` de cada `PhaseTemplate`).
**Archivos**: `app/Services/Tournaments/Graph/TournamentGraphValidationService.php`.
**Dependencias**: ninguna.
**Riesgos**: bajo — es una verificación puramente aditiva (nuevo código de
error/warning), no cambia comportamiento existente.
**Resultado esperado**: un nuevo código de diagnóstico (ej.
`ENTRY_CAPACITY_MISMATCH`) que se detecta antes de intentar ejecutar,
en vez de solo en tiempo de Runtime.

### FASE 5.3 — Claridad de diagnósticos en el Laboratorio (frontend)
**Objetivo**: asegurar que cualquier bloqueo del Runtime (`BLOCKED`,
`AWAITING_DECISION` por causas no obvias, `ENTRY_PORT_OVERFLOW`, contrato de
puerto incumplido) se explica en la interfaz sin que el usuario necesite
abrir la consola del navegador.
**Qué se modifica**: la vista del Laboratorio, agregando o mejorando el
panel que ya renderiza `graph_runtime.diagnostics[]` si se detecta que
falta cobertura visual para algún código de diagnóstico.
**Archivos**: `resources/views/tournaments/lab/workspace.blade.php` y sus
partials; posiblemente `resources/js/tournaments/lab/competition-lab.js`
(sin tocar lógica de negocio, solo helpers de presentación).
**Dependencias**: FASE 5.1 (para saber qué diagnósticos realmente aparecen
en escenarios reales antes de diseñar su presentación).
**Riesgos**: bajo — cambios de presentación, no de lógica.
**Resultado esperado**: un usuario que ejecuta un Tournament Graph y lo ve
bloqueado puede entender por qué sin ayuda externa.

### FASE 5.4 — Documentación de cierre
**Objetivo**: registrar los hallazgos reales de 5.1-5.3 (qué se confirmó, qué
se corrigió si algo apareció) en este mismo documento, sección "Notas de
implementación", siguiendo la convención de las Fases 1-4.
**Qué se modifica**: este documento.
**Dependencias**: 5.1-5.3 completas.
**Riesgos**: ninguno.
**Resultado esperado**: documento de referencia actualizado con el estado
real posterior a la implementación.

No se numeran más etapas porque, a diferencia de lo que anticipaba la
estructura sugerida en el pedido original (FASE 5.1 a FASE 5.14), la
auditoría demostró que la mayoría de esas etapas (contrato común,
runtime del grafo, entradas/salidas y su routing, estados y ciclo de vida,
integración de los 3 motores, Competition Lab, seguridad básica) **ya están
resueltas** y no requieren trabajo nuevo — solo verificación. Proponer 14
etapas habría sido, en la práctica, planificar trabajo que ya existe.

## 13. Riesgos y decisiones técnicas

- **Decisión**: no implementar persistencia en esta fase, aunque el pedido
  original la incluía en el análisis. Justificación: es un cambio de
  arquitectura de mayor escala, ya reservado como fase separada desde el
  Master Plan original, y mezclarlo aquí diluiría el alcance real de "hacer
  ejecutable lo que ya existe" con "construir algo nuevo".
- **Decisión**: no proponer una policy de "ejecutar" separada de "editar"
  todavía, porque no hay ningún caso de uso real hoy que las diferencie
  (nadie más que el dueño puede ni editar ni ejecutar). Se documenta como
  decisión consciente, no como omisión.
- **Riesgo**: FASE 5.1 podría descubrir un bug real de convergencia/
  bifurcación combinada no cubierto por las pruebas de la Fase 4. Si
  aparece, se resolvería puntualmente, igual que el bug de configuración
  ausente — no debería requerir cambios estructurales, dado que toda la
  maquinaria (merge policy, `allIncomingConnectionsClosed`, fan-out) ya está
  implementada y solo faltaría un ajuste puntual.

## 14. Fuera de alcance (explícito)

- Runtime persistente en base de datos (Fase 6 del Master Plan).
- Rediseño de Single Elimination, Round Robin o Group Stage.
- Un segundo sistema de torneos o un segundo Runtime.
- Integración de Swiss (mencionado en fases anteriores del proyecto pero
  explícitamente no listado por el usuario entre los 3 motores de esta
  fase; el código ya lo soporta igual que a los otros 3, pero no se audita
  activamente aquí).
- Rediseño visual general de OmniMerge.
- Tests automatizados nuevos.
- Autorización granular multi-colaborador (nadie la necesita hoy).

## 15. Notas de implementación (post-ejecución del plan)

Ejecutado el 21/08/2026. Resultado: **ninguna de las 4 etapas requirió
cambios de código.** Esto es un resultado distinto al de la Fase 4 (donde
la verificación empírica sí encontró un bug real) — aquí la auditoría
previa había sido lo suficientemente rigurosa como para anticipar
correctamente que la arquitectura ya estaba completa.

### FASE 5.1 — Verificación empírica (bifurcación + convergencia + 3 motores)

Se construyó un Tournament Graph real con:
- 2 Starts independientes (4 participantes cada uno).
- Los 3 motores como Nodes: Round Robin, Group Stage, Single Elimination.
- Bifurcación triple: cada uno de los 3 Nodes envía a 2 destinos distintos
  (RR y GS bifurcan hacia la Fase final Y hacia el terminal de eliminados;
  SE bifurca hacia el terminal de campeón Y hacia el de eliminados).
- Convergencia en una puerta de entrada: la Fase final (Single Elimination,
  4 participantes exactos) recibe simultáneamente 2 clasificados de Round
  Robin y 2 clasificados de Group Stage por la misma puerta de entrada.
- Convergencia en un terminal: el terminal "Eliminados del torneo" recibe
  de 3 fuentes distintas (Round Robin, Group Stage, y la propia Fase final).

Resultado: `COMPLETED`, sin diagnósticos, con matemática exacta —
8 participantes totales → 1 campeón + 7 eliminados (2 de RR + 2 de GS + 3 de
la Final), sin duplicados ni pérdidas. Confirma que `EntryPortMergePolicy`,
`allIncomingConnectionsClosed()` y el fan-out de `RuntimeConnectionRouter`
funcionan correctamente combinados, con los 3 motores, en un solo grafo.

### FASE 5.2 — Validación de contrato de participantes por Node

Al revisar el código antes de escribir la validación propuesta en el plan
(`ENTRY_CAPACITY_MISMATCH`), se encontró que **ya existe una implementación
más completa** de lo planeado:
`TournamentGraphFlowValidationService` + `TournamentGraphCapacityCalculator`
ya calculan, para cada puerta de entrada y cada Node, un pronóstico de
cantidad de participantes (`min`/`max`/`exact`) propagado desde los Starts
a través de cada `allocation_mode` (ALL/TAKE_N/PERCENTAGE/REMAINDER) y cada
`merge_policy`, y lo comparan contra el contrato real
(`min_participants`/`max_participants`/`exact_participants`) tanto de la
puerta de entrada como de la Fase — produciendo códigos como
`ENTRY_BELOW_EXACT`, `ENTRY_EXACT_NOT_GUARANTEED`, `NODE_OVER_MAXIMUM`, etc.
Incluso contempla el caso especial de Single Elimination
(`target_survivors` para el selector `SURVIVORS`/`ELIMINATED`). No se
escribió ningún código nuevo — habría sido una duplicación exacta de lógica
ya existente y ya más sofisticada que lo planeado.

### FASE 5.3 — Claridad de diagnósticos en el Laboratorio

Se reprodujo deliberadamente un escenario de bloqueo (una Fase Round Robin
con una sola puerta de salida conectada, dejando participantes sin ruta) y
se inspeccionó la página renderizada (no solo el estado interno). El panel
"Problemas detectados" ya existe en
`resources/views/tournaments/lab/partials/automatic-runtime.blade.php` y se
muestra automáticamente (`x-show="runtimeDiagnostics().length"`) con código
y mensaje de cada diagnóstico. Además, la sección "Competidores → Recorridos
individuales" ya distingue visualmente cada participante bloqueado ("Sin
ruta") de los que sí llegaron a un destino ("Finalizado"), lo cual es más
claro todavía que solo mostrar los diagnósticos agregados. No se necesitó
ningún cambio de frontend.

### Conclusión general de la Fase 5

Tournament Graph ya era, antes de esta fase, un sistema completamente
ejecutable para Single Elimination, Round Robin y Group Stage combinados —
incluyendo bifurcación, convergencia, y validación/diagnóstico de calidad
productiva. Esta fase aportó **certificación empírica**, no correcciones.
El único trabajo pendiente real del alcance original del usuario sigue
siendo el mismo identificado en la sección 9: runtime persistente en base
de datos, deliberadamente fuera de esta fase.

---

**Estado final**: plan ejecutado completamente. Sin cambios de código —
resultado esperado dado que la auditoría previa ya había verificado la
arquitectura en profundidad. Datos de prueba (2 Fases adicionales, 2
Tournament Templates) eliminados al cierre.
