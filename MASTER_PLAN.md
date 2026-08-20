# OMNIMERGE — MASTER PLAN

> Hoja de ruta oficial del proyecto, producto de una auditoría completa del código (agosto 2026). Sustituye la necesidad de releer todo el repositorio para entender en qué estado se encuentra y qué sigue. Se actualizará al cierre de cada fase.

## 1. Objetivo del proyecto

OmniMerge es una plataforma donde el usuario crea **Entidades** de cualquier dominio (personajes, países, lo que sea) con atributos dinámicos, catálogos y versiones, las organiza dentro de **Universos** con tiempo propio (temporadas), y las hace competir en **Torneos** configurables cuyos resultados se resuelven mediante un motor de simulación — desde algo tan simple como "quién saca el número mayor" hasta reglas basadas en los atributos de cada Entidad.

Biblioteca, Torneos, Comunidad, Universos y Simulación son módulos separados que se conectan mediante contratos explícitos y deliberadamente desacoplados:
- Torneos no conoce el dominio de las Entidades (no sabe qué es "Naruto" ni "Konoha", solo trabaja con participantes y resultados abstractos).
- Universo no copia Entidades — les añade contexto competitivo (ranking, temporada, elegibilidad) sin duplicar el dato canónico.
- Simulación solo le entrega un resultado a Torneos; Torneos no necesita saber cómo se calculó.

## 2. Estado actual

OmniMerge es una aplicación Laravel 12 / PHP 8.2 / MySQL con ~105.000 líneas de PHP en `app/`, 78 commits, 61 controladores, 94 servicios y 44 modelos Eloquent. Tiene dos subsistemas grandes y maduros, y uno completamente inexistente:

1. **Biblioteca de Entidades** (núcleo): sistema dinámico donde el usuario define `EntityType` (categorías — funcionan como etiqueta, no definen esquema de atributos), `Attribute` (campos dinámicos con tipos TEXT/NUMBER/BOOLEAN/DATE/OPTION, catálogos jerárquicos, reglas contextuales SHOW/HIDE/REQUIRE), `Entity` (el registro canónico), y **4 conceptos de "versión" independientes**: Entity original (nunca cambia), Base activa ★ (`EntityBaseVersion`, solo afecta lo que ve el dueño en su panel), Resolver por defecto ⚡ (`EntityVersion.is_default`, fallback de `VersionResolverService`), y Presentación pública ◎ (`EntityPresentation`, lo único que ven terceros). Es robusto y funcional, pero su lógica más compleja (resolución de versiones, reglas contextuales) **no tiene ningún test automatizado**.

2. **Torneos**: con enorme diferencia el módulo con más código del proyecto (61 controladores, 94 servicios, 117 rutas solo de este dominio). Tiene 4 formatos de fase diseñables como `PhaseTemplate` (Single Elimination, Round Robin, Group Stage, Swiss), combinables en un grafo (`TournamentTemplate` → `TournamentPhaseNode` → `TournamentPhaseConnection` → `TournamentTerminal`), y un **Competition Lab** que ya ejecuta el grafo completo de punta a punta con participantes sintéticos y resolución aleatoria de resultados — el "juego simple" de quién saca el número mayor ya existe y funciona. Single Elimination está muy por delante de los otros tres formatos: es el único con estructura interna persistida en base de datos (rondas → encuentros → slots → resultados), generador automático, validador y visualizador de bracket.

3. **Universos, Temporadas, Rankings, Trofeos**: **no existe absolutamente nada en código** — ni modelo, ni tabla, ni ruta, ni controlador. Lo único que hay son carpetas de vistas vacías (`resources/views/universes/`), residuo de un ejemplo didáctico usado en un manual interno para enseñar "cómo crear un módulo nuevo" (nunca implementado de verdad). Lo importante: **existe documentación de diseño muy avanzada, escrita por el propio usuario**, que ya resuelve prácticamente todas las preguntas de diseño de este módulo.

4. **Comunidad**: módulo funcional de explorar/clonar Entidades, Colecciones, Atributos y Catálogos entre usuarios (perfiles públicos de creador, clonación con seguimiento de procedencia). No incluye aún compartir plantillas de torneo — está correctamente pospuesto según el propio roadmap del usuario.

**Hallazgo más importante de esta auditoría**: el proyecto no está empezando de cero conceptualmente. Ya tiene un roadmap propio y muy detallado (`docs/md/15-Doc-Roadmap-Desarrollo-Evolución.md`, actualizado el 17 de agosto de 2026, con prioridades P0–P15) que el propio usuario escribió, y que el estado real del código confirma como acertado. Actualmente el proyecto está a mitad de camino de su prioridad **P3: cierre de Single Elimination como motor de referencia** (los parches P3.6.1–P3.6.5 ya están aplicados). Este documento no inventa una visión nueva: audita el código real contra ese roadmap ya existente, confirma qué está hecho, corrige lo que había que corregir, y lo convierte en fases ejecutables con criterios de aceptación concretos.

## 3. Arquitectura actual

