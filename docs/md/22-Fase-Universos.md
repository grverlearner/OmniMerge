# Universos — Contenedor organizativo superior

> **AVISO — parcialmente superado por `23-Fase-Universos-Workspace.md`.**
> La decisión de asignar torneos mediante `tournament_templates.universe_id`
> descrita aquí fue **revertida**: ataba una plantilla a un solo Universo y
> rompía su reutilización (`09-Para Futuro.md` §57 y §906). La sustituye la
> tabla intermedia `universe_tournaments`. Universos tampoco vive ya dentro
> del módulo de Torneos: tiene módulo, layout y sidebar propios en
> `/universes`. El resto de este documento (modelo `Universe`, servicio,
> política, CRUD, decisiones de alcance) sigue vigente.

## 1. Estado actual encontrado

**Cero implementación de código.** Confirmado por auditoría exhaustiva:

- Ningún archivo (`app/`, `database/`, `resources/`, `routes/`) contiene
  `Universe`/`universe` salvo texto de documentación.
- No existen migraciones, modelos, controladores, policies ni vistas.
- Existe una rama git `feature/universes-sprint-1` (revisada explícitamente
  por su nombre prometedor) — resultó ser un **commit antiguo en la línea
  directa de `main`**, de antes de que existiera el sistema de torneos
  (`git merge-base main feature/universes-sprint-1` devuelve exactamente la
  punta de esa rama). No contiene ningún archivo que no esté ya en `main`.
  No hay nada que rescatar.
- La visión de Universos **sí está documentada en detalle** por el propio
  usuario en `docs/md/15-Doc-Roadmap-Desarrollo-Evolución.md` (secciones
  69-72) y en `docs/md/09-Para Futuro.md`: un Universo como contenedor rico
  que agruparía tipos de entidad, entidades, versiones, atributos,
  colecciones, torneos y simulaciones — el ejemplo dado es "Universo Naruto"
  con personajes, aldeas, técnicas, torneos, etc.

**Tensión con el roadmap propio detectada y documentada, no resuelta
unilateralmente**: la sección 90 del roadmap ("Orden de dependencias") sitúa
Universos **después** de Runtime persistente, Torneos reales, Historial y
Consolidación de Biblioteca. El pedido actual invierte ese orden
deliberadamente ("antes de continuar con la Fase 6"). Esto es una decisión
válida del usuario — se documenta aquí como contexto, no como objeción.
Como consecuencia directa de ese orden invertido, esta fase construye
**solo la capa organizativa** (Universo → Tournament Templates), sin
Entidades reales ni Runtime — exactamente lo que el propio pedido delimita
en su sección 13 ("Qué NO hacer").

## 2. Arquitectura actual relevante (auditada)

Patrón de "recurso de primer nivel propiedad de un usuario", ya usado dos
veces de forma consistente por `TournamentTemplate` y `PhaseTemplate`:

- Tabla con `user_id` (FK, cascade delete), `sequence_number` +
  `code` (formato `XXX%06d`) únicos por usuario, `name`, `slug` único por
  usuario, `description`, `image` (Storage disk `public`), `status`
  (DRAFT/ACTIVE/ARCHIVED), timestamps, soft deletes.
- `{Resource}Service` con `create()/update()/duplicate()/archive()/delete()`
  transaccionales, bloqueando al usuario (`lockForUpdate`) para generar
  `sequence_number` sin colisiones, `uniqueSlug()`, manejo de imagen con
  rollback si la transacción falla.
- `{Resource}Policy` ownership-based: `viewAny`/`create` → `isActive()`;
  `view` → dueño o publicado; `update`/`delete` → solo dueño.
- `{Resource}Controller` con `index` (búsqueda + filtros + stats + paginación
  18/página), `create`, `store`, `show`, `edit`, `update`, `duplicate`,
  `archive`, `destroy`.
