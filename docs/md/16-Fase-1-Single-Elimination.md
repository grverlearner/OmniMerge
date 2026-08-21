# FASE 1 — CIERRE DE SINGLE ELIMINATION

## 1. Objetivo

Declarar formalmente Single Elimination como el motor de referencia de OmniMerge, siguiendo la Fase 1 del [MASTER_PLAN.md](../../MASTER_PLAN.md). Esto implica auditar cada parámetro de configuración contra la matriz P3.1–P3.8 ya definida en `docs/md/15-Doc-Roadmap-Desarrollo-Evolución.md`, corregir únicamente lo que esté realmente incompleto o sea engañoso para el usuario, y retirar el sistema legacy de fases (`TournamentPhase`) si se confirma que es seguro hacerlo. No se agrega funcionalidad nueva ni se avanza a otros motores (Round Robin, Group Stage, Swiss quedan fuera de esta fase).

## 2. Estado inicial

Según la auditoría previa (ver [MASTER_PLAN.md](../../MASTER_PLAN.md), sección 4), Single Elimination ya era el motor más maduro del proyecto: ~11.000 líneas de servicios, estructura interna persistida (rondas → encuentros → slots → resultados → conexiones), generador automático, validador, visualizador de bracket y un motor de ejecución en el Competition Lab. Pero no existía una auditoría de cierre formal, y quedaban dos hallazgos sin verificar: el sistema legacy `TournamentPhase` (sospechado huérfano) y varios problemas de autorización documentados por el propio usuario en `docs/md/14-Doc-Auditoria-Interfaz.md`.

## 3. Problemas encontrados

Se investigó con tres auditorías paralelas de solo lectura (seeding/pairing/BYE/generación de estructura; series/K→Q/puertas de salida/routing; seguridad de retirar `TournamentPhase` + reverificación de autorización). Hallazgos con evidencia concreta:

### Confirmados y corregidos en esta fase

1. **Sistema legacy `TournamentPhase` completamente huérfano.** Tabla, modelo, controlador, servicio, requests y vistas reemplazados por `PhaseTemplate` + Tournament Graph, sin ningún enlace real desde la interfaz (solo un `routeIs()` cosmético en el sidebar para resaltar navegación), sin tests que lo ejerzan, sin foreign keys entrantes desde otras tablas. Único uso real de `TournamentTemplate::phases()` era dentro del propio sistema legacy.

2. **Seeding, Pairing y BYE Assignment son ignorados por completo en modo Avanzado (`configuration_mode = ADVANCED`), sin ninguna advertencia en la interfaz.** El formulario de configuración muestra y permite editar `seeding_mode`, `pairing_mode` y `bye_assignment` exactamente igual en modo Básico y Avanzado, pero `SingleEliminationLabEngine::prepare()` (líneas 83-97) desvía la ejecución hacia `SingleEliminationGraphRuntime::prepare()` **antes** de aplicar cualquiera de esos tres ajustes (líneas 143-171). El motor Avanzado asigna posiciones estrictamente por orden de llegada (`$participantIds[$connection['allocation_value'] - 1]`), y el generador de estructura (`SingleEliminationStructureGenerator::createInputFeeders()`) tampoco reordena los feeders según `seeding_mode` ni orienta los BYE según `bye_assignment` — el `distribution_mode` se guarda como metadato pero nunca se lee para reordenar nada. Esto es exactamente el tipo de funcionalidad "engañosa" que el propio roadmap del proyecto (P0) prohíbe: la interfaz prometía un comportamiento que el backend no cumplía.

### Confirmados, documentados, y deliberadamente NO corregidos en esta fase (ver sección 9 — Decisiones técnicas)

