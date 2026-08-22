# FASE 11 — Motor de Juegos, Batallas y Simulación

> Plan de implementación. 21 de agosto de 2026.

## 1. Hallazgo que decide la arquitectura

Battle y Encounter **ya existen** en OmniMerge, con otros nombres:

| Concepto de la Fase 11 | Lo que ya existe |
|---|---|
| Battle | un *match* del runtime + su entrada en `$runtime['series'][$matchId]` |
| Encounter | un *game* dentro de esa serie |
| BO1/BO3/BO5/BO7/BO9/FIXED_GAMES | `MatchSeriesRuntime` (completo, 767 líneas) |
| Quién participa | Tournament Graph (Fases 5-6) |
| Historial de la batalla | `tournament_instance_matches` + proyector (Fase 8) |

Lo único que falta es **quién decide el resultado de un Encounter**. Hoy lo
decide `TournamentGraphRuntimeService::randomScore()` — un `random_int(0, 5)`
incrustado en el motor, llamado desde dos sitios. Esa función es la costura
exacta donde entra el Game Engine.

Por lo tanto esta fase **no crea tablas de Battle ni de Encounter**: sería
duplicar la serie que ya funciona. Crea el motor que le da resultados.

## 2. Arquitectura

```
Universe → UniverseTournament (game_key) → TournamentInstance
    → Phase → Battle (match + serie) → Encounter (game de la serie)
        → GameEngine.roll()      · genera el valor de UN participante
        → GameEngine.adjudicate() · decide el ganador del Encounter
    → serie decide cuándo termina la Battle
    → Tournament Graph decide quién sigue
```

Cada capa mantiene su responsabilidad: **el torneo decide quiénes
participan, el juego decide cómo se resuelve, la serie decide cuándo
termina.**

### Contrato del Game Engine

```php
interface GameEngine
{
    public function definition(): array;                  // catálogo + esquema de stats + controles
    public function defaultStats(array $context = []): array;
    public function normalizeStats(array $stats): array;
    public function roll(array $participant, array $config): array;   // un participante
    public function adjudicate(array $rolls, array $config): array;   // ranking + ganador
}
```

`roll()` separado de `adjudicate()` es lo que hace posible el simulador
interactivo («Generar Naruto», después «Generar Sasuke») **sin** que la
interfaz sepa nada de Highest Number. Un juego futuro que solo pueda
resolverse de golpe deja `roll()` vacío y hace todo en `adjudicate()`.

Los juegos **se declaran en código**, no en una tabla: añadir una clase de
engine lo publica en el catálogo. No hay seed que sincronizar.

### Pureza del motor

El motor de torneos es una función pura sin acceso a base de datos (Fase 6).
El Game Engine lo respeta:

- las **stats del competidor se congelan** en el estado al iniciar, igual que
  los atributos (`participant['game_stats']`);
- los encuentros resueltos se acumulan en `$state['game_log']`, y el
  `TournamentInstanceRuntimeService` —que sí tiene base de datos— los vuelca
  a `game_encounters` después de cada acción.

## 3. Base de datos

| Tabla | Para qué |
|---|---|
| `universe_games` | qué juegos usa el Universo y con qué configuración |
| `universe_entity_game_stats` | stats por competidor **y por juego**, en JSON flexible |
| `game_encounters` | registro estructurado de cada Encounter resuelto |
| `game_encounter_participants` | valor, posición y victoria de cada participante |

Columnas añadidas: `universe_tournaments.game_key`, `tournament_instances.game_key`.

**Las stats configurables se guardan** (min/max no se derivan de nada). **Los
contadores se derivan** de `game_encounter_participants` — mismo criterio que
la clasificación de la Fase 10: lo derivado nunca se desincroniza.

El JSON de `stats` es lo que permite que Highest Number use `min_value`/`max_value`
y otro juego use `strength`/`speed`/`defense` sin migración nueva.

## 4. Servicios

- `GameRegistry` — resuelve `game_key` → engine; lista definiciones.
- `HighestNumberGameEngine` — primer juego de referencia.
- `UniverseGameService` — juegos habilitados del Universo, juego por defecto.
- `GameStatsService` — asegura/normaliza stats por competidor; agregados derivados.
- `EncounterRuntime` — **puro**: prepara el borrador del Encounter, tira, adjudica
  y lo entrega a la serie existente.
- `GameEncounterRecorder` — vuelca `$state['game_log']` a base de datos.

## 5. Interfaz

