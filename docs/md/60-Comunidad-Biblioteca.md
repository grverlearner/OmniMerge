# La comunidad de la biblioteca

El panel donde se ve lo que otros han hecho público —entidades, colecciones,
atributos y valores de catálogo— y se copia a la biblioteca propia. Es el
hermano del explorador de plantillas de torneo, pero de la biblioteca.

## 1. Qué había y qué faltaba

La pantalla ya tenía sus seis pestañas, un controlador serio con filtros por
tipo, creador, imagen, periodo y tamaño, y el clonado funcionando de verdad.
Todo en claro, y con tres agujeros que no eran de diseño sino de información:

- **No se decía de quién viene nada.** Al copiar algo, la copia guarda su origen
  (`source_entity_id`, `source_collection_id`, `source_attribute_id`,
  `source_attribute_option_id`). El dato estaba desde el principio y no se
  pedía en ninguna consulta, así que **una cadena de tres copias parecía tres
  creaciones originales**.
- **No se decía si ya lo tenías.** El controlador ya traía `clones` filtrado al
  que mira —o sea, la respuesta estaba cargada en memoria— y la vista no la
  usaba. La comunidad invitaba a duplicar lo mismo una y otra vez.
- **No se decía si su autor deja copiarlo.** El botón se podía pulsar siempre y
  fallaba después.

## 2. Lo que hace ahora

### La atribución, en todas partes

Cada ficha, cada fila y cada tabla enseña **de quién es** y, si es una copia,
**de quién la copió esa persona**: «inspirado en @alguien», en ámbar, enlazado a
su perfil. Las tablas de las cuatro pestañas tienen su propia columna
«Inspirada en», que dice `original` cuando no lo es.

### El botón de copiar, con sus cuatro estados

Un botón que se puede pulsar y luego falla es peor que uno que no está, así que
la pieza compartida distingue:

| Estado | Qué se ve |
|---|---|
| es tuyo | «Tuyo», apagado |
| ya lo copiaste | «✓ Ya lo tienes», y lleva **a tu copia** |
| su autor no deja | «No se copia», con el motivo en el título |
| se puede | «Copiar» |

### Cinco formas de mirar por pestaña

Galería, cuadrícula, lista, tabla y una **especial distinta en cada una**:

- **Entidades → por creador.** Agrupa lo de la página por quien lo hizo, porque
  la pregunta real al explorar no es «qué entidades hay» sino «quién está
  haciendo cosas buenas».
- **Colecciones → contenido.** Una colección no se juzga por su portada sino por
  lo que tiene dentro: se ven las caras de sus miembros antes de copiarla.
- **Atributos → valores.** Un atributo de catálogo se copia por lo que trae
  dentro, y uno vacío no sirve de nada. Se ve antes, no después.
- **Catálogos → por catálogo.** Un valor suelto no significa nada —«Uzumaki»
  fuera de «Clan» es una palabra—, así que van agrupados por a cuál pertenecen.

Galería y cuadrícula llevan control de tamaño de 4 a 9 columnas, y **la vista
elegida se recuerda por pestaña**: elegir «tabla» en catálogos no deja las
entidades en tabla.

### Copiar varias entidades de una vez

En la vista de lista se marcan varias y se copian juntas. Los ids se emiten una
sola vez desde la selección, no uno por modo de vista. Las que ya tengas se
saltan, y el servidor lo dice.

### El explicador

Plegado, con un dibujo de las tres piezas: la biblioteca de otro → tu copia →
la nota de «inspirado en». Y lo que hay que saber antes de pulsar: copiar una
colección se lleva sus entidades, y copiar un atributo de catálogo se lleva sus
valores.

### La pestaña «Todo»

Deja de ser una lista mezclada y pasa a ser un escaparate: un bloque por clase
con un puñado de fichas y un enlace a su pestaña. Mezclar entidades,
colecciones, atributos y valores en una sola rejilla no deja comparar nada.

### Los creadores