3. Dentro del modo Avanzado, incluso si se llegara a aplicar `seeding_mode`, el generador de estructura consume los feeders de participantes en orden estricto (los primeros llenan los encuentros, los últimos quedan como BYE) — lo opuesto a la convención de `TOP_SEEDS` que usa el modo Básico (donde los mejores seeds reciben el BYE). Corregir esto correctamente requeriría replicar dentro del generador de estructura Avanzada la lógica de `canonicalSeedOrder()`/`relocateSeededByes()` que hoy solo existe en el motor Básico — es una pieza de lógica de negocio nueva, no una corrección de una línea, y toca exactamente el área que esta fase pidió verificar con más cuidado (BYE con cantidades irregulares). Se decidió no improvisar esa lógica sin poder verificarla con tests automatizados (explícitamente fuera de alcance de esta fase) y documentarla como brecha conocida en su lugar.
4. `structure_mode = HYBRID` está declarado en el esquema y en comentarios, pero no tiene ninguna rama de código que lo implemente (solo `MANUAL` tiene efecto real).
5. Las políticas de empate de serie mencionadas en la documentación de planificación (`USE_TIEBREAKERS`, `RANDOM_RESOLUTION`, `MANUAL_RESOLUTION`, `REQUIRE_PLAYOFF`) no existen como opciones configurables en el código — lo que sí existe es un mecanismo implícito equivalente a "muerte súbita" hardcodeado en `MatchSeriesRuntime`, sin etiqueta ni selector.
6. `TOP_N`/`BOTTOM_N` en `RuntimeOutcomeResolver` no valida que la cantidad pedida no exceda los participantes disponibles — devuelve silenciosamente menos de lo pedido en vez de fallar. Comportamiento defendible ("best effort"), pero no documentado como tal.
7. Problemas de autorización de `docs/md/14-Doc-Auditoria-Interfaz.md` que **no son de Single Elimination** y siguen abiertos: `UpdateTournamentStartRequest`, `UpdateTournamentTerminalRequest` y `UpdatePhaseEntryPortRequest` tienen `authorize(): false` fijo (boilerplate nunca implementado) — pero ninguna vista activa hoy invoca esas rutas de actualización, así que no hay ningún botón roto visible. La visibilidad `UNLISTED` también se sigue comportando como `PRIVATE` (solo hay rama de código para `PUBLIC`). Ambos son del Tournament Graph en general, no de Single Elimination — quedan fuera de alcance de esta fase.

### Ya resueltos por trabajo previo (P3.6.x), reverificados en esta fase

8. Los formularios de edición de reglas Swiss con `authorize(): false` que documentaba `docs/md/14` ya fueron corregidos (ahora heredan `authorize()` de sus `Store*Request` correspondientes, con policy `update` real).
9. `allow_cloning=false` ya se respeta correctamente al duplicar `PhaseTemplate`/`TournamentTemplate` (policy `duplicate()` + `$this->authorize()` en los controladores).
10. Los botones de Editar/Archivar/Eliminar visibles a no propietarios en vistas públicas ya están correctamente envueltos en `@can(...)`.
11. El conteo de fases ya usa `graphNodes`/`graph_nodes_count`, no la relación legacy `phases()`.
12. Ningún `authorize()` de los 12 Form Requests de Single Elimination está roto — todos verifican correctamente `can('update', $phaseTemplate)`.

## 4. Plan de implementación

1. Verificar con evidencia concreta (archivo:línea) cada punto de la matriz P3.1–P3.8, sin asumir nada por el nombre de una función.
2. Confirmar de forma exhaustiva si `TournamentPhase` es seguro de eliminar (referencias cruzadas, foreign keys, tests, vistas activas) antes de tocar nada.
3. Reverificar el estado real de los problemas de autorización de `docs/md/14`, distinguiendo cuáles son de Single Elimination y cuáles no.
4. Aplicar solo las correcciones que resuelvan una brecha real dentro del alcance de Single Elimination: retirar el sistema legacy confirmado como huérfano, y corregir la funcionalidad engañosa de Seeding/Pairing/BYE en modo Avanzado mediante una advertencia clara en la interfaz (no mediante una reimplementación apresurada de lógica de negocio no verificable sin tests).
5. Revisar que no queden referencias rotas, imports muertos, rutas huérfanas ni vistas apuntando a controladores eliminados.
6. Documentar todo — incluidas las brechas que se decidió no corregir y por qué.

## 5. Cambios realizados

- Se retiró por completo el sistema legacy `TournamentPhase`: modelo, controlador, servicio, form requests, vistas, rutas nombradas `tournaments.phases.*`, y la relación `TournamentTemplate::phases()`.
- Se creó una migración reversible para eliminar la tabla `tournament_phases` (**no se ejecutó** — ver sección 11, Pendientes).
- Se limpió el sidebar de torneos (ya no referencia la ruta retirada).
- Se actualizó un comentario obsoleto en `TournamentTemplateService.php` que mencionaba el sistema legacy.
- Se agregaron dos advertencias visuales en el formulario de configuración de Single Elimination (secciones "Distribución" y "BYEs"), visibles únicamente cuando `configuration_mode = ADVANCED`, indicando explícitamente que Seeding, Pairing y BYE Assignment no tienen efecto en ese modo — siguiendo el mismo patrón visual ("Próximamente"/"sin efecto") ya usado en el resto del formulario para otras capacidades no ejecutables.
- No se modificó ninguna lógica de negocio ni de runtime — todo lo que ya funcionaba (modo Básico completo, motor de series BO1–BO9/FIXED_GAMES, K→Q en modo Avanzado, puertas de salida, ON_ELIMINATION, validación de conexiones/routing) se dejó intacto porque la auditoría no encontró ningún defecto real en esas áreas.