| Página | Qué es |
|---|---|
| `universes/{u}/games` | catálogo de juegos del Universo |
| `universes/{u}/games/{game}` | ficha del juego: reglas, participantes, stats, cómo se gana |
| Ficha del competidor → pestaña **Juegos** | Game Stats por juego + récord derivado |
| Competición → **simulador** | Encounter interactivo: generar uno, generar todos, avanzar |

El simulador se dibuja **desde `definition()['controls']`**, no desde código
de Highest Number.

## 6. Orden de implementación

1. Migraciones y modelos
2. Contrato + registro + Highest Number
3. Game Stats por competidor (congelado en el estado)
4. Costura en el motor: `randomScore()` → `EncounterRuntime`
5. Acciones interactivas + persistencia del log
6. Catálogo de juegos y ficha
7. Pestaña Juegos del competidor
8. Simulador
9. Verificación

## 7. Fuera de alcance (deliberadamente)

IA, simulación por atributos de la Entity, economía, habilidades,
multijugador, ranking nuevo, sistemas sociales, y **la lógica de recompensas
de la Fase 12** — de la que aquí solo se deja el registro estructurado que
necesitará.

También queda fuera el `PhaseSimulatorService` de diseño y el grafo interno
de Single Elimination: usan participantes sintéticos, sin Universo y sin
stats que leer. Siguen resolviéndose al azar, como hasta ahora.

## 8. Cierre de la fase

Fase cerrada el 21 de agosto de 2026. Verificada de punta a punta contra
MySQL con un torneo real de 8 competidores: 16 enfrentamientos jugados, 32
filas de resultado, cero residuos al limpiar.

### 8.1 Qué quedó funcionando

| Área | Estado |
|---|---|
| Contrato `GameEngine` + `GameRegistry` | Funcional |
| Highest Number | Funcional |
| Game Stats por competidor y por juego | Funcional |
| Battle / Encounter sobre la serie existente | Funcional |
| BO1 · BO3 · BO5 · BO7 · BO9 · FIXED_GAMES | Funcional (reutilizado) |
| 2, 3 o más participantes | Funcional |
| Simulador interactivo | Funcional |
| Integración con el Tournament Runtime | Funcional |
| Registro estructurado de enfrentamientos | Funcional |
| Catálogo y ficha de juego | Funcional |
| Elección de juego por torneo y por Universo | Funcional |

### 8.2 Decisiones que conviene recordar

**No se crearon tablas de Battle ni de Encounter.** La Battle ya era un
match con su serie y el Encounter ya era un juego de esa serie. Lo que
faltaba era quién decidía el resultado. Crear tablas nuevas habría
duplicado `MatchSeriesRuntime` y obligado a mantener dos verdades sobre el
mismo hecho.

**A la serie se le entrega 1-0, no el número generado.** `submitGame()`
recibe enteros: un 7.82 se truncaría a 7 y dos tiradas de 7.1 y 7.9 se
convertirían en un empate que no ocurrió. El marcador de la serie cuenta
enfrentamientos ganados —que es lo que necesitan las clasificaciones de
Round Robin y Group Stage— y los números reales se guardan íntegros en
`game_encounter_participants`.

**`roll()` y `adjudicate()` están separados en el contrato.** Es lo único
que hace posible generar el resultado de un competidor, verlo, y después
generar el del otro sin que la pantalla conozca las tripas del juego.

**Los juegos se declaran en código, no en una tabla.** Añadir uno es
escribir su engine y sumarlo a `GameRegistry::ENGINES`. Aparece solo en el
catálogo, en la ficha del competidor, en el selector del torneo y en el
simulador. No hay seed que sincronizar ni fila que pueda quedar obsoleta.

**El motor sigue siendo una función pura.** El Game Engine no toca base de
datos: las stats se congelan en el estado al arrancar y los resultados se
acumulan en `$state[game_log]`, que `TournamentInstanceRuntimeService`
vuelca después. Por eso una competición se sigue pudiendo reanudar y
reproyectar sin efectos raros.

**Sin juego, el motor resuelve al azar como siempre.** El Competition Lab
de diseño usa participantes sintéticos que no tienen Game Stats. La
presencia de `$state[game]` es lo que distingue una competición real de
una prueba de diseño.

### 8.3 Preparado para la Fase 12

Cada enfrentamiento guarda ganador, posición de cada participante,
participación, valores obtenidos, estadísticas usadas, juego, torneo,
temporada y Universo. Con eso una recompensa futura ya puede expresarse
como «si gana el torneo, max_value +0.3» o «por participar en X batallas,
recompensa» sin añadir una sola columna.

La lógica de modificación de stats por recompensas NO está implementada,
a propósito.