- **Backend**: Laravel 12, PHP 8.2+, Eloquent ORM. Flujo estándar: Route → Controller → Form Request → Policy → Service → Model → DB → vista Blade.
- **Frontend**: Blade + Tailwind CSS + Alpine.js + Vite. Sin SPA ni drag&drop — el constructor de grafos de torneo es deliberadamente basado en formularios/selectores, no en arrastrar y soltar (decisión documentada, no una limitación accidental).
- **Base de datos**: MySQL en producción, SQLite en memoria para tests.
- **Auth**: Laravel Breeze, integración estándar sin modificaciones sospechosas (registro, login, verificación de email, reset de password, confirmación de password).
- **Autorización**: 10 Policies (`Attribute`, `AttributeGroup`, `AttributeOption`, `Collection`, `Entity`, `EntityType`, `EntityVersion`, `Version`, `PhaseTemplate`, `TournamentTemplate`) para 44 modelos totales — el resto de submodelos (todo el árbol de `Phase*`/`Tournament*` de torneos, y las tablas de reglas contextuales de atributos) se autorizan indirectamente vía ownership del modelo padre. Es un patrón razonable, pero contradice literalmente la afirmación de `AGENTS.md` de que la autorización es "per-model".
- **Dominio Biblioteca**: `EntityType` (categoría, sin relación con qué atributos aplican) → `Entity` (registro canónico) ← `EntityAttribute`/`EntityAttributeValue` (cualquier `Attribute` del usuario puede aplicarse a cualquier `Entity`, independientemente de su `EntityType`) → `Version`/`EntityVersion` (definición general reutilizable vs. aplicación concreta a una Entity, con herencia vía `parent_entity_version_id`) → `VersionResolverService` (resuelve qué versión mostrar según catálogo activado con lógica AND/OR por grupos, o cae al fallback `is_default`) → `EntityBaseVersion`/`EntityPresentation` (dos capas adicionales de "qué mostrar", completamente independientes entre sí y del resolver).
- **Dominio Torneos**: `PhaseTemplate` (fase reutilizable: SINGLE_ELIMINATION/ROUND_ROBIN/GROUP_STAGE/SWISS, con `PhaseExit`/`PhaseInputGate` propios) → `TournamentTemplate` (el grafo: `TournamentPhaseNode` usa una `PhaseTemplate` en un contexto, `TournamentPhaseConnection` conecta salidas con entradas o terminales) → `CompetitionLabService` (motor de ejecución **efímero**: todo el estado se serializa como JSON cifrado en un `state_token` que viaja ida y vuelta entre cliente y servidor — no hay ninguna fila en base de datos que registre "quién ganó el partido X") → `RuntimeOutcomeResolver`/`TournamentGraphRuntimeService` (enruta participantes por el grafo hasta un `TournamentTerminal`).
- **Infraestructura prevista** (`.env.example`): sesión/cola/caché vía base de datos por defecto (Redis disponible como opción, no configurado), almacenamiento local (soporte S3 previsto pero sin credenciales), correo vía `log` (sin proveedor SMTP/transaccional real todavía) — stack modesto y coherente con que el proyecto aún no está en fase de producción.

## 4. Funcionalidades existentes

### Biblioteca de Entidades

| Pieza | Estado | Nota |
|---|---|---|
| CRUD EntityType | **COMPLETA** | Código/slug automáticos, secuencia, protección contra borrado con entidades activas |
| CRUD Attribute | **COMPLETA** | Bloqueo de `data_type` una vez usado, publicación, catálogos |
| AttributeOption (jerarquía `parent_option_id`) | **FUNCIONAL PERO MEJORABLE** | Anti-ciclo probado; recorrido de árbol sin límite de profundidad ni memoización (aceptable a escala actual) |
| AttributeGroup | **FUNCIONAL** | CRUD + pivote con orden/label personalizado, sin tests dedicados |
| Collection | **FUNCIONAL PERO MEJORABLE** | Cero tests |
| Version / EntityVersion | **FUNCIONAL** | Herencia, exclusividad SHARED/EXCLUSIVE bien resueltas; cero tests |
| VersionResolverService | **FUNCIONAL, SIN TESTS** | La lógica condicional más compleja del subsistema (resolución por catálogo AND/OR + fallback + herencia con HIDE) y la peor protegida |
| EntityBaseVersion (★) / EntityPresentation (◎) | **FUNCIONAL, SIN TESTS** | Bien diseñadas, coherentes entre sí, sin cobertura |
| AttributeContextRule / AttributeOptionRelationship | **FUNCIONAL, SIN TESTS** | Reglas SHOW/HIDE/REQUIRE y ALLOWS/BLOCKS; sin Policy dedicada, autorización solo por checks manuales |

### Torneos