- `Store{Resource}Request`/`Update{Resource}Request` (`authorize()` vía
  Policy, `prepareForValidation()` normaliza mayúsculas de enums).
- Vistas: `index.blade.php` (grid de cards + stats), `create.blade.php`/
  `edit.blade.php` (envuelven un `partials/{resource}-form.blade.php`
  compartido), `show.blade.php`, `partials/{resource}-card.blade.php`.
- Layout: `<x-tournament-layout>` + sidebar
  (`resources/views/partials/tournaments/sidebar.blade.php`) con secciones
  "Principal / Diseño / Pruebas / Recursos / Descubrir".
- Rutas: `Route::prefix('tournaments')->name('tournaments.')->group(...)`
  dentro de `Route::middleware('auth')`.

**PhaseExit y PhaseTemplate ya se diseñaron deliberadamente reutilizables
entre `TournamentTemplate`s** (una Fase de la Biblioteca puede usarse en
muchos Nodes de muchos torneos). Este principio es directamente relevante:
**Universo no debe romper esa reutilización.**

## 3. Problemas encontrados

Ninguno de código — el problema es puramente de ausencia. El único riesgo
real detectado: si `universe_id` se agrega como columna **obligatoria** en
`tournament_templates`, rompería los registros existentes (plantillas ya
creadas sin Universo). Debe ser nullable.

## 4. Arquitectura propuesta

```
Universe (nuevo, primer nivel, propiedad de un usuario)
  └── TournamentTemplate (universe_id nullable, FK)
        └── PhaseTemplate (sin cambios — sigue siendo Biblioteca reutilizable,
             NO pertenece a un Universo)
             └── Tournament Graph / Competition Lab (sin cambios)
```

Decisiones de responsabilidad (sección 2 del pedido):

- **Universe** posee: identidad (nombre/descripción/imagen/código/slug),
  estado de ciclo de vida (DRAFT/ACTIVE/ARCHIVED), propietario. Nada más
  todavía.
- **NO posee**: configuración de participantes (eso es de
  `TournamentTemplate`), estructura de fases (eso es de `PhaseTemplate`),
  estado de ejecución/runtime (eso será de la futura Fase 6), ni Entidades
  reales (fuera de alcance, depende de Biblioteca + Fase 9 del Master Plan).
- **TournamentTemplate** gana un `universe_id` **opcional**: una plantilla
  puede seguir existiendo sin Universo (compatibilidad total con lo
  existente) o pertenecer a exactamente uno.
- **PhaseTemplate no cambia.** Sigue siendo una Biblioteca transversal, sin
  relación directa con Universe — coherente con "no dupliques".
- **Tournament / TournamentRuntime** (Fase 6, todavía no existen): cuando se
  construyan, la relación natural será `Tournament belongsTo
  TournamentTemplate` (que a su vez puede pertenecer a un Universe). Universe
  **no** almacenará snapshots de runtime — eso vivirá en las tablas de la
  Fase 6. Esto se documenta explícitamente para no bloquear esa fase futura.

## 5. Modelo de datos

### Tabla `universes` (nueva)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | FK → users | cascadeOnDelete |
| `sequence_number` | unsignedInteger | único junto a `user_id` |
| `code` | string(30) | formato `UNI%06d` |
| `name` | string(150) | |
| `slug` | string(180) | único junto a `user_id` |
| `description` | text nullable | |
| `image` | string nullable | Storage disk `public`, carpeta `universes` |
| `status` | string(20) default `DRAFT` | DRAFT/ACTIVE/ARCHIVED |
| `settings` | json nullable | extensibilidad futura, sin uso todavía |
| `created_at`/`updated_at` | timestamps | |
| `deleted_at` | softDeletes | |

