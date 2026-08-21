# FASE 6 — Tournament Runtime persistente

## 1. Auditoría del runtime actual

Hallazgos verificados sobre el código, no supuestos:

| Hallazgo | Consecuencia para esta fase |
|---|---|
| **El motor es una función pura.** `TournamentGraphRuntimeService::initialize/step/run` tiene la firma `(array $state, TournamentTemplate $template) → array $state`. Un `grep` de `save/update/create/delete/DB::` sobre todo `CompetitionLab/` devuelve **cero escrituras**. | El motor ya es persistible tal cual. Fase 6 es una capa de persistencia, no un motor nuevo. |
| **El estado ya es un array serializable** (`LabStateFactory`): `participants`, `starts`, `nodes`, `connections`, `terminals`, `timeline`, `summary`, `graph_runtime`. Hoy se cifra en un token (`LabStateTokenService`) y vive en `sessionStorage`. | Guardar ese mismo array en una columna JSON sustituye al token sin tocar el motor. |
| **`LabPhaseEngine`** es `prepare(PhaseTemplate, ids, participants): array` + `submit(array $runtime, ...): array`. Los tres motores (SE/RR/GS) ya cumplen ese contrato. | No hay que tocar los motores. |
| **`TournamentGraphRuntimeService::loadGraph()`** hace `$template->load([...23 relaciones...])` — lee el **grafo vivo**. | Es el punto exacto donde se filtraría la configuración modificada. Es también la definición autoritativa de "qué hay que congelar". |
| **`CompetitionLabService::execute()`** = decodificar token → validar propiedad → `match($action)` → responder. El `match` cubre `START/PAUSE/RESUME/RESET`, `PREPARE_NODE`, `SUBMIT_MATCH_RESULT`, `SUBMIT_ENCOUNTER_RESULT`, `SIMULATE_MATCH`, `SIMULATE_ROUND`, `RESOLVE_MANUAL_DECISION`, `START_TOURNAMENT`, `STEP_RUNTIME`, `RUN_TOURNAMENT`. | Ese `match` es el despachador que quiero reutilizar. Se extrae a un método público; **no se mueve ninguna lógica**. |
| **El participante ya prevé entidades reales**: `PreviewParticipantFactory` emite `entity_id` y `entity_version_id` (hoy `null`). El identificador es la clave del array (`lab_id`), opaca para el motor. | Se pueden inyectar competidores reales sin tocar el motor. |
| **Tres puntos consultan la BD por método de relación**: `SingleEliminationLabEngine:94` (`singleEliminationRounds()->exists()`) y `CompetitionLabService:550,694` (`graphNodes()->with()->find()`). | Son las **únicas** fugas posibles del snapshot. Hay que corregirlas para que prefieran la relación ya cargada. |
| **El lazy loading no está bloqueado** (no hay `preventLazyLoading`). | Una hidratación incompleta filtraría datos vivos en silencio. La hidratación debe ser completa y verificarse. |
| **El componente Alpine `competitionLab`** (1301 líneas) hace `POST {action, state_token, ...}` → `{state, state_token}` y persiste en `sessionStorage`. | Se reutiliza entero añadiendo un modo `persistent` (3 guardas). |

**Conclusión**: no se escribe un motor nuevo. Se añade alrededor del motor existente una capa que (a) congela su entrada, (b) guarda su estado y (c) proyecta ese estado a tablas consultables.

## 2. Arquitectura propuesta

```
TournamentTemplate      diseño reutilizable          (Biblioteca)
        ↓
UniverseTournament      adopción dentro del Universo (configuración)
        ↓  crear competición  → SNAPSHOT INMUTABLE
TournamentInstance      competición real             (ejecución)
        ↓
TournamentInstanceRuntimeService   ← persistencia/orquestación
        ↓  delega en
CompetitionLabService::applyAction ← motor existente, sin cambios de lógica
```

La separación que pediste es correcta y se mantiene intacta. `TournamentInstance` cuelga de `UniverseTournament`, nunca de `TournamentTemplate` directamente.

