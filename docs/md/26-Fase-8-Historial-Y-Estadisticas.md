# FASE 8 — Historial y estadísticas

## 1. Arquitectura propuesta

La Fase 6 ya dejó el principio correcto: **el estado JSON es la fuente de
verdad del motor, y las tablas son su proyección consultable**. Fase 8 no
cambia eso: **extiende el proyector existente**.

```
Estado del motor (JSON, ya congelado e inmutable)
        ↓  TournamentInstanceProjector  (se amplía, no se duplica)
Proyecciones consultables
        ↓
TournamentHistoryService        → "¿qué pasó en este torneo?"
EntityCompetitionStatsService   → "¿cómo le fue a esta Entidad?"
        ↓
Vistas de historial (solo lectura)
```

**Decisión central**: no se crea ningún sistema de historial paralelo. El
historial *ya está ocurriendo* — cada acción del runtime proyecta encuentros,
participantes, fases y eventos. Lo que falta es proyectar unas cuantas cosas
más y construir la capa de lectura.

**Estabilidad del historial**: garantizada por construcción. Las proyecciones
se alimentan del estado congelado, y los nombres de Entidad, versión y
atributos ya se copiaron en la Fase 7. Editar plantilla, Entidad o atributos
no puede alterar nada.

## 2. Datos que ya existen y se reutilizan

| Tabla | Qué aporta al historial |
|---|---|
| `tournament_instances` | nombre, código, estado, `started_at`, `completed_at`, nº participantes, temporada |
| `tournament_instance_snapshots` | la configuración exacta que se jugó |
| `tournament_instance_states` | estado completo del motor: standings, grupos, brackets, recorridos |
| `tournament_instance_participants` | Entidad, versión, tipo, atributos, seed, W/D/L/puntos, ubicación final |
| `tournament_instance_phases` | qué fases se ejecutaron, tipo de motor, estado |
| `tournament_instance_matches` | encuentros, marcadores, ganador, perdedor, ronda, series |
| `tournament_instance_events` | ledger cronológico de todo lo ocurrido |

Es mucho más de lo que parece: **el 70% del historial ya está persistido**.

## 3. Datos nuevos realmente necesarios

Tres huecos concretos, ninguno inventado:

### 3.1 Desenlace de cada participante — columnas en `tournament_instance_participants`

```
+ outcome        string(30) nullable   -- CHAMPION | ELIMINATED | QUALIFIED | UNPLACED
+ placement      unsignedInteger nullable
+ round_reached  unsignedInteger nullable
```

Hoy solo se guarda `final_location_name` (texto del terminal). El **tipo** de
terminal existe en el estado (`terminals[].type`) pero se pierde al proyectar:
sin él no se puede responder "¿quién fue campeón?" con una consulta.

### 3.2 Rendimiento por fase — nueva tabla `tournament_instance_phase_participants`

```
tournament_instance_id, tournament_instance_phase_id, runtime_key,
entity_id (desnormalizado), participant_name,
group_label nullable, position nullable,
matches, wins, draws, losses, points,
score_for, score_against, score_difference,
status  -- ADVANCED | ELIMINATED | PLAYED
unique(tournament_instance_phase_id, runtime_key)
```

**Es la única tabla nueva.** Se alimenta de `runtime['standings']` (Round
Robin) y `runtime['groups'][].standings` (Group Stage), que ya se calculan y
hoy solo viven dentro del JSON. Sin ella no hay "posición en el grupo",
"tabla de clasificación" ni "rendimiento por motor".

### 3.3 Desnormalización para consultas entre torneos — columnas en `tournament_instance_matches`

```
+ participant_a_entity_id  FK entities nullOnDelete
+ participant_b_entity_id  FK entities nullOnDelete
+ winner_entity_id         FK entities nullOnDelete
+ group_label              string(60) nullable
+ completed_at             timestamp nullable
```

