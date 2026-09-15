# El centro de Universos — qué pasa en todos tus mundos

## 1. Qué había

La primera pantalla del módulo: un hero, cinco contadores, los universos
recientes y —lo peor— un bloque de **hoja de ruta** que prometía «resultados y
rankings, cuando las competiciones puedan jugarse de verdad».

Las competiciones llevan jugándose desde hace tiempo. Ese bloque enseñaba una
versión del producto que ya no existe, en la primera pantalla que se ve al
entrar. Una promesa caducada es peor que no decir nada.

## 2. Para qué sirve ahora

Hay dos pantallas parecidas y conviene que cada una haga lo suyo:

- **Mis universos** es la estantería: sirve para **encontrar y comparar** tus
  mundos, con sus cinco formas de ver y sus filtros.
- **El centro** —esta— contesta otra pregunta: **qué está pasando ahora mismo en
  todos tus mundos a la vez, y dónde hace falta que entres**.

Todo lo que sale aquí es transversal. Una competición bloqueada en un universo y
otra sin premios en otro salen en la misma lista, cada una con su mundo al lado,
porque lo que se quiere saber es «dónde tengo algo parado», no «qué le pasa a
este universo concreto».

## 3. Lo que espera por ti, en todos los mundos

El bloque principal. Recorre cada universo con **los mismos criterios que su
propio Resumen** —si la portada del módulo dijese una cosa y el mundo otra, uno
de los dos estaría mintiendo— y junta el resultado:

- competiciones paradas esperando una decisión (marcadas «frena el juego» y
  primeras en la lista)
- competiciones listas y sin empezar
- competiciones terminadas sin repartir premios, **solo si hay premios que dar**
- mundos sin temporadas
- mundos vacíos

Cada punto lleva la cara de su universo delante y un botón que va justo a donde
se resuelve.

En los datos de prueba eso da cuatro avisos repartidos en tres mundos: una
competición parada en el Universo Anime, una lista y sin empezar en cada uno de
los dos mundos poblados, y el tercero completamente vacío.

## 4. El resto

**La portada** cambia de titular según lo que esté pasando: «Hay 10
competiciones en juego» si las hay, «N cosas te están esperando» si no se juega
nada pero hay trabajo parado, y «Tus mundos están al día» cuando no hay ni una
cosa ni la otra. Detrás, un mosaico con las caras de los habitantes de todos los
mundos.

**Ocho cifras** del conjunto, incluida una que no estaba en ninguna parte: los
**enfrentamientos jugados en total** (1.096 en los datos de prueba).

**En juego ahora, en todos tus mundos** — cada competición viva con su universo
en la cabecera y su estado de **recorrido**, no solo el administrativo: «En
curso» y «Bloqueada» conviven, y la segunda es la que importa.

**El último año** — cuántas competiciones empezaron cada mes, sumando todos los
mundos. Las cifras de arriba dicen cuánto hay; esto dice cuándo pasó, que es lo
único que distingue un proyecto vivo de uno que se usó una temporada. Sale de
las competiciones ya cargadas, sin consulta nueva.

**Quién manda en cada mundo** — el número uno de cada clasificación, con su cara
y la de su universo. Es lo que hace interesante juntarlos: la clasificación es de
**cada** universo, así que el primero de uno puede ser el último de otro, y hasta
ahora eso no se veía en ninguna parte porque ninguna pantalla ponía dos mundos al
lado. Jura manda en el Universo Anime con 126 puntos y 3 títulos siendo 1º de 22;
Kakashi Hatake manda en la Franquicia Naruto con 30 y 1 título siendo 1º de 9.

**Sigue donde lo dejaste** — los mundos por la última vez que se movieron, no por
fecha de creación: al entrar al módulo lo que se quiere es volver a lo de ayer.

**Los últimos en ganar** y **Qué ha pasado**, los dos mezclando todos los mundos
y etiquetando cada línea con el suyo. El historial trae filtro por tipo que se
aplica en el sitio.

## 5. Cuando no hay ningún mundo

En el sitio de la hoja de ruta caducada hay ahora lo único útil ahí: **qué es un
universo**, con el recorrido dibujado en SVG —Biblioteca → Universo → Temporadas
→ Torneos → Competiciones → Clasificación—, la advertencia de que traer una
entidad es copiarla, y el botón de crear el primero.

## 6. Lo que se comprobó

- Las 36 pantallas del módulo devuelven 200, incluidas las tres del universo
  vacío.
- **Las cifras cuadran con la base de datos**: 3 mundos, 31 habitantes, 22
  competiciones (10 en juego, 10 terminadas), 1.096 enfrentamientos, y los 4
  avisos salen de 1 competición parada + 2 borradores + 1 mundo vacío.
- «Quién manda» coincide con lo que devuelve el servicio de clasificación para
  cada universo por separado.
- El filtro del historial pasa de 14 líneas a 5 («Terminan»), 8 («Empiezan») y 1
  («Llegan competidores») —que suman exactamente 14— y vuelve a 14 al quitarlo.
- El estado sin mundos se comprobó con un usuario real que no tiene ninguno: sale
  el titular correcto, el diagrama con sus seis pasos y el botón de crear, y
  **no** sale el bloque de avisos.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 7. Lo que queda

«Quién manda en cada mundo» calcula la clasificación de hasta seis universos, uno
por consulta. Con tres da igual; con cincuenta mundos habría que guardar el
número uno de cada uno en vez de recalcularlo al pintar la portada.
