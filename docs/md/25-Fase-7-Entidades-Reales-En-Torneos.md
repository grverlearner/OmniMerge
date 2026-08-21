# FASE 7 — Torneos reales con Entidades de la Biblioteca

## 1. Objetivo

Que un participante de torneo deje de ser un nombre y pase a ser una Entidad
real de la Biblioteca, con su versión y su contexto de atributos:

```
Naruto                    Sasuke
Personaje          VS     Personaje
Poder 95                  Poder 93
```

Aplicable a los tres motores ya construidos (Single Elimination, Round Robin,
Group Stage). Swiss queda fuera.

## 2. Punto de partida real

Revisión acotada a lo que afecta esta fase:

| Pieza existente | Estado | Qué aporta |
|---|---|---|
| `UniverseCompetitor` | Ya tiene `entity_id` | El vínculo Biblioteca↔Universo **ya existe**; no hay que inventarlo |
| Participante del Runtime (Fase 6) | Ya lleva `entity_id`, `image_url`, y un `entity_version_id` **siempre nulo** | El hueco está previsto desde el diseño original; solo falta rellenarlo |
| `tournament_instance_participants` | Ya tiene `entity_id` | Falta la versión y los atributos |
| `VersionResolverService::resolve()` | Funciona | Resuelve qué `EntityVersion` corresponde |
| `VersionResolverService::effectiveAttributes()` | Funciona | Devuelve los atributos efectivos con herencia BASE→padres→versión |
| `Entity::activeBaseVersion()` | Funciona | La "Base activa ★" del usuario |
| Snapshot de Fase 6 | Funciona | Ya congela configuración; hay que extenderlo a los participantes |

**Conclusión**: Fase 7 no crea un sistema de participantes. Rellena el hueco que
Fase 6 dejó abierto a propósito y añade la capa de presentación.

## 3. Arquitectura propuesta

```
Entity  (Biblioteca, canónica)
   ↓  UniverseCompetitor  (contexto en el Universo)
   ↓  TournamentParticipantResolver   ← NUEVO, única pieza de lógica nueva
   ↓     · resuelve EntityVersion
   ↓     · lee atributos efectivos (VersionResolverService)
   ↓     · CONGELA todo
   ↓
Participante del estado (JSON)  +  tournament_instance_participants (tabla)
   ↓
Tournament Runtime (Fase 6, sin cambios)
   ↓
Motores SE / RR / GS  (sin cambios: la clave del participante sigue siendo
                       una cadena opaca para ellos)
```

**Los motores no se tocan.** Para ellos un participante es una clave de array;
todo lo nuevo son campos adicionales que ignoran. Esto es lo que garantiza que
cualquier motor futuro —incluido Swiss— herede el sistema sin trabajo extra.

### Cómo se elige la versión

Cadena determinista, toda con mecanismos que ya existen:

1. **Base activa (★)** de la Entidad, si la tiene → `Entity::activeBaseVersion()`
2. Si no, **versión por defecto** → `VersionResolverService::resolve($entity)`
3. Si no hay ninguna → participa la Entidad sin versión (caso perfectamente
   válido: una Entidad sin versiones)

No se inventa un cuarto criterio ni se duplica lógica de resolución.

## 4. Modelo de datos

**Una sola migración**, aditiva, sobre una tabla de la Fase 6:

```
tournament_instance_participants
  + entity_version_id     FK entity_versions nullOnDelete
  + entity_version_name   string(150) nullable   -- congelado
  + entity_type_name      string(120) nullable   -- congelado
  + attributes            json nullable          -- congelado
```

Ninguna tabla nueva. Los nombres se congelan (no se leen por join) precisamente
para que renombrar la Entidad o su versión no altere un torneo jugado; el FK se
conserva solo para poder enlazar a la ficha cuando siga existiendo.

### Qué se congela en `attributes`

Máximo 12 atributos por participante, cada uno:

```json
{ "name": "Poder", "values": ["95"], "display": "95", "numeric": 95 }
```

`numeric` se rellena solo cuando el valor es numérico. Sirve para ordenar y
para que un futuro motor de simulación por atributos (Fase 11) lo tenga ya
disponible sin tocar nada — pero **esta fase no lo usa para decidir resultados**.

**No se duplica la Biblioteca**: no se copian opciones, grupos, catálogos ni
jerarquías. Solo el par nombre/valor mostrable, que es lo único que el torneo
necesita enseñar.

## 5. Compatibilidad con el snapshot de Fase 6

Se integra, no se paraleliza:

