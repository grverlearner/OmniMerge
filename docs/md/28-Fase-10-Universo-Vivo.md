# FASE 10 — Universo vivo

## 1. Estado actual (revisión enfocada)

| Pieza | Estado |
|---|---|
| `Universe` | CRUD completo. **`settings` (JSON) existe pero nunca se lee ni se escribe** |
| `UniverseEntity` | Copia independiente con identidad, atributos y versiones. Ficha con estadísticas. Sin progresión ni ranking |
| `UniverseSeason` | CRUD + estados + regla de temporada única activa. **Sin ficha propia**: no se puede ver qué pasó en ella |
| `UniverseTournament` | Solo `name`, `description`, `status`. **Sin imagen, sin contexto, sin recurrencia** |
| `TournamentInstance` + runtime | Completo (Fase 6-8) |
| Estadísticas | Existen por entidad del Universo. **No hay ranking**: el sidebar lo marca "Próximo" |
| Actividad | No existe a nivel de Universo |
| Explorador | No existe |
| `universes/show` | Resumen correcto pero plano: cifras y dos listas |

**Diagnóstico**: los cimientos están bien. Lo que falta es lo que convierte un contenedor en un mundo — clasificación, tiempo con contenido, recurrencia, actividad y un panel que cuente qué está pasando.

## 2. Objetivo

Que entrar a un Universo responda de un vistazo: *qué temporada corre, quién manda, qué se juega ahora, qué pasó últimamente*.

## 3. Decisiones de arquitectura

**El ranking se deriva, no se almacena.** Sale de `tournament_instance_participants` agregado por `universe_entity_id`. Guardarlo obligaría a mantenerlo sincronizado; derivarlo lo hace siempre correcto. El sistema de puntos es configurable por Universo.

**La configuración vive en `settings`**, la columna JSON que ya existe y nunca se usó. Un objeto tipado (`UniverseSettings`) le da acceso seguro con valores por defecto, sin migración.

**La actividad sí se almacena.** Podría derivarse de `tournament_instance_events`, pero esos eventos son del motor (`DISPATCH_START`, `NODE_COMPLETED`), no del mundo. Una tabla propia y pequeña, escrita en los momentos que importan, es más honesta y más barata de leer.

**La recurrencia se calcula, no se agenda.** Nada de scheduler: la definición dice "cada N temporadas desde la temporada X" y un método responde si toca en una temporada dada. Suficiente para lo pedido y extensible.

**La progresión se prepara, no se implementa.** Una columna JSON `progression` en `universe_entities` con la forma `{atributo: {inicial, actual, min, max}}`, más un servicio que sabe aplicarle ajustes. **Ningún motor la modifica todavía**: existe para que una recompensa futura tenga dónde escribir sin rehacer nada.

## 4. Cambios de base de datos

**Tres migraciones, todas aditivas.**

```
universe_tournaments
  + image                   string nullable
  + context                 text nullable      -- reglas/ambientación
  + recurrence_mode         string(20) default 'ONCE'
                            -- ONCE | EVERY_SEASON | EVERY_N_SEASONS | MANUAL
  + recurrence_interval     unsignedInteger nullable
  + first_season_number     unsignedInteger nullable

universe_entities
  + progression             json nullable      -- preparado, sin motor

universe_activities          (tabla nueva)
  id, universe_id FK cascade,
  universe_season_id FK nullOnDelete,
  universe_entity_id FK nullOnDelete,
  tournament_instance_id FK nullOnDelete,
  type string(40),           -- COMPETITION_STARTED | COMPETITION_COMPLETED |
                             -- CHAMPION_CROWNED | SEASON_STARTED | ENTITIES_IMPORTED
  icon string(8), message string(255), context json nullable,
  occurred_at timestamp, timestamps
  índice (universe_id, occurred_at)
```

## 5. Backend

**Nuevos**
- `UniverseSettings` — acceso tipado a `universes.settings` con defaults.
- `UniverseRankingService` — clasificación derivada + puntos configurables.
- `UniverseActivityRecorder` + modelo `UniverseActivity`.
- `UniverseProgressionService` — lee/ajusta `progression` (preparación).
- `UniverseRankingController`, `UniverseExplorerController`.
- `UniverseSeasonController::show`, `UniverseTournamentService::occursInSeason()`.

**Modificados**
- `UniverseController::show` — panel de control.
- `TournamentInstanceRuntimeService` — registra actividad al iniciar y completar.
- `UniverseEntityImporter` — registra actividad e inicializa `progression`.
- `UniverseSeasonService` — registra actividad al activar.
- Requests de torneo: imagen, contexto, recurrencia.

## 6. Frontend

- **`universes/show`** — panel: temporada en curso, líder del ranking, últimos campeones, próximos torneos, actividad reciente, accesos rápidos.
- **`universes/ranking`** — clasificación con podio visual e imágenes.
- **`universes/seasons/show`** — qué pasó en esa temporada: torneos, campeones, cifras.
- **`universes/explorer`** — base del explorador: agrupación por tipo y por atributo, con imágenes.
- **`universes/entities/show`** — añade posición en el ranking e historial agrupado por temporada.
- **`universes/tournaments/*`** — imagen, contexto y recurrencia; la ficha muestra en qué temporadas ocurre.
- **Configuración** — pestaña de Competencia (puntos del ranking) sobre `settings`.
- **Sidebar** — Rankings deja de ser "Próximo"; se añade Explorar.