**Deliberadamente excluido** (vs. el patrón de `TournamentTemplate`):
`visibility`, `allow_cloning`, `views_count`, `clones_count`, `published_at`,
`source_universe_id`. Son mecánica de Comunidad/clonación pública, sin
ningún consumidor hoy (Comunidad todavía no lista Torneos, mucho menos
Universos — el propio sidebar marca "Comunidad" como "Próximo"). Agregarlos
ahora violaría "evita sobrecargar esta primera versión" sin ningún caso de
uso real. Se documentan aquí como extensión futura de bajo riesgo (migración
aditiva) cuando la Comunidad llegue a Universos.

Índices: `unique(user_id, sequence_number)`, `unique(user_id, code)`,
`unique(user_id, slug)`, `index(user_id, status)`.

### Modificación a `tournament_templates` (migración aditiva)

- `universe_id` — `foreignId nullable, constrained('universes'), nullOnDelete`.
  `nullOnDelete` (no cascade): eliminar un Universo **no debe destruir**
  las plantillas de torneo que contenía — quedan huérfanas (sin Universo),
  igual que hoy pueden existir sin uno. Esto prioriza no perder trabajo del
  usuario sobre una limpieza automática agresiva.
- Índice `index(universe_id)`.

No se crean más tablas. No se toca `phase_templates`, `tournament_graph_*`,
ni ninguna tabla de Competition Lab.

## 6. Relaciones (resumen)

```
User 1—N Universe
User 1—N TournamentTemplate (como hoy)
Universe 1—N TournamentTemplate (nuevo, opcional)
TournamentTemplate 1—N TournamentPhaseNode (como hoy, sin cambios)
TournamentPhaseNode N—1 PhaseTemplate (como hoy, Biblioteca reutilizable)
```

## 7. Estructura de navegación

En `resources/views/partials/tournaments/sidebar.blade.php`, sección
"Diseño": se agrega "Universos" **antes** de "Torneos", reflejando la
jerarquía conceptual (Universo contiene Torneos). No se elimina ni renombra
ningún enlace existente.

## 8. Estructura de interfaz

- `tournaments.universes.index` — grid de cards (mismo patrón visual que
  `tournaments.templates.index`), con stats (Total/Activos/Borrador) y
  búsqueda+filtro de estado. Sin filtro de visibilidad (no existe ese campo).
- `tournaments.universes.create` / `.edit` — formulario compartido
  (`partials/universe-form.blade.php`): nombre, descripción, imagen, estado.
- `tournaments.universes.show` — página de detalle con 3 tabs, exactamente
  las que el pedido justifica funcionalmente:
  - **Resumen**: nombre, descripción, código, estado, contador de torneos
    (total/activos/archivados), acceso rápido "+ Nuevo torneo".
  - **Torneos**: lista de `TournamentTemplate` con `universe_id` = este
    Universo (reutiliza `partials/template-card.blade.php` sin
    modificarlo), botón "+ Nuevo torneo" que enlaza a
    `tournaments.templates.create?universe_id=X`.
  - **Configuración**: el mismo formulario de edición (nombre/descripción/
    imagen/estado) + zona de peligro (archivar/eliminar), mismo patrón que
    `tournaments.templates.edit` + la sección "Danger Zone" ya usada en
    `templates/show.blade.php`.
- No se agrega una sección "Configuración avanzada" ni "Actividad reciente"
  todavía — no hay datos reales de actividad que mostrar sin Runtime (Fase
  6), y agregar un feed vacío sería exactamente el tipo de sección
  injustificada que el pedido pide evitar.

## 9. Rutas necesarias

Dentro de `Route::prefix('tournaments')->name('tournaments.')`, nuevo bloque
antes de "Plantillas":

```
GET    /universes                          tournaments.universes.index
GET    /universes/create                   tournaments.universes.create
POST   /universes                          tournaments.universes.store
GET    /universes/{universe}                tournaments.universes.show
GET    /universes/{universe}/edit           tournaments.universes.edit
PUT    /universes/{universe}                tournaments.universes.update
PATCH  /universes/{universe}/archive        tournaments.universes.archive
DELETE /universes/{universe}                tournaments.universes.destroy
```

