# El perfil — la persona entera, y cómo te ves tú

## 1. Qué había

Tres pantallas con la palabra «perfil» y ninguna era el perfil de la persona:

| Pantalla | Qué enseñaba |
|---|---|
| `community.creators.show` | Su **biblioteca** pública, a fondo. |
| `tournaments.community.creator` | Sus **plantillas de torneo** publicadas. |
| `profile.edit` | Los ajustes de cuenta que trae Breeze, en claro. |

Los dos perfiles públicos son buenos para lo suyo y no se conocían entre sí más
que por un enlace suelto. Quien llegaba desde una entidad copiada veía la
biblioteca y no se enteraba de que esa persona además diseña torneos; quien
llegaba desde una plantilla, al revés. Ninguno contestaba **«¿quién es este?»**.

Y los ajustes no contestaban las dos preguntas que uno se hace ahí: **cómo me
ven** y **qué se ve de lo mío**.

## 2. El perfil completo: `/perfil/{username}`

Ruta nueva, `profiles.show`, por nombre de usuario para que la dirección se
pueda leer y compartir.

- **Portada**: el mosaico de sus entidades públicas detrás, su foto, `@usuario`,
  nombre, presentación, biografía, ubicación, sitio web y desde cuándo está.
- **Ocho cifras** de las dos mitades a la vez: entidades, colecciones, atributos,
  valores de catálogo, torneos, fases, **le han copiado** —la única que no depende
  de lo que uno haga, sino de lo que le sirva a otro— y visitas.
- **Su biblioteca**: una muestra con caras de entidades, colecciones, atributos y
  valores de catálogo.
- **Lo que ha diseñado para competir**: sus torneos (con cuántas fases y salidas)
  y sus fases publicadas.
- **Por dónde seguir**: los dos perfiles especializados, diciendo qué ofrece cada
  uno, porque «ver perfil» dos veces no explica en qué se diferencian.

Los perfiles especializados **se conservan**: para bucear en un lado siguen
siendo mejores. Esta pantalla es la persona.

### Qué es público: las mismas reglas, no unas parecidas

Una entidad o colección es pública si es `PUBLIC`, `ACTIVE` y tiene fecha de
publicación; un atributo, lo mismo con `scope`; un valor de catálogo, cuando lo
es su catálogo; una plantilla, cuando está publicada. Son **exactamente** las
reglas de la Comunidad. Repetirlas «parecidas» es como se acaba enseñando por
error algo que su autor no publicó.

### Perfil privado

Si el perfil es privado y quien mira no es el dueño, se enseña el nombre, la
foto y **«Este perfil es privado»** — nada más: ni la biblioteca, ni los torneos,
ni las cifras. El dueño sí ve su página entera, con una etiqueta «perfil
privado» que le recuerda que nadie más puede abrirla.

## 3. Mi perfil: `/profile`

La misma ruta de siempre, rehecha en oscuro y ordenada por lo que importa:

**Cómo te ven** — tu tarjeta tal como aparece en la comunidad, el estado del
perfil con su consecuencia escrita («cualquiera con cuenta puede abrir tu
página» / «nadie más que tú») y el botón a tu perfil completo.

**Qué se ve de lo tuyo** — la pieza que faltaba. La visibilidad se decide pieza a
pieza en la ficha de cada cosa, y eso está bien; la consecuencia mala era que se
podía abrir el perfil sin saber qué quedaba a la vista. Ahora, por tipo, cuántas
hay y cuántas son públicas, con su barra y enlace a donde se cambia. Y dos avisos:

- cosas publicadas **con el perfil cerrado**: siguen saliendo en la comunidad,
  pero sueltas, sin camino a lo demás;
- perfil **abierto y vacío**: no has publicado nada todavía.

Los universos no aparecen, y se dice por qué: **no se publican**.

**Quién eres** — foto con vista previa, nombre, usuario (con la dirección que
genera), presentación, biografía, ubicación, web (el `https://` se pone solo),
correo (con la nota de que no se enseña) y la visibilidad del perfil como dos
opciones explicadas, no como un desplegable.

**Contraseña**, **Tu cuenta** —estado, correo verificado o no, desde cuándo y
última entrada: datos que existían en la tabla y no se veían en ninguna parte— y
**Borrar mi cuenta**, diciendo exactamente qué se pierde con sus números
(«22 entidades, 1 colección, 6 atributos, 12 torneos, 25 fases y 3 universos») en
vez de «esta acción no se puede deshacer».

## 4. De dónde se llega: los accesos revisados

| Sitio | Antes | Ahora |
|---|---|---|
| Centro, «Tu página pública» | Perfil de biblioteca, y solo si era público | **Perfil completo**, siempre, con etiqueta «privado» si lo es |
| Chip de usuario de los tres sidebars | Ajustes | **Perfil completo** (los ajustes siguen en su botón) |
| Panel de la Biblioteca | Perfil de biblioteca | **Perfil completo** |
| Perfil de biblioteca | Solo enlazaba a sus torneos | + **«Su perfil completo»** |
| Perfil de torneos | Solo enlazaba a su biblioteca | + **«Su perfil completo»** |
| Mi perfil | — | **«Ver mi perfil completo»** |

Los menús de cabecera siguen llevando a `profile.edit`, porque ahí dicen
«Perfil y cuenta»: son ajustes, y está bien que lo sean.

## 5. Una trampa de Blade que salió en la captura

La primera versión escribía el nombre de usuario como `@{{ $creador->username }}`,
buscando una arroba delante. En Blade, **`@{{` significa «no interpretes esto»**:
es la forma de dejar llaves literales para Alpine o Vue. La página enseñaba, tal
cual, `{{ $creador->username }}`.

Ninguna comprobación por HTTP lo detectó —la respuesta era 200 y el texto
buscado estaba—; lo destapó mirar la captura. La salida es la entidad HTML:
`&#64;{{ $creador->username }}`. Se buscó el patrón en todas las vistas y solo
aparecía en las dos pantallas nuevas.

## 6. Lo que se comprobó

- `/perfil/{usuario}` devuelve 200 para el propio perfil, el de otro y el propio
  visto por otro; **404** para un usuario que no existe.
- `/profile` devuelve 200 para dos usuarios distintos, y las demás pantallas
  tocadas (Centro, panel de la Biblioteca, los dos perfiles especializados,
  Universos) siguen a 200.
- **El perfil privado** se probó por HTTP real **dentro de una transacción que se
  deshizo** —los seis usuarios son públicos y no se toca su configuración—:
  visto por otro sale «Este perfil es privado» y no sale ni la biblioteca, ni los
  torneos, ni las cifras; visto por su dueño sale entero, con «perfil privado» y
  «eres tú». Al revertir, la visibilidad volvió a `PUBLIC`.
- Las cifras públicas cuadran con la base de datos: 2 entidades, 1 colección,
  0 atributos, 1 torneo y 1 fase publicados.
- Cada pantalla de acceso contiene al menos un enlace al perfil completo; ningún
  usuario tiene el nombre de usuario vacío, que es de lo que depende el chip del
  sidebar.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 7. Lo que queda

El chip del sidebar construye la dirección con `auth()->user()->username`. Hoy es
obligatorio y ningún usuario lo tiene vacío, pero si alguna vez se permitiera
registrarse sin él, ese enlace fallaría: el sitio para blindarlo es el propio
componente.
