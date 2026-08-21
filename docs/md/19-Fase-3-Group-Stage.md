# Fase 3 — Simulador de Group Stage

## 1. Objetivo

Elevar Group Stage al mismo estándar que Single Elimination (Fase 1) y Round
Robin (Fase 2): una pestaña "Simulador" dentro de la fase que permite probar
la configuración real (grupos, distribución, calendario interno, reglas de
clasificación, puertas de salida) con participantes ficticios, sin crear un
torneo ni tocar datos reales.

Fase implementada de forma autónoma siguiendo el plan aprobado en chat el
20/08/2026 ("EJECUTA EL PLAN"). No se crearon tests automatizados; la
verificación se hizo en navegador, con una cuenta de prueba desechable.

## 2. Auditoría previa (resumen)

Antes de escribir código se auditó el 100% del subsistema Group Stage ya
existente: 4 migraciones, 4 modelos (`PhaseGroupStageSetting`,
`PhaseGroupStageGroup`, `PhaseGroupStageAdvancementRule`,
`PhaseGroupStageTiebreaker`), 9 servicios (`GroupStageDefinitionService`,
`GroupStageValidator`, `GroupStageSettingsService`, `GroupStageGroupService`,
`GroupStageAllocator`, `GroupStageAdvancementRuleService`,
`GroupStageAdvancementCalculator`, `GroupStageTiebreakerService`,
`GroupStagePreviewService`), el motor de ejecución `GroupStageLabEngine`, 4
controladores y 8 Form Requests. Conclusión de la auditoría: la configuración,
el motor de ejecución y las reglas de clasificación (11 tipos, más ricas que
las de Round Robin) ya estaban completos y funcionales; solo faltaba el
Simulador y su UI. `GroupStageLabEngineTest.php` era un stub vacío, igual que
`RoundRobinLabEngineTest` antes de la Fase 2 — sin cobertura real del motor.

## 3. Arquitectura del Simulador (reutilización)

Se siguió exactamente el patrón establecido en Fases 1 y 2:
`SimulatorParticipantFactory` + `PhaseSimulatorStateFactory` +
`PhaseSimulatorService` (orquestador compartido) + `LabPhaseEngineManager` →
`GroupStageLabEngine` (motor real, sin duplicar). Nada de esto se modificó
más allá de dos puntos de extensión ya diseñados para ser genéricos:

- `PhaseSimulatorService::engineResolvesOwnOutcomes()`: se agregó
  `'GROUP_STAGE'` junto a `'ROUND_ROBIN'`. Confirmado por auditoría que
  `GroupStageLabEngine::complete()` resuelve sus propias Phase Exits
  internamente (recorriendo `advancement_rules` y agrupando por `exit_id`),
  exactamente como Round Robin — el resolver genérico
  (`RuntimeOutcomeResolver`) habría producido una segunda resolución
  redundante.
- `PhaseSimulatorService::eagerLoadRelationsFor()`: nuevo caso `GROUP_STAGE`
  cargando `groupStageSetting`, `groupStageGroups`, `groupStageTiebreakers`,
  `groupStageAdvancementRules.phaseExit`, `groupStageAdvancementRules.group`,
  `exits`.

El resto de la infraestructura del Lab (`MatchSeriesRuntime` para BO1-9,
`CutoffPolicyResolver` para desempates variables, `LabManualDecisionManager`
para decisiones de preparación) ya soportaba Group Stage sin cambios —
verificado leyendo el código, no asumido.

## 4. Archivos creados

- `app/Http/Controllers/Tournaments/GroupStageSimulatorController.php`
- `app/Http/Requests/Tournaments/InitializeGroupStageSimulatorRequest.php`
- `app/Http/Requests/Tournaments/ExecuteGroupStageSimulatorActionRequest.php`
  (incluye el campo nuevo `group_assignments` para la decisión manual de
  grupos, no presente en el Request equivalente de Round Robin)
- `resources/js/tournaments/group-stage/simulator.js`
- `resources/views/tournaments/phase-templates/group-stage-simulator.blade.php`
- `resources/views/tournaments/phase-templates/partials/simulator/groups-viewer.blade.php`

## 5. Archivos modificados

- `app/Services/Tournaments/CompetitionLab/PhaseSimulatorService.php` (los
  dos puntos de extensión de la sección 3)
- `routes/web.php` (rutas `group-stage.simulator.{show,initialize,action}`)
- `resources/views/tournaments/phase-templates/partials/workspace-navigation.blade.php`
  (pestaña "Simulador" para `GROUP_STAGE`; **decisión de diseño**: no se creó
  una pestaña "Entrada y salida" separada como la de Round Robin, porque las
  Phase Exits de Group Stage ya viven integradas junto a las Reglas de
  clasificación en la pestaña "Reglas" — separarlas habría sido redundante)
- `resources/views/tournaments/phase-templates/partials/simulator/manual-decision.blade.php`
  (nuevo bloque para la decisión `GROUP_ASSIGNMENT`, ver sección 6)
- `resources/js/app.js` (registro de `groupStageSimulator`)

## 6. Decisión manual nueva: asignación de grupos

A diferencia de Single Elimination (orden + BYE) y Round Robin (solo orden),
Group Stage con `distribution_mode = MANUAL` genera una decisión de
preparación de tipo `GROUP_ASSIGNMENT` (ya implementada en
`LabManualDecisionManager::groupStageDecision()`, sin cambios) que no existía
como interfaz. Se extendió `manual-decision.blade.php` con un tercer bloque
condicionado a `pendingDecision()?.type === 'GROUP_ASSIGNMENT'`: un selector
por participante para elegir su grupo, con contadores en vivo por grupo
(ej. "Grupo A: 2/2") y el botón "Confirmar" deshabilitado hasta que cada
grupo alcance exactamente su capacidad. Como este bloque vive en un partial
compartido con Single Elimination y Round Robin, la lógica de habilitación
(`canResolveManualDecision()`) se referencia con `typeof === 'function'` para
no romper esos dos simuladores, que no implementan ese método.