El Competition Lab **sigue existiendo sin cambios funcionales**: es el banco de pruebas efímero de una plantilla. La diferencia visible es el lenguaje: Lab = "probar la plantilla"; Competición = "esto cuenta".

## 3. Modelo de datos

7 tablas. Justificación de cada una:

| Tabla | Por qué existe | Por qué no es JSON |
|---|---|---|
| `tournament_instances` | Fila ligera y listable | Se lista, filtra y ordena |
| `tournament_instance_snapshots` | Configuración congelada (1:1) | Se escribe una vez y no se consulta relacionalmente → JSON. **Tabla aparte** para que la fila pesada no se reescriba en cada acción |
| `tournament_instance_states` | Estado vivo del motor (1:1) + `revision` | El motor trabaja sobre el array completo; normalizarlo sería reescribir el motor |
| `tournament_instance_participants` | Participantes congelados de ESA ejecución | Pediste explícitamente independencia de `UniverseCompetitor` |
| `tournament_instance_phases` | Qué fases ejecuta y su estado | Pediste explícitamente que la instancia lo sepa |
| `tournament_instance_matches` | Encuentros, resultados, ganador/perdedor | Pediste listarlos y consultarlos |
| `tournament_instance_events` | Ledger append-only | Historial reconstruible |

Las 4 últimas son **proyecciones** del estado JSON, regeneradas tras cada acción. El JSON es la fuente de verdad del motor; las tablas son la vista consultable. Esto es la "combinación" que pedías evaluar, y evita las ~23 tablas espejo que exigiría normalizar la configuración.

### Columnas

```
tournament_instances
  id, universe_id FK, universe_tournament_id FK, universe_season_id FK nullable,
  tournament_template_id FK nullable (trazabilidad, nullOnDelete),
  sequence_number, code (CMP%06d), name,
  status (DRAFT|RUNNING|PAUSED|COMPLETED|CANCELLED),
  runtime_status (espejo del motor: READY|RUNNING|COMPLETED|BLOCKED|AWAITING_DECISION),
  participant_count, started_at, completed_at, timestamps, softDeletes
  índices: (universe_id,status), (universe_tournament_id), unique(universe_id,code)

tournament_instance_snapshots
  id, tournament_instance_id FK unique cascade, schema_version, hash,
  snapshot JSON(longText), created_at            ← sin updated_at: es inmutable

tournament_instance_states
  id, tournament_instance_id FK unique cascade, schema_version,
  revision unsigned, state JSON(longText), timestamps

tournament_instance_participants
  id, tournament_instance_id FK cascade,
  runtime_key (clave en state.participants), universe_competitor_id FK nullOnDelete,
  entity_id FK nullOnDelete, name, seed, source_start_id,
  status, matches, wins, draws, losses, points,
  final_location_type, final_location_name, timestamps
  índices: (tournament_instance_id,status), unique(tournament_instance_id,runtime_key)

tournament_instance_phases
  id, tournament_instance_id FK cascade, node_id, node_code, node_name,
  phase_type, status, participant_count, timestamps
  unique(tournament_instance_id,node_id)

tournament_instance_matches
  id, tournament_instance_id FK cascade, node_id, runtime_match_id,
  round_number, label, status,
  participant_a_key, participant_b_key, participant_a_name, participant_b_name,
  score_a, score_b, winner_key, loser_key, is_draw, series JSON nullable, timestamps
  unique(tournament_instance_id,runtime_match_id), índice (tournament_instance_id,status)

tournament_instance_events
  id, tournament_instance_id FK cascade, sequence, type, level, message,
  context JSON nullable, created_at
  índice (tournament_instance_id,sequence)
```

`universe_competitor_id` y `entity_id` son `nullOnDelete`: si borras un competidor del Universo, el histórico conserva `name`/`seed` congelados y solo pierde el enlace. Eso es exactamente lo que pediste.

## 4. Estrategia de snapshot

**JSON inmutable + hidratación a objetos Eloquent en memoria.**