## 6. Archivos modificados

| Archivo | Cambio |
|---|---|
| [routes/web.php](../../routes/web.php) | Se retiraron las 6 rutas `tournaments.phases.*` y el `use` de `TournamentPhaseController`. |
| [app/Models/TournamentTemplate.php](../../app/Models/TournamentTemplate.php) | Se eliminó el método de relación `phases()` hacia el modelo legacy. |
| [app/Services/Tournaments/TournamentTemplateService.php](../../app/Services/Tournaments/TournamentTemplateService.php) | Se actualizó un comentario que mencionaba el sistema legacy (sin cambio de lógica). |
| [resources/views/partials/tournaments/sidebar.blade.php](../../resources/views/partials/tournaments/sidebar.blade.php) | Se quitó la referencia a `tournaments.phases.*` en el resaltado de navegación. |
| [resources/views/tournaments/phase-templates/partials/single-elimination-settings-form.blade.php](../../resources/views/tournaments/phase-templates/partials/single-elimination-settings-form.blade.php) | Se agregaron dos avisos condicionales ("sin efecto en modo Avanzado") en las secciones de Distribución y BYEs. |

## 7. Archivos creados

| Archivo | Motivo |
|---|---|
| [database/migrations/2026_08_20_000000_drop_tournament_phases_table.php](../../database/migrations/2026_08_20_000000_drop_tournament_phases_table.php) | Migración reversible para retirar la tabla `tournament_phases`. Creada pero **no ejecutada** (ver Pendientes). |
| Este documento | Documentación de la Fase 1. |

## 8. Archivos eliminados

- `app/Models/TournamentPhase.php`
- `app/Http/Controllers/Tournaments/TournamentPhaseController.php`
- `app/Services/Tournaments/TournamentPhaseService.php`
- `app/Http/Requests/Tournaments/StoreTournamentPhaseRequest.php`
- `app/Http/Requests/Tournaments/UpdateTournamentPhaseRequest.php`
- `resources/views/tournaments/phases/index.blade.php`
- `resources/views/tournaments/phases/create.blade.php`
- `resources/views/tournaments/phases/edit.blade.php`
- `resources/views/tournaments/partials/phase-form.blade.php`
- El directorio `resources/views/tournaments/phases/` (quedó vacío tras retirar sus 3 vistas).

La migración original `database/migrations/2026_08_12_195106_create_tournament_phases_table.php` **se conservó sin modificar**, siguiendo la convención de Laravel de no editar migraciones ya aplicadas; el retiro de la tabla se hace con una migración nueva (ver sección 7).

## 9. Decisiones técnicas

- **Por qué se retiró `TournamentPhase` en vez de solo documentarlo**: la investigación confirmó de forma exhaustiva (sin ninguna duda razonable) que no tiene ninguna referencia activa, ningún test, ninguna foreign key entrante, y ningún enlace real en la interfaz. Es exactamente el tipo de "código muerto que puede eliminarse con seguridad" que el propio Master Plan autorizaba a retirar en esta fase.
- **Por qué no se ejecutó la migración de borrado de la tabla**: eliminar una tabla es una operación destructiva y difícil de revertir si llegara a tener datos. Se creó el archivo de migración (reversible, con `down()` que recrea la tabla exacta) pero se dejó pendiente de ejecución para que el usuario la aplique conscientemente — ver sección 11.
- **Por qué no se implementó Seeding/Pairing/BYE en modo Avanzado en vez de solo advertir en la interfaz**: se determinó con evidencia de código que hacerlo correctamente no es una corrección menor. El generador de estructura Avanzada consume participantes con una semántica de cola (primeros = emparejados, sobrantes = BYE) que es la **inversa** de la convención que usa el modo Básico (mejores seeds = BYE). Replicar esa convención dentro del grafo Avanzado exige portar la lógica de `canonicalSeedOrder()`/`relocateSeededByes()` — código nuevo no trivial, exactamente en el área (BYE con cantidades irregulares) que esta fase pidió verificar con más cuidado, y que esta fase tiene explícitamente prohibido probar con tests automatizados. Añadir esa lógica sin poder verificarla automáticamente habría sido más arriesgado que dejar la brecha documentada y visible en la interfaz. Se prefirió la corrección mínima y segura (avisar) sobre una corrección mayor y no verificable.
- **Por qué no se llenó `PhaseTemplateManagementTest.php`**: la instrucción explícita para esta fase prohíbe crear una batería de tests automatizados nueva. Se dejó el archivo vacío tal como estaba; la cobertura de este CRUD queda pendiente para una fase posterior si el usuario lo solicita.
- **Por qué no se tocaron los problemas de autorización de Start/Terminal/EntryPort ni la visibilidad `UNLISTED`**: son del Tournament Graph en general, no de Single Elimination, y hoy no tienen ninguna interfaz activa que los exponga (no son "engañosos" en el sentido de prometer algo visible que falla) — corregirlos está fuera del alcance que esta fase definió.