Los encuentros guardan `runtime_key` (`UC-000123`), que solo tiene sentido
dentro de su competición. Para head-to-head e historial por Entidad haría
falta un join a participantes en **cada** consulta. Se desnormaliza el
`entity_id` en la propia tabla de proyección —que es exactamente para lo que
existe— y las consultas pasan a ser de una sola tabla con índice.

`completed_at` habilita el orden cronológico fiable de encuentros, necesario
para rachas.

### 3.4 Reproyección de lo ya jugado

Comando `php artisan tournaments:reproject` que vuelve a proyectar todas las
instancias desde su estado guardado. Necesario porque las competiciones ya
terminadas no volverán a ejecutar ninguna acción y, por tanto, nunca
rellenarían los campos nuevos. Es seguro: el proyector es idempotente y el
estado JSON no se toca.

## 4. Estadísticas esenciales (se implementan)

**Por competición**: participantes, fases, encuentros jugados, fecha de inicio
y fin, duración, campeón (con imagen), clasificación final.

**Por participante en una competición**: W/D/L, puntos, encuentros, desenlace,
posición final, ronda alcanzada (SE), grupo y posición (GS/RR).

**Por fase**: tipo de motor, participantes, encuentros, clasificación,
quién avanzó y quién quedó eliminado.

**Por encuentro**: los dos participantes con imagen, marcador, ganador, fase,
ronda, detalle de la serie si la hubo.

**Por Entidad (entre torneos)**: torneos jugados, campeonatos, victorias,
derrotas, empates, win rate, mejor resultado, historial cronológico,
rendimiento por motor.

**Head-to-head**: encuentros totales, victorias de cada lado, empates, lista
cronológica con torneo, fase y fecha.

## 5. Estadísticas recomendadas y opcionales

**B) Recomendadas** — derivables con fiabilidad, se implementan si el paso
principal va bien:

- Finales y semifinales alcanzadas (SE: desde `round_reached` contra el total
  de rondas de esa fase).
- Posición media.
- Rival más enfrentado; mejor y peor registro contra un rival.
- Eliminaciones repartidas y veces eliminado (desde `winner_key`/`loser_key`).
- Porcentaje de clasificación (fases superadas / fases disputadas).
- Racha más larga de victorias y de derrotas, ordenando por
  `(instance.completed_at, match.completed_at, match.id)`.

**C) Fuera de esta fase, con motivo**:

- **Rendimiento por periodo / temporada**: los datos existen, pero sin varias
  temporadas jugadas no hay nada que enseñar. Se añade cuando haya historia.
- **Rendimiento por seed**: el seed existe, pero con muestras de 2-4
  participantes cualquier porcentaje sería ruido estadístico.
- **Torneos consecutivos**: ambiguo — no está definido qué cuenta como
  "consecutivo" cuando hay varios Universos.

No se implementa ninguna estadística sin fuente fiable.

## 6-7. Diseño de las vistas y navegación

Dos puertas de entrada, cada una responde una pregunta distinta:

```
Universo → Historial          →  "¿qué pasó en este torneo?"
Biblioteca → Entidad → Competiciones  →  "¿cómo le fue a Naruto?"
```

**Sidebar del Universo**: nueva entrada `◷ Historial` bajo "Historia" (la
sección ya existe con "Recompensas / Rankings" marcados como Próximo).

## 8. Vista por competición

Se **amplía la página que ya existe** (`competitions/show`), no se crea otra:
cuando la competición está `COMPLETED` la página deja de mostrar controles de
ejecución y se convierte en su ficha histórica, con pestañas:

```
RESUMEN · FASES · ENCUENTROS · PARTICIPANTES · RECORRIDO
```

Se fusionan dos de las pestañas propuestas, por una razón concreta:
"Resultados" y "Encuentros" son el mismo dato mirado dos veces, y
"Estadísticas" sin una pestaña propia queda mejor: las cifras cobran sentido
junto a lo que describen, no aisladas en una tabla aparte.

**RESUMEN**: héroe del campeón con imagen grande, nombre y marcador final;
debajo, tarjetas de cifras (participantes, fases, encuentros, duración) y el
podio con los primeros clasificados.