- **Qué se congela**: exactamente el árbol de relaciones que `loadGraph()` carga. No lo invento — lo derivo del código, que es la definición autoritativa de lo que el motor lee. Incluye `TournamentTemplate` + starts + nodes + `PhaseTemplate` completa (exits, input gates, entry ports, ajustes y reglas de SE/RR/GS, rondas/encuentros/slots/resultados de SE, grupos y reglas de avance de GS, tiebreakers) + conexiones + terminales.
- **Formato**: recursivo y genérico, `{'class', 'attributes', 'relations'}`. No hay 23 mapeos escritos a mano: un `TournamentSnapshotBuilder` recorre un mapa de relaciones declarado y un `TournamentSnapshotHydrator` reconstruye por nombre de clase.
- **Cómo se devuelve al motor**: modelos no persistidos con `setRelation()`. El motor recibe un `TournamentTemplate` normal y funciona sin cambios.
- **Protección contra fugas**: `SnapshotTournamentTemplate extends TournamentTemplate` y `SnapshotPhaseTemplate extends PhaseTemplate` sobrescriben `load()`/`loadMissing()` como no-op, de modo que el `$template->load([...])` de `loadGraph()` no puede refrescar contra la plantilla viva. Complementado con los 3 arreglos de la sección 1 y con hidratación completa.
- **Versionado**: `schema_version` + `hash` en la tabla. Si mañana cambia el formato, un snapshot viejo se detecta y se rechaza con un mensaje claro en vez de romperse.
- **Cuándo se toma**: al **crear** la competición, no al iniciarla. Motivo: la pantalla de asignación de participantes ya depende de los `starts` del grafo; si la plantilla cambiara entre crear e iniciar, la asignación quedaría inconsistente. Congelar en la creación cumple con creces "una vez iniciado no cambia". Una competición en `DRAFT` que quedó obsoleta se borra y se crea otra.

**Descartado**: tablas normalizadas para el snapshot (≈23 tablas espejo, cero consultas relacionales sobre ellas) y versionar `TournamentTemplate` con copias reales (contamina la Biblioteca con plantillas fantasma).

## 5. Universe → Season → UniverseTournament → TournamentInstance

- `TournamentInstance` **pertenece obligatoriamente** a `UniverseTournament` (y por él, a `Universe`). `universe_id` se desnormaliza para poder listar todas las competiciones de un Universo sin join.
- `universe_season_id` es **opcional**. Se rellena automáticamente con la temporada en curso al crear, y se puede dejar vacía. Queda como dato informativo inmutable.
- **No se añade ninguna regla de temporada**: nada de "no puedes jugar si no hay temporada activa", ni recurrencia, ni cierre en cascada. Eso es Sprint U7/U8.

## 6. Persistencia de participantes

Al crear la competición se eligen `UniverseCompetitor`s y se reparten entre los `starts` del grafo. Se congelan en `tournament_instance_participants` con `name` y `seed` copiados en ese momento, más el enlace `universe_competitor_id`/`entity_id`.

En el estado del motor, la clave del participante pasa a ser `UC-<id>` (mismo campo `lab_id`, que el motor trata como cadena opaca), y se rellenan `entity_id`/`entity_version_id`, que ya existían vacíos.

## 7. Fases, encuentros y resultados

Tras cada acción, `TournamentInstanceProjector` recorre el estado y hace `upsert` de fases, encuentros y participantes, y **añade** los eventos nuevos del `timeline` al ledger (append-only, nunca reescribe). Es idempotente: proyectar dos veces el mismo estado da el mismo resultado.

## 8. Estados de TournamentInstance

```
DRAFT ──iniciar──► RUNNING ──►COMPLETED
  │                  │  ▲
  │                  │  └──reanudar──┐
  │                  ▼               │
  │                PAUSED ───────────┘
  └──cancelar──► CANCELLED ◄── (desde RUNNING/PAUSED)
```

