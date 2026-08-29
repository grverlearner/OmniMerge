# Los competidores de un Universo

Una entidad del Universo es una **copia**, no un enlace. Esa decisión es
vieja y es buena: un torneo no puede depender de datos que se cambian por
fuera, porque entonces renombrar un atributo alteraría una competición
terminada hace un año.

El precio era que la copia se quedaba congelada para siempre, y que no había
forma de mirar lo que había. Este trabajo paga ese precio sin renunciar a la
decisión.

---

## 1. El panel

Cinco formas de mirar lo mismo, porque no siempre se busca lo mismo:

| Modo | Qué enseña | Para qué |
|---|---|---|
| **Cuadrícula** | solo la cara | abarcar muchos de golpe |
| **Galería** | la cara, su récord y sus atributos | reconocerlos |
| **Lista** | una línea con su catálogo entero | leer por lo que uno es |
| **Tabla** | columnas alineadas | comparar cifras |
| **Ficha** | todo, versiones incluidas | estudiar a uno |

El tamaño se ajusta con `−` y `+`. Medido a 1280 px en cuadrícula: 7 columnas
a tamaño 1 (fichas de 128 px), 20 a tamaño 5 (40 px). La preferencia se
recuerda en el navegador, salvo que el enlace traiga `?view=` — un enlace
compartido lleva la intención de quien lo mandó.

### Filtrar por atributo, no solo por nombre

Buscar «sharingan» encuentra a quien lo lleva, no solo a quien se llama así:
la búsqueda mira nombre, código, tipo, **atributos, valores de catálogo y
nombres de versión**.

Y hay filtro explícito por catálogo: cada atributo dice cuántos lo llevan y
cada valor cuántos lo tienen, así que elegir a ciegas y descubrir después que
el filtro deja cero ya no pasa. Los filtros puestos se ven como etiquetas y
se quitan de una en una.

Las seis cifras de arriba —total, activos, retirados, con trofeos, con
versiones, sin jugar— **son también filtros**. Un número que solo se mira es
un número desperdiciado.

### Por qué se filtra en la pantalla

Los atributos viven en un JSON copiado. Consultarlo con `LIKE` encontraría
«hoja» dentro de «hojarasca», así que el filtro se aplica en memoria. Es un
universo, no un censo: traer unos cientos de entidades cuesta menos que
mantener un índice invertido.

El servidor sabe hacer lo mismo (`UniverseEntityBrowser`), y esa duplicación
tiene un motivo: un enlace con filtros llega ya filtrado sin depender de que
el JavaScript haya arrancado.

---

## 2. La ficha

Cinco pestañas: **quién es**, **juegos y stats**, **palmarés**, **rivales**,
**historial**. Por pestañas y no en una columna de tres metros porque son
cosas distintas y casi nunca se vienen a ver todas: se viene a mirar una.

- **Quién es** — sus atributos con cada valor del catálogo (y cada valor
  enlaza al panel filtrado por él), y sus versiones con imagen.
- **Juegos y stats** — un bloque por juego, con sus estadísticas y **de
  cuánto partió**: un número solo no dice si ha mejorado, y mejorar es justo
  lo que le hacen los premios de un torneo.
- **Rivales** — con una barra que reparte ganados, empatados y perdidos. Un
  8–4 y un 4–8 se leen igual en texto y al revés en color.
- **Historial** — agrupado por temporada, porque un Universo tiene tiempo
  propio y «la temporada 3» dice más que una fecha.

---

## 3. Traer de la Biblioteca

Dos pasos a propósito: primero **se ve qué cambiaría**, después se aplica.
Una actualización que puede retirar atributos no debería ocurrir de un clic a
ciegas.

El diff se calcula pidiéndole al importador la copia que **haría hoy** y
comparándola con la que hay. Reusarlo, en vez de reimplementar la lectura, es
lo que garantiza que sincronizar y volver a importar den lo mismo.

### Lo que nunca se toca

**Un atributo del que dependa un torneo real no se quita.** No es una
cortesía: las reglas de participación y el reparto por puertas se escribieron
con ese atributo, y borrarlo de los competidores dejaría un torneo cuyas
reglas ya no casan con nadie.

Cuentan dos sitios —la elegibilidad del torneo y el `start_rules` de la
edición—, y solo si esa entidad **participó de verdad**: una regla escrita en
un torneo que nadie ha jugado no ata nada.

Se marca en el diff, se explica quién lo usa, se conserva, y se dice en el
resumen aunque no haya cambiado nada más. Callarlo era lo peor de los dos
mundos: la Biblioteca los había perdido, aquí seguían, y el mensaje decía
«no había nada nuevo que traer» —cierto, y aun así engañoso—.

**El nombre y la imagen no se traen por defecto.** Dentro de un Universo se
renombra a propósito, y machacarlo en cada actualización convertiría traer
atributos en una sorpresa. Hay una casilla para pedirlo.

---

## 4. Las versiones, y con qué cara sale en cada torneo

Un personaje no es uno solo. Naruto tiene su versión de niño y su Sennin, y
cada una tiene su propia imagen. Un torneo de Shippuden tiene que enseñar la
de Shippuden.

### Qué se copia ahora

`version_snapshot` guardaba un nombre y una foto sueltos. Ahora guarda además
el **id** de la `EntityVersion`, su prioridad, si es la base, y **sus propios
atributos**.

Ese último campo es la pieza que faltaba: sin él una versión no se podía
elegir por lo que es.

### Cómo se elige