**FASES**: cada fase con su visualización propia (§13), navegable con
`< Anterior / Siguiente >`, y entre fases una banda que explica el traspaso:
*"3 clasificados de Fase de grupos → Eliminatoria"*.

**ENCUENTROS**: lista filtrable por fase y ronda, cada encuentro como tarjeta
con las dos imágenes enfrentadas y el marcador.

**PARTICIPANTES**: clasificación final con imagen, desenlace y estadísticas.

**RECORRIDO**: el ledger de eventos, ya persistido.

## 9. Vista por fase — diferencias entre motores

Cada motor se ve distinto porque cada motor *es* distinto:

**Single Elimination** → bracket. Columnas por ronda, encuentros con las dos
imágenes, ganador resaltado, camino del campeón destacado. Se construye
agrupando `tournament_instance_matches` por `round_number` (no se reutiliza el
visualizador de plantilla, que representa la estructura de diseño, no lo
jugado).

**Round Robin** → tabla de posiciones con imágenes + matriz de enfrentamientos
(rivales en filas y columnas, marcador en cada celda).

**Group Stage** → un panel por grupo con su mini tabla, y debajo una franja de
clasificación que muestra quién pasó a la fase siguiente.

## 10. Vista por Entidad

En la ficha de la Entidad de la Biblioteca, sección **Competiciones**:

- Cabecera con las cifras grandes: campeonatos, torneos, victorias, derrotas,
  win rate, mejor resultado.
- **Rendimiento por formato**: un bloque por motor (SE / RR / GS) con sus
  cifras propias.
- **Últimas competiciones**: lista cronológica con el resultado de cada una
  (🏆 Campeón, Semifinal, 3º de grupo…).
- **Rivales**: los más enfrentados con su registro, cada uno enlazando al
  head-to-head.

## 11. Head-to-head

Vista de comparación entre dos Entidades: totales, victorias de cada lado y
lista cronológica de sus enfrentamientos con torneo, fase, fecha y marcador.
Consulta directa sobre `tournament_instance_matches` usando los `entity_id`
desnormalizados — sin sistema paralelo.

## 12. Integración con imágenes

Se reutiliza el componente `participant-chip` de la Fase 7, que ya sabe pintar
imagen, tipo, versión y atributos, y degrada al nombre si no hay Entidad. Se
añade una variante `xl` para el héroe del campeón. Las imágenes aparecen en
campeones, participantes, enfrentamientos, brackets, tablas e historial.

## 14. Filtros

**Historial del Universo**: motor, temporada, estado, y orden por más
reciente / más antiguo. Se descartan los filtros por campeón y por
participante en esta vista: son búsquedas por Entidad, y para eso está la
ficha de la Entidad.

**Encuentros de una competición**: por fase y por ronda.

**Rivales de una Entidad**: orden por más enfrentamientos o mejor registro.

## 15. Rendimiento

- Índices en las columnas desnormalizadas: `(participant_a_entity_id)`,
  `(participant_b_entity_id)`, `(winner_entity_id)`, y
  `(tournament_instance_id, node_id)`.
- Las estadísticas por Entidad son agregados `COUNT/SUM` sobre una sola tabla
  indexada; a la escala del proyecto no necesitan caché.
- El listado de historial nunca carga snapshots ni estados: por eso la Fase 6
  los puso en tablas aparte.
- El bracket se construye en memoria desde los encuentros ya cargados.

## 16. Orden de implementación

1. Migraciones (columnas + `tournament_instance_phase_participants`).
2. Ampliar `TournamentInstanceProjector`: desenlaces, standings por fase,
   `entity_id` desnormalizados, `completed_at` de encuentros.
3. Comando `tournaments:reproject` y ejecución sobre lo existente.
4. `TournamentHistoryService`.
5. Página de competición con pestañas (Resumen / Participantes / Encuentros /
   Recorrido).
