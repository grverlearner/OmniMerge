# El taller de versiones

**Fecha:** 4 de septiembre de 2026
**Alcance:** las cuatro pantallas del taller, su navegación y el acceso desde
el índice de Entidades.

---

## 1. El problema

El primer intento se quedó a medias: se cambió el fondo a oscuro y se dejó el
marcado de antes. El resultado era exactamente eso, **una pantalla vieja con
fondo oscuro** — cabeceras de degradado enormes junto a cabeceras compactas,
tarjetas de 400px al lado de fichas de 200, y ninguna manera de mirar los datos
que no fuera la única que ya había. Los saltos entre pestañas se notaban porque
cada una estaba hecha en un momento distinto.

Este segundo pase reescribe las cuatro y las pone sobre el mismo patrón.

---

## 2. Un molde, muchas versiones

El concepto que sostiene todo el taller:

- Una **definición** —«Shippuden», «Niño», «Forma final»— es un **molde**.
  Existe una sola vez y no pertenece a nadie.
- Una **versión de entidad** es ese molde ya puesto sobre alguien: «Naruto —
  Shippuden», con su imagen, su nombre y sus características.
- De cada entidad, una de sus versiones puede ser la **Base activa**: la cara
  que el resto de la aplicación enseña.

Sigue dibujado en el índice, en un bloque plegable, pero ahora su cabecera es
una sola línea en vez de un bloque con subtítulo.

---

## 3. La navegación

Cuatro pantallas, **cuatro palabras**. La explicación que llevaba cada pestaña
debajo del nombre hacía la barra el doble de alta en todas partes: una barra de
navegación se lee cien veces y solo hace falta entenderla una. Lo que cada
pantalla significa se explica **dentro** de ella, no en el camino.

| pestaña | qué es |
|---|---|
| **Definiciones** | los moldes |
| **Aplicadas** | cada molde ya puesto sobre una entidad |
| **Cobertura** | a quién le falta cada molde |
| **Imágenes** | las caras de cada versión |

Las cuatro pantallas comparten ahora la misma cabecera —migaja, título de una
línea, frase corta y las cifras a la derecha—, la misma barra de filtros
pegajosa y el mismo selector de vista y tamaño. Ese es el motivo de que ya no
se note el salto al cambiar de pestaña.

### El probador, retirado

La quinta pestaña preguntaba «dado este catálogo, qué versión saldría». Exigía
entender el resolver antes de poder usarla. Se retiró la **pantalla**: la ruta,
la vista y el método del controlador. El motor (`VersionResolverService`) sigue
intacto y en uso por la aplicación; si la pantalla vuelve a hacer falta, está en
el historial de git.

---

## 4. Definiciones

La cabecera era un bloque con degradado, un título de 24 px, un párrafo de tres
líneas y un botón grande de crear: media pantalla antes de ver una sola
definición. Ahora es la cabecera compacta común, y **crear bajó**: una baldosa
al final del mosaico —donde se busca cuando ya se ha mirado lo que hay, para no
repetir un molde que existía— y un acceso corto en la barra de filtros.

Tres maneras de mirar, recordadas entre visitas: **mosaico**, **árbol** (la
jerarquía: qué definición cuelga de cuál) y **tabla**.

---

## 5. Aplicadas

Una fila de esta pantalla es siempre la unión de dos cosas —una entidad y una
definición—, así que ahora se puede mirar desde los dos lados:

| modo | qué enseña |
|---|---|
| **galería** | solo la cara y el nombre |
| **cuadrícula** | la ficha con sus etiquetas y acciones |
| **lista** | una línea por versión, para repasar muchas |
| **tabla** | para comparar cambios, imágenes y estado |
| **por entidad** | agrupadas: qué versiones tiene cada una |

La última es la vista especial, y es la que responde la pregunta real de la
pantalla: *«¿a esta entidad le falta alguna?»*. Por eso cada grupo lleva dentro
su propio botón de **+ Añadir versión**.

**Filtrar por entidad, con la cara.** Elegir entre cien nombres en un
desplegable no es elegir. Arriba hay una tira con la foto de cada entidad que ya
aplica alguna definición y cuántas lleva; un clic filtra. Y debajo dice cuántas
entidades activas **no tienen ninguna**, con enlace a la cobertura.

Los filtros pasaron de cuatro a ocho —búsqueda, definición, tipo, entidad, base
activa, cambios propios, imágenes y estado— más seis órdenes. «Por entidad»
ordena por el nombre de la entidad para que los grupos salgan enteros y no
partidos entre dos páginas.