| Formato | CRUD config | Estructura persistida | Ejecutable en Lab | Clasificación |
|---|---|---|---|---|
| Single Elimination | Completo | Sí (rondas/encuentros/slots/resultados/conexiones) | Sí | **COMPLETA** (para alcance de plantilla/diseño) |
| Round Robin | Completo | No (solo en runtime del Lab) | Sí | **FUNCIONAL PERO MEJORABLE** |
| Group Stage | Completo | No | Sí | **FUNCIONAL PERO MEJORABLE** |
| Swiss | Completo | No | Sí | **FUNCIONAL PERO MEJORABLE** |
| League / Custom | Inexistente | — | No | **NO IMPLEMENTADA** (correctamente pospuesta, ni siquiera creable desde el formulario) |

**Tournament Graph**: **FUNCIONAL** — construye grafos con bifurcaciones/convergencias, valida compatibilidad de flujo (mín/máx/exacto de participantes entre salida y entrada), y el Competition Lab ejecuta el grafo completo (`START_TOURNAMENT`/`STEP_RUNTIME`/`RUN_TOURNAMENT`, límite de 1000 operaciones) hasta los terminales, incluso a través de múltiples fases encadenadas. Pero **todo es efímero**: los participantes son siempre sintéticos (`entity_id = null`), y no existe ninguna tabla `tournament_instances`/`tournament_matches`/`tournament_results` — nada sobrevive al cierre del navegador salvo lo que quede en el token de estado cifrado.

### Universos / Temporadas / Rankings / Trofeos

**NO IMPLEMENTADA** — 0% en código. Coincide exactamente con la propia autoevaluación del roadmap del usuario, que sitúa Universos en P12 con una barra de progreso casi vacía.

### Comunidad

**FUNCIONAL** para Entity/Collection/Attribute/Catalog: explorar contenido público, ver perfil de creador, clonar con seguimiento de procedencia (`source_*_id`, contador de clones/vistas). No incluye `TournamentTemplate` como recurso compartible todavía — correctamente pospuesto según el propio roadmap (Sprint T9).

### Tests

- **Auth (Breeze)**: sólido y completo — 6 archivos cubriendo login, registro, verificación, reset y confirmación de password.
- **Single Elimination**: con diferencia el área más testeada de todo el proyecto — 17 archivos entre Feature y Unit, más los motores de Competition Lab.
- **Round Robin / Group Stage / Swiss**: solo tests unitarios de calculadoras matemáticas puras (schedule, allocator, pairing). **0 tests HTTP/feature** de sus controladores.
- **Biblioteca**: cobertura razonable en generación de código/slug/imágenes (24 tests en 4 archivos); **0 tests** en resolución de versiones, reglas contextuales, clonación de Version/EntityVersion.
- **Community**: **0 tests**.
- Existen al menos dos archivos de test fantasma: `tests/Feature/Tournaments/PhaseTemplateManagementTest.php` (0 bytes) y un directorio duplicado `tests/Unit/Unit/Tournaments/CompetitionLab/` (vacío, probablemente resultado de un path erróneo).

## 5. Problemas detectados

### Antes de la primera versión funcional (bloqueantes o de alto riesgo)

1. **`VersionResolverService`, `AttributeContextService`, `EntityBaseVersion`, `EntityPresentation` sin ningún test.** Es la lógica condicional más compleja de toda la Biblioteca y sustentará la representación de cada Entity que participe en cualquier torneo o universo futuro. Riesgo real de regresión silenciosa.
2. **Código huérfano**: tabla/modelo/controlador/vistas legacy `tournament_phases` (`app/Models/TournamentPhase.php`, `TournamentPhaseController.php`, `resources/views/tournaments/phases/**`), reemplazado conceptualmente por `PhaseTemplate` + Tournament Graph pero nunca retirado. Tiene 6 rutas activas (`tournaments.phases.*`) sin ningún enlace real desde la interfaz actual — solo accesible por URL directa. El propio documento de diseño del usuario ya advertía no seguir ampliándolo.
3. **`PhaseTemplateManagementTest.php` vacío** — el recurso raíz de todo el subsistema de fases no tiene ninguna prueba HTTP.
4. **Asimetría arquitectónica entre formatos de fase**: Single Elimination persiste su estructura interna completa; Round Robin, Group Stage y Swiss no persisten nada más allá del runtime efímero del Lab. No es un error — es fiel al plan original ("no persistir rounds todavía") — pero Single Elimination se desvió de ese plan sin que quede documentado como decisión intencional. Debe resolverse explícitamente antes de avanzar con los otros tres formatos, para no tener que decidirlo (o reinventarlo) tres veces.
5. **Posibles fallos de autorización ya documentados por el propio usuario** en `docs/md/14-Doc-Auditoria-Interfaz.md` (16 ago 2026): formularios Swiss con `authorize(): false` (botones de editar que siempre devuelven 403), `allow_cloning=false` no respetado al clonar plantillas, botones de Editar/Archivar/Eliminar visibles a usuarios no propietarios en vistas públicas. Ese documento es anterior a los parches P3.6.1–P3.6.5 ya aplicados — **hay que reverificar cuáles de estos problemas siguen abiertos**, no asumir que ya se corrigieron.
6. **`AttributeStructureController` sin Policy dedicada** — la autorización de reglas contextuales de atributos depende solo de checks manuales de propiedad dentro del Service, rompiendo el patrón de Policies usado en el resto del módulo de Biblioteca.

