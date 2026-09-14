# Las temporadas de un universo

Una temporada es un tramo del tiempo del mundo. Los torneos dicen cada cuánto se
juegan y las competiciones se juegan dentro de una.

## 1. Qué había y qué faltaba

Una lista paginada de veinte, sin filtros, sin formas de mirar, y con dos
agujeros de fondo:

- **La recurrencia no se veía.** Al configurar un torneo se dice cada cuánto
  toca —cada temporada, cada tres, una sola vez— y eso se guardaba y
  desaparecía. `occursInSeason()` ya sabía contestarlo desde el principio; nadie
  se lo preguntaba en el índice.
- **Las temporadas se creaban de una en una.** Un mundo con historia necesita
  diez, y eran diez veces el mismo formulario.

## 2. Lo que hace ahora

### El calendario

La vista que abre el panel. Por cada temporada, **qué torneos tocan en ella**,
con su imagen. Y cuatro temporadas más allá de la última creada, en línea
discontinua: saber qué tocaría en la 11 es lo que dice si merece la pena
crearla.

Los torneos de recurrencia manual salen aparte, en «fuera del calendario»: no
tienen periodicidad, así que nunca se anuncian en una temporada concreta y
fingir que sí sería mentir.

### Crear varias de golpe

Cuatro decisiones, deliberadamente pocas, porque cada opción de más es una
pregunta que hay que contestar diez veces mentalmente antes de pulsar:

| | |
|---|---|
| **cuántas** | de 1 a 50, con atajos de 3, 5, 10 y 20 |
| **nombre** | un patrón con `{n}`, que se sustituye por el número de cada una |
| **fechas** | opcional: cuándo empieza la primera y cuánto dura cada una; la siguiente arranca donde terminó la anterior |
| **estado** | si la primera de la tanda queda en curso |

El número **no se pregunta**: sigue siendo correlativo al universo, que es lo
único que garantiza que la recurrencia de los torneos cuadre.

Al lado, una **vista previa en vivo** que calcula nombres y fechas con las
mismas reglas que aplica el servidor, para que lo que se ve sea lo que se va a
crear. Se dibujan ocho filas: con veinte, la vista previa deja de ser una vista
previa y pasa a ser otra lista.

### Seis formas de mirar

Calendario, línea de tiempo, cuadrícula, galería, lista y tabla, con tamaño de 4
a 9 columnas y la vista recordada.

### Lo demás

- **Filtros y orden**: búsqueda, estado, con o sin competiciones, y ordenar de
  la última a la primera, al revés, o por las más jugadas.
- **Acciones sin abrir la ficha**: activar, terminar y archivar, ofreciendo solo
  lo que tiene sentido en cada estado.
- **La que está en curso** ocupa su propio bloque arriba; si no hay ninguna, se
  avisa de que las competiciones nuevas no sabrán a qué tramo pertenecen.
- **Crear y editar** rehechos: tres secciones numeradas, una tarjeta de «así
  quedará» que se actualiza al escribir —con el color de su estado y la duración
  calculada—, el aviso de a qué temporada va a relevar si se marca en curso, y
  el de a cuántas competiciones afecta al editar.

## 3. Lo que se añadió por detrás

`UniverseSeasonService::createMany()` monta la tanda dentro de una transacción:
todas o ninguna. Encadena las fechas —cada temporada termina el día antes de que
empiece la siguiente— y respeta que solo una pueda estar en curso.

`UniverseSeasonController::storeMany()` valida el lote con mensajes en
castellano y lo enruta en `POST universes/{universe}/seasons/bulk`.

El índice acepta filtros y orden, cuenta competiciones y competiciones
terminadas por temporada, y arma el calendario preguntando `occursInSeason()`
por cada número, incluidas las cuatro proyectadas.

## 4. Decisiones que conviene recordar

**Las fechas no son un cronómetro.** Nada se activa ni se cierra solo al llegar
el día; son la referencia del mundo. El formulario lo dice para que nadie
espere lo contrario.

**Dos temporadas no comparten día.** En el lote, la anterior termina la víspera
de que empiece la siguiente.

**El aviso de fechas invertidas no bloquea.** Se puede guardar una temporada que
termina antes de empezar —hay mundos raros— pero se avisa de cómo quedará en el
calendario.

## 5. Verificación

- Cinco direcciones del índice responden 200, más crear y editar.
- El calendario se comprobó contra la base de datos: la única temporada del
  universo de prueba es la 1, y los tres torneos tocan en ella; en las
  proyectadas 2, 3 y 4 solo aparecen dos, porque el tercero es de una sola vez.
- **La creación en lote se probó dentro de una transacción que se deshizo**:
  diez temporadas trimestrales desde 2027-01-01 salieron numeradas de la 2 a la
  11, con fechas encadenadas y **sin solaparse** (01/01→31/03, 01/04→30/06…),
  con una sola en curso y relevando a la anterior. Sin fechas, tres temporadas
  con fecha nula. Y un lote de 51 rebotó sin crear nada. Al deshacer, el
  universo volvió a tener su única temporada y la misma activa.
- La vista previa del navegador coincide exactamente con lo que creó el
  servidor, tanto en meses como en semanas.
- La tarjeta de «así quedará» del formulario se actualiza al escribir: nombre,
  estado con su color, periodo y duración («3 meses»), y deja la duración vacía
  cuando el rango está invertido.
- Un fallo encontrado y corregido: el formulario compartido usaba `$season?->…`
  sin declarar la variable, y crear una temporada daba 500. `?->` sobre una
  variable inexistente no es lo mismo que sobre `null`.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

La **ficha de una temporada** (`universes/seasons/show`) sigue en el diseño
claro anterior.