---

## 6. Cobertura

Era una lista de barras de progreso: decía que a «Baryon» le faltaban 16
entidades y ahí se acababa. **Un número que no se puede tocar no sirve de
nada.**

Ahora cada fila se abre y enseña **por nombre y con foto** a quién le falta, con
un botón «Aplicársela» por entidad que lleva al aplicador en lote ya filtrado
por ese nombre. Y se mide de tres maneras, porque no todas las definiciones
significan lo mismo:

- **Por catálogo** — tiene reglas `ACTIVATES`: solo cuentan las entidades que
  las cumplen, y las reglas se listan al abrir la fila.
- **Alcance libre** — compartida y sin reglas: la puede aplicar cualquiera, así
  que la base es la biblioteca entera. Antes estas decían «no se puede calcular
  cobertura automática» y no se medían; ahora sí, diciendo por qué el
  porcentaje es bajo.
- **Exclusiva** — reservada para una entidad: medirla contra la biblioteca no
  significaría nada, así que no se mide.

Un detalle de honestidad: «sin huecos» y «no hay nadie que pueda llevarla» dan
los dos cero faltantes y son cosas **opuestas** —la primera es trabajo
terminado, la segunda una regla de catálogo que no encaja con nada—. Son estados
distintos, con texto distinto, y el segundo no cuenta como completo ni entra en
la media.

Arriba: cuántas definiciones, cuántas completas, cuántos huecos en total, la
media y cuántas entidades hay. Filtros por estado (con huecos / completas / por
catálogo / alcance libre) y cuatro órdenes.

---

## 7. Imágenes

Cada versión ocupaba una franja entera con sus fotos a 160 px: había que rodar
la página para ver seis. Y para cambiar el tipo de una imagen o hacerla portada
había que salir a la ficha de la versión, corregir y volver.

Ahora es un mosaico compacto con cuatro modos —**galería** (cada imagen suelta),
**cuadrícula** (una ficha por versión con sus miniaturas), **lista** (la tira de
cada una) y **tabla** (para localizar huecos: quién no tiene portada, quién no
tiene galería, qué tipos hay)— y control de tamaño de 2 a 6 columnas.

**Aquí no se navega, se corrige.** Pulsar una imagen abre un panel con sus tres
acciones: cambiar el tipo y el pie, **hacerla portada** y **borrarla**. Es un
único panel compartido que cambia de destino según la imagen pulsada — pintar un
formulario por cada miniatura sería multiplicar por veinte el mismo marcado. Los
tres endpoints ya existían en `EntityVersionController` y devuelven `back()`,
así que se vuelve exactamente aquí.

La portada no es una fila de la galería: es el campo `image` de la versión. Por
eso no se puede editar desde el panel y su baldosa lleva otro botón, que va a la
ficha.

Filtros: búsqueda, definición, tipo de entidad, **tipo de imagen** (retrato,
combate, apariencia…) y estado (con galería / solo portada / sin nada), más
cinco órdenes, entre ellos «las más vacías primero» para ir a rellenar huecos.

---

## 8. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/versions/partials/workspace-navigation.blade.php` | reescrita: cuatro palabras, sin explicaciones |
| `resources/views/versions/index.blade.php` | cabecera compacta; crear movido al final del mosaico |
| `resources/views/versions/workspace/entities.blade.php` | reescrita entera: 5 vistas, selector visual de entidad, 8 filtros |
| `resources/views/versions/workspace/coverage.blade.php` | reescrita entera: quién falta con foto, tres modos de medida |
| `resources/views/versions/workspace/media.blade.php` | reescrita entera: 4 vistas, tamaño, panel de corrección |
| `resources/views/versions/workspace/resolver.blade.php` | **eliminada** |
| `app/Http/Controllers/Versions/VersionWorkspaceController.php` | `entities()`, `coverage()` y `media()` reescritos; `resolver()` retirado |
| `routes/web.php` | ruta `versions.resolver` retirada |

---

## 9. Verificación

- Las **cuatro pantallas** responden 200, también con filtros aplicados
  (`?scope=SHARED&status=ACTIVE`, `?sort=entity&media=YES`,
  `?state=INCOMPLETE&sort=worst`, `?state=GALLERY&sort=images`).
- **Aplicadas** enseña las cuatro versiones reales, la tira de entidades con
  foto y cuenta («Naruto Uzumaki 3», «Shikamaru Nara 1»), el aviso de las 15
  entidades sin ninguna versión, y las cinco vistas renderizan.