Sin `duplicate` (a diferencia de `TournamentTemplate`): duplicar un Universo
plantea la pregunta de si también se duplican sus torneos — una decisión de
producto no pedida explícitamente y con riesgo de sorprender al usuario
(duplicar N torneos completos con sus grafos). Se omite deliberadamente;
puede agregarse después con una decisión explícita sobre ese alcance.

`tournaments.templates.store` (existente) gana soporte opcional para
`universe_id` en el payload — sin ruta nueva.

## 10. Modelos

- **Nuevo**: `App\Models\Universe` (mismo patrón que `TournamentTemplate`:
  `HasFactory`, `SoftDeletes`, `scopeOwnedBy`, `scopeActive`, `formatCode()`,
  accessors de label, `getImageUrlAttribute()`, relación `tournamentTemplates()`).
- **Modificado**: `App\Models\TournamentTemplate` — agrega `universe_id` a
  `$fillable` y la relación `universe(): BelongsTo`.
- **Modificado**: `App\Models\User` — agrega `universes(): HasMany`.

## 11. Controllers

- **Nuevo**: `App\Http\Controllers\Universes\UniverseController` (namespace
  propio, igual que `Entities/`, `Attributes/`, `Community/` existentes) con
  `index/create/store/show/edit/update/archive/destroy`. `show()` carga
  contadores de torneos por estado para el tab Resumen.
- **Modificado**: `TournamentTemplateController::create()` acepta
  `?universe_id=` para preseleccionar/bloquear el Universo en el formulario;
  `store()` sin cambios de firma (el campo ya viaja en `$request->validated()`).

## 12. Services

- **Nuevo**: `App\Services\Universes\UniverseService` — `previewCode()`,
  `create()`, `update()`, `archive()`, `delete()`. Mismo patrón transaccional
  que `TournamentTemplateService` (bloqueo de usuario, secuencia, slug único,
  manejo de imagen con rollback). Sin `duplicate()` (ver sección 9).
- **Modificado**: `TournamentTemplateService::create()`/`update()` — sin
  cambios de lógica; `universe_id` viaja igual que cualquier otro campo de
  `$data` porque ya está en `$fillable`.

## 13. Policies

- **Nuevo**: `App\Policies\UniversePolicy` — `viewAny`/`create` →
  `$user->isActive()`; `view`/`update`/`delete` → `$universe->user_id ===
  $user->id`. Sin rama de "público" (no existe visibilidad todavía). Se
  registra en el service provider de policies si el proyecto usa mapeo
  explícito (se verificará `AuthServiceProvider` o el mecanismo de
  auto-descubrimiento de Laravel al implementar).

## 14. Form Requests

- **Nuevos**: `StoreUniverseRequest`, `UpdateUniverseRequest` — `name`
  (required, max 150), `description` (nullable, max 5000), `image` (nullable,
  File::image, max 4mb), `status` (required, in DRAFT/ACTIVE/ARCHIVED).
- **Modificados**: `StoreTournamentTemplateRequest`/
  `UpdateTournamentTemplateRequest` — agregan `universe_id` (nullable,
  integer, `exists:universes,id`) con validación adicional en
  `withValidator()` de que el Universo pertenezca al usuario autenticado
  (mismo patrón ya usado en varios Form Requests de Group Stage para
  verificar pertenencia cruzada).

## 15. Migraciones

1. `create_universes_table` (nueva tabla, ver sección 5).
2. `add_universe_id_to_tournament_templates_table` (columna aditiva
   nullable + índice).

Ninguna migración destructiva. Ninguna toca datos existentes.

## 16. Vistas/componentes

**Nuevos:**
- `resources/views/universes/index.blade.php`
- `resources/views/universes/create.blade.php`
- `resources/views/universes/edit.blade.php`
- `resources/views/universes/show.blade.php`
- `resources/views/universes/partials/universe-form.blade.php`
- `resources/views/universes/partials/universe-card.blade.php`

