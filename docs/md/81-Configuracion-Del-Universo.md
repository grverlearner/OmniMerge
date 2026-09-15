# 81 · La configuración del universo

## Qué había

«Configuración» solo permitía cambiar nombre, descripción, estado y portada,
además de archivar y borrar. El único ajuste con efecto real eran los puntos
de la clasificación, y se editaban en otra pantalla.

Guardar redirigía a `tournaments.universes.show`, una ruta que no existe.

## Qué hay ahora

`universes/edit` es un panel completo con tres columnas:

- **Índice**: salta a cada sección y marca en cuál estás. También avisa si
  hay cambios sin guardar.
- **Secciones**: todos los ajustes.
- **Vista previa**: muestra en vivo cómo quedan el sidebar, la tarjeta de
  «Mis universos» y la clasificación.

Todo va en un solo formulario salvo archivar y borrar. Hay una barra de
guardado fija, «Descartar», un aviso al salir con cambios y un
«Restablecer» por sección.

### Qué ajustes hay y dónde se nota

| Sección | Ajustes | Dónde se aplica |
|---|---|---|
| Identidad | Nombre, descripción, estado, portada y **lema** (120 caracteres) | El lema, bajo el nombre en el Resumen y en la tarjeta de «Mis universos» |
| Apariencia | **Color** (paleta o cualquier hex), **icono** (18 del juego de iconos) y **encuadre de la portada** (arriba, centro o abajo) | Línea de color en todas las pantallas del universo; marca del sidebar; rótulo de la cabecera; portada del Resumen (borde, halo y línea); tarjeta y fila de «Mis universos». El icono sale donde no hay portada |
| Vocabulario | Cómo se llaman sus habitantes, una temporada, varias temporadas, los torneos y las competiciones | Sidebar, cifras del Resumen, «Temporada N» en el Resumen y en la marca del sidebar |
| Menú e inicio | **Secciones visibles** (9 escondibles; Resumen y Configuración siempre están) y **al entrar, abrir** (Resumen, Explorar, Competiciones, Torneos, Clasificación o Entidades) | Sidebar. Los enlaces a un universo desde «Mis universos», el Centro y el panel de Universos usan `Universe::home_url`. Si la sección elegida está escondida, se abre el Resumen |
| Resumen | **Orden** de sus 9 bloques dentro de su zona (arriba, columna principal, columna lateral) y cuáles se **esconden** | `universes/show`. «Las cifras» pasa a ser un bloque (`resumen/cifras`). Si se esconde todo, se dice y se enlaza aquí |
| Clasificación | **Puntos** (título, victoria, empate, derrota y participación), con un ejemplo en vivo; **mínimo de competiciones** para aparecer; **desempates** ordenables (títulos, victorias, % de victorias, menos partidas, nombre) | `UniverseRankingService::ranking()`, y por tanto la clasificación, el podio y los dashboards |
| Torneos nuevos | Formato (al mejor de 1/3/5/7/9 o juegos fijos), cómo se decide, empates, cada cuánto se juega, **cara** con la que sale cada uno y **reparto** entre puertas | El diseñador de un torneo nuevo nace con esto. La sala de participantes nace con esa cara y ese reparto. Los torneos existentes no cambian |
| Explorar | **Criterio** con el que se reparte el mapa (del universo o cualquier atributo) y **forma de mirarlo** | `UniverseExplorerController` y el mapa. Si la configuración cambia, manda ella sobre lo último que se miró en ese navegador |
| Zona de peligro | Archivar, y borrar escribiendo el nombre del universo | Igual que antes, pero borrar pide confirmar con el nombre |

### Por detrás

- **`App\Support\Universes\UniverseSettings`**:
  - define todas las claves, sus valores por defecto y sus opciones (`ICONS`,
    `PALETTE`, `NAV`, `HOMES`, `BLOCKS`, `TIEBREAKS`…);
  - `save()` solo acepta claves conocidas y pasa cada valor por su tipo
    (enteros acotados, hex válido, listas con valores permitidos, textos
    recortados y sin etiquetas);
  - las listas siempre llegan, aunque vayan vacías: vacía también es una
    decisión.
- **Lecturas**: `accent()`, `icon()`, `tagline()`, `coverPosition()`,
  `label()`, `navVisible()`, `homeUrl()`, `summaryBlocks($zona)` y
  `competitionDefaults()`.
- **`Universe`**: `ajustes()`, y los atributos `home_url` y `accent`.
- **`UniverseController@update`**:
  - guarda la identidad y después los ajustes;
  - vuelve a la configuración, a la sección en la que estabas
    (`#clasificacion`, etc.).
- **`omni-sidebar-brand`** acepta `color`.
- **`UniverseTournamentController`**: un torneo nuevo recibe
  `face_mode` y `doors.strategy` del universo.
- **Ranking de antes**: el formulario de puntos de la clasificación sigue
  funcionando, porque usa el mismo `save()`.

## Verificación

Todo lo que escribe se probó dentro de una transacción deshecha. Los ajustes
del universo 7 terminan como estaban.

- **Pantallas:** responden 200 la configuración, el Resumen, «Mis universos»,
  Explorar, la clasificación, crear torneo, el Centro y el panel de
  Universos.
- **Guardado:** se envía una configuración completa más una clave inventada.
  - Redirige a `/universes/7/edit#clasificacion`.
  - La clave inventada no se guarda.
  - El color queda en `#f472b6`.
  - El inicio pasa a ser la clasificación.
  - Los bloques quedan en el orden enviado, sin «La línea del tiempo».
- **Efectos:**
  - **Resumen:** muestra el lema, el color y el vocabulario (Ninjas, Copas,
    Ediciones, «Arco 2»); el sidebar ya no enlaza a Juegos ni a Historial.
  - **«Mis universos»:** enlaza a la clasificación.
  - **Torneo nuevo:** nace al mejor de 5 y decidiendo solo por anotaciones.
  - **Mapa:** recibe su modo por defecto.
  - **Clasificación:** con 20 puntos por título y 0 por participar, Jura pasa
    de 126 a 201.
- **Navegador:**
  - la pantalla carga sin errores de consola;
  - cambiar color, icono, vocabulario, menú y lema actualiza la vista previa
    y activa el aviso de cambios sin guardar.
