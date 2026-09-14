# Las competiciones de un universo

Una competición es una **edición jugada** de un torneo, dentro de una temporada.
El torneo es la receta; esto es la partida.

## 1. Qué había y qué faltaba

Una lista por fecha con un filtro de estado. No decía las tres cosas que uno
viene a saber:

- **Qué está esperando por mí.** Una competición parada a mitad, con una
  decisión pendiente o bloqueada, se veía **igual que cualquier otra fila**. No
  avanza sola, y nadie lo decía.
- **Qué está preparado y sin lanzar.** Configurar no es jugar, y un borrador se
  mezclaba con lo demás.
- **De qué torneo y de qué temporada sale.** Sin imágenes y sin etiqueta.

## 2. Lo que hace ahora

**«Te están esperando»**, lo primero de la página y solo cuando hay algo: las
competiciones cuyo motor se detuvo esperando una decisión o bloqueado, con su
cara, su temporada, su torneo, el motivo escrito y un botón de **Atender**.

**«Lo que se viene»**: las preparadas y sin empezar, con su play directo y el
recordatorio de que hasta que no se lanzan no ha pasado nada.

**Cada competición bien etiquetada**: la temporada como distintivo violeta
—`T1 · Temporada 1`— sobre la portada, el torneo del que sale como tarjeta
enlazada **con su imagen**, el juego que resuelve sus batallas, cuántos compiten,
cuándo empezó, y el campeón con su cara si ya terminó.

**Cinco formas de mirar**: cuadrícula con tamaño, lista, tabla, **por torneo** y
**por temporada**. Las dos últimas son las que contestan «¿qué ha pasado con
este torneo edición a edición?» y «¿qué se jugó en esta temporada?».

**Filtros y orden**: búsqueda por nombre o código, estado, torneo, temporada,
juego; y ordenar por más nuevas, más antiguas, por temporada, por más
competidores o por nombre. Las cifras de arriba son también filtros, y la que
está aplicada se marca con su color.

## 3. Lo que se añadió por detrás

`TournamentInstanceController::index()` acepta los filtros y el orden, trae el
campeón de cada competición terminada, y separa dos listas nuevas: las que
esperan una decisión y las preparadas sin lanzar.

`TournamentInstance` gana un accesor `image_url`. La columna `image` existía
desde el principio y no la leía nadie, así que una competición con portada se
enseñaba igual que una sin ella. Cuando no tiene, la pantalla cae en la del
torneo del que sale, que es de donde viene su identidad.

Piezas nuevas: `partials/tarjeta` y `partials/fila`, esta última compartida por
la lista y las dos agrupaciones —pintarla tres veces por separado es la forma
segura de que las tres dejen de parecerse—.

## 4. Decisiones que conviene recordar

**«En curso» incluye las pausadas.** Una competición pausada sigue siendo una
partida empezada, y separarlas en el filtro obligaría a mirar dos veces.

**Las agrupaciones agrupan la página, y lo dicen.** «Por torneo» y «por
temporada» reparten lo que hay en pantalla, no el universo entero.

**Una competición sin temporada se dice, no se esconde.** Sale como «sin
temporada» y, al agrupar, en su propio bloque explicando que se jugó fuera de
cualquier tramo del mundo.

## 5. Verificación

- Nueve direcciones responden 200: sin filtros, por cada estado, dos órdenes,
  una búsqueda sin resultados, y filtrando por un torneo y por una temporada
  concretos.
- Las agrupaciones cuadran con la base de datos: por torneo salen «Eliminación
  directa clasic» (2), «Torneo Eliminación Directa [2^3]» (2) y «Torneo triple»
  (1); por temporada, «Temporada 1» con las cinco. Es exactamente el reparto de
  las cinco competiciones del universo de prueba.
- **«Te están esperando» no salía, y con razón**: ninguna de las cinco está
  parada esperando nada. Para comprobar que aparece cuando hay de qué, se puso
  una en `AWAITING_DECISION` **dentro de una transacción que se deshizo**:
  salieron la sección, el motivo «Esperando una decisión», el botón «Atender» y
  la etiqueta «te espera» en su fila. Al deshacer, los estados volvieron a ser
  los de antes.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

La **ficha de una competición** (`universes/competitions/show`), su pantalla de
juego (`play`) y el formulario de crearla siguen en el diseño claro anterior.