**Modificados:**
- `resources/views/partials/tournaments/sidebar.blade.php` (nuevo enlace).
- `resources/views/tournaments/templates/create.blade.php` /
  `partials/template-form.blade.php` — campo oculto/select opcional de
  Universo, preseleccionado cuando llega `?universe_id=`.
- `resources/views/tournaments/partials/template-card.blade.php` — badge
  opcional con el nombre del Universo si la plantilla pertenece a uno (una
  línea condicional, no un rediseño).

## 17. Qué reutilizar

`x-tournament-layout`, `partials/template-card.blade.php` (sin tocar, tal
cual), el patrón completo de `TournamentTemplateService`/`Controller`/
`Policy`/`Request`, las clases de badge de estado ya usadas en varias
vistas, el patrón de "Danger Zone" de `templates/show.blade.php`, el sistema
`data-omni-confirm` para confirmaciones destructivas.

## 18. Qué modificar

`TournamentTemplate` (modelo, migración aditiva), `User` (modelo),
`TournamentTemplateController::create()`, `StoreTournamentTemplateRequest`,
`UpdateTournamentTemplateRequest`, `TournamentTemplateService` (sin cambios
de lógica, solo se beneficia de `universe_id` ya en `$fillable`),
`sidebar.blade.php`, `template-form.blade.php`, `template-card.blade.php`.

## 19. Qué crear

Ver secciones 10-16 (modelo, controller, service, policy, 2 requests, 2
migraciones, 6 vistas nuevas).

## 20. Qué NO tocar

`PhaseTemplate` y todo su ecosistema (Fases 1-3), `TournamentPhaseNode`,
`TournamentPhaseConnection`, `PhaseEntryPort`, `PhaseExit`, Competition Lab
completo (Fases 4-5), Swiss (explícitamente excluido por el usuario),
Comunidad, sistema de autenticación/roles.

## 21. Compatibilidad con la futura Fase 6

Cuando se construya el Runtime persistente, el modelo `Tournament` (instancia
real y ejecutable, distinta de `TournamentTemplate`) tendrá
`tournament_template_id` (ya existente conceptualmente) y, por herencia
indirecta a través de esa plantilla, pertenecerá también a un Universo sin
necesidad de que `Tournament` tenga su propio `universe_id` — aunque
agregarlo directamente como columna desnormalizada (para consultas más
simples: "todos los torneos jugados de este Universo") es una decisión
razonable a tomar **en ese momento**, no ahora. Esta fase no bloquea esa
opción de ninguna manera: `universe_id` en `tournament_templates` ya
resuelve la trazabilidad completa vía join.

## 22. Orden exacto de implementación

1. Migración `create_universes_table`.
2. Modelo `Universe`.
3. Migración `add_universe_id_to_tournament_templates_table`.
4. Modificar `TournamentTemplate` (relación + fillable) y `User` (relación).
5. `UniversePolicy`.
6. `UniverseService`.
7. `StoreUniverseRequest` / `UpdateUniverseRequest`.
8. `UniverseController`.
9. Rutas.
10. Vistas de Universo (index/create/edit/show + partials).
11. Modificar `StoreTournamentTemplateRequest` / `UpdateTournamentTemplateRequest`
    (campo `universe_id`).
12. Modificar `TournamentTemplateController::create()` (preselección).
13. Modificar `template-form.blade.php` (selector de Universo).
14. Modificar `template-card.blade.php` (badge de Universo).
15. Modificar `sidebar.blade.php` (navegación).
16. Verificación en navegador (crear Universo, crear torneo dentro,
    verificar badge, editar, archivar).
17. Limpieza de datos de prueba.
18. Actualizar este documento con notas de implementación.

## 23. Riesgos y decisiones técnicas