Su tarjeta enseña **seis de sus caras**, no solo «12 entidades». Con eso se
decide en un vistazo si vale la pena entrar en su perfil, que es para lo único
que sirve esa tarjeta.

## 3. Lo que se añadió por detrás

En `ExploreController::index()`:

- Las cuatro consultas base cargan ahora la cadena de origen
  (`sourceEntity.creator`, `sourceCollection.creator`, `sourceAttribute.creator`,
  `sourceOption.user`). Sin eso, la atribución no se podía enseñar.
- Las colecciones cargan sus miembros públicos y los atributos sus valores
  activos —doce de cada— para las vistas de contenido y de valores. Sin eso, las
  dos habrían enseñado «está vacío» de todo.
- `$carasDeCreador`: hasta seis entidades con imagen por creador, sacadas de una
  vez para todos los que salen en pantalla en lugar de una consulta por tarjeta.

Piezas nuevas: `partials/atribucion`, `partials/copiar`, las cinco
`partials/tarjeta-*`, las cuatro `partials/resultados-*`,
`partials/resumen-todo` y `partials/vacio`. Se retiraron `results-view`,
`filters`, `item-card` y `list-item`, que ya no usaba nadie.

## 4. Decisiones que conviene recordar

**El vacío distingue sus dos motivos.** Si hay filtros puestos, lo que falla es
la búsqueda; si no los hay, es que la comunidad todavía no tiene nada de eso, y
entonces lo útil es decir que nada se comparte solo —algo aparece aquí cuando su
autor lo marca como público—.

**Una colección con miembros privados no dice «está vacía».** Dice que tiene N
entidades pero que ninguna es pública, que es otra cosa. El vacío se decide por
la cuenta real, no por si la relación vino cargada.

**Las vistas agrupadas dicen que agrupan la página.** «Por creador» y «por
catálogo» reparten lo que hay en pantalla, no la comunidad entera, y lo avisan.

**Los modos conviven ocultos, no desmontados.** Con `x-show` y no con `x-if`,
para que las casillas de selección no pierdan lo marcado al cambiar de vista.

## 5. Verificación

- Las ocho direcciones responden 200: las seis pestañas, una búsqueda sin
  resultados y una combinación de filtros con orden y tamaño de página.
- **La atribución no salía en pantalla, y con razón**: de las 22 entidades
  públicas de la biblioteca de prueba, ninguna es una copia —las 20 copias que
  existen son privadas—. Para comprobar que la línea aparece cuando hay de qué,
  se marcó una entidad pública como copia de otra de otro creador **dentro de
  una transacción que se deshizo**: salió «inspirado en @groverromeo», y de paso
  el original quedó marcado como «Ya lo tienes», que es la otra cara de la misma
  moneda. Al deshacer, la cuenta de copias volvió a 20.
- Las cinco vistas de catálogos agrupan bien: «Anime» y «Clanes» con 1 y 23
  valores en la página mirada.
- Las tarjetas de creador salen con su avatar, sus caras y sus tres contadores.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

Las **fichas de detalle** de la comunidad —entidad, colección, atributo y
catálogo— siguen en el diseño claro anterior. Usan todavía `entity-card`,
`collection-card`, `attribute-card` y `gallery-item`, que por eso no se
retiraron. El perfil de creador sí está rehecho: ver el anexo A.

---

# Anexo A — El perfil de biblioteca de un creador

## A.1 Qué había y qué faltaba

Seis entidades, seis colecciones, seis atributos y se acabó. Un escaparate sin
fondo: no había manera de explorar de verdad lo que alguien ha hecho, ni de
saber si valía la pena.

Y las cuatro preguntas que un perfil debería contestar no se contestaban:

- **¿Cuánto le han copiado?** Es el único número que dice si su trabajo le sirve
  a alguien más que a él.
- **¿Qué le has copiado tú?** Sin eso, se copia dos veces lo mismo.
- **¿De quién se inspira él?** La atribución también va hacia atrás.
- **¿De qué está hecha su biblioteca?** Un número no lo dice; el reparto por
  tipo de entidad, sí.