- **Cobertura** calcula con datos reales: «Baryon · alcance libre · 1/17 · 6 %»
  y, al abrir la fila, los 16 nombres con su foto y su botón. «Shippuden» —cuyas
  reglas de catálogo no coinciden con nada— sale como «Ninguna entidad cumple
  sus reglas», no como completa.
- **Imágenes**: la galería enseña portadas y galería con su tipo, y el panel de
  corrección abre con el tipo correcto ya seleccionado («Alternativa»).
- La única superficie clara que queda en las cuatro es el desplegable del
  usuario de la cabecera, componente compartido que sale igual en todos los
  módulos oscuros. No se tocó porque cambiarlo afecta a seis módulos.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos fallos
  de SQLite que ya existían—.

---

# Anexo — La ficha de una definición

**Fecha:** 4 de septiembre de 2026

## 10. El problema

La ficha era una pantalla de **lectura**. Enseñaba las reglas de catálogo y las
entidades vinculadas, y para tocar cualquiera de las dos había que irse a otro
sitio: las reglas solo se podían editar desde el formulario completo —que las
borra todas y las vuelve a crear— y asociar una entidad significaba salir al
aplicador en lote. Seguía además en claro.

## 11. Qué se ve ahora

Toda la información del molde, en secciones que no esconden nada:

- **Qué es** — su cara, su clase y estado, su ámbito y su activación, y también
  **prioridad** y **orden**, que antes no se veían: cada uno con la frase que
  dice qué hace («cuando dos versiones encajan, gana la de número más alto»).
- **Las cifras** — cuántas la aplican, cuántas la tienen de base activa, cuántas
  le han cambiado características, cuántas tienen galería, y sus subversiones.
- **Dónde encaja** — la ruta de jerarquía completa desde la raíz, y las
  definiciones que cuelgan de esta con su cara y su uso.

## 12. Cuándo se activa sola

La parte que de verdad hace algo, y la que estaba peor contada.

La regla montada se lee ahora **en castellano**: «se activa cuando se cumple
cualquiera de estos 2 grupos», y dentro de cada grupo cada condición encadenada
con su `Y` o su `O`. Eso estaba escrito solo en el resolver: los grupos son
alternativas entre sí y dentro de cada uno el operador de cada enlace encadena
con el anterior.

Y cada valor lleva **cuántas entidades tuyas lo tienen**. Una regla que apunta a
una opción que nadie usa no activa nada nunca, y eso era invisible hasta que se
cuenta: el contador sale en rojo y un aviso lo dice.

### Editar las reglas sin salir

Tres endpoints nuevos, uno por operación, para no seguir borrando y recreando la
lista entera desde el formulario grande:

| ruta | qué hace |
|---|---|
| `POST versions/{version}/catalog-links` | añade una regla |
| `PATCH versions/{version}/catalog-links/{link}` | corrige tipo, grupo y operador |
| `DELETE versions/{version}/catalog-links/{link}` | la quita |

El formulario de añadir tiene selección en cascada —catálogo, luego sus
valores—, los tres tipos de relación (**activa la versión**, **contexto**,
**relacionada**) y los dos campos de regla compuesta (grupo y operador) con la
explicación al lado. Cada regla ya puesta se corrige desde su propia línea, que
se despliega.

Los `CONTEXT` y `RELATED` se enseñan aparte, diciendo lo que son: **no activan
nada**, son documentación. El motor solo mira los `ACTIVATES`.

`is_required` y la prioridad del enlace existen en la tabla pero el resolver no
los lee, así que no se ofrecen: enseñar un campo que no hace nada es peor que no
tenerlo.

## 13. Asociar entidades desde aquí

`POST versions/{version}/entities`.

El obstáculo era que una versión de entidad **necesita imagen sí o sí**, y pedir
una imagen es exactamente lo que obliga a irse al aplicador en lote. La solución
es copiar la de la propia entidad: es justo lo que se quiere de partida cuando la
versión todavía no tiene cara propia. Se dice en el mensaje de éxito, y se puede
cambiar después.

El selector enseña las entidades **con su foto**, con las que **cumplen las
reglas** del molde marcadas y puestas primero, botones de todas/ninguna y
contador. Si alguna entidad no tiene imagen propia que copiar, se rechaza el
envío **nombrándola** y se enlaza al aplicador en lote, que sí pide imagen.

## 14. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/versions/show.blade.php` | reescrita entera |
| `app/Http/Controllers/Versions/VersionController.php` | `show()` reescrito |
| `app/Http/Controllers/Versions/VersionCatalogLinkController.php` | **nuevo** |
| `app/Http/Controllers/Versions/VersionEntityLinkController.php` | **nuevo** |
| `routes/web.php` | cuatro rutas nuevas |