- **Riesgo bajo**: toda modificación a tablas/modelos existentes es aditiva
  y nullable — cero riesgo de romper datos actuales.
- **Decisión**: sin `visibility`/clonación en Universo todavía (sección 5).
- **Decisión**: sin `duplicate()` de Universo todavía (sección 9).
- **Decisión**: `PhaseTemplate` permanece sin relación a Universo — la
  Biblioteca de Fases sigue siendo transversal.
- **Decisión**: eliminar un Universo desasocia sus torneos en vez de
  borrarlos (`nullOnDelete`), priorizando no perder trabajo del usuario.
- **Riesgo medio, mitigado**: el pedido invierte el orden del roadmap propio
  del usuario (sección 1). Mitigado limitando estrictamente el alcance a la
  capa organizativa, sin tocar nada de Biblioteca/Entidades/Runtime.

## 24. Notas de implementación (post-ejecución)

Todos los 18 pasos de la sección 22 se ejecutaron en el orden previsto, sin
desviaciones respecto al diseño de las secciones 1-23. No se necesitó ninguna
migración adicional ni ningún cambio de alcance durante la implementación.

### 24.1 Qué se encontró

Confirmado en código (no solo en la auditoría inicial): cero implementación
previa de Universos salvo el residuo muerto ya descartado en la sección 1
(rama `feature/universes-sprint-1`, verificada con `git merge-base` como un
commit ancestro de `main`, sin código único que aprovechar).

### 24.2 Qué se decidió (además de lo ya fijado en el plan)

- El selector de Universo en `template-form.blade.php` se resuelve con dos
  modos, no uno: (a) **bloqueado** — cuando se llega desde
  `tournaments.templates.create?universe_id=X` (botón "+ Nuevo torneo" dentro
  de un Universo), el campo se muestra como texto fijo + `input hidden`, con
  un enlace "Cambiar" que lleva al formulario sin preselección; (b) **libre**
  — un `<select>` con "Sin Universo" como opción por defecto, disponible
  tanto en creación como en edición. Esto evita que un usuario asigne por
  error un torneo a un Universo ajeno vía manipulación del formulario, sin
  necesitar JavaScript adicional.
- La validación de propiedad de `universe_id` se resolvió con
  `Rule::exists('universes', 'id')->where('user_id', $this->user()?->id)`
  en ambos Form Requests, en vez de un `withValidator()` manual — más corto,
  mismo efecto, y consistente con el resto de reglas `Rule::in()` ya usadas
  en esos archivos.
- Se añadió `->with('universe')` a las consultas de listado de plantillas
  (`TournamentTemplateController::index()` y `UniverseController::show()`)
  para que el nuevo badge de Universo en `template-card.blade.php` no
  introduzca N+1 queries — no estaba en el plan original a nivel de línea,
  pero se deriva directamente de la decisión de reutilizar esa card tal
  cual (sección 16).

### 24.3 Qué se implementó (resumen por capa)

**Backend (pasos 1-9, ya completados antes de este cierre):**
migraciones `create_universes_table` y
`add_universe_id_to_tournament_templates_table`; modelo `Universe`; relación
`TournamentTemplate::universe()` + `universe_id` en `$fillable`; relación
`User::universes()`; `UniversePolicy`; `UniverseService`;
`StoreUniverseRequest`/`UpdateUniverseRequest`; `UniverseController`
(CRUD completo, sin `duplicate`); 8 rutas `tournaments.universes.*`.

**Frontend e integración (pasos 10-15, este cierre):**
- `resources/views/universes/partials/universe-form.blade.php` — formulario
  compartido create/edit (Identidad: nombre/descripción/portada/código;
  Organización: estado).
- `resources/views/universes/create.blade.php` y `edit.blade.php` — wrappers
  delgados, mismo patrón que `tournaments/templates/*`.
- `resources/views/universes/partials/universe-card.blade.php` — tarjeta de
  listado (badge de estado, contador de torneos, portada con fallback 🌌).
