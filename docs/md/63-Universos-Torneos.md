# Los torneos de un universo

Un torneo de este panel es una **receta del mundo**: qué formato se juega, con
qué juego se resuelven las batallas, quién puede entrar y qué se lleva el que
gana. Lo que se juega de verdad son sus **ediciones**, que viven en
Competiciones.

## 1. Qué había y qué faltaba

Una lista paginada de veinte, sin filtros, sin orden y sin formas de mirar. Y
sin lo único que distingue un torneo vivo de una ficha guardada: **cuántas veces
se ha jugado y quién ganó**.

## 2. Lo que hace ahora

**Filtros y orden**: búsqueda, estado, juego, jugados o no, y ordenar por más
nuevos, más antiguos, más jugados o nombre. Con tamaño de página.

**Cinco formas de mirar**: cuadrícula con la ficha completa, galería de
portadas, lista, tabla comparativa y **campeones** —la especial—.

**La ficha de cada torneo** enseña su portada, el juego con el que se resuelve
—con el icono y el color de ese motor—, la plantilla de formato, cada cuánto se
repite, sus premios y modificadores, y **quién ganó**: la cara del campeón, con
su nombre.

**La vitrina de campeones** pone en fila quién ganó en cada torneo, con su cara.
Los que nunca se han jugado salen igualmente y dicen que nunca se lanzaron: su
hueco vacío es la información.

**«Sin jugar» es una cifra y un filtro.** Un torneo configurado y nunca lanzado
es trabajo a medias; el panel lo cuenta, lo avisa en rojo y deja filtrarlos.

**El explicador** de que torneo y competición no son lo mismo —la receta y la
cena—, con un dibujo de la plantilla, sus ediciones y el campeón, y la
aclaración de que cambiar la receta no toca lo ya jugado.

## 3. Lo que se añadió por detrás

`UniverseTournamentController::index()` acepta los filtros y el orden, cuenta
ediciones, premios y modificadores por torneo, y trae las **cinco últimas
ediciones** de cada uno con su ganador y su temporada. También la cifra de
torneos sin jugar y las definiciones de los juegos, para pintar cada tarjeta con
el acento de su motor.

## 4. Decisiones que conviene recordar

**El campeón sale de la última edición que terminó, no de la última a secas.**
Con una sola edición cargada, un torneo cuya edición más reciente está en
borrador decía «aún sin campeón» aunque tuviera historia: mentía por omisión.
Ahora se traen cinco y se busca la primera que llegó a tener un primer puesto; y
si no es la más reciente, **se dice de qué edición es** ese campeón.

**Un torneo puede no haber elegido juego.** En la biblioteca de prueba hay uno
con `game_key` vacío, y enseñar una cadena en blanco como nombre de juego es
peor que decirlo: sale como «Sin juego».

**El acento de cada tarjeta viene del motor que usa**, así que viaja como color
en `style`. Tailwind solo genera las clases que encuentra escritas enteras.

## 5. Verificación

- Seis direcciones responden 200: sin filtros, por estado, sin jugar nunca,
  jugados ordenados por más jugados, filtrado por juego, y una búsqueda sin
  resultados.
- Las cifras cuadran con la base de datos: 3 torneos, 0 sin jugar, 5 ediciones.
- La vitrina de campeones se comprobó fila a fila contra los participantes:
  «Eliminación directa clasic» → Kakashi Hatake, primero de su edición #47;
  «Torneo Eliminación Directa [2^3]» → Ino Yamanaka, y la pantalla añade que es
  **campeón de «Era de shinobis»**, porque la edición más reciente de ese torneo
  (#46) sigue en borrador y no tiene puestos; «Torneo triple» sigue diciendo
  «aún sin campeón», que es lo cierto.
- El torneo sin juego sale como «Sin juego» en la tarjeta y en la tabla.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

La **ficha de un torneo** (`universes/tournaments/show`) y sus pantallas de
premios, modificadores y edición siguen en el diseño claro anterior.