### Importantes, pero no bloqueantes para la primera versión

7. Inconsistencia en el uso de `isActive()` entre Policies — un usuario desactivado podría seguir editando/eliminando ciertos recursos existentes aunque no pueda crear nuevos.
8. Campo `json_value` muerto en `EntityAttributeValue`/`EntityVersionAttributeValue` — existe en migración y modelo pero ningún `data_type` lo popula.
9. `EntityController::show()` con ~350 líneas de lógica de presentación directamente en el controlador, duplicando parcialmente reglas que también viven como accessors en el modelo `Entity`.
10. `AttributeContextService::recalculateHierarchyLevels()` hace un `UPDATE` individual por atributo en un loop en vez de un bulk update — no crítico a la escala actual, pero mal patrón si los catálogos crecen.
11. Eager loading no siempre consistente en los `show()` de sub-controladores de configuración de torneo (no es N+1 clásico, pero sí round-trips evitables).
12. Falta de tests HTTP/feature para casi todos los controladores de Round Robin, Group Stage, Swiss, y para la mayoría de sub-recursos del Tournament Graph (starts, terminals, connections, entry-ports).

### Secundarias / deuda documental (no bloquean nada)

13. La tabla de "Estado general del proyecto" en `README.md` está desactualizada — marca Torneos, Universos y Simulaciones como "⏳ Pendiente" cuando Torneos es, con diferencia, el módulo más desarrollado del proyecto. Contrasta con la sección "Estado Single Elimination P3.6" del mismo README, que sí está actualizada.
14. Labels fantasma `LEAGUE`/`CUSTOM` en `PhaseTemplate::getTypeLabelAttribute()` sin implementación real detrás (correctamente bloqueados en el formulario de creación, pero pueden confundir a quien lea el modelo).
15. 5 archivos `.patch` sueltos en la raíz del repositorio (`OmniMerge-P3.6.1` a `P3.6.5`) — ya aplicados y commiteados; son ruido organizativo, no un problema funcional.
16. Convención de migración distinta (`unsignedBigInteger` + `foreign()` manual en vez de `foreignId()->constrained()`) en las tablas del sistema de contexto de atributos frente al resto del proyecto.

## 6. Objetivo de la primera versión funcional

OmniMerge V1 se considerará **funcional** cuando:

- Los 4 formatos de fase (Single Elimination, Round Robin, Group Stage, Swiss) tengan el mismo estándar de completitud que Single Elimination hoy: configuración validada, estructura ejecutable, tests HTTP y unitarios.
- Un torneo pueda crearse, jugarse con Entidades reales de la Biblioteca (no solo participantes sintéticos), y sus resultados sobrevivan al cierre de la sesión — es decir, exista un runtime persistente además del Competition Lab efímero actual.
- Exista al menos un registro histórico mínimo: quién ganó, cuándo, con qué participantes.
- La lógica de resolución de versiones y reglas contextuales de la Biblioteca esté cubierta por tests, dado que sustentará todo lo anterior.
- No quede código huérfano ni funcionalidad que aparente funcionar sin hacerlo realmente.
- Universos, Temporadas, Ranking y Recompensas queden **explícitamente fuera** de esta primera versión — son la V2, y construirlos antes repetiría el riesgo que el propio roadmap del usuario ya identificó: muchos módulos parcialmente implementados, ninguno completamente funcional.

## 7. Arquitectura objetivo

La misma arquitectura actual, con tres adiciones concretas para llegar a V1:

1. Un **Tournament Runtime persistente** en base de datos, adicional al Competition Lab efímero existente (que se conserva tal cual, para pruebas y diseño de plantillas sin generar historial).
2. Los 4 formatos de fase compartiendo el mismo patrón de estructura interna que hoy solo tiene Single Elimination — decidido explícitamente en la Fase 2, no replicado por inercia.
3. `TournamentParticipant` como abstracción que hoy no existe formalmente en el motor (los participantes del Lab son siempre sintéticos): introducirla permitirá, sin reescribir el motor, que más adelante (Fase 10, Universos, fuera de V1) los participantes puedan proceder también de `UniverseCompetitor` sin tocar el núcleo de Torneos.

## 8. Plan de implementación

### FASE 1 — Cierre de Single Elimination (Engine Stable V1)

**Objetivo**: declarar formalmente Single Elimination como motor de referencia del proyecto, cerrando huecos de auditoría, tests y limpieza — sin agregar funcionalidad nueva.

**Estado actual**: el motor más maduro del proyecto (~11.000 líneas de servicios, generador de estructura, validador, visualizador de bracket, estructura persistida a nivel de rondas/encuentros/slots/resultados), con los parches P3.6.1–P3.6.5 ya aplicados, pero sin una auditoría formal de cierre documentada y con un test raíz vacío.

**Problemas que ataca**: hallazgos D.2, D.3, D.5, D.6 de la sección anterior.

**Dependencias**: ninguna — es el punto de partida.

