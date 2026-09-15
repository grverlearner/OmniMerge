# Mis universos — la estantería de mundos

## 1. Qué había

Una rejilla de tarjetas con tres contadores (habitantes, temporadas, torneos),
un buscador, un filtro por estado y cuatro órdenes. Fondo claro.

Funcionaba, y no contestaba ninguna de las preguntas que uno se hace de verdad
al abrir esta pantalla:

- ¿cuál de mis mundos tiene algo pasando ahora mismo?
- ¿cuál está parado esperándome?
- ¿cuál está vivo y cuál tuvo un arranque fuerte y se quedó?
- ¿cuál de los tres es este, sin tener que leer el nombre?

## 2. Reconocer un mundo por su gente

Un universo se reconoce por sus habitantes antes que por su nombre. Por eso la
tarjeta grande lleva un **mosaico de sus caras de fondo**, su imagen encima, y
en la esquina **el último campeón** con su cara y el nombre de la competición
que ganó.

Las caras se traen en **una sola consulta** para todos los mundos de la página y
se reparten después: una consulta por tarjeta convertiría la estantería en
veinte viajes a la base de datos.

## 3. Las cinco formas de mirar

| Modo | Para qué |
|---|---|
| **Galería** | Cada mundo con su gente, su último campeón y lo que tiene pendiente. |
| **Cuadrícula** | Muchos mundos de un vistazo: solo la cara, si algo se juega y si algo está atascado. |
| **Lista** | Una línea por mundo, con sus caras apiladas y cuándo se movió. |
| **Tabla** | Todas las cifras exactas a la vez, para comparar números. |
| **Pulso** | Los mundos comparados de lado. |

Galería y cuadrícula llevan control de tamaño (2 a 6 columnas), y la elección de
modo y tamaño se recuerda entre visitas.

### El pulso

Es el modo que contesta lo que ninguno de los otros contesta: **cuál de tus
mundos está vivo**.

Cada mundo es una fila con una barra por temporada —lo que se jugó en cada una—
y, lo importante, **todas las barras de la pantalla están en la misma escala**.
Así se compara un mundo con otro y no solo consigo mismo: la nota de arriba dice
cuánto vale la barra más alta.

Un mundo con una barra alta al principio y nada después tuvo un arranque fuerte
y se quedó; uno con barras parejas se juega de verdad. Eso no lo dice ningún
contador.

Al lado, sus cifras y lo que tiene parado, con enlace directo a resolverlo.

## 4. Filtrar por lo que le PASA al mundo

El filtro por estado (en marcha, borrador, archivado) dice lo que un universo
**es**. Faltaba el que dice lo que le **pasa**, que es la pregunta real:

- **Con algo jugándose** — tiene competiciones vivas
- **Con algo atascado** — tiene ediciones esperando una decisión
- **Donde no se ha jugado nada** — existe pero nunca ha visto una competición
- **Vacíos, sin habitantes** — ni siquiera tiene gente

Y dos órdenes nuevos que hacían falta: **por movimiento** (el último que tocaste
primero, sacado de la fecha de la última actividad registrada) y **con más
partidas jugadas**.

## 5. Lo que cada mundo enseña sin entrar en él

Las tarjetas y las filas llevan el mismo criterio de atención que el Resumen de
cada universo, para que la estantería no diga una cosa y el mundo otra:

- **N atascadas** — competiciones paradas esperando una decisión, en rojo, con
  enlace a resolverlas
- **N sin empezar** — borradores listos, con enlace ya filtrado a `DRAFT`
- **N en juego** — con un punto que late
- **sin temporada en marcha** — cuando tiene temporadas pero ninguna activa
- **Vacío / Tiene gente pero aquí no se ha jugado nada** — dicho con esas
  palabras, porque son dos situaciones distintas

Más cinco atajos por tarjeta —mapa, competiciones, clasificación, trofeos,
historial— para entrar directamente a donde se quiere ir.

Las cifras de arriba son **del conjunto, no de lo filtrado**: son el índice, y
un índice que cambia con el filtro deja de ser un índice.

## 6. Una competición fuera del calendario

Al dibujar el pulso apareció algo que los datos ya tenían y nadie había mirado:
en el Universo Anime hay **una competición sin temporada asignada**. Diecisiete
competiciones, dieciséis en la primera temporada, y una suelta.

La primera versión la pintaba como «T0», lo que la colocaba antes de la primera
temporada y mentía sobre cuándo ocurrió. Ahora se dibuja con **trama rayada**,
se etiqueta **«suelta»**, va al final de la fila, y su descripción dice «Fuera
del calendario — 1 competición, sin temporada asignada».

No es un error que haya que arreglar aquí: es un dato real que ahora se ve.

## 7. Lo que se comprobó

- Las trece URL de prueba devuelven 200, y las treinta rutas de universo siguen
  a 200 después del cambio.
- Cada filtro devuelve exactamente lo que dice la base de datos: `status=ACTIVE`
  dos mundos, `status=DRAFT` uno, `con=jugando` dos, `con=atascados` uno (solo
  el Universo Anime tiene una edición bloqueada), `con=sin_jugar` uno,
  `con=vacios` uno, `search=anime` uno, `search=zzzz` ninguno.
- Los cinco modos se alternan correctamente: en una carga limpia hay
  exactamente una sección visible y las otras cuatro en `display: none`.
- Las clases de rejilla salen literales (`sm:grid-cols-2 xl:grid-cols-3`), nunca
  compuestas al vuelo, porque Tailwind lee los ficheros fuente y una clase
  construida en JavaScript no existiría en el CSS generado.
- El modo y el tamaño se guardan y se restauran entre cargas.
- Las barras del pulso cuadran: First Season 16, fuera del calendario 1,
  Temporada 1 del otro mundo 5.
- Se borró `partials/universe-card.blade.php`, que quedaba sin usar en todo el
  repositorio.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 8. Lo que queda

Las vistas de tabla y pulso trabajan sobre la página actual, no sobre todos los
universos. Con tres mundos da igual; si algún día hay cincuenta, comparar
debería mirar el conjunto y no la página, y eso pide una consulta agregada
aparte en vez de reaprovechar la paginada.