## 10. Funcionalidades verificadas por análisis

| Funcionalidad | Estado verificado |
|---|---|
| Seeding INPUT_ORDER (modo Básico) | Correcto — respeta el orden de entrada, con test dedicado indirecto. |
| Seeding RANDOM (modo Básico) | Correcto — determinista por diseño (hash estable), con test explícito de reproducibilidad. |
| Seeding MANUAL (modo Básico) | Correcto en el camino de rechazo (duplicados); sin test del camino feliz completo, pero la lógica es coherente. |
| BYE automático, cantidades irregulares (3,5,6,7,10,13) | Fórmula de conteo correcta y verificada a mano para los 6 casos; distribución (quién recibe el BYE) correcta en modo Básico. |
| Pairing SEQUENTIAL / RANDOM / STANDARD_SEEDED (modo Básico) | Correctos; STANDARD_SEEDED implementa el algoritmo canónico de bracket sembrado verificado a mano. |
| Series BO1/BO3/BO5/BO7/BO9 y FIXED_GAMES | Correctos y bien testeados (16 tests dedicados), incluida la resolución de empate por muerte súbita en FIXED_GAMES. |
| Clasificados múltiples K→Q | Correcto en modo Avanzado (única vía donde existe); no aplica en modo Básico (2→1 siempre). |
| Puertas de salida (MATCH_WINNERS, MATCH_LOSERS, TOP_N, BOTTOM_N, RANK_POSITION, RANK_RANGE, ALL, REMAINING) | Todas resueltas correctamente, con protección contra doble-consumo de un mismo participante por dos salidas. |
| ON_ELIMINATION | Correcto e implementado a nivel de Tournament Graph, con ledger de deduplicación y tests dedicados. |
| Conexiones y routing internos | Validación exhaustiva (duplicados, huérfanas, ciclos, alcanzabilidad, sobre-asignación) con ~25 códigos de error distintos. |
| Resultados de encuentro | Estructuralmente imposible registrar un resultado con ambos lados ganadores o sin ambos participantes resueltos. |
| Autorización de Single Elimination | Los 12 Form Requests verificados uno por uno — ninguno roto. |
| Seeding/Pairing/BYE en modo Avanzado | **Sin efecto real** — brecha documentada y ahora advertida en la interfaz (ver sección 3, punto 2). |

## 11. Pendientes

- **Ejecutar la migración `2026_08_20_000000_drop_tournament_phases_table.php`**: el archivo está listo, pero por ser una operación destructiva (DROP TABLE) no se ejecutó automáticamente. Debes correr `php artisan migrate` cuando quieras aplicarla (ver Guía de pruebas manuales, paso 0).
- **Seeding/Pairing/BYE en modo Avanzado siguen sin implementarse** (solo advertidos en la interfaz). Si el uso real del modo Avanzado lo requiere, es candidato a una fase futura con tests que lo verifiquen — no se improvisó en esta fase por el riesgo ya explicado.
- `structure_mode = HYBRID` sigue sin implementación real (declarado, no usado).
- Las políticas de empate de serie mencionadas en la documentación de planificación (`USE_TIEBREAKERS`, etc.) siguen sin existir como opciones configurables — solo hay un mecanismo implícito de muerte súbita.
- `TOP_N`/`BOTTOM_N` sigue sin validar que la cantidad pedida no exceda los participantes disponibles (comportamiento "best effort" no documentado en código).
- Los problemas de autorización de Start/Terminal/EntryPort (`authorize(): false`) y la visibilidad `UNLISTED` del Tournament Graph siguen abiertos — no son de Single Elimination, quedan para cuando se trabaje esa parte del sistema.
- `PhaseTemplateManagementTest.php` sigue vacío (0 bytes) — no se creó batería de tests por instrucción explícita de esta fase.
- Nota de corrección sobre `docs/md/14-Doc-Auditoria-Interfaz.md:101`: ese documento afirma que ON_ELIMINATION "no produce actualmente transferencias en tiempo real", lo cual ya no es cierto — el código actual sí emite en tiempo real vía `TournamentGraphRuntimeService`. No se editó el documento original (para preservar el historial de auditoría), pero queda corregido aquí.