**Archivos probablemente involucrados**:
- `app/Services/Tournaments/SingleElimination/**`
- `app/Http/Controllers/Tournaments/SingleElimination*.php`
- `app/Http/Requests/Tournaments/*SingleElimination*.php`
- `tests/Feature/Tournaments/SingleElimination*`, `tests/Unit/Tournaments/**` (subset de SE)
- `app/Models/PhaseTemplate.php`
- `app/Models/TournamentPhase.php`, `app/Http/Controllers/Tournaments/TournamentPhaseController.php`, `app/Services/Tournaments/TournamentPhaseService.php` (a retirar)
- `resources/views/tournaments/phases/**`, `resources/views/partials/tournaments/sidebar.blade.php` (a retirar/actualizar)
- `routes/web.php` (retirar rutas `tournaments.phases.*`)

**Base de datos**: sin migraciones nuevas de funcionalidad; posible migración de limpieza para retirar `tournament_phases` (destructiva — requiere confirmación explícita antes de ejecutarse, y un `grep` exhaustivo previo para descartar referencias ocultas).

**Backend**: auditoría punto por punto de la matriz que el propio roadmap ya define (Seeding INPUT/RANDOM/MANUAL, BYE automático con cantidades irregulares, pairing SEQUENTIAL/RANDOM/STANDARD_SEEDED, series BO1/BO3/BO5/BO7/BO9/FIXED_GAMES, clasificados múltiples K→Q, puertas de salida en todos sus casos, ON_ELIMINATION, estructura avanzada — rounds/encounters/slots/input gates/results/connections/routing).

**Frontend**: sin cambios funcionales; solo retirar vistas huérfanas del sistema legacy de fases.

**Lógica de negocio**: no se agrega, se valida y se completa la existente.

**Validaciones**: completar donde la auditoría detecte huecos reales (no asumidos).

**Pruebas**: completar `PhaseTemplateManagementTest.php` con cobertura real del CRUD raíz; agregar los tests que falten según la matriz de auditoría P3.1–P3.8 ya definida por el propio roadmap del usuario.

**Riesgos**: retirar `tournament_phases` podría romper algo no detectado si existe una referencia oculta no descubierta en esta auditoría.

**Criterios de aceptación**: matriz de auditoría en estado "Completo + Probado" para cada elemento crítico; cero código huérfano de fases legacy; test raíz de `PhaseTemplate` con cobertura real; reverificación explícita de los problemas de autorización de `docs/md/14-Doc-Auditoria-Interfaz.md` con veredicto actualizado (abierto/cerrado) para cada uno.

**Resultado esperado**: Single Elimination con su Definition of Done cumplida, funcionando como contrato de referencia para las Fases 2–4.

---

### FASE 2 — Round Robin completo

**Objetivo**: elevar Round Robin al mismo estándar que Single Elimination — estructura ejecutable de punta a punta con tests HTTP, no solo cálculo aislado.

**Estado actual**: CRUD de configuración completo, `RoundRobinScheduleCalculator` testeado unitariamente, pero sin estructura persistida a nivel de plantilla y sin tests de feature/HTTP.

**Problemas que ataca**: hallazgo D.4 (asimetría de persistencia, resuelta aquí como decisión explícita) y D.12 (tests HTTP).

**Dependencias**: Fase 1 — usa el contrato de estructura persistida que deje cerrado Single Elimination como referencia de patrón a replicar (adaptado a round-robin), o documenta explícitamente por qué difiere.

**Archivos probablemente involucrados**: `app/Services/Tournaments/RoundRobin/**`, `app/Http/Controllers/Tournaments/RoundRobin*.php`, posibles migraciones nuevas si se decide persistir calendario/resultados, `tests/Feature/Tournaments/RoundRobin*` (a crear).

**Base de datos**: evaluar en el kickoff de esta fase si se necesita una tabla análoga a la estructura interna de Single Elimination (p. ej. `phase_round_robin_matches`) — decisión a tomar con evidencia, no asumida de antemano.

**Backend / Frontend / Validaciones**: seguir el patrón que deje cerrado la Fase 1.

**Pruebas**: tests HTTP para `RoundRobinController` y sub-recursos; casos borde de número impar de participantes, single/double round robin, sistema de puntuación configurable (no codificado rígidamente).

**Riesgos**: si se decide persistir estructura, es un cambio de esquema no trivial; si se decide no persistir, debe quedar documentada la razón para que la asimetría con SE sea una decisión, no un accidente.

**Criterios de aceptación**: calendario generado correctamente para N par e impar; clasificación en vivo correcta (PJ/PG/PE/PP/puntos); tiebreakers encadenados aplicados; tests HTTP cubriendo el CRUD completo y al menos un flujo end-to-end de competición jugada en el Lab.

**Resultado esperado**: Round Robin jugable de punta a punta con la misma confianza que Single Elimination.

---

### FASE 3 — Group Stage completo

**Objetivo**: mismo estándar, construido sobre la base conceptual de Round Robin (un grupo se comporta como una competición round-robin independiente).