- La configuración del torneo sigue congelándose en
  `tournament_instance_snapshots` (sin cambios).
- Los participantes se congelan donde ya se congelaban: en el estado JSON y en
  `tournament_instance_participants`, ahora con más campos.

Resultado: editar la Entidad, su versión o sus atributos **después** de crear la
competición no altera nada, exactamente igual que ya ocurría con la plantilla.

## 6. Selección de Entidades

El punto de entrada correcto **ya existe** y no debe duplicarse: los
participantes de una competición salen de los competidores del Universo, que a
su vez son Entidades de la Biblioteca. Crear un segundo selector "directo a
Biblioteca" rompería la capa de Universos que establecieron las Fases 5 y 6.

Lo que sí falta es que ambos selectores **muestren la Entidad de verdad**:

1. **Añadir competidores al Universo** (`universes/competitors/create`): ya
   tiene búsqueda y filtro por tipo; se le añade la ficha visual real.
2. **Elegir participantes de la competición** (`universes/competitions/create`):
   hoy son casillas de texto; pasa a ser una rejilla de fichas con imagen,
   tipo, atributos destacados, buscador, contador y orden de seeding.

## 7. Integración con los tres motores

Ninguna modificación. El participante enriquecido viaja como campos extra del
mismo array que los motores ya consumen. Verificación prevista: una competición
por motor (SE, RR, GS) con Entidades reales, comprobando bracket/jornadas/grupos.

## 8. Interfaz

- **Ficha de participante** (`partials/participant-chip`): componente único
  reutilizado en el selector, el workspace y el inspector del Lab. Si hay
  Entidad muestra imagen, tipo y atributos; si no (Lab sintético) degrada al
  nombre. Un solo componente evita tres maneras distintas de pintar lo mismo.
- **Selector de participantes**: rejilla con buscador, filtro por tipo,
  contador de seleccionados y reordenado por seed.
- **Workspace**: la tabla de participantes gana avatar, tipo y versión usada.
- **Enfrentamientos**: las tarjetas VS del runtime muestran los atributos
  destacados de cada lado.

Se mantiene el lenguaje visual violeta del módulo Universos.

## 9. Archivos

**Nuevo**: migración de campos; `TournamentParticipantResolver`;
`resources/views/universes/competitions/partials/participant-chip.blade.php`.

**Modificado**: `TournamentInstanceStateFactory` (usa el resolver);
`TournamentInstanceService::freezeParticipants()` (persiste los campos nuevos);
`TournamentInstanceParticipant` (fillable/casts + accesores);
`TournamentInstanceController::create()` (carga atributos para el selector);
vistas `competitions/create`, `competitions/show`, `competitors/create`;
partial `tournaments/lab/partials/participants-inspector`.

**Intocado**: los tres motores, `TournamentGraphRuntimeService`,
`CompetitionLabService`, el snapshot de configuración, `VersionResolverService`,
`AttributeContextService`, y todo el módulo Biblioteca.

## 10. Qué NO se implementa en esta fase

- **Swiss.**
- **Simulación por atributos**: los resultados se siguen resolviendo como hasta
  ahora. Los atributos se congelan y se muestran, pero no deciden. Eso es Fase 11.
- **Políticas de representación por competidor** (ORIGINAL / BASE_ACTIVE /
  SPECIFIC_VERSION elegible a mano): `09-Para Futuro.md` §48 las sitúa
  explícitamente después de la primera versión del módulo Universo.
- **Competition Lab con Entidades reales**: el Lab prueba plantillas, no
  Universos. Sus participantes siguen siendo sintéticos; lo que cambia es que
  el componente de ficha, compartido, ya sabe pintar Entidades cuando las hay.
- **Filtros por atributo para elegir participantes** (Sprint U5).
- Estadísticas históricas por Entidad, rankings, recompensas.
- Batería de tests automatizados.

## 11. Riesgos

| Riesgo | Mitigación |
|---|---|
| Cargar atributos de N competidores en el selector → N+1 | Eager loading acotado y tope de 12 atributos por entidad |
| `effectiveAttributes()` es la lógica menos protegida del proyecto (sin tests) | No se modifica; solo se consume. Si falla, falla igual que hoy en la Biblioteca |
| Entidad sin versiones | Caso soportado explícitamente: participa sin versión |
| Snapshot más pesado | Los atributos van en la tabla de participantes, no en el snapshot de configuración |

## 12. Notas de implementación (post-ejecución)

Implementada completa. Una desviación y un detalle técnico relevante.

### 12.1 Desviación: la columna no se llama `attributes`