- `DRAFT`: snapshot congelado, participantes asignados, aún no arranca. Es el único estado en el que se puede **borrar**.
- `RUNNING`: el motor ha sido inicializado. `runtime_status` refleja el detalle (`BLOCKED`, `AWAITING_DECISION`…).
- `PAUSED`: pausa explícita del usuario.
- `COMPLETED`: se fija cuando `graph_runtime.status === 'COMPLETED'`, con `completed_at`.
- `CANCELLED`: terminal, conserva todo el histórico.

Intentar actuar sobre `COMPLETED`/`CANCELLED` devuelve un error claro, no un 500.

## 9. Reanudación

No hay nada que reanudar: **no existe estado en sesión**. Cada acción es `cargar estado de BD → hidratar snapshot → ejecutar → guardar`. Cerrar el navegador, cerrar sesión o reiniciar el servidor no afecta.

Concurrencia: `revision` en `tournament_instance_states`. El cliente envía la revisión que tiene; si no coincide (dos pestañas), la acción se rechaza con un mensaje pidiendo recargar, en vez de pisar resultados. Toda acción va en `DB::transaction` con `lockForUpdate` sobre la fila de estado.

## 10-11. Integración con motores y Tournament Graph

`CompetitionLabService` gana **un método público**, `applyAction(array $state, TournamentTemplate $template, string $action, array $payload): array`, que contiene el `match` que hoy está dentro de `execute()`. `execute()` pasa a llamarlo. **No se mueve ni se reescribe ninguna acción.** El servicio persistente inyecta `CompetitionLabService` y llama a `applyAction()`.

Correcciones puntuales para que el snapshot no se filtre (3 sitios, comportamiento equivalente):
1. `SingleEliminationLabEngine:94` → usar la relación cargada en vez de `->singleEliminationRounds()->exists()`.
2. y 3. `CompetitionLabService:550,694` → helper `findNode()` que prefiere `graphNodes` ya cargada.

El Tournament Graph se usa exactamente igual; simplemente el `TournamentTemplate` que recibe viene del snapshot.

## 12. Autorización

`TournamentInstancePolicy` siguiendo el patrón existente (`UniversePolicy`): propiedad vía `$instance->universe->user_id === $user->id`; `viewAny`/`create` exigen `$user->isActive()`. Autodescubrimiento de Laravel, sin registro manual. Las rutas anidadas usan `scopeBindings()`, igual que el resto del módulo Universos, de modo que una competición de otro Universo da 404 y de otro usuario da 403. No se crea ningún sistema de permisos paralelo.

## 13. Interfaz

Vocabulario: la plantilla se "diseña", el torneo del Universo se "configura", y lo real es una **Competición**.

- **Sidebar del Universo**: nueva entrada `⚔ Competiciones` en "Contenido", con contador.
- `/universes/{u}/competitions` — todas las competiciones del Universo, con filtro por estado y tarjetas que distinguen a simple vista En curso / Finalizada / Borrador / Cancelada.
- `/universes/{u}/tournaments/{ut}` — **nueva** página del torneo configurado: qué plantilla usa, sus competiciones, y el botón "Iniciar nueva competición". Es el puente que faltaba entre configuración y ejecución.
- `/universes/{u}/competitions/create?universe_tournament_id=` — elegir temporada y repartir competidores entre los starts.
- `/universes/{u}/competitions/{c}` — **workspace**, reutilizando los parciales del Lab (`automatic-runtime`, `manual-engine`, `manual-decision`, `participants-inspector`) con una cabecera distinta: banda que deja claro que es una competición real y persistente, con estado, temporada y fechas.

El componente Alpine se reutiliza con `persistent: true` (sin `sessionStorage`, sin token, con `revision`).

## 14. Migraciones

7 migraciones nuevas, todas creaciones de tabla; **ninguna toca tablas existentes ni datos existentes**, y no se edita ninguna migración histórica. `universe_tournaments`, `universe_competitors`, `universe_seasons`, `tournament_templates` y todo el grafo quedan intactos. Por tanto no hay migración de datos que hacer.

## 15. Servicios, controladores y requests