**Estado actual**: CRUD completo, el motor de Lab más rico en reglas de avance de los 4 formatos, pero sin estructura persistida ni tests HTTP.

**Dependencias**: Fase 2 — reutiliza el patrón de persistencia que se decida ahí.

**Archivos**: `app/Services/Tournaments/GroupStage/**`, `app/Http/Controllers/Tournaments/GroupStage*.php`, tests a crear.

**Pruebas**: distribución de participantes (BALANCED/SNAKE/RANDOM/POTS/MANUAL), grupos con capacidades desiguales, comparación entre terceros de distintos grupos (clasificación secundaria), normalización cuando los grupos tienen distinto tamaño.

**Criterios de aceptación**: el caso de referencia que el propio roadmap del usuario define funcionando end-to-end (fase de grupos → 1º y 2º avanzan automáticamente → terceros compiten por plazas adicionales → clasificados enviados a eliminación directa).

**Resultado esperado**: Group Stage jugable de punta a punta, conectable con Single Elimination vía Tournament Graph.

---

### FASE 4 — Swiss completo

**Objetivo**: mismo estándar; es el más complejo porque cada ronda depende del estado generado por las anteriores.

**Estado actual**: CRUD completo, el motor de Lab más grande en líneas de código de los 4 formatos, pero sin estructura persistida, sin tests HTTP, y con posibles botones rotos (`authorize(): false`) ya señalados por el propio usuario — deben reverificarse y corregirse aquí como parte del cierre, no asumirse resueltos.

**Dependencias**: Fases 2–3 — reutiliza patrón de persistencia y de tests ya validado.

**Archivos**: `app/Services/Tournaments/Swiss/**`, `app/Http/Controllers/Tournaments/Swiss*.php`, `app/Http/Requests/Tournaments/*Swiss*.php` (revisar cada `authorize()`), tests a crear.

**Pruebas**: emparejamiento dinámico evitando revanchas, floaters en grupos de puntuación impares, BYE Swiss con control de prioridad/elección manual, clasificación anticipada por récord, tiebreakers Swiss (Sonneborn-Berger, opponent score, etc.).

**Criterios de aceptación**: 16 participantes ejecutando rondas completas sin intervención técnica hasta clasificación/eliminación final; los botones de edición previamente señalados como rotos verificados como funcionales o explícitamente deshabilitados con mensaje claro ("Próximamente"/"No soportado").

**Resultado esperado**: los 4 motores de fase al mismo nivel de madurez que Single Elimination.

---

### FASE 5 — Tournament Graph completamente ejecutable

**Objetivo**: validar que el grafo completo (bifurcaciones, convergencias, múltiples ramas, partido de tercer puesto, múltiples terminales, políticas de merge WAIT_ALL/APPEND/FIRST_AVAILABLE/PRIORITY) funciona con los 4 motores ya cerrados, no solo con Single Elimination en aislamiento.

**Dependencias**: Fases 1–4.

**Alcance**: pruebas de integración de grafos multi-fase reales (por ejemplo, Group Stage → Single Elimination), validación exhaustiva de políticas de merge.

**Resultado esperado**: Competition Lab ejecutando torneos multi-fase completos de principio a fin con cualquier combinación de formatos.

---

### FASE 6 — Runtime persistente de torneos

**Objetivo**: el cambio arquitectónico más importante de todo el plan — pasar de "Competition Lab efímero en token de sesión" a un Tournament Runtime que persiste en base de datos: estado, participantes, encuentros, resultados, decisiones, eventos.

**Dependencias**: Fase 5 — necesita el grafo y los 4 motores ya estables.

**Alcance**: nuevas tablas (`tournament_instances` o equivalente, más relacionadas), snapshot inmutable de la configuración de plantilla al iniciar el torneo (para que ediciones futuras de la plantilla no alteren retroactivamente un torneo ya en curso), capacidad de reanudar un torneo tras cerrar sesión y volver otro día.

**Riesgos**: es el cambio de mayor superficie de todo el plan — requiere diseño cuidadoso de esquema antes de escribir migraciones, a evaluar en el kickoff propio de esta fase.

**Resultado esperado**: un torneo puede jugarse a lo largo de varios días sin perder estado.

---

### FASE 7 — Torneos reales con Entidades de la Biblioteca

**Objetivo**: conectar el Competition Lab/Runtime con `Entity`/`EntityVersion` reales en vez de participantes siempre sintéticos.

**Dependencias**: Fase 6, más los tests críticos de `VersionResolverService`/`AttributeContextService` (ver Fase 9 — se recomienda explícitamente adelantar ese subconjunto de tests antes de esta fase, no esperar a la consolidación completa de Biblioteca).

**Resultado esperado**: un usuario puede crear "Naruto vs Sasuke" con sus Entidades reales de la Biblioteca y obtener un resultado real y persistido.

---

### FASE 8 — Historial y estadísticas

**Objetivo**: convertir un torneo terminado en información reutilizable — historial por participante (torneos jugados, campeonatos, victorias/derrotas) y por torneo (bracket, fases, resultados, campeón).

