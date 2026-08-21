# FASE UNIVERSOS — WORKSPACE PROPIO (Sprints U2, U3 y U4-lite)

Continuación de `22-Fase-Universos.md`. Aquella fase creó el CRUD de Universo
(Sprint U1 de tu propio roadmap, `09-Para Futuro.md` §84). Esta fase le da a
Universos **su propio módulo con interfaz, sidebar y paneles**, y construye
Competidores y Temporadas.

---

## 1. Corrección arquitectónica importante

La fase anterior asignó torneos a un Universo mediante
`tournament_templates.universe_id` (FK directa). **Eso está mal** según tu
propio diseño, y lo corrijo en esta fase.

Tu documento `09-Para Futuro.md` §57 dice literalmente:

> No se recomienda ejecutar directamente el TournamentTemplate.
> Debe existir una configuración intermedia:
> `TournamentTemplate → UniverseTournamentDefinition`

Y §906-907 establece el principio del proyecto:

> crear una vez y reutilizar muchas veces.

Una FK directa `tournament_templates.universe_id` significa que una plantilla
pertenece **a un solo Universo**. Eso rompe la reutilización: la plantilla
"Eliminación 16" debería poder usarse en el Universo Naruto y en el Universo
Dragon Ball a la vez, e incluso dos veces dentro del mismo Universo con
nombres distintos ("Copa de Primavera" y "Copa de Otoño").

**Corrección**: se elimina `tournament_templates.universe_id` y se crea la
tabla intermedia `universe_tournaments`. La plantilla vuelve a ser un diseño
reutilizable de la Biblioteca de Torneos; el Universo la **adopta** y le da
nombre y contexto propios.

### Consecuencia en el reparto de responsabilidades

| Concepto | Responsabilidad | Estado |
|---|---|---|
| `Entity` (Biblioteca) | Registro canónico, ajeno a cualquier Universo | Existe |
| `UniverseCompetitor` | Contexto de una Entity **dentro de** un Universo | **Se crea** |
| `UniverseSeason` | El tiempo propio del Universo | **Se crea** |
| `PhaseTemplate` | Mecanismo competitivo reutilizable | Existe, sin tocar |
| `TournamentTemplate` | Diseño de torneo reutilizable | Existe, se le **quita** `universe_id` |
| `UniverseTournament` | Uso concreto de una plantilla dentro de un Universo | **Se crea** |
| `TournamentInstance` | Competición real jugada, con resultados | **Fase 6, NO se construye** |

La frontera está clara: `UniverseTournament` dice *qué torneo se juega en este
Universo*; el futuro `TournamentInstance` (Fase 6 / Sprint U6) dirá *qué pasó
cuando se jugó en la Temporada 8*. Por eso `universe_tournaments` **no** tiene
`season_id`: la definición es atemporal, la instancia sí pertenecerá a una
temporada.

---

## 2. Universos como módulo propio

Hasta ahora Universos vivía dentro de la interfaz de Torneos. Se separa:

- **URL**: `tournaments/universes/*` → `universes/*`
- **Rutas**: `tournaments.universes.*` → `universes.*`
- **Layout**: nuevo `<x-universe-layout>` con sidebar propio (violeta/índigo),
  independiente del layout ámbar de Torneos.
- **Entrada**: desde el Hub (`hub/index.blade.php`), no desde el sidebar de
  Torneos.

---

## 3. Diseño del sidebar

El sidebar es **contextual**: cambia según estés en el módulo o dentro de un
Universo concreto. Es la decisión de diseño central de esta fase, porque un
Universo es un *mundo*: una vez dentro, todo lo que haces le pertenece.

### 3.1 Nivel módulo (`/universes`, `/universes/dashboard`)

```
← Centro OmniMerge
🌌 OmniMerge · Universos

PRINCIPAL
  ▦  Dashboard
  🌌 Mis Universos
  +  Nuevo Universo
```

### 3.2 Dentro de un Universo (`/universes/{universe}/...`)

```
← Todos los Universos
[portada] Universo Shinobi
          UNI000001 · Temporada 3

UNIVERSO
  ▦  Resumen

CONTENIDO
  ✦  Competidores   (184)
  ◷  Temporadas      (12)
  🏆 Torneos          (7)

HISTORIA
  ◇  Recompensas    Próximo
  📊 Rankings        Próximo

AJUSTES
  ⚙  Configuración
```

Los contadores se cargan con un único `loadCount` en el propio partial.
"Recompensas" y "Rankings" aparecen deshabilitados con la etiqueta "Próximo"
—el mismo patrón que ya usa el sidebar de Torneos— porque dependen de
resultados reales (Sprints U8/U9) y sería deshonesto enlazarlos a nada.