## 15. Verificación

Las cuatro fichas responden 200. Los endpoints nuevos se probaron **dentro de
una transacción revertida**, comprobando al final que los contadores volvían a
su valor de partida y borrando a mano las imágenes copiadas (el disco no entra
en la transacción):

- `store` crea la regla con su grupo y operador; **repetirla se rechaza** con el
  mensaje en castellano en vez de dejar reventar el UNIQUE de la tabla.
- `update` cambia tipo y grupo; `destroy` la quita.
- Asociar dos entidades crea sus dos versiones con la imagen copiada y existente
  en disco.
- Una entidad sin imagen se rechaza nombrándola.
- Al terminar: `enlaces=0 aplicadas=1`, igual que antes de empezar.

En el navegador: la selección en cascada puebla los valores del catálogo elegido
(«Clanes» → 52 valores), el selector de entidades marca las 15 candidatas con
«Todas» y el contador las sigue.

Un fallo encontrado y corregido en la verificación: el aviso de «esta regla no
puede cumplirse nunca» estaba condicionado a que el conteo de usos no fuera
vacío — pero una opción que **ninguna** entidad tiene no aparece en ese conteo,
así que el aviso se callaba justo en el caso peor.

---

# Anexo II — Las reglas, con imágenes

**Fecha:** 4 de septiembre de 2026

## 16. Lo que no iba

El editor de reglas del anexo anterior funcionaba, pero se manejaba con **dos
desplegables encadenados**: elegir «Clanes» y luego buscar «Uzumaki» entre
cincuenta y dos líneas de texto idénticas. Los catálogos y sus valores tienen
imagen —para eso se les puso— y no se veía ninguna.

Y los rasgos de la definición (ámbito, activación, prioridad, orden) colgaban
**debajo** de su imagen, en una columna estrecha que los dejaba en una lista
larga y fina mientras media pantalla quedaba vacía.

## 17. Los rasgos, al lado de la cara

La imagen a la izquierda y **todo lo demás a su derecha**: clase, ámbito,
activación, estado, prioridad y orden, cada uno como tarjeta con su icono, su
valor y la frase que dice qué hace («cuando dos versiones encajan a la vez, gana
la de número más alto»). Debajo, en la misma columna, las cifras, la descripción
y la jerarquía.

## 18. El selector de reglas, en tres pasos

Cada paso aparece solo cuando el anterior está resuelto:

1. **¿De qué catálogo?** — una tira con la imagen de cada catálogo y cuántos
   valores tiene. Los que no tienen ninguno salen apagados y no se pueden
   pulsar, con el motivo en el título.
2. **¿Qué valor?** — la cuadrícula de valores **con su imagen**, un filtro por
   texto para los catálogos largos, y en cada uno **cuántas entidades tuyas lo
   tienen**: en cian si alguna, en rojo si ninguna. Los que ya son regla de esta
   definición llevan la marca `YA`.
3. **¿Qué hace?** — el valor elegido con su cara al lado, el tipo de relación,
   el grupo y el operador, y **la frase resultante escrita antes de guardarla**:
   «Esta versión se activará cuando la entidad tenga Anime = «Naruto», y con eso
   basta». Si el valor no lo tiene nadie, avisa ahí mismo de que la regla no se
   cumplirá nunca.

Las reglas ya puestas también llevan ahora la **imagen del valor**, que es como
se reconocen: el nombre del catálogo va arriba en pequeño y el valor debajo en
grande, con su contador de uso.

## 19. Qué tienen en común las que la llevan

El revés de una regla. Una regla dice «actívate con esto»; esta sección dice
«las que ya la llevan tienen esto en común» —con la imagen de cada
característica y la proporción `2/2`—, que es justo de donde sale la regla que
habría que escribir.

Lo que comparten **todas** se marca en verde, y si todavía no es regla aparece
un botón **Convertir en regla** que la crea de un clic.

## 20. Un fallo encontrado verificando

El filtro de valores estaba escrito como `x-show="coincide(nombre)"`, llamando a
un método del componente. Alpine calculaba bien el resultado la primera vez pero
**no registraba `buscar` como dependencia del efecto**, así que escribir en el
filtro no volvía a evaluar nada: los cincuenta y dos clanes seguían visibles.
Se pasó a leer `buscar` dentro de la propia expresión, que es lo que Alpine
sigue.

## 21. Verificación