**Dependencias**: Fase 7.

---

### FASE 9 — Consolidación de Biblioteca y Comunidad

**Objetivo**: auditoría formal de toda la Biblioteca ahora que los Torneos ya la usan de verdad (EntityTypes, Attributes, Options, Groups, Relationships, Conditions, Collections, Versions, Policies, Cloning, Community, Hub), con foco especial en cubrir con tests `VersionResolverService`, `AttributeContextService`, `EntityBaseVersion`, `EntityPresentation`.

**Nota importante**: la recomendación explícita de esta auditoría es **adelantar la parte de testing de estos 4 componentes específicos antes de la Fase 7**, no esperar a esta consolidación completa — sustentan directamente la fiabilidad de "qué Entity/versión participa en el torneo", y siguen sin ningún test hoy.

**Dependencias**: Fase 8 para la auditoría completa; el subconjunto crítico de tests puede y debe adelantarse antes de la Fase 7.

---

### FASE 10 — Universos

**Objetivo**: el módulo que motivó la visión original del usuario. `Universe` (espacio independiente), `UniverseCompetitor` (contexto competitivo de una Entity dentro de un Universo concreto — ranking, puntos, victorias — sin copiar la Entity, que sigue siendo canónica), `UniverseSeason` (temporadas con estados PLANNED/ACTIVE/COMPLETED/ARCHIVED), `UniverseTournamentDefinition` (cómo se usa una `TournamentTemplate` dentro de un Universo concreto, con elegibilidad y overrides de configuración), recurrencia de torneos entre temporadas.

**Dependencias**: Fase 9 — tanto el roadmap propio del usuario como esta auditoría coinciden en que construir Universos antes de tener un motor de torneos persistente y probado sería construir sobre una base inestable.

**Alcance**: ya diseñado en gran detalle por el propio usuario en `docs/md/09-Para Futuro.md` (secciones 44–65) — CRUD de Universe, agregar competidores por filtros de atributos (ej. "Aldea = Konoha AND Tipo = Personaje"), elegibilidad por torneo, diferenciación explícita entre ELIGIBLE y SELECTED, estrategias de selección (MANUAL/RANDOM/RANKING/TOP_N/ATTRIBUTE).

---

### FASE 11 — Motor de Simulación

**Objetivo**: un `ResultProvider` más allá del aleatorio simple ya existente en el Competition Lab — resolución de enfrentamientos basada en atributos configurables (ej. 60% poder + 20% velocidad + 10% inteligencia + 10% resistencia).

**Dependencias**: Fase 10 — necesita competidores de Universo con atributos efectivos ya resueltos.

---

### FASE 12 — Recompensas y Ranking de Universo

**Objetivo**: `RewardTemplate` (tipos TROPHY/MEDAL/POINTS/TITLE/ITEM/CURRENCY), `TournamentRewardSlot` (posiciones premiadas configurables), ranking contextualizado por Universo — una misma Entity puede ser #1 en un Universo y #18 en otro; el ranking no es un atributo global de la Entity.

**Dependencias**: Fase 10.

---

### FASE 13 — Plataforma pública, Comunidad ampliada y Producción

**Objetivo**: compartir/clonar `TournamentTemplate` y `RewardTemplate` vía Comunidad, perfiles públicos de torneo (bracket, resultados, historial visibles sin login), y el cierre técnico de producción — auditoría de seguridad (Policies, CSRF, mass assignment, XSS, uploads), integridad transaccional (concurrencia, race conditions, idempotencia), optimización de base de datos (índices, N+1), pirámide de tests, CI/CD, observabilidad, documentación final.

**Dependencias**: todas las anteriores.

## 9. Dependencias entre fases

```
Fase 1 (cerrar SE)
   ↓
Fase 2 (Round Robin) → Fase 3 (Group Stage) → Fase 4 (Swiss)
   ↓                                              ↓
   └──────────────────→ Fase 5 (Graph multi-fase) ┘
                              ↓
                    Fase 6 (Runtime persistente)
                              ↓
          [adelantar aquí: tests de VersionResolverService /
                    AttributeContextService, de la Fase 9]
                              ↓
                    Fase 7 (Entidades reales)
                              ↓
                    Fase 8 (Historial/estadísticas)
                              ↓
                Fase 9 (Consolidación completa Biblioteca)
                              ↓
                       Fase 10 (Universos)
                              ↓
                       Fase 11 (Simulación)
                              ↓
                    Fase 12 (Recompensas/Ranking)
                              ↓
              Fase 13 (Plataforma pública / Producción)
```

Cada fase construye sobre un contrato que la anterior debe dejar estable. No se salta ninguna hacia adelante: Round Robin/Group Stage/Swiss (2–4) comparten el patrón de persistencia que decida la Fase 2; el Runtime persistente (6) necesita el grafo y los 4 motores ya cerrados (5); Universos (10) necesita torneos reales, persistentes y con historial (7–9) para no repetir el riesgo de construir sobre una base inestable que el propio roadmap del usuario ya identificó como el mayor peligro del proyecto.