- `TournamentSnapshotBuilder`, `TournamentSnapshotHydrator`, `SnapshotTournamentTemplate`, `SnapshotPhaseTemplate`
- `TournamentInstanceStateFactory` (estado inicial con competidores reales)
- `TournamentInstanceService` (crear, iniciar, pausar, reanudar, cancelar, borrar)
- `TournamentInstanceRuntimeService` (aplicar acción con bloqueo + revisión + proyección)
- `TournamentInstanceProjector` (estado JSON → tablas)
- `TournamentInstancePolicy`
- `StoreTournamentInstanceRequest`, `TournamentInstanceActionRequest`
- `TournamentInstanceController` (index/create/store/show/action/pause/resume/cancel/destroy)
- `UniverseTournamentController::show()` (nuevo)

## 16. Documentación

Este documento, más una nota en `23-Fase-Universos-Workspace.md` cerrando el hueco que dejaba (`TournamentInstance` ya existe) y en `MASTER_PLAN.md` marcando la Fase 6.

## 17. Riesgos y decisiones

| Riesgo | Mitigación |
|---|---|
| **Fuga del snapshot** por lazy loading no bloqueado | Hidratación completa + subclases con `load()` no-op + los 3 arreglos + verificación explícita de que modificar la plantilla no altera una competición en curso |
| Extraer `applyAction` rompe el Lab | Es un corte, no una reescritura: el `match` se mueve entero y `execute()` lo llama. Se verifica el Lab después |
| El estado JSON crece mucho | Tabla aparte de la fila listable; el snapshot, en otra tabla más, no se reescribe nunca |
| Dos pestañas pisándose | `revision` + `lockForUpdate` + transacción |
| Proyección desincronizada | Es idempotente y se recalcula entera desde el estado tras cada acción; el JSON manda |

**Decisiones**: snapshot al crear (no al iniciar); temporada opcional e informativa; proyecciones en vez de normalizar la configuración; el Lab se conserva sin cambios funcionales; los resultados se registran manualmente o se simulan con los mecanismos que ya existen.

## 18. Orden de implementación

1. Migraciones (7) y ejecución
2. Modelos (7) + relaciones en `Universe`/`UniverseTournament`/`UniverseSeason`
3. Snapshot: builder, hydrator, subclases
4. `applyAction` + los 3 arreglos anti-fuga
5. `TournamentInstanceStateFactory`
6. `TournamentInstanceProjector`
7. `TournamentInstanceService` + `TournamentInstanceRuntimeService`
8. Policy + Requests
9. Controladores + `UniverseTournamentController::show()`
10. Rutas
11. Vistas + sidebar
12. JS modo persistente
13. Verificación (incluida la prueba de inmutabilidad)
14. Documentación final

## 19. Notas de implementación (post-ejecución)

Implementada completa siguiendo las secciones 1-18. Dos desviaciones y un
hallazgo, todos documentados abajo.

### 19.1 Hallazgo importante durante la verificación

La primera prueba de extremo a extremo **falló**, y el fallo destapó un
agujero real en la estrategia de snapshot:

```
Column not found: 'phase_round_robin_settings.snapshot_phase_template_id'
```

Dos causas encadenadas:

1. **Los ajustes de motor se crean de forma perezosa.** Una fase que nunca
   se abrió en su pestaña de Reglas no tiene fila en
   `phase_round_robin_settings`; el motor la crea al vuelo con
   `ensure()` (patrón introducido en la Fase 4). Al congelar, esa relación
   iba `null` al snapshot, y al ejecutar, el motor caía en el `??` y
   consultaba **la base de datos viva**. Es decir: el snapshot tenía un
   agujero por el que se colaba la configuración actual.
2. **Eloquent deriva la clave foránea del nombre de la clase.** Como
   `SnapshotPhaseTemplate` extiende `PhaseTemplate`, cualquier consulta que
   se escapara buscaba `snapshot_phase_template_id`, que no existe.

Correcciones aplicadas:

- `TournamentSnapshotBuilder::materializePhaseSettings()` llama a los mismos
  servicios `ensure()` **sobre la plantilla viva antes de congelar**, de modo
  que el snapshot siempre lleva los ajustes que se habrían usado. Es
  idempotente y no inventa valores: son los defaults reales del motor.
- `SnapshotTournamentTemplate::getForeignKey()` y
  `SnapshotPhaseTemplate::getForeignKey()` devuelven la clave del padre.
  Es defensa en profundidad: si algún día se escapara una relación, fallaría
  de forma visible en lugar de leer datos vivos en silencio.

Sin la prueba empírica este agujero habría pasado la revisión de código sin
problemas: el mapa de relaciones incluía `roundRobinSetting`, simplemente la
fila no existía todavía.

### 19.2 Desviaciones respecto al plan

- El helper de la sección 10 se llama `findGraphNode()`, no `findNode()`.
- La relación de rutas necesitó `Universe::competitions()` (alias de
  `tournamentInstances()`), porque `scopeBindings()` deriva el nombre de la
  relación del parámetro de ruta `{competition}`. Sin ella, una competición
  de otro Universo no habría dado 404.

### 19.3 Verificación realizada

Prueba de dominio de extremo a extremo (script temporal, datos borrados):

| Comprobación | Resultado |
|---|---|
| Snapshot congelado al crear | 11.9 KB, con hash |
| **Renombrar plantilla, fase y nodo con el torneo EN CURSO** | el estado sigue diciendo `F6 Plantilla` / `F6 Liguilla`; **cero contaminación** |
| Reanudar recargando todo desde la base de datos | 3 participantes, revisión 7 |
| Bloqueo optimista con revisión obsoleta | rechazado |
| Proyecciones | 3 encuentros (3 jugados), 1 fase, 9 eventos |
| Clasificación proyectada | líder con 4 pts en 2 partidos |
| Finalización | `status=COMPLETED`, `runtime=COMPLETED`, `completed_at` fijado |
| Competición cerrada | rechaza nuevas acciones |
| Grafo mal formado (salida que deja gente sin ruta) | `runtime_status=BLOCKED` persistido, con diagnósticos |

Prueba HTTP (test temporal contra la base real, eliminado después): las 3
páginas nuevas devuelven 200; crear una competición por POST funciona; el
workspace renderiza; `START_TOURNAMENT` por JSON devuelve
`{state, revision, instance}` y deja la competición en `RUNNING`; una
revisión obsoleta devuelve 422; la acción `RESET` (prohibida en
competiciones reales) devuelve 422; otro usuario recibe 403.

Además: `php -l` sobre todo lo creado/modificado, `php artisan view:cache`
(compila todo Blade del proyecto) y `vite build` sin errores. Sin datos de
prueba residuales.

### 19.4 Qué NO se verificó

No se hicieron pruebas manuales de interfaz en el navegador: las harás tú.
La suite automática del proyecto sigue rota por una incidencia **anterior a
esta fase** (migración `2026_08_08_211827` con `UPDATE ... INNER JOIN`, que
SQLite no soporta), por lo que la verificación HTTP se hizo contra MySQL.

### 19.5 Cómo probarlo desde la interfaz

1. Entra en un Universo con competidores → pestaña **Torneos** → botón
   **⚔ Competiciones** de un torneo configurado.
2. **Iniciar nueva competición**: pon nombre, elige temporada (opcional) y
   reparte competidores entre los puntos de entrada. Guarda.
3. En el workspace, pulsa **▶ Comenzar competición**.
4. Avanza con los controles del recorrido y registra resultados.
5. **La prueba interesante**: con la competición a medias, ve a la plantilla
   original y cámbiale el nombre, o edita las reglas de la fase. Vuelve a la
   competición: seguirá exactamente igual.
6. Cierra sesión, cierra el navegador, vuelve otro día y abre la misma
   competición: continúa donde la dejaste.

## 20. Fuera de alcance

Swiss, rankings, recompensas, historial avanzado, recurrencia entre temporadas, tests automatizados nuevos, segundo motor, cambios en los motores existentes más allá de los 3 arreglos anti-fuga.