- Las cuatro fichas responden 200.
- El selector: la tira enseña «Clanes (52)» y «Anime (4)» pulsables y los veinte
  catálogos vacíos apagados; elegir «Anime» abre sus cuatro valores con su
  imagen y su contador (17 / 0 / 0 / 0); elegir «Naruto» abre el paso 3 con su
  cara y la frase completa.
- El filtro, ya corregido: 52 valores → 2 al escribir «uzu» («Inuzuka»,
  «Uzumaki») → 0 con «zzz» → 52 al limpiarlo.
- Los dos formularios —el del selector y el botón de un clic de «qué tienen en
  común»— envían la misma carga correcta: `attribute_id=10`,
  `attribute_option_id=20`, `relation_type=ACTIVATES`.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

---

# Anexo III — Crear una versión, y todas las de una entidad

**Fecha:** 4 de septiembre de 2026

## 22. Crear versión: lo que la pantalla no decía

El paso 1 ofrecía tres botones de texto del mismo tamaño: «usar existente»,
«nueva compartida», «nueva exclusiva». Nada advertía de que **dos de los tres
crean dos cosas**: la versión de la entidad y, además, una **definición nueva
que se queda en la biblioteca para siempre** y podrá aplicarse a otras
entidades.

Ahora las tres opciones son tarjetas con su esquema dibujado —el molde
repartiéndose entre varias entidades, o reservado a una— y las dos que crean
definición lo dicen en amarillo, dentro de la tarjeta y otra vez al abrirse el
bloque. Arriba, un explicador plegable dibuja la suma: entidad + molde =
versión.

**Elegir el molde se hace por la cara.** Antes era una lista; ahora es una
cuadrícula con la imagen de cada definición, su clase, cuántas entidades la
aplican, buscador, y las que esta entidad **ya tiene** aparecen marcadas y
deshabilitadas —el servicio las rechazaría igualmente, así que mejor decirlo
antes—.

**Crear el molde** tiene ahora su clase y su activación como tarjetas con la
frase que las distingue («solo automática: sale sola cuando el catálogo encaja;
no se ofrece a mano»), y su primera regla de catálogo se elige con el mismo
selector visual de la ficha de una definición: catálogo con imagen → valor con
imagen → la frase resultante.

## 23. Los ajustes finos, explicados con dibujos

«Padre concreto», «Prioridad», «Orden» y «Estado» eran cuatro etiquetas sueltas
sin decir qué hacía ninguna. Cada una es ahora una tarjeta con su esquema:

| campo | qué dice ahora, y su dibujo |
|---|---|
| **De qué versión cuelga** | un árbol: se hereda de la de arriba, no de la entidad |
| **Prioridad** | dos barras y un «gana»: cuando dos encajan a la vez, el número más alto |
| **Orden** | tres filas numeradas: solo coloca la ficha, no toca el motor |
| **Estado** | un interruptor: guardada pero fuera de juego |

Y las dos casillas que sí cambian el comportamiento —heredar características y
hacerla base activa— llevan también su esquema, en vez de una frase de seis
palabras.

La barra de guardar es pegajosa y dice en cada momento **qué se va a crear**:
«Se creará el molde *Modo Kurama* y *Naruto Uzumaki — Modo Kurama*».

## 24. Todas las versiones de una entidad

De dos maneras de mirar a **seis**, porque son seis preguntas distintas:
galería, cuadrícula, lista, tabla, **árbol** (cuál hereda de cuál, que ninguna
otra enseña) y **recorrido** (en qué orden se leen, con la base marcada). Con
control de tamaño en las dos primeras y memoria entre visitas.

## 25. Cambiar la base, explicado

«Cambiar base» era un botón junto a un nombre. La pregunta que nadie podía
responder mirándolo era la única que importa: **¿qué le pasa a lo demás si la
cambio?**

La respuesta —nada— está ahora dibujada: tres versiones intactas y una flecha
que se mueve. La base activa es solo la **cara** que el resto de la aplicación
enseña; no borra, no sustituye, no toca ninguna versión.

El modal elige por la cara, enseña **de dónde vienes y a dónde vas** antes de
confirmar, incluye la opción de volver a la entidad original, y el botón se
deshabilita solo diciendo «Ya es la que está puesta» cuando eliges la actual.

## 26. Comparar, explicado y con las diferencias marcadas

La pantalla ponía las columnas una al lado de otra pero no hacía lo único que
hace útil una comparación: **decir en qué se diferencian**. Todas las filas se
veían igual.