## 10. Criterios de aceptación

Ver criterios específicos por fase en la sección 8. Regla general, heredada directamente del roadmap que el propio usuario ya había definido: **una funcionalidad no está terminada porque la interfaz existe — está terminada cuando configuración, dominio, runtime, frontend y pruebas representan el mismo comportamiento.** Ninguna fase se da por cerrada solo porque el código fue escrito; se cierra cuando cumple su matriz de criterios de aceptación con evidencia (tests pasando, auditoría documentada).

## 11. Pruebas

- **Fases de torneos (1–5)**: tests unitarios de calculadoras/validadores, tests de feature/HTTP del CRUD completo y del flujo de configuración, y al menos un caso end-to-end jugado en el Competition Lab por formato.
- **Fase 9 (adelantado antes de Fase 7)**: tests específicos de `VersionResolverService` (resolución por catálogo con grupos AND/OR, fallback a `is_default`, herencia con HIDE, detección de ciclos) y de `AttributeContextService` (reglas SHOW/HIDE/REQUIRE, relaciones ALLOWS/BLOCKS entre opciones, recálculo de jerarquía).
- **Fases 6–13**: tests de integridad transaccional — persistencia correcta, reanudación de torneos, snapshots inmutables de configuración, ausencia de condiciones de carrera en registro concurrente de resultados.

## 12. Riesgos

- Cambiar la estrategia de persistencia de estructura en las Fases 2–4 es un rediseño de esquema, no una simple adición — riesgo de retrabajo si se decide mal en la Fase 2, ya que las Fases 3–4 heredan esa decisión.
- El Runtime persistente (Fase 6) es el cambio de mayor superficie de todo el plan; requiere diseño de esquema cuidadoso antes de escribir la primera migración.
- Construir Universos (Fase 10) antes de tiempo repetiría el error que el propio roadmap del usuario ya identificó como el mayor riesgo del proyecto: "que existan muchos módulos parcialmente implementados pero ninguno completamente funcional".
- Postergar los tests de `VersionResolverService`/`AttributeContextService` hasta la Fase 9 completa (en vez de adelantarlos antes de la Fase 7) arriesga construir "torneos reales" sobre lógica de resolución de versiones no verificada — es el riesgo silencioso más peligroso de todo el plan porque no se manifiesta hasta que algo ya está en producción con datos reales.
- Los posibles fallos de autorización ya documentados por el propio usuario (Swiss `authorize(): false`, `allow_cloning` no respetado) podrían seguir presentes: deben reverificarse explícitamente en la Fase 1 y Fase 4, no asumirse corregidos por los parches recientes sin comprobarlo.

## 13. Deuda técnica (puede dejarse para después de V1)

- Inconsistencia en `isActive()` entre Policies (algunas verifican, otras no, en los mismos métodos `update`/`delete`).
- Campo `json_value` muerto en `EntityAttributeValue`/`EntityVersionAttributeValue`.
- Lógica de presentación duplicada entre `EntityController::show()` y los accessors del modelo `Entity`.
- `recalculateHierarchyLevels()` con N updates individuales en vez de bulk update.
- Convención de migración distinta (`unsignedBigInteger` + `foreign()` manual) en las tablas del sistema de contexto de atributos.
- Labels fantasma `LEAGUE`/`CUSTOM` en `PhaseTemplate` sin implementación real.
- 5 archivos `.patch` sueltos en la raíz del repositorio, ya aplicados — se pueden archivar o eliminar cuando el usuario lo confirme.
- Tabla de "Estado general del proyecto" desactualizada en `README.md`.

## 14. Futuras mejoras (fuera de alcance de V1 y V2)

IA avanzada, chat social, gamificación, rankings públicos, aplicación móvil, API pública completa, motores League/Custom, doble eliminación — todo explícitamente pospuesto por el propio roadmap del usuario, y esta auditoría no encuentra ningún motivo técnico para adelantarlo.

---

## Sistema de trabajo para las siguientes sesiones

1. **Seleccionar fase**: se elige la siguiente fase de este documento (empezando por la Fase 1).
2. **Planificar**: antes de tocar código, se analiza la fase específica en detalle y se presenta un plan de implementación concreto.
3. **Aprobación**: se espera confirmación explícita del usuario antes de implementar.
4. **Implementación**: una vez aprobado, se modifican solo los archivos necesarios, manteniendo el alcance de la fase.
5. **Pruebas**: se ejecutan las pruebas relevantes; los errores encontrados relacionados con la fase se investigan y corrigen.
6. **Revisión**: se explica qué se modificó, qué archivos, qué se implementó, qué se probó, qué problemas quedaron pendientes.
7. **Verificación humana**: el usuario revisa el resultado antes de continuar.
8. **Siguiente fase**: solo se avanza cuando el usuario confirma que la fase actual está correcta.

Si en el camino una fase de este documento resulta técnicamente incorrecta a la luz de lo que se descubra implementándola, se debe explicar por qué y proponer una actualización de este documento — no seguirlo ciegamente.
