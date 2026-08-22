# FASE 12 — Recompensas, Progresión, Ranking y Palmarés

> 21 de agosto de 2026.

## 1. Qué resuelve

La Fase 11 decide **cómo se juega**. Esta decide **qué queda después**.

```
Game Engine        → resultado del enfrentamiento
Tournament Runtime → resultado de la competición
Fase 12            → consecuencias permanentes en el Universo
```

## 2. El hueco que había que tapar primero

El proyector de la Fase 8 solo marcaba `placement = 1`, para el campeón, y
lo hacía a propósito: *"solo se afirma la posición cuando es indiscutible"*.

Esa cautela era correcta mientras la posición solo se mostraba. Deja de
serlo en cuanto una recompensa dice «2.º puesto → +0.3», porque entonces
alguien tiene que decidirlo. Lo decide `TournamentPlacementResolver`, de lo
más firme a lo más aproximado:

| Puesto | Cómo se decide | Exactitud |
|---|---|---|
| 1.º | el campeón que ya marcaba el proyector | exacto |
| 2.º | quien perdió la última batalla que ganó el campeón | exacto en eliminación directa |
| 3.º+ | profundidad alcanzada → puntos → victorias → seed | mejor aproximación |

Se documenta porque un tercer puesto en una liguilla es una decisión de
criterio, no un hecho.

## 3. Decisiones de arquitectura

**RewardTemplate: no.** Una configuración de recompensas son 3-8 filas
colgadas de un torneo; una capa de plantilla más su instanciación
triplicaría los modelos para ahorrar un «copiar filas». Y el mecanismo de
reutilización **ya existe**: un torneo con recurrencia (Fase 10) se juega
temporada tras temporada con la misma configuración. Se añadió en su lugar
«reprocesar edición», que resuelve el caso real de cambiar las reglas
después de haber jugado.

**Permanente y temporal son dos tablas, no una con bandera.** Se aplican en
momentos distintos, tocan cosas distintas (las stats guardadas contra el
estado congelado) y solo una necesita auditoría. Unirlas obligaría a
filtrar por modo en cada consulta.

**Los bonus temporales viajan en el congelado que ya existía.** La Fase 11
congela las Game Stats en el estado del torneo; el bonus se aplica sobre
una copia al preparar el enfrentamiento. El competidor nunca se entera:
cuando el torneo acaba, sus stats siguen siendo las que eran.

**Idempotencia por clave única, no por bandera.** `universe_stat_changes`
es única en (competición, competidor, juego, stat, regla). Procesar dos
veces no puede duplicar aunque el intento anterior se interrumpiera a la
mitad. `rewards_processed_at` solo evita trabajo inútil.

**Nada de `if game == highest_number`.** Una regla se valida preguntando si
la stat existe en el esquema que declara el Game Engine. Si no existe, se
ignora: perder una recompensa es mucho menos grave que reventar el cierre
de una competición ya jugada.

## 4. Tablas

| Tabla | Para qué |
|---|---|
| `universe_trophies` | trofeos definidos en el Universo |
| `universe_tournament_rewards` | reglas de recompensa permanente |
| `universe_tournament_modifiers` | bonus temporales |
| `universe_stat_changes` | historial de progresión + garantía de idempotencia |
| `universe_trophy_awards` | trofeos conquistados |

Columna añadida: `tournament_instances.rewards_processed_at`.

## 5. Disparadores de recompensa

`POSITION` · `PARTICIPATION` · `UNBEATEN` · `WIN_COUNT` ·
`ENCOUNTER_WIN_COUNT`, con operaciones `ADD` / `SUBTRACT` / `MULTIPLY` /
`SET`, y trofeo opcional.

**Participar es haber entrado en la competición, no haber jugado.** En un
cuadro con BYEs se puede llegar lejos sin disputar una sola batalla, y eso
sigue siendo participar. Solo queda fuera quien nunca llegó a entrar.

## 6. Ámbito de los bonus temporales

`TOURNAMENT` (todo el torneo) · `PHASE` (por nombre de fase) · `ROUND`
(por número de ronda), dirigidos a `ALL` o a una entidad concreta —que es
como se modela la ventaja de anfitrión sin inventar un concepto de
anfitrión.

## 7. Lo derivado sigue siendo derivado

Ranking, palmarés, títulos y récords se calculan; no se almacenan. Mismo
criterio que la Fase 10. El ranking ganó filtros por **juego**, **temporada**
y **torneo** sin tablas nuevas.

Lo único que se almacena es lo que no se puede derivar: el valor actual de
una stat y el motivo de cada cambio.

## 8. Fuera de alcance, deliberadamente

IA, economía, mercado, habilidades, multijugador, diplomacia, misiones,
árbol de habilidades, scripting y editor visual complejo.

También queda fuera, y conviene tenerlo presente:

- **Efectos automáticos del ranking** (#1 → +2 a una stat). La infraestructura
  está lista —`universe_stat_changes` acepta `source_type = RANKING`— pero
  ningún flujo los dispara. Aplicar efectos por posición necesita decidir
  *cuándo* se cierra un ranking, y esa decisión pertenece al cierre de
  temporada, que hoy no existe como acto explícito. Prometerlo funcionando
  habría sido exactamente el tipo de engaño que estas fases evitan.
- **Revertir recompensas.** Borrar una regla no deshace lo concedido: queda
  en el historial, que es lo que hace auditable la progresión.