6. Visualizaciones por motor: bracket SE, tabla+matriz RR, grupos GS.
7. Historial del Universo con tarjetas y filtros.
8. `EntityCompetitionStatsService` + sección Competiciones en la Entidad.
9. Head-to-head.
10. Estadísticas recomendadas (§5 B) y documentación de cierre.

## 17. Archivos principales

**Nuevos**: 2 migraciones; `TournamentInstancePhaseParticipant`;
`TournamentHistoryService`; `EntityCompetitionStatsService`;
`ReprojectTournamentInstances` (comando); `UniverseHistoryController`;
`EntityCompetitionController`; vistas de historial y los 3 partials de
visualización por motor.

**Modificados**: `TournamentInstanceProjector` (el cambio de fondo);
`TournamentInstanceParticipant` y `TournamentInstanceMatch` (campos);
`competitions/show.blade.php` (pestañas); `participant-chip` (variante `xl`);
sidebar del Universo; ficha de Entidad de la Biblioteca; `routes/web.php`.

**Intocados**: los tres motores, el runtime, el snapshot, `CompetitionLabService`,
`VersionResolverService` y el resto de la Biblioteca.

## 18. Riesgos y decisiones

| Riesgo / decisión | Tratamiento |
|---|---|
| **Reproyectar lo ya jugado** podría alterar historial existente | El proyector es idempotente y lee del estado congelado; solo rellena campos nuevos. Se verifica antes y después sobre una competición real |
| **Posición final** no siempre es calculable | Cuando el grafo no produce un orden completo se deja `placement` nulo y se muestra el desenlace, no una posición inventada |
| **El bracket de SE** no se puede reutilizar del visualizador de plantilla | Se construye desde los encuentros jugados: representa lo ocurrido, no el diseño |
| **Denormalizar `entity_id`** en encuentros | Es una tabla de proyección, no de dominio: desnormalizar es su función. Se recalcula íntegra en cada proyección |
| **Competiciones sin Entidades** (Lab sintético) | Todas las vistas degradan al nombre; las estadísticas por Entidad simplemente no las incluyen |
| Empate a estadísticas entre motores | Cada motor aporta lo suyo: no se fuerza "posición en grupo" en SE ni "ronda alcanzada" en RR |

## 20. Notas de implementación (post-ejecución)

Implementada siguiendo el plan. Un bug real encontrado al probar y dos
detalles técnicos que merecen quedar escritos.

### 20.1 Bug encontrado al probar: rachas infladas

La primera ejecución dio a una Entidad **racha de 4 derrotas teniendo solo 3
derrotas registradas**. Causa: `streaks()` contaba como derrota cualquier
encuentro cuyo ganador no fuera la Entidad — incluidos los que **no tienen
ganador y tampoco son empate** (BYEs y encuentros nunca resueltos).

Corregido saltando esos encuentros. Tras el arreglo las cifras cuadran:
V2-E0-D2, win rate 50%, racha mejor 2 / peor 1, y el head-to-head coincide.

Es exactamente el tipo de dato ambiguo que pediste evitar: un BYE no es una
derrota.

### 20.2 Desenlace `IN_PROGRESS`

Al proyectar tu competición real (que está a medias) todos los participantes
salían como `UNPLACED`. El estado del motor los marca como `COMPETING` dentro
de un nodo, no en un terminal. Se añadió el desenlace `IN_PROGRESS`: no es
"sin ubicar", es que la competición aún no ha terminado para ellos.

### 20.3 Desempate del campeón

`champion()` ordenaba por nada y devolvía el primero. Si un grafo mal formado
enviara varios participantes al terminal de campeón (posible: el motor solo
avisa con `TERMINAL_OVER_CAPACITY`), devolvía uno al azar. Ahora desempata por
puntos, victorias y seed. El historial sigue siendo fiel a lo ocurrido: si el
grafo dice que ganaron cuatro, se registran cuatro.

### 20.4 Nombres de clave foránea