## A.2 Lo que hace ahora

**Solo biblioteca.** Entidades, colecciones, atributos y valores de catálogo.
Sus plantillas de torneo viven en la otra comunidad y aquí hay un botón «Sus
torneos →», porque mezclar las dos cosas convierte el perfil en un cajón.

**Un mosaico de sus caras como portada.** Hasta dieciocho de sus entidades
públicas ocupando el ancho, con el avatar montado encima. No es adorno: es lo
primero que dice qué clase de biblioteca es esta, y a la vez la forma más rápida
de entrar en cualquiera de ellas.

**Seis cifras**, cuatro de ellas enlazadas a su pestaña: entidades, colecciones,
atributos, catálogos, **↺ le han copiado** y **ya tienes suyo**.

**Cinco pestañas.** Resumen y una por clase, cada una con búsqueda, orden
—incluido «lo más copiado»—, sus filtros propios, tamaño de página y **cinco
formas de mirar** reutilizando las mismas piezas que el explorador: galería,
cuadrícula, lista, tabla y la especial de cada clase. Tamaño de 4 a 9 columnas,
y la vista elegida se recuerda **por pestaña**.

**El resumen**, con lo que un perfil debería tener:

- **Lo que más le han copiado**, con barras y las caras.
- **Sus entidades, colecciones y atributos** en pequeño, con enlace a su
  pestaña.
- **De qué está hecha**: reparto por tipo de entidad, con barras y filtrando al
  pulsar.
- **Sus catálogos**, con su cara y cuántos valores tiene cada uno —los vacíos en
  rojo—, enlazados a la pestaña de catálogos ya filtrada.
- **De quién se inspira**: los creadores de los que él ha copiado, con su
  avatar.
- **Lo último que publicó**: las tres clases mezcladas y ordenadas por fecha,
  que es la única forma de ver si sigue trabajando o lleva un año parado.

**Copiar desde aquí**, con los mismos cuatro estados del explorador: tuyo / ya
lo tienes / no se copia / copiar.

## A.3 Decisiones que conviene recordar

**Qué cuenta como público vive en un sitio.** Las tres reglas —visibilidad,
estado y fecha de publicación— están en tres métodos privados que usan todas las
consultas. Repetirlas en cada una es como se acaba enseñando por error algo que
su autor no publicó.

**Un valor de catálogo es público cuando lo es su catálogo.** Un valor no se
publica por su cuenta, así que la consulta va por el atributo que lo contiene.

**El dueño ve su propio perfil aunque lo tenga en privado**, y se le avisa con
una etiqueta de que los demás no lo ven. También se le dice, cuando no tiene
nada publicado, que su biblioteca puede estar llena y aun así no verse aquí.

**«Ya tienes suyo» no aplica al dueño.** En su propio perfil sale un guión, no
un cero: cero significaría que no ha copiado nada de sí mismo, que no es una
frase con sentido.

## A.4 Verificación

- Las ocho direcciones responden 200: el resumen de un perfil ajeno, sus cuatro
  pestañas, el perfil propio, una combinación de orden y filtro, y una búsqueda
  sin resultados.
- Las dos cifras nuevas se comprobaron **contra la base de datos**: «le han
  copiado» marca ↺3 en el perfil de @groverromeo, que es exactamente la suma de
  `clones_count` de sus entidades, colecciones y atributos públicos.
- «Ya tienes suyo» marca 0 sin copias y pasa a **1** al crear una **dentro de
  una transacción que se deshizo**; en la misma prueba, su entidad copiada
  apareció con «Ya lo tienes» en la pestaña de entidades y las demás siguieron
  ofreciendo «Copiar». Al deshacer, la cuenta de copias volvió a 20.
- Un fallo encontrado y corregido por el camino: los métodos que aplican las
  reglas de «público» estaban tipados como `Builder`, y `$user->entities()`
  devuelve una relación, no un `Builder`. Las cuatro pestañas daban 500 hasta
  aceptar los dos tipos.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.