- `resources/views/universes/index.blade.php` — grid + stats (total/activos/
  borradores) + filtros (búsqueda/estado/orden) + paginación.
- `resources/views/universes/show.blade.php` — hero + 3 pestañas Alpine
  (Resumen con stats y estado vacío accionable; Torneos reutilizando
  `tournaments.partials.template-card`; Configuración con edición y zona de
  peligro de borrado).
- `app/Http/Requests/Tournaments/StoreTournamentTemplateRequest.php` y
  `UpdateTournamentTemplateRequest.php` — campo `universe_id` opcional,
  validado contra la propiedad del usuario.
- `app/Http/Controllers/Tournaments/TournamentTemplateController.php` —
  `create()` acepta `?universe_id=` y expone `$universes`/
  `$selectedUniverseId`; `edit()` expone `$universes`; `index()` con eager
  loading de `universe`.
- `app/Http/Controllers/Universes/UniverseController.php` — `show()` con
  eager loading de `universe` en el listado de torneos del Universo.
- `resources/views/tournaments/partials/template-form.blade.php` — selector
  de Universo (bloqueado o libre, ver 24.2).
- `resources/views/tournaments/partials/template-card.blade.php` — badge
  🌌 opcional junto a los badges de estado/visibilidad existentes.
- `resources/views/partials/tournaments/sidebar.blade.php` — enlace
  "Universos" en la sección "Diseño", antes de "Torneos".

### 24.4 Verificación realizada

Por instrucción explícita del usuario, no se ejecutaron pruebas manuales de
interfaz en este cierre (a diferencia de las Fases 4 y 5). La verificación
automatizada disponible se ejecutó igualmente:
- `php artisan route:list --name=universes` → 8 rutas registradas
  correctamente.
- `php artisan view:cache` (compila todo Blade del proyecto) → sin errores
  de sintaxis en ninguna vista, incluidas las 6 nuevas y las 3 modificadas.
- `php -l` sobre los 4 archivos PHP modificados/creados en este cierre → sin
  errores de sintaxis.
- No se creó ningún dato de prueba en el proceso, por lo que el paso 17
  (limpieza) no tuvo nada que limpiar.

### 24.5 Pendiente / fuera de alcance de este cierre

- Ningún test automatizado nuevo (no se pidió, y no había riesgo de
  regresión crítica sobre funcionalidad existente: los cambios en
  `TournamentTemplate`/sus Form Requests/controlador son estrictamente
  aditivos y `universe_id` es nullable en todos los flujos).
- `duplicate()` de Universo — deliberadamente no implementado (sección 9).
- Cualquier forma de compartir/clonar Universos vía Comunidad — fuera de
  alcance (sección 13, "Qué NO hacer").
- El selector de Universo en `template-form.blade.php` no distingue aún
  Universos archivados de activos en el `<select>` — se listan todos los
  del usuario sin filtrar por `status`. No se consideró necesario para este
  cierre (un Universo archivado sigue siendo un contenedor válido para
  organizar torneos existentes), pero es una mejora candidata futura si se
  vuelve confuso en la práctica.

### 24.6 Relación con la futura Fase 6 (Runtime persistente)

Sin cambios respecto a lo ya establecido en la sección 11: `Universe` no
almacena ni referencia ningún dato de ejecución de torneo (participantes,
resultados, estado de grafo). Su única responsabilidad es agrupar
`TournamentTemplate`s. Cuando la Fase 6 introduzca un Tournament Runtime
persistente, la relación natural será `TournamentRuntime belongsTo
TournamentTemplate` (ya existente) — `Universe` queda automáticamente
"aguas arriba" de esa cadena sin requerir ninguna migración adicional en
este módulo.

---

**Estado**: Fase Universos implementada y cerrada. Ver guía de pruebas
manuales en el mensaje final de la conversación que ejecutó esta fase.