## 12. Guía de pruebas manuales

### Paso 0 — Aplicar la migración de limpieza (opcional, pero recomendado antes de dar la fase por cerrada)

```bash
php artisan migrate
```

Verifica que aparezca `2026_08_20_000000_drop_tournament_phases_table` como ejecutada. Si prefieres no aplicarla todavía, el resto del sistema funciona igual — la tabla `tournament_phases` simplemente queda sin usar en la base de datos.

---

### Prueba 1: confirmar que el sistema legacy de fases ya no existe

1. Con sesión iniciada, entra a `/tournaments/templates` y abre cualquier plantilla de torneo (o crea una nueva).
2. Verifica que el sidebar de Torneos solo tenga "Torneos" y "Fases" (plantillas de fase) como opciones de Diseño — no debe haber ningún enlace a una pantalla separada de "Fases del torneo" con formularios `best_of`/`allow_byes` sueltos.
3. Intenta navegar manualmente a una URL como `/tournaments/templates/1/phases` (sustituye `1` por un ID real de plantilla).
4. **Resultado esperado**: página de error 404 (ruta no encontrada). Antes de esta fase, esa URL habría mostrado una pantalla de gestión de fases obsoleta.
5. **Qué significaría que algo está mal**: si esa URL sigue funcionando y muestra contenido, algo no se retiró correctamente.

---

### Prueba 2: modo Básico con 8 participantes (caso normal completo)

1. Entra a `/tournaments/phase-templates` y crea una nueva plantilla de fase, tipo **Single Elimination**.
2. En la configuración, deja **Nivel de configuración = Básico**.
3. En "Distribución", configura **Seeding = Ranking** y **Pairing = Seeded estándar**.
4. En "BYEs", configura **Asignar BYEs a = Mejores seeds** (si el contrato de la fase permite BYEs).
5. En "Series", elige **Best of = BO3**.
6. Guarda la configuración.
7. Ve al **Competition Lab** (Laboratorio de competición) y selecciona esta plantilla con 8 participantes ficticios, asignando un `seed` del 1 al 8 a cada uno.
8. Ejecuta la fase.
9. **Resultado esperado**: el emparejamiento de primera ronda debe seguir el patrón sembrado estándar (Seed 1 vs Seed 8, Seed 4 vs Seed 5, Seed 2 vs Seed 7, Seed 3 vs Seed 6). Cada partido debe pedir 2 resultados como mínimo (BO3, se cierra en 2-0 o continúa a un tercer juego si va 1-1).
10. **Qué significaría que algo está mal**: si el emparejamiento no respeta el seeding (por ejemplo, Seed 1 vs Seed 2 en primera ronda), o si un partido se cierra con un solo resultado en vez de exigir mayoría en BO3.

---

### Prueba 3: BYE con cantidad irregular de participantes (caso borde)

1. Repite la Prueba 2 pero con **5 participantes** en vez de 8, con `seed` 1 a 5 asignados y **Asignar BYEs a = Mejores seeds**.
2. Ejecuta la fase en el Competition Lab.
3. **Resultado esperado**: solo debe haber **1 encuentro real** en la primera ronda (entre los seeds más bajos/débiles), y los **3 mejores seeds (1, 2, 3) deben avanzar automáticamente por BYE** sin necesidad de jugar. La segunda ronda debe iniciar con 4 participantes.
4. Repite con **13 participantes**: deben producirse **3 BYEs** y los mejores 3 seeds deben avanzar automáticamente.
5. **Qué significaría que algo está mal**: si el número de BYEs no coincide con "siguiente potencia de 2 menos participantes" (ej. con 5 debe haber exactamente 3 BYEs, con 13 exactamente 3 BYEs), o si el BYE se lo lleva un seed débil en vez de uno fuerte.

