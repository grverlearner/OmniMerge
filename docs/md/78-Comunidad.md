# 78 · La Comunidad, con casa propia

## El problema

«Comunidad» no era un sitio. En el Centro era un enlace que caía en el
explorador de la Biblioteca (`/explore`). La otra mitad de lo que se
comparte, las plantillas de torneo y de fase, vivía en `/tournaments/community`,
dentro del módulo de Torneos. Había además tres perfiles distintos que se
enlazaban con botones sueltos:

- el público general, `/perfil/{usuario}`;
- el de la Biblioteca, `/explore/creators/{usuario}`;
- el de Torneos, `/tournaments/community/creators/{id}`.

Nadie podía ver a la gente de la comunidad entera, ni saber si alguien
publica solo entidades, solo torneos o las dos cosas.

## Qué hay ahora

### Un espacio propio

`CommunityLayout` (`<x-community-layout>`), con `layouts/community`,
`partials/community/sidebar` y `partials/community/header`. Tiene el mismo
armazón que los otros módulos y su propio color, el verde: se añadió
`emerald` a los mapas literales de `omni-sidebar`, `omni-sidebar-brand` y
`omni-nav-item`. El sidebar tiene estas secciones:

| Sección | Entradas |
|---|---|
| Principal | Inicio (`community.home`) |
| Explorar | Biblioteca (`community.index` y sus fichas) · Torneos y fases (`tournaments.community.*`) |
| Personas | Creadores (directorio, las fichas de creador de los dos lados y los perfiles ajenos) |
| Tú | Mi perfil público · Qué se ve de lo mío (`profile.edit`) · Panel de creador |
| Pie | Ir a Biblioteca / Torneos / Universos |

Los dos exploradores **no se reescribieron**. Sus once vistas solo cambiaron
la etiqueta del layout, y conservan rutas, props y contenido:

- `community/{index,entity,collection,attribute,catalog,creator}`;
- `tournaments/community/{index,tournament,phase,creator}`;
- `profiles/show`.

### Inicio: `/comunidad`

`CommunityHomeController`, vista `community/home/index` y sus partials:

- **Portada**:
  - un mosaico con las caras públicas;
  - el **buscador de toda la comunidad**, en vivo, agrupado por clase y con
    las personas al final. La tecla `/` lo enfoca;
  - cifras de los dos lados.
- **Puertas**: las cinco clases de pieza, con sus últimas imágenes y cuántas
  hay, más la gente. Cada puerta abre su explorador ya filtrado (`tab=` en la
  Biblioteca, `kind=` en Torneos).
- **Recién publicado**:
  - reúne las cinco clases;
  - se filtra por clase sin recargar;
  - se ve en mosaico o en lista, y la preferencia se recuerda.
- **Tu huella**:
  - tu tipo de creador;
  - cuánto de lo tuyo está publicado, por clase;
  - cuántas copias y vistas tiene;
  - si el perfil es privado, un aviso con un botón para cambiarlo. Es la
    razón de que no aparezcas en Creadores.
- **Lo que más se copia**: un ranking por `clones_count`.
- **Quién lo hace**: los cuatro creadores con más publicaciones de cada tipo.

`/comunidad/buscar` (`community.buscar`) responde de dos formas:

- **JSON**, con `Accept: application/json`, para el buscador en vivo;
- **una página de resultados**, con un enlace «Abrir en su explorador» por
  grupo.

Busca por `name` y `code`, con los comodines de `LIKE` escapados.

### Creadores: `/comunidad/creadores`

`CommunityCreatorsController`. Las cuentas salen de
`App\Services\Community\CreatorDirectory`, que junta las dos comunidades.

| Tipo | Cuándo |
|---|---|
| Creador completo | publica en la biblioteca **y** en torneos |
| Creador de biblioteca | solo entidades, colecciones o atributos |
| Creador de torneos | solo plantillas de torneo o de fase |

Las reglas de «público» son las mismas de los exploradores:

- **Biblioteca**: `visibility` o `scope` = `PUBLIC`, `status` = `ACTIVE` y
  `published_at` no nulo.
- **Torneos**: el scope `published()`.

En el directorio aparecen personas `ACTIVE` con perfil `PUBLIC` que hayan
publicado algo.

Controles:

- **Filtro por tipo**: las cuatro tarjetas de arriba son a la vez índice y
  filtro.
- **Búsqueda**: por nombre, usuario o presentación.
- **Orden**: más publicado, más copiado, publicación más reciente o nombre.

Formas de mirar, recordadas en `localStorage`:

- **Mapa**: la comunidad partida en tres cajas, Solo biblioteca | Completos |
  Solo torneos.
- **Galería**: una tarjeta por creador, con sus caras, cifras y botones a su
  perfil y a cada una de sus dos mitades.
- **Cuadrícula**: solo las caras, con tamaño ajustable de 3 a 8 columnas.
- **Lista**.
- **Tabla**: todas las cifras.

Al pie se dice cuántos perfiles visibles aún no han publicado nada.

### Un perfil, tres vistas

`community/partials/perfil-pestanas` se incluye bajo la cabecera de los tres
perfiles. Muestra la tira **Todo · Biblioteca · Torneos**:

- cada pestaña lleva su cuenta;
- una pestaña sin nada publicado se muestra apagada, pero no se oculta;
- a la derecha, la etiqueta del tipo de creador y un botón a «Otros creadores»
  de ese tipo.

En tu propia ficha las cuentas salen aunque tu perfil sea privado
(`CreatorDirectory::consulta(false)`).

## Redirecciones cambiadas

- Centro, tarjeta Comunidad, y navegación del Centro (escritorio y móvil) →
  `community.home`.
- Centro, primeros pasos «Copiar de la comunidad» → `community.home`.
- Salto de módulos del dashboard de Biblioteca → `community.home`.
- `profiles/show`: «Volver a la comunidad» pasa a ser «Ver otros creadores» →
  `community.creators.index`.
- Sidebar de Torneos: el bloque Comunidad pasa al verde, con «Explorar
  plantillas» y «Creadores de torneos».
- Los enlaces contextuales (Descubrir en la Biblioteca, fichas, clonar)
  siguen yendo a su explorador. Lo que cambia es que ahora se abren dentro de
  este espacio.

## Límites conocidos

`CreatorDirectory::creadores()` trae a todos los usuarios con perfil visible
y filtra en memoria, porque el tipo sale de cinco subconsultas sumadas. Va
sobrado con cientos de creadores. Con decenas de miles convendría guardar el
tipo en una columna.
