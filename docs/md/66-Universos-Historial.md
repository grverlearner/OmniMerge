# El historial de un universo

«¿Qué ha pasado en este mundo?». Solo lectura: no toca el runtime ni el estado.

## 1. Qué había y qué faltaba

Una lista de las competiciones jugadas, con su campeón. Correcta y corta,
porque la historia de un mundo es más que su lista de partidas.

Y había algo mejor sin usar: **`universe_activities`, un registro de actividad
que ya se escribía solo** —temporadas que empiezan, competiciones que arrancan y
terminan, campeones coronados, competidores que llegan— y que **ninguna pantalla
miraba**. Esa es la crónica del mundo, y estaba escrita sin que nadie la leyera.

Tampoco se contestaba la pregunta que un historial debería contestar antes que
ninguna otra: **quién ha ganado qué**.

## 2. Lo que hace ahora

**La crónica** abre el panel: todo lo que ha ido pasando, **agrupado por día**,
con la cara de lo que menciona cada evento —el competidor, la competición o su
torneo—, el tipo escrito con su color, un enlace a la temporada y otro a la
competición. Se anotan solas.

**El salón de la fama**: quién ha ganado qué, ordenado por títulos, con barras,
caras y **el detalle de cada campeonato** —qué competición, en qué temporada y
en qué fecha—. Se cuenta sobre la historia entera, no sobre lo filtrado arriba,
y lo dice.

**El palmarés**: las competiciones jugadas, con portada, distintivo de
temporada, estado y la cara del campeón.

**Por temporada**: el mundo contado por tramos. Una temporada sin nada jugado
también sale: su hueco es parte de la historia.

**Tabla**: competición, torneo, temporada, campeón, cuántos compitieron, cuándo
empezó, cuándo terminó y estado.

**Filtros**: búsqueda, temporada, tipo de evento —con el recuento real de cada
tipo en este mundo— y orden de lo más reciente o desde el principio.

**Seis cifras**: jugadas, terminadas, campeones distintos, encuentros resueltos,
batallas decididas por un motor de juego, y eventos anotados.

## 3. Lo que se añadió por detrás

`UniverseHistoryController` pasa ahora la crónica (`UniverseActivity` con su
temporada, su competidor y su competición cargados), los tipos de evento que
existen de verdad en ese mundo, el salón de la fama agrupado por competidor con
sus títulos, y la historia repartida por temporadas.

## 4. Decisiones que conviene recordar

**Un borrador no es historia.** El palmarés sigue excluyendo las competiciones
que nunca arrancaron, y por eso su cifra de «jugadas» es menor que el total del
panel de competiciones. Es intencionado.

**La crónica se corta en 120 eventos** y lo dice, ofreciendo filtrar por
temporada o por tipo para llegar más atrás. Una lista infinita no es una
crónica.

**Los meses se escriben en la vista.** La aplicación corre con el idioma en
«en», así que `translatedFormat` ponía «22 de August de 2026» en medio de una
pantalla en castellano. Se escriben aquí en vez de cambiar el idioma de toda la
aplicación por una fecha.

## 5. Verificación

- Seis direcciones responden 200: sin filtros, por dos tipos de evento, en orden
  inverso, una búsqueda sin resultados y filtrado a una temporada.
- La crónica cuadra con la base de datos: 14 eventos —11 arranques, 2 finales y
  1 importación de competidores—, todos del 22 de agosto de 2026, agrupados en
  ese único día y ya escrito en castellano.
- El salón de la fama cuadra fila a fila: Naruto Uzumaki, Ino Yamanaka y Kakashi
  Hatake con un título cada uno, y cada título enlazado a la competición que
  ganó con su temporada y su fecha.
- «Por temporada» sale con Temporada 1 y sus cuatro competiciones jugadas, que
  son las cinco del universo menos el borrador.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

El registro de actividad solo anota cuatro clases de evento
(`COMPETITION_STARTED`, `COMPETITION_COMPLETED`, `SEASON_STARTED`,
`ENTITIES_IMPORTED`). `CHAMPION_CROWNED` está previsto en la pantalla y en el
grabador, pero todavía no lo escribe nadie: cuando se escriba, aparecerá en la
crónica sin tocar esta vista.