Ahora las que cambian se resaltan con un punto azul y las iguales se apagan, con
un interruptor de **«solo lo que cambia»**. Arriba se cuenta cuántas cambian de
verdad, y elegir qué versiones comparar se hace con una cuadrícula de caras en
vez de un desplegable. En el índice, un bloque plegable dibuja para qué sirve
antes de entrar.

## 27. Dos arreglos que no eran de diseño

- **El plugin Collapse de Alpine no estaba instalado.** `x-collapse` se usa en
  media aplicación —todos los bloques que se despliegan del taller de versiones,
  de la ficha de una definición, de este formulario— y la consola avisaba en
  cada carga: los bloques se abrían de golpe en vez de deslizarse. Instalado y
  registrado en `resources/js/app.js`.
- **Los errores de validación salían en inglés.** La aplicación corre con el
  locale en `en`, así que un fallo aparecía como «The version id field is
  required» en medio de una pantalla escrita entera en castellano. Cambiar el
  locale exigiría traducir la validación de toda la aplicación; en su lugar,
  `StoreEntityVersionRequest` lleva ahora sus propios mensajes, que además
  explican en vez de nombrar: «Elige el molde que se aplica, o crea uno nuevo».

## 28. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/entity-versions/form.blade.php` | marcado reescrito entero; el motor `entityVersionBuilder` intacto |
| `resources/views/entity-versions/index.blade.php` | reescrita: seis modos de vista |
| `resources/views/entity-versions/partials/version-branch.blade.php` | **nuevo**: una rama del árbol |
| `resources/views/entity-versions/compare.blade.php` | reescrita: diferencias marcadas, selector visual |
| `resources/views/entities/partials/base-version-manager.blade.php` | reescrita: explicación dibujada y modal visual |
| `app/Http/Controllers/Versions/EntityVersionController.php` | imágenes en el payload de catálogos; `entityType` en el índice |
| `app/Http/Requests/Versions/StoreEntityVersionRequest.php` | mensajes en castellano |
| `resources/js/app.js` | plugin Collapse de Alpine |

## 29. Verificación

- Las cinco pantallas responden 200: crear, editar, todas, comparar y ver.
- El formulario conserva **los 25 campos** del original —comprobado leyendo el
  `FormData` del formulario ya montado— y su motor de Alpine no se tocó.
- La validación real acepta las dos rutas del formulario (molde existente y
  molde compartido nuevo) y rechaza la incompleta con el mensaje en castellano.
- El selector de moldes marca «YA LA TIENE» y deshabilita las tres definiciones
  que Naruto Uzumaki ya aplica, dejando elegible solo «Shippuden».
- El modal de base activa actualiza el «quedaría» al elegir otra cara y habilita
  el botón; con la actual seleccionada dice «Ya es la que está puesta».
- La comparación calcula de verdad: con tres columnas y una sola característica
  común dice «Ninguna cambia. Estas versiones se distinguen por su cara y su
  nombre, no por sus datos».
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

**Nota sobre la verificación en el navegador:** el panel del navegador no
siempre pinta un fotograma entre acciones, y `x-show` de Alpine muestra a través
de `requestAnimationFrame` —así que un bloque puede quedarse en `display:none`
hasta que algo fuerza un repintado—. Eso hizo parecer, durante un rato, que el
formulario no reaccionaba. No es un fallo de la aplicación: forzando el pintado
con una captura, todo se despliega correctamente.

---

# Anexo IV — La ficha de una versión de entidad

**Fecha:** 4 de septiembre de 2026

## 30. Lo que la pantalla no enseñaba

Una versión de entidad es el punto donde se juntan las dos mitades del taller, y
la ficha no lo decía: enseñaba una imagen suelta con su nombre debajo. Pero una
versión **no existe sola**: sale de una ENTIDAD más un MOLDE.

Ahora lo primero que hay es esa suma, con las **tres caras** —la de la entidad,
la del molde y la que resulta—, y cada una lleva a su ficha.

## 31. Las cuatro secciones, con lo que hay dentro

Se mantienen porque responden cuatro preguntas distintas, pero ahora cada
pestaña dice **cuántas cosas tiene** antes de abrirla, y la elegida se recuerda.

### Resumen — qué se puede hacer con ella

Seis tarjetas, y cada una dice qué pasa **antes** de pulsarla:

| acción | qué avisa |
|---|---|
| **Base activa** | «La que lo sea ahora deja de serlo; ninguna se borra» |
| **Compararla** | pone sus características al lado de otras y marca las diferencias |
| **Una versión encima** | crea otra que hereda de *esta*, no de la entidad |
| **Presentación pública** | distinta de la base activa: es la que ven *los demás* |
| **El molde, a más entidades** | «no toca esta versión» |
| **Cómo hereda** | dice cuántas de sus características son propias y cuántas no |