## 7-17. Alcance por área

| Área | Esta fase |
|---|---|
| Participantes | Ranking, posición, historial por temporada, `progression` preparada |
| Temporadas | Ficha propia con sus torneos, campeones y cifras |
| Torneos | Imagen, contexto, recurrencia (4 modos) |
| Recurrencia | `ONCE`, `EVERY_SEASON`, `EVERY_N_SEASONS`, `MANUAL` + cálculo de próxima ocurrencia |
| Ranking | Derivado, puntos configurables, contextual al Universo |
| Progresión | Estructura + servicio. **Sin motor que la modifique** |
| Explorer | Navegación, página y filtros por tipo/atributo. Sin visualización avanzada |
| Actividad | Tabla + registro en los momentos clave + feed en el panel |
| Configuración | General (ya existe) + Competencia (puntos). Nada que no funcione de verdad |

**Deliberadamente fuera**: motor de simulación, habilidades, economía, diplomacia, mapas, quests, social, multiplayer, tipos de juego nuevos (numérico, N participantes). Sobre esto último: el runtime ya acepta cualquier motor que respete el contrato de `LabPhaseEngine`, así que un juego nuevo se añade como motor de fase sin tocar Universe.

## 18. Orden de implementación

1. Migraciones (3)
2. `UniverseSettings` + `UniverseActivity` + recorder
3. `UniverseRankingService`
4. Recurrencia en `UniverseTournament`
5. `progression` + servicio
6. Controladores y rutas (ranking, explorer, season show)
7. Vistas: panel, ranking, temporada, explorador
8. Enriquecer torneos y ficha de participante
9. Registro de actividad en runtime/importador/temporadas
10. Sidebar y verificación

## 19. Criterios de aceptación

- El panel del Universo responde sin navegar: temporada activa, líder, próximos torneos, actividad.
- El ranking es contextual: la misma Entidad puede ser #1 en un Universo y #18 en otro.
- Una temporada muestra qué se jugó y quién ganó.
- Un torneo recurrente indica en qué temporadas ocurre.
- La Biblioteca sigue sin datos competitivos.
- Nada de lo existente se rompe.

## 20. Cierre de la fase

Fase cerrada el 21 de agosto de 2026. Verificado end-to-end contra MySQL
(`omnimerge`) y con `view:cache`, que compila todas las plantillas Blade.

### 20.1 Qué quedó funcionando

| Área | Estado |
|---|---|
| Panel del Universo como centro de control | Funcional |
| Clasificación contextual (derivada, siempre correcta) | Funcional |
| Sistema de puntos configurable por Universo | Funcional |
| Recurrencia de torneos entre temporadas | Funcional (calculada) |
| Vista de temporada como era del mundo | Funcional |
| Ficha de participante con crónica por temporada | Funcional |
| Explorador del Universo | Funcional (base) |
| Registro de actividad del mundo | Funcional |
| Ambientación de torneos (portada, contexto) | Funcional |
| Progresión del participante | **Preparada, no activa** |

### 20.2 Decisiones que conviene recordar

**La clasificación es derivada, no almacenada.** Se calcula desde
`tournament_instance_participants` en cada petición. Nunca puede quedar
desincronizada, y cambiar el sistema de puntos recalcula la historia entera
de forma retroactiva — que es el comportamiento deseado en un mundo cuyas
reglas el usuario todavía está afinando. Si el volumen crece, el punto de
cacheo es `UniverseRankingService::ranking()`, sin tocar nada más.

**La actividad sí se almacena.** Es un hecho del mundo ("empezó la temporada
3"), no un cálculo sobre resultados; no se puede derivar. `UniverseActivityRecorder`
traga sus propios errores a propósito: que falle el registro de una anécdota
nunca debe impedir que empiece un torneo.

**La recurrencia se calcula, no se programa.** `occursInSeason()` /
`nextSeasonNumber()` son funciones puras sobre el número de temporada. No hay
cron, ni tabla de ocurrencias, ni filas fantasma que limpiar si el usuario
cambia la recurrencia después.

**La progresión existe pero nadie la mueve.** `UniverseProgressionService::adjust()`
está escrito y probado, y `universe_entities.progression` se inicializa al
importar. Ningún flujo lo invoca. Es deliberado: mover atributos según
resultados es simulación (Fase 11), y presentar una barra que no se mueve como
si fuera progresión real habría sido exactamente el engaño que la fase pedía
evitar. La interfaz muestra la progresión como "preparada".

### 20.3 Fuera de alcance (deliberadamente)

Simulación avanzada, IA, habilidades, economía, diplomacia, mapas, misiones,
red social y multijugador. También: mover atributos por resultados, y
sincronización Biblioteca → Universo (importar sigue siendo una copia única).