## 7. Visor de grupos (`groups-viewer.blade.php`)

Grid de tarjetas de grupo (una por grupo activo), cada una con: estado,
standings del grupo (PJ/PG/PE/PP/DIF/PTS), calendario interno expandible por
jornada (reutilizando el patrón visual de `matchdays-viewer.blade.php` de
Round Robin), indicador de "Descansa" cuando el grupo tiene tamaño impar, y
un botón propio para simular la jornada de ESE grupo. Al final, una tabla de
"Clasificación combinada" con todas las posiciones de todos los grupos, útil
para verificar reglas que comparan entre grupos (ej. mejores terceros).

## 8. Notas de implementación (bug encontrado y corregido)

Durante la verificación en navegador se detectó un problema real de UX, no
anticipado en el plan: el botón global "Simular jornada pendiente"
(`SIMULATE_ROUND`, acción genérica y compartida con Round Robin) resuelve la
**primera** ronda del runtime plano con encuentros pendientes — y en Group
Stage cada grupo tiene su propia numeración de ronda independiente, así que
un solo clic solo avanzaba UN grupo (el primero en aparecer en la lista),
no los cuatro en paralelo como sugiere visualmente la grilla de tarjetas.

Se verificó primero que la causa era exactamente esa (no un bug de datos):
tras simular la jornada de un grupo, los otros tres seguían en `0/1
resueltos`. La corrección se hizo enteramente en la capa JS específica de
Group Stage, sin tocar `PhaseSimulatorService` ni la acción `SIMULATE_ROUND`
compartida (que sigue siendo válida y se reutiliza tal cual): se agregó
`simulateGroupRound(group)` en `resources/js/tournaments/group-stage/simulator.js`,
que resuelve los encuentros pendientes de la jornada de UN grupo específico
reutilizando `SIMULATE_MATCH` (ya genérico) partido por partido, y un botón
por tarjeta de grupo en `groups-viewer.blade.php`. El botón global se
conservó (sigue siendo válido para avanzar un encuentro cualquiera) pero se
renombró a "Simular un encuentro grupal pendiente" para no prometer más de
lo que hace.

Verificado en navegador tras la corrección: cada botón de grupo afecta
únicamente a ese grupo (confirmado con 4 grupos simultáneos en distintos
estados), y se deshabilita automáticamente al completarse ese grupo.

Ningún otro problema real fue encontrado. A diferencia de las Fases 1 y 2, no
apareció ningún bug preexistente en el código de Group Stage ya auditado.

## 9. Instrucciones para probar desde la interfaz

1. Crear una Fase nueva de tipo "Fase de grupos", con entrada abierta desde
   8 participantes (o la cantidad que prefieras) y Best of 3.
2. En "Reglas", dejar la configuración por defecto (Cantidad fija de grupos,
   Snake Seeded) o cambiarla.
3. Crear una puerta de salida (ej. "Clasificados", selector "Definida por
   reglas del Group Stage Engine").
4. Agregar una regla de clasificación (ej. "Top N de cada grupo", cantidad 2,
   apuntando a la puerta creada).
5. Entrar a la pestaña "Simulador", generar una simulación con 8+
   participantes.
6. Usar el botón de cada tarjeta de grupo ("⚡ Simular jornada de este
   grupo") o cargar resultados manualmente por encuentro.
7. Al completarse todos los grupos, verificar el panel "Puertas de salida":
   debe mostrar exactamente los participantes que cumplen la regla
   configurada.
8. Opcional — probar la asignación manual: cambiar "Modo de distribución" a
   "Manual" en Reglas, generar una nueva simulación, y asignar cada
   participante a un grupo en el panel de decisión pendiente antes de
   confirmar.

## 10. Resultado esperado de cada prueba

- Paso 5-6: los grupos se distribuyen según el modo configurado (Snake
  Seeded reparte alternando dirección); cada partido resuelto actualiza los
  standings del grupo en vivo.
- Paso 6: solo el grupo cuyo botón se presionó avanza; los demás permanecen
  sin cambios.
- Paso 7: la puerta muestra únicamente a los participantes seleccionados por
  la regla (ej. posiciones 1-2 de cada grupo si hay más de 2 participantes
  por grupo); el resto aparece en "Sin salida asignada".
- Paso 8: la decisión pendiente no deja continuar hasta que cada grupo tenga
  exactamente su capacidad asignada; al confirmar, los grupos generados
  respetan exactamente esa asignación.

## 11. Limitaciones y pendientes reales

- No se creó una página "Entrada y salida" dedicada para Group Stage (ver
  decisión de diseño en sección 5) — las puertas se gestionan desde la
  pestaña "Reglas" y desde el resumen general de la fase.
- `GroupStageLabEngine::schedule()` duplica la lógica de
  `RoundRobinScheduleCalculator` (que sí se reutiliza para el preview de
  configuración, no en runtime) — deuda ya aceptada desde la Fase 2 para
  Round Robin, no se resolvió aquí por la misma razón (no bloqueante, mismo
  patrón en los dos motores).
- No hay panel de estadísticas por participante ni comparación visual
  avanzada entre grupos más allá de la tabla combinada (scope-trimmed, igual
  que en Fase 1).
- No se avanzó a Swiss.