`tournament_instance_phase_participants` produce nombres de FK que superan el
límite de 64 caracteres de MySQL. Se declaran a mano (`tiphpart_*_fk`). MySQL
además no revierte DDL, así que el primer intento dejó la tabla a medias y
hubo que eliminarla antes de repetir.

### 20.5 Verificación realizada

Prueba de dominio con los tres motores (datos borrados al terminar):

| Motor | Proyección |
|---|---|
| Single Elimination | vista `bracket`, campeón detectado, encuentros con `entity_id` y ganador |
| Round Robin | vista `table`, 6 encuentros, 4 filas de clasificación con posición y puntos |
| Group Stage | vista `groups`, **2 grupos** correctamente etiquetados (Grupo A / Grupo B) |

Estadísticas por Entidad tras jugar 3 competiciones: torneos=3,
campeonatos=3, V2-D2, win rate 50%, rendimiento desglosado por los tres
motores, rivales con su registro, rachas y head-to-head coherente (2-0).

Prueba HTTP (test temporal, eliminado después): historial del Universo,
ficha de competición con campeón y fases, historial de Entidad con
"Rendimiento por formato", head-to-head — todas 200; otro usuario recibe 403.

Sobre tu competición real ya existente (`CMP000001`, en curso):
`tournaments:reproject` la actualizó sin tocar su estado — 4 encuentros con
`entity_id` desnormalizado, `round_reached=1` y desenlaces `IN_PROGRESS`.

Además: `php artisan view:cache` y `vite build` sin errores. Sin datos
residuales.

### 20.6 Pendientes reconocidos

- **Single Elimination publica su clasificación solo al terminar la fase**:
  una competición SE a medias muestra su bracket (que sale de los encuentros)
  pero aún no tiene tabla. No es un fallo: el bracket es la representación
  correcta para ese motor.
- **`placement`** solo se afirma para el campeón. El resto queda nulo porque
  el grafo no produce un orden completo, y la clasificación final se ordena
  por puntos y victorias. Preferible a inventar posiciones.
- Las estadísticas **B** del §5 que dependen de muchas competiciones
  (posición media, porcentaje de clasificación) están calculadas pero se
  lucirán cuando haya más historia.

### 20.7 Guía de pruebas manuales

1. **Universo → ◷ Historial** (nueva entrada del sidebar): verás las
   competiciones jugadas como tarjetas, cada una con la **imagen del campeón**
   en la cabecera. Prueba los filtros de formato y temporada.
2. Abre una competición: arriba aparece el **héroe del campeón** con su imagen
   grande, y debajo la fila de cifras (competidores, fases, encuentros,
   jugados, duración).
3. Baja a **◆ Fases**: si hay varias, usa las pestañas y los botones
   `← Anterior / Siguiente →`. Cada motor se ve distinto:
   - *Eliminación directa* → bracket por rondas, ganador con anillo violeta.
   - *Todos contra todos* → tabla de posiciones + jornadas.
   - *Fase de grupos* → un panel por grupo con su tabla y sus encuentros.
4. Al final de una fase con clasificados verás la banda violeta
   *"N competidores salieron de esta fase hacia la siguiente"*.
5. **Biblioteca → una Entidad → ⚔ Competiciones**: cabecera oscura con su
   imagen, campeonatos, torneos, victorias, derrotas y win rate; después
   *Rendimiento por formato* con barras, *Últimas competiciones* y *Rivales*.
6. Pulsa un rival → **head-to-head**: las dos imágenes enfrentadas, el marcador
   global y la cronología de todos sus encuentros.
7. **Comprobación de estabilidad**: renombra la Entidad en la Biblioteca y
   vuelve al historial — la competición sigue mostrando el nombre con el que
   jugó.
8. Si ya tenías competiciones de antes de esta fase, ejecuta una vez
   `php artisan tournaments:reproject` para rellenar sus datos de historial.

## 19. Fuera de alcance

Swiss, rankings de Universo, recompensas, recurrencia entre temporadas,
estadísticas C de §5, exportación de datos y tests automatizados nuevos.