### Características — de dónde sale cada valor

La distinción que más falta hacía: una característica **heredada** viene de la
entidad y cambiará cuando la entidad cambie; una **propia** solo existe aquí.
Cada tarjeta lo dice con color y con etiqueta, y hay un filtro de «solo las
propias».

Y los valores de catálogo se enseñan **con su cara**: un clan, una aldea o un
anime se reconocen antes por la imagen que por el nombre. Para eso el
controlador junta ahora cada característica con las opciones de las que sale
—`effectiveAttributes` devuelve el valor ya resuelto en texto, no el objeto de
catálogo—.

### Multimedia — dos cosas que se mezclaban

La **portada** es el campo `image` de la versión: es su cara, siempre hay una y
no se borra —se sustituye—. La **galería** son filas aparte, cada una con su
tipo, su pie y su texto alternativo, y cualquiera puede **ascender a portada**.
Se dice cuál es cuál, porque las acciones que admiten no son las mismas.

El motor de la galería conserva lo que tenía —lista, arrastrar y soltar, imagen
grande— y gana dos cosas que el marcado nuevo necesita: cuál se está
corrigiendo, y si el orden cambió, para no enseñar el botón de guardar cuando no
hay nada que guardar.

### Jerarquía — dos jerarquías, no una

La de las **versiones** (de quién hereda esta y quién hereda de ella, con las
caras) y la de los **moldes**, que es independiente. Antes se mezclaban.

## 32. La pantalla que abre «Características»

Tenía la decisión escondida en un desplegable de tres palabras —«Heredar»,
«Sobrescribir», «Ocultar»— que no dicen qué pasa. Y no enseñaba lo más
importante para decidir: **qué vale ahora mismo en la entidad**.

Ahora cada característica es una fila con el valor heredado **y su cara**
siempre a la vista, las tres decisiones como botones que dicen qué hacen
(«Igual que la entidad», «Cambiarla aquí», «Que no la tenga»), y el campo del
valor propio solo cuando hace falta. Elegir un valor de catálogo también se hace
por la cara, en cuadrícula, tanto si admite uno como si admite varios.

Arriba, un bloque plegable dibuja las tres decisiones —la flecha que pasa, la
flecha cortada, la tachadura— y un contador dice cuántas características cambia
esta versión de verdad, que es la cifra que la define.

## 33. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/entity-versions/show.blade.php` | reescrita entera |
| `resources/views/entity-versions/partials/trait-card.blade.php` | **nueva**: una característica con su origen y su cara |
| `resources/views/entity-versions/partials/media-manager.blade.php` | reescrita: portada y galería separadas |
| `resources/views/entity-versions/attributes/edit.blade.php` | reescrita: decisiones explicadas, valores con imagen |
| `resources/views/components/omni-multi-image-upload.blade.php` | prop `surface` para fondo oscuro |
| `app/Http/Controllers/Versions/EntityVersionController.php` | `show()` junta cada característica con sus opciones |

## 34. Verificación

- Las cinco pantallas responden 200 y ninguna deja fondo claro salvo el
  desplegable compartido de la cabecera.
- La ficha enseña la suma con las tres caras reales, las cuatro pestañas con su
  contador, las seis tarjetas de acción y la característica «Anime» con la
  imagen de su opción y la etiqueta **Heredada · de Entidad base**.
- La pestaña Multimedia distingue portada de galería y explica el vacío.
- El editor de características enseña «En Naruto Uzumaki: [imagen] Naruto»,
  cambia a «Cambiarla aquí» y despliega los cuatro valores de Anime con sus
  imágenes, avisando de que admite varios a la vez.
- El campo oculto `[mode]` sigue al botón elegido, así que lo que se envía es lo
  que se ve.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

Un detalle que se repitió y conviene no olvidar: **llamar a un método desde
`x-show` no registra como dependencia lo que ese método lee**, así que el filtro
se calculaba una vez y no volvía a evaluarse. Se escribe la condición dentro de
la propia expresión.

---

# Anexo V — Aplicar un molde en lote

**Fecha:** 4 de septiembre de 2026

## 35. Una carrera de obstáculos

La pantalla vieja era una lista plana de hasta doscientas fichas, un campo de
archivos «en masa» que emparejaba por nombre **sin decir qué había
emparejado**, y una regla dura escondida —cada versión necesita imagen— que
rechazaba el envío entero **al final**, cuando ya habías elegido cincuenta.

