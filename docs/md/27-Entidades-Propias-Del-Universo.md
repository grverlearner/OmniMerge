# Entidades propias del Universo — separación de Biblioteca

Corrección arquitectónica posterior a la Fase 8.

## 1. El problema

`UniverseCompetitor` era un **enlace** a la Entidad de Biblioteca
(`universe_id` + `entity_id`). Todo lo demás —nombre, imagen, tipo,
atributos, versión— se leía en vivo de la Biblioteca en el momento de usarlo.

Consecuencias:

- Editar una Entidad en la Biblioteca cambiaba cómo se veía en todos los
  Universos a la vez.
- Las estadísticas de la Fase 8 se agregaban por `entity_id`, así que lo
  jugado en el Universo A se **sumaba** con lo del Universo B.
- La Biblioteca acababa teniendo, de facto, historial competitivo: existía
  `entities/{entity}/competitions`.

## 2. La separación

```
BIBLIOTECA                          UNIVERSO
Entity  ──── importar ────►  UniverseEntity
(material reutilizable)      (copia independiente)
                                   │
                                   ├── atributos copiados
                                   ├── versiones copiadas
                                   ├── torneos
                                   ├── historial
                                   └── estadísticas
```

**Importar no es sincronizar.** Se copia una vez y ahí acaba la relación.
No hay listeners, ni recálculos, ni actualización automática.

`source_entity_id` es **solo procedencia**: sirve para enlazar a la ficha de
origen mientras exista, nunca para leer datos ni para agregar estadísticas.

## 3. Modelo de datos

`universe_competitors` → **`universe_entities`**, con copia propia:

```
sequence_number, code (UEN%06d)
name, description, image, entity_type_name   ← copiados
attribute_snapshot  JSON                     ← atributos efectivos copiados
version_snapshot    JSON                     ← versiones copiadas
source_entity_id          FK entities        ON DELETE SET NULL
source_entity_version_id  FK entity_versions ON DELETE SET NULL
imported_at
display_name, status, notes                  ← contexto del Universo
```

Los atributos y versiones se copian como JSON, no replicando las ~8 tablas de
la Biblioteca: el Universo necesita **mostrarlos**, no volver a modelarlos.
Se respeta lo que el usuario ya configuró allí (`is_visible`, `is_featured`,
`custom_label`).

### Proyecciones de la Fase 8, repuntadas

| Antes | Ahora |
|---|---|
| `tournament_instance_participants.universe_competitor_id` | `universe_entity_id` |
| `tournament_instance_participants.entity_id` | `source_entity_id` (solo traza) |
| `tournament_instance_phase_participants.entity_id` | `universe_entity_id` |
| `tournament_instance_matches.participant_a/b_entity_id` | `participant_a/b_universe_entity_id` |
| `tournament_instance_matches.winner_entity_id` | `winner_universe_entity_id` |

Así **no queda ninguna vía** para agregar estadísticas por Entidad de
Biblioteca: la separación está garantizada por el esquema, no por disciplina.

## 4. Migraciones

| Migración | Qué hace |
|---|---|
| `060000_rename_universe_competitors_to_universe_entities` | Renombra la tabla y `entity_id` → `source_entity_id`; añade las columnas de copia |
| `060001_repoint_tournament_projections_to_universe_entities` | Renombra las columnas de las proyecciones y **rellena** los `universe_entity_id` desde los participantes |
| `060002_detach_universe_entities_from_library_deletes` | `source_entity_id` pasa a nullable y `ON DELETE SET NULL` |

Ninguna destruye datos. Las 8 entidades y la competición existentes se
migraron y siguen funcionando.

## 5. Interfaz

- Sidebar: **Competidores → Entidades**.
- `universes/{u}/entities` — rejilla con imagen, tipo y las cifras
  competitivas **de este Universo** (torneos / victorias / títulos).
- `universes/{u}/entities/create` — importador con buscador, filtro por tipo,
  contador y aviso explícito de que importar no sincroniza. Solo ofrece lo que
  aún no está importado, así que los duplicados no llegan a existir.
- `universes/{u}/entities/{e}` — ficha completa: datos copiados, atributos,
  versiones, rendimiento por formato, cronología, rivales y ajustes.
- `universes/{u}/entities/{e}/head-to-head` — comparación dentro del Universo.
- **Biblioteca**: se retiró `entities/{entity}/competitions` y su enlace. Ya no
  muestra nada competitivo.

## 6. Verificación realizada

Prueba de dominio (datos borrados al terminar):

| Comprobación | Resultado |
|---|---|
| Dos Universos importan la misma Entidad | dos filas distintas, independientes |
| Reimportar | 0 (idempotente, sin duplicados) |
| Renombrar en el Universo A | B no cambia |
| **Renombrar en la Biblioteca** | **ninguna copia cambia** |
| ¿`Entity` guarda estadísticas? | no |
| **Borrar la Entidad de origen** | **la copia sobrevive**, `source_entity_id` queda nulo |

Flujo completo con los tres motores usando entidades del Universo: SE, RR y GS
`COMPLETED`, con campeón, clasificaciones, grupos etiquetados y estadísticas
por entidad del Universo (3 torneos, desglose por motor, rivales, rachas).

HTTP: importador, índice, ficha y head-to-head responden 200; importar por POST
crea 3 copias; la ruta de historial de la Biblioteca ya no existe; otro usuario
recibe 403.

## 7. Bug encontrado al probar

`source_entity_id` heredó `ON DELETE CASCADE` del enlace anterior: **borrar una
Entidad de la Biblioteca borraba la entidad del Universo y todo su historial**.
Exactamente lo contrario de lo que persigue esta separación. Corregido en
`060002`, que además hace la columna nullable (`SET NULL` lo exige).

## 8. Pendientes

- **"Actualizar desde Biblioteca"**: no implementado a propósito. La regla es
  importar ≠ sincronizar; si más adelante se quiere, debe ser una acción
  explícita y por entidad.
- **Editar atributos y versiones dentro del Universo**: hoy se muestran tal
  como se copiaron. Editarlos allí es el paso natural siguiente.
- **La imagen se copia por ruta, no duplicando el archivo**: mismo disco, y
  duplicar binarios no aporta independencia real. Si el archivo se borrara, la
  ficha degrada al icono.
- Los estados de competiciones anteriores conservan la clave
  `universe_competitor_id`; el proyector la acepta como alternativa, así que
  siguen reproyectándose sin tocar nada.