`UniverseEntityVersionResolver` la elige con el **mismo lenguaje** con el que
se eligen los competidores —atributos y valores de catálogo—. No es
casualidad: si un torneo se define como «los que llevan saga → shippuden», la
versión buena es exactamente la que también lo lleva.

El orden, de más fuerte a más débil:

1. la que casa con las reglas; si casan varias, la de más prioridad
2. la base activa ★
3. la marcada por defecto
4. ninguna, y entonces manda la copia de la entidad

Nunca devuelve una versión inexistente. Si nada casa, se enseña la de
siempre: quedarse sin cara por afinar un filtro sería peor.

### Dónde se aplica

En `TournamentParticipantResolver`, al montar la competición, y **se congela
con el resto**: si mañana cambias las versiones de la entidad, el torneo ya
jugado sigue enseñando la cara con la que se jugó. El estado guarda además
`version_from` —`MATCHED`, `BASE` o `ENTITY`— para poder explicar por qué
salió así.

Medido sobre un competidor con tres versiones:

| El torneo pide | Sale | Origen |
|---|---|---|
| `Anime = 20` | Naruto Modo Sennin | `MATCHED` |
| nada | Naruto Baryon (la base) | `BASE` |

Dos imágenes distintas del mismo competidor según el torneo.

---

## 5. Cara a cara

Dos pantallas en una, y cuál sale depende de si hay rival.

**Sin rival: el selector.** Entrar sin elegir a nadie es lo normal —se viene
aquí justamente a elegir—, y antes eso devolvía **404**: el rival se buscaba
con `findOrFail` sobre la query, así que sin ella buscaba el id 0. Ahora es
opcional, y sin él se ve la lista de candidatos con su cara, sus atributos y
**cuántas veces ya se cruzaron**, ordenada por eso mismo: contra quien hay
historia va primero. Con búsqueda por nombre, tipo o atributo, y dos modos
—galería y lista—.

**Con rival: la comparación.** El marcador directo primero y grande, porque
es lo único que de verdad enfrenta a estos dos; todo lo demás son sus
carreras por separado, y la pantalla lo dice cuando nunca se han visto.

Debajo, sus cifras **enfrentadas**, no listadas dos veces: cada fila marca
quién la gana, porque un 54% y un 51% puestos en columnas distintas se leen
como iguales. Y las barras crecen hacia dentro, así que se tocan en el
centro. Luego, sus capacidades juego a juego, y por último cada
enfrentamiento que jugaron, con quién ganó.

---

## 6. Comprobado

| Qué | Resultado |
|---|---|
| Buscar «hoja» | Encuentra a quien lo lleva como valor de catálogo |
| Filtrar `aldea = hoja` | 1 esperado, 1 mostrado, etiqueta «aldea: hoja» |
| Ordenar por victorias | Jura 22, Hiruzen 20, Kurama 20 |
| Solo con trofeos | 2 competidores, con su recuento |
| Los cinco modos a 1280 px | cuadrícula 71×71 · galería 226×310 · lista 929×66 · tabla 927×45 · ficha 304×196 |
| Los cinco tamaños | 7 → 20 columnas (128 px → 40 px) |
| La ficha, cinco pestañas | Una visible cada vez, sin desbordar |
| Diff de sincronización | Detecta añadidos, cambiados, retirados y versiones |
| Atributo usado por un torneo | Se marca «se conserva» y sobrevive a la sincronización |
| Versión por reglas del torneo | Dos caras distintas del mismo competidor |
| Cara a cara sin rival | 20 candidatos con imagen, 4 ya cruzados primero |
| Buscar «iruka» en el selector | Lo encuentra |
| La comparación | Marcador, carreras, dos juegos y los cruces — 6 de 6 imágenes cargadas |
| Ajustar stats | Formulario `PUT` en la propia ficha, con sus campos |
| Rivales | 4, todos con imagen y atributos, ordenables y buscables |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |

---

## 7. Cuatro fallos que costaron encontrar

**El catálogo llegaba vacío.** Se construye como mapa —clave ⇒ cuenta—
porque así se cuenta sin duplicar, pero un mapa llega a JavaScript como
objeto y la pantalla lo recorre como lista. Faltaba reindexar.

**Los tamaños grandes no hacían nada.** La escala de `grid-cols-*` de
Tailwind termina en 12: pedir 20 columnas daba 12 en silencio. De ahí arriba
hacen falta valores arbitrarios —`grid-cols-[repeat(20,minmax(0,1fr))]`—,
que Tailwind sí genera porque también los lee del código.

**«Ajustar sus stats» era un enlace, y no llevaba a ningún sitio.** La ruta
de stats es un `PUT` —un formulario que se envía—, no una página que se
visita: pulsarlo devolvía 405. El formulario existía y se perdió al
reescribir la ficha; ahora vive dentro de la pestaña de juegos, en oscuro.

**Una imagen con `src` vacío pedía la página entera.** El modal de
confirmación del layout monta su `<img :src="image">` con `x-show`, que
oculta pero no desmonta: mientras no hay nada que confirmar, `image` es null,
el `src` queda vacío y el navegador vuelve a pedir la URL actual. Una
petición duplicada en casi todas las pantallas. Con `x-if` no se monta hasta
que hay imagen.

---

## 8. Lo que queda fuera

- **La versión no cambia por fase.** Se resuelve una vez por competición, con
  las reglas del torneo. Enseñar una cara distinta en la fase de Konoha y
  otra en la de Akatsuki necesitaría reglas propias por fase, que hoy no
  existen: el resolutor ya las aceptaría tal cual.
- **La sincronización es de una en una.** No hay «poner al día todo el
  Universo»; con veinte entidades importadas del mismo sitio, eso se va a
  echar de menos.