---

### Prueba 4: modo Avanzado — verificar el nuevo aviso de "sin efecto"

1. En la misma plantilla (o una nueva), cambia **Nivel de configuración = Avanzado**.
2. Ve a la sección "Distribución" del formulario de configuración.
3. **Resultado esperado**: debe aparecer un recuadro ámbar con el texto "Sin efecto en modo Avanzado" explicando que Seeding y Pairing no se aplican en Structure Graph.
4. Ve a la sección "BYEs".
5. **Resultado esperado**: debe aparecer el mismo tipo de aviso, explicando que el modo Avanzado asigna los BYEs por orden de entrada, no según la política elegida.
6. Cambia de nuevo a **Nivel de configuración = Básico**.
7. **Resultado esperado**: ambos avisos deben desaparecer.
8. **Qué significaría que algo está mal**: si el aviso no aparece en modo Avanzado (el usuario seguiría sin saber que esos campos no hacen nada), o si aparece incorrectamente en modo Básico (donde sí tienen efecto).

---

### Prueba 5: clasificados múltiples K→Q (modo Avanzado)

1. Con **Nivel de configuración = Avanzado**, en "Formato competitivo avanzado" configura **Participantes por encuentro (K) = 4** y **Clasificados por encuentro (Q) = 2**.
2. Genera la estructura interna (botón de generar estructura en la pantalla de Estructura).
3. Verifica en el visualizador que cada encuentro de primera ronda tenga 4 slots de entrada y produzca 2 clasificados.
4. En el Competition Lab, al resolver un encuentro debes poder seleccionar exactamente 2 ganadores de los 4 participantes.
5. **Resultado esperado**: si seleccionas menos o más de 2 clasificados, el sistema debe rechazar la selección con un mensaje de error claro.
6. **Qué significaría que algo está mal**: si el sistema permite avanzar con una cantidad de clasificados distinta a la configurada, o si el modo Básico (2→1) permitiera configurar K/Q distintos de 2/1 sin avisar.

---

### Prueba 6: puertas de salida y ON_ELIMINATION

1. En una plantilla con estructura generada, ve a la configuración de Puertas de salida (Phase Exits).
2. Crea o revisa una salida con selector **MATCH_LOSERS** y **exit_timing = ON_ELIMINATION**.
3. Ejecuta un encuentro en el Competition Lab y registra un resultado.
4. **Resultado esperado**: el participante perdedor debe aparecer inmediatamente enrutado hacia la salida configurada (no debe esperar a que termine toda la fase).
5. Intenta configurar una salida con **exit_timing = ON_ELIMINATION** pero con un selector distinto de eliminados (por ejemplo, TOP_N).
6. **Resultado esperado**: el sistema debe rechazar esa combinación con un mensaje de validación claro ("ON_ELIMINATION solo puede utilizar selectores de participantes eliminados").
7. **Qué significaría que algo está mal**: si el enrutamiento en tiempo real no ocurre hasta el final de la fase, o si se permite guardar una combinación inválida de selector/timing.

---

### Prueba 7: autorización — usuario no propietario

1. Inicia sesión con un segundo usuario (o crea uno nuevo).
2. Intenta acceder directamente a la URL de edición de una plantilla de Single Elimination que pertenece al primer usuario (por ejemplo `/tournaments/phases/{id}/edit`, usando el ID de la plantilla del primer usuario).
3. **Resultado esperado**: acceso denegado (403) o redirección — nunca debe poder ver ni editar el formulario de configuración.
4. Si la plantilla del primer usuario es pública, visita su página de visualización pública.
5. **Resultado esperado**: no deben aparecer botones de Editar/Archivar/Eliminar/Duplicar (salvo que `allow_cloning` esté activado, en cuyo caso solo "Duplicar" debe estar disponible).
6. **Qué significaría que algo está mal**: si el segundo usuario puede editar o ve botones de gestión sobre contenido que no le pertenece.

---

### Resumen de qué NO necesitas probar en esta fase

No es necesario probar Round Robin, Group Stage, Swiss, persistencia de torneos entre sesiones, ni Universos — todo eso pertenece a fases posteriores del Master Plan y no fue tocado en esta fase.