Se llama **`attribute_snapshot`**. `attributes` colisiona con la propiedad
interna `Model::$attributes` de Eloquent: habría funcionado por `__get()`, pero
es un campo de minas para el mantenimiento. Se detectó antes de migrar.

### 12.2 Se respetan las decisiones de la Biblioteca

`effectiveAttributes()` no devuelve solo nombre y valor: también trae
`is_visible`, `is_featured` y `custom_label`. El resolutor los usa:

- los atributos ocultos **no** entran en el torneo;
- los destacados (★) van primero y se pintan en violeta;
- la etiqueta personalizada gana al nombre del atributo.

Es decir, lo que el usuario ya configuró en su Biblioteca manda también aquí,
sin tener que configurarlo dos veces.

### 12.3 Dónde se muestran los atributos, y dónde no

En el **selector** solo se cargan imagen, nombre y tipo. Resolver los atributos
de cada competidor allí serían decenas de consultas para pintar una rejilla en
un Universo grande. Se congelan al crear la competición y aparecen ya en el
workspace, el inspector y las tarjetas de enfrentamiento.

### 12.4 Verificación realizada

Prueba de dominio de extremo a extremo con los **tres motores** y Entidades
reales de la Biblioteca (datos borrados al terminar):

| Motor | Resultado |
|---|---|
| Single Elimination | `COMPLETED`, 1 encuentro, *Naruto vs Mitsuki*, 2/2 con Entidad y atributos |
| Round Robin | `COMPLETED`, 6 encuentros, 4/4 con Entidad y atributos |
| Group Stage | `COMPLETED`, 2 grupos, 4/4 con Entidad y atributos |

Resolutor sobre una Entidad real: `entity_id=1`, tipo `Anime`, 2 atributos
congelados (`anime: naruto`, `aldea: Lluvia`).

**Prueba de versionado**: renombrar la Entidad en la Biblioteca con la
competición ya creada **no** cambió el participante del torneo.

Prueba HTTP (test temporal, eliminado después): el selector responde 200 y
muestra buscador, filtro por tipo y las Entidades; crear la competición congela
`entity_id`, `entity_type_name` y atributos; el workspace responde 200 y
muestra el tipo de la Entidad.

Además: `php artisan view:cache` y `vite build` sin errores; sin datos
residuales.

### 12.5 Guía de pruebas manuales

1. **Biblioteca** → asegúrate de tener varias Entidades con atributos. Si
   marcas alguno como **destacado**, saldrá resaltado en el torneo.
2. **Universo** → *Competidores* → añade esas Entidades.
3. **Universo** → *Torneos* → **⚔ Competiciones** → *Iniciar nueva competición*.
4. En el selector: usa el **buscador** y el **filtro por tipo**; verás cada
   Entidad con su imagen y su tipo. Marca varias y mira el contador.
5. Guarda y abre el workspace: la tabla de participantes muestra avatar, tipo,
   versión (si la Entidad tiene Base activa ★) y atributos.
6. **▶ Comenzar competición** y avanza. En las tarjetas de enfrentamiento verás
   *Naruto* con su atributo destacado debajo, frente a su rival.
7. Repite con una plantilla de **Round Robin** y otra de **Group Stage**
   (recuerda: Single Elimination pide potencia de 2 si la fase no permite BYEs,
   y Group Stage pide al menos 4 participantes).
8. Cierra sesión, vuelve y abre la competición: participantes intactos.
9. **La prueba clave**: renombra en la Biblioteca una Entidad que esté jugando,
   o cambia sus atributos. Vuelve a la competición: sigue mostrando el nombre y
   los atributos que tenía al empezar.

### 12.6 Pendientes reconocidos

- Ninguna Entidad de prueba tenía versiones, así que la ruta
  "Base activa ★ → versión por defecto" quedó verificada por código pero no con
  datos reales. Al probarlo manualmente con una Entidad que sí tenga Base
  activa, el nombre de la versión debe aparecer en violeta junto al tipo.
- Los atributos numéricos ya se congelan aparte (`numeric`), listos para el
  motor de simulación por atributos, que sigue siendo Fase 11.

## 13. Orden de implementación

1. Migración de campos
2. `TournamentParticipantResolver`
3. Modelo `TournamentInstanceParticipant`
4. `TournamentInstanceStateFactory` + `TournamentInstanceService`
5. Componente `participant-chip`
6. Selector de participantes
7. Workspace + inspector del Lab
8. Verificación con los tres motores
9. Guía de pruebas manuales y cierre del documento