## 36. Se dice antes, no después

Un bloque plegable dibuja desde el principio que cada versión necesita una cara,
y las **tres maneras** de dársela: subir en masa por nombre de archivo, copiar
la de la entidad, o subirla una a una. Cada una con su esquema.

Y el botón de enviar **dice por qué no se puede enviar** —«13 de las 16 elegidas
siguen sin imagen»— en vez de dejar que lo diga el servidor cuando ya es tarde.

## 37. El emparejamiento, visible antes de enviar

Al elegir los archivos se calcula **en el navegador** el mismo emparejamiento
que hará el servidor, y se enseña: «4 archivos · 3 emparejados · 1 sin pareja»,
con el nombre del que se ha quedado fuera. Cada entidad elegida lleva encima su
estado —`✓ subida`, `✓ archivo`, `✦ entidad`, `✕ falta`— y una frase que dice de
dónde saldrá exactamente su cara.

Para que el cálculo sea fiel, los **slugs de las entidades vienen ya hechos
desde PHP** con `Str::slug`: lo único que se aproxima en el navegador es el del
nombre del archivo. Y se respeta el mismo orden de prioridad del servidor: la
imagen individual manda sobre la emparejada, y la emparejada sobre la copiada.

## 38. Copiar la imagen de la entidad

`use_entity_image[]`, nuevo. Era **el único motivo por el que este formulario
fallaba**: sin archivo para cada entidad seleccionada, el envío entero se
rechazaba. Ahora un botón resuelve las que falten copiando la cara de cada
entidad —que es justo lo que se quiere de partida— y el mensaje de éxito lo dice
para que nadie crea que subió algo.

## 39. Elegir a quién, de tres maneras

**Cuadrícula** con las caras y tamaño de 3 a 8 columnas, **lista** y **tabla**
—esta última enseña además cómo se va a llamar cada versión—. Con filtro en vivo
sobre lo cargado, y los botones *todas las visibles*, *ninguna*, *invertir* y
**«las N que encajan»**, que selecciona exactamente las entidades que cumplen las
reglas de catálogo del molde. Eso lo calcula ahora el controlador.

También se dice cuántas se han cargado de cuántas hay —el límite de 200
existía y no se mencionaba— y que para llegar a las demás hay que buscar en el
servidor.

## 40. Nombre y descripción por entidad

El campo de descripción ya lo aceptaba el backend y la pantalla no lo ofrecía.
Ahora cada entidad elegida se puede desplegar para ponerle nombre —con la
sugerencia como marcador— y descripción.

## 41. Dos trampas evitadas

- Los tres modos de vista **conviven en el DOM** (`x-show` oculta, no desmonta),
  así que un `name="entity_ids[]"` por modo enviaba cada entidad **tres veces**.
  La petición las deduplicaba, pero depender de eso es frágil: los ids se envían
  ahora una sola vez desde la selección.
- La lista de elegidas filtraba con `x-for`, así que al resolverse la imagen de
  una entidad su tarjeta **desaparecía del DOM y con ella el archivo recién
  subido**. Ahora se oculta, no se desmonta.

## 42. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/versions/bulk-entities.blade.php` | reescrita entera, con su motor |
| `app/Http/Controllers/Versions/BulkEntityVersionController.php` | quién encaja, copia de imagen, mensajes |
| `app/Http/Requests/Versions/BulkEntityVersionRequest.php` | `use_entity_image` y mensajes en castellano |

## 43. Verificación

- Las cuatro definiciones responden 200, con y sin filtro de búsqueda.
- Con las 16 entidades elegidas y cuatro nombres de archivo de prueba, la
  pantalla empareja tres («Kakashi Hatake», «Ino Yamanaka», «Kurama»), marca el
  cuarto como sin pareja, cuenta 13 sin imagen y **deshabilita el envío**. Al
  pulsar «copiar la imagen de cada entidad» las 16 quedan resueltas —el archivo
  emparejado gana sobre la copia— y el botón se habilita.
- Lo que se enviaría son **16 ids, sin duplicados**, y 16 `use_entity_image`.
- El backend, probado dentro de una transacción revertida: crea las tres
  versiones copiando la imagen de cada entidad y dejándola en disco, respeta el
  nombre y la descripción puestos a mano, rechaza con nombres propios las que se
  quedan sin ninguna fuente de imagen, y rechaza el envío vacío en castellano.
  Al terminar, `aplicadas=1`, igual que antes.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.