---

## 4. Modelo de datos

### 4.1 `universe_competitors` (Sprint U2, doc §46-47)

```
id
universe_id     FK universes  cascadeOnDelete
entity_id       FK entities   cascadeOnDelete
display_name    string(150) nullable   -- alias dentro de este Universo
status          string(20) default ACTIVE   -- ACTIVE | INACTIVE | RETIRED
notes           text nullable
timestamps
unique (universe_id, entity_id)
index  (universe_id, status)
```

- **Sin Soft Delete**: quitar un competidor es deshacer una asociación, no
  destruir información. La Entity de la Biblioteca queda intacta (doc §46:
  "No se creará otra Entity"). Cuando exista historial real (Sprint U8) habrá
  que reconsiderarlo, y queda anotado.
- **Sin `representation_policy`** (ORIGINAL/BASE_ACTIVE/...): tu doc §48 dice
  explícitamente que eso va *después* de la primera versión del módulo.
- **Sin `ranking`/`puntos`/`victorias`**: doc §47 los menciona, pero son
  resultados derivados de competiciones jugadas. No existen hasta la Fase 6.
  Guardarlos ahora sería inventar datos.

### 4.2 `universe_seasons` (Sprint U3, doc §54-56)

```
id
universe_id     FK universes  cascadeOnDelete
number          unsignedInteger        -- 1, 2, 3... correlativo por Universo
name            string(150)
description     text nullable
status          string(20) default PLANNED  -- PLANNED | ACTIVE | COMPLETED | ARCHIVED
starts_at       date nullable
ends_at         date nullable
timestamps + softDeletes
unique (universe_id, number)
index  (universe_id, status)
```

Los 4 estados son exactamente los de tu doc §55. **Regla de negocio**: solo
una temporada `ACTIVE` por Universo; activar una pasa la anterior a
`COMPLETED`, avisando antes en la interfaz. La "temporada actual" del Universo
(doc §45) se **deriva** de esta regla, no se duplica en una columna.

### 4.3 `universe_tournaments` (Sprint U4-lite, doc §57)

```
id
universe_id             FK universes            cascadeOnDelete
tournament_template_id  FK tournament_templates cascadeOnDelete
name                    string(150)             -- nombre dentro del Universo
description             text nullable
status                  string(20) default DRAFT  -- DRAFT | ACTIVE | ARCHIVED
timestamps + softDeletes
index (universe_id, status)
```

Sin índice único: el mismo template puede adoptarse varias veces con nombres
distintos. Sin `season_id` ni recurrencia (Sprint U7) ni reglas de
elegibilidad (Sprint U5).

### 4.4 Migración de datos

`tournament_templates.universe_id` se copia a `universe_tournaments` antes de
eliminar la columna, para no perder las asignaciones ya hechas.

---

## 5. Autorización

Sin Policies nuevas. Los tres recursos son hijos de `Universe` y se autorizan
vía la Policy del padre (`$this->authorize('update', $universe)`), que es el
patrón ya dominante en el proyecto (10 Policies para 44 modelos). Además todo
va con Route Model Binding con scoping explícito: se verifica que el hijo
pertenezca al Universo de la URL.

---

## 6. Archivos

**Migraciones (4)**: crear las 3 tablas + migrar y eliminar `universe_id`.

**Modelos (3 nuevos)**: `UniverseCompetitor`, `UniverseSeason`,
`UniverseTournament`.
**Modelos editados**: `Universe` (relaciones + `activeSeason()`),
`TournamentTemplate` (quitar `universe_id`).

**Servicios (3)**: `UniverseCompetitorService` (alta masiva idempotente),
`UniverseSeasonService` (numeración correlativa + regla de temporada única
activa), `UniverseTournamentService`.

**Requests (6)**: Store/Update de cada recurso.

**Controladores (3)**: `UniverseCompetitorController`,
`UniverseSeasonController`, `UniverseTournamentController`.

**Interfaz**: `UniverseLayout` + `layouts/universes.blade.php` +
`partials/universes/{sidebar,header}.blade.php`.

**Vistas**: `universes/show` se reescribe como Resumen (sin pestañas, ahora
son secciones del sidebar); nuevas carpetas `competitors/`, `seasons/`,
`tournaments/`.

**Reversiones**: se quita el selector de Universo y el badge del formulario y
la tarjeta de plantilla de torneo, y el campo `universe_id` de sus Form
Requests y su controlador.

---

## 7. Qué NO se construye en esta fase

- Filtros de elegibilidad por atributos (Sprint U5, doc §49-50).
- `TournamentInstance` / runtime persistente (Sprint U6 = Fase 6).
- Recurrencia entre temporadas (Sprint U7, doc §58-59).
- Recompensas e historial (Sprint U8, doc §63-64).
- Rankings de Universo (Sprint U9, doc §65).
- Políticas de representación de competidor (doc §48).
- Swiss, motores nuevos, y cualquier cosa de Biblioteca/Comunidad.

---

## 8. Notas de implementación

Implementado completo según las secciones 1-7, sin desviaciones de diseño.

### 8.1 Archivos creados

**Migraciones**: `create_universe_competitors_table`,
`create_universe_seasons_table`, `create_universe_tournaments_table`,
`move_universe_id_to_universe_tournaments` (traslada los datos existentes y
elimina la columna).

**Modelos**: `UniverseCompetitor`, `UniverseSeason`, `UniverseTournament`.

**Servicios**: `UniverseCompetitorService` (alta masiva idempotente con filtro
de propiedad), `UniverseSeasonService` (numeración correlativa + temporada
única en curso), `UniverseTournamentService`.

**Form Requests**: Store/Update de competidores, temporadas y torneos (6).

**Controladores**: `UniverseCompetitorController`, `UniverseSeasonController`,
`UniverseTournamentController`.

**Interfaz**: `App\View\Components\UniverseLayout`,
`layouts/universes.blade.php`, `partials/universes/sidebar.blade.php`,
`partials/universes/header.blade.php`.

**Vistas**: `universes/competitors/{index,create}`,
`universes/seasons/{index,create,edit}` + `partials/season-form`,
`universes/tournaments/{index,create,edit}`.

### 8.2 Archivos modificados

- `Universe`: relaciones `competitors()`, `seasons()`,
  `universeTournaments()` y `activeSeason()` derivada.
- `TournamentTemplate`: se elimina `universe_id` del `$fillable` y la relación
  `universe()`; se añade `universeTournaments()`.
- `UniverseController`: `show()` pasa a ser el panel Resumen;
  `index()` cuenta los tres contenidos.
- `UniverseDashboardController`: estadísticas agregadas del módulo.
- `TournamentTemplateController`, `StoreTournamentTemplateRequest`,
  `UpdateTournamentTemplateRequest`, `template-form.blade.php`,
  `template-card.blade.php`: revertido todo el cableado de `universe_id`.
- `routes/web.php`: Universos sale del prefijo `tournaments` y pasa a
  `/universes` con nombres `universes.*`; el subgrupo `/{universe}` usa
  `scopeBindings()`.
- `HubController` + `hub/index.blade.php`: la tarjeta de Universos deja de ser
  un placeholder "Próximamente" y enlaza al dashboard del módulo.
- `partials/tournaments/sidebar.blade.php`: se retira "Universos" del sidebar
  de Torneos (ahora es módulo propio, se entra desde el Hub).

### 8.3 Verificación realizada

- `php artisan migrate`: las 4 migraciones aplicadas correctamente.
- `php artisan view:cache`: todas las plantillas Blade del proyecto compilan.
- `php -l` sobre los 20 archivos PHP creados/modificados: sin errores.
- Prueba de dominio (script temporal, datos borrados al terminar): alta de
  competidores idempotente (repetir devuelve 0), rechazo de entidades de otro
  usuario, numeración correlativa de temporadas, regla de temporada única en
  curso (activar la 2 pasa la 1 a COMPLETED), `activeSeason()` derivada.
- Prueba de reutilización de plantillas: una misma `TournamentTemplate`
  adoptada por 2 Universos distintos y 2 veces dentro del mismo con nombres
  distintos — imposible con el modelo anterior. Quitar una adopción no toca la
  plantilla.
- Prueba HTTP (test temporal contra la base real, eliminado después): las 12
  rutas del módulo devuelven 200; el sidebar contextual aparece dentro de un
  Universo; pedir un hijo de otro Universo devuelve 404 (`scopeBindings`);
  otro usuario recibe 403 (Policy del padre).
- Sin datos de prueba residuales.

### 8.4 Incidencia preexistente detectada (no causada por esta fase)

`php artisan test` falla en 95 pruebas por una migración anterior
(`2026_08_08_211827_add_identity_fields_to_attribute_options_table`, commit
`3b98378`) que usa `UPDATE ... INNER JOIN`, sintaxis exclusiva de MySQL. Como
la suite corre sobre SQLite en memoria, ninguna prueba que toque base de datos
puede ejecutarse. Es anterior a todo el trabajo de Universos y queda pendiente
de corregir aparte.
