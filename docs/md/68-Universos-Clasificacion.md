# La clasificación de un universo

Contextual por definición: la misma entidad de la biblioteca puede ser la número
uno aquí y la dieciocho en otro mundo. Se calcula sola con lo que se ha jugado.

## 1. Qué había y qué faltaba

Una tabla ordenada por puntos, filtrable por temporada, y el sistema de puntos.
Correcto, y dejando sin usar dos cosas que **el servicio ya sabía hacer desde
que se escribió** y que el panel nunca le pedía:

- **Clasificar por juego.** Quién manda en un motor concreto no tiene por qué
  ser quien manda en general.
- **Clasificar por torneo.** Quién domina una competición a lo largo de sus
  ediciones.

Y faltaba lo que convierte una tabla de puntos en algo que se entiende: **de
dónde salen esos puntos**.

## 2. Lo que hace ahora

**Cuatro formas de mirar**:

- **Podio** —la que abre—: los tres primeros en grande, escalonados, con su
  medalla, su cara, sus títulos, su desglose de ganados/empatados/perdidos y el
  porcentaje de victorias. Detrás, el resto en filas con una barra que compara
  cada puntuación con la del líder.
- **Tabla**: la clasificación completa con todas las columnas.
- **Tarjetas**: con caras, y tamaño de 4 a 9 columnas.
- **De dónde salen los puntos**: la vista nueva. La barra de cada competidor se
  parte en los cinco conceptos que suman, con el mismo color que tienen en el
  sistema de puntos, y debajo la cuenta escrita —«Victorias 46×2 = 92»—.

**Filtros**: búsqueda, temporada, **juego**, **torneo** y cinco órdenes.

**El sistema de puntos, con un simulador al lado.** Cinco números no dicen nada
por sí solos: lo que importa es qué clase de mundo describen juntos. El panel
calcula, con los valores que se están tecleando, cuántos puntos sacarían tres
trayectorias de ejemplo —el que gana la final, el regular y el que solo
participa— y marca cuál quedaría arriba. Es la forma de ver a quién premia el
sistema antes de guardarlo.

**Los últimos en ganar**, con caras, aparte de la clasificación: no es lo mismo
quién va primero que quién ganó lo último.

## 3. Decisiones que conviene recordar

**Ordenar no renumera.** Mirando por títulos o por porcentaje de victorias, el
número de posición sigue siendo el del ranking por puntos, y la pantalla lo
avisa. Renumerar al vuelo sería inventarse otra clasificación sin decirlo.

**El podio solo se dibuja ordenando por puntos.** Con otro orden, los tres
primeros de la lista no son el podio, así que se enseña solo la tabla y se
explica por qué.

**La búsqueda se hace sobre lo ya calculado.** Son pocas filas y filtrar en la
consulta cambiaría las posiciones: buscar un nombre no debería ascender a nadie.

## 4. Verificación

- Doce direcciones responden 200 en dos universos, con los cinco órdenes y los
  filtros de juego y torneo.
- La clasificación cuadra con el servicio: 22 clasificados en el universo de
  prueba, con Jura primero —126 puntos, 3 títulos, 46/3/19 y 67,6 %—.
- **El desglose de puntos cuadra fila a fila**: Jura suma 3×5 de títulos, 46×2
  de victorias, 3×1 de empates y 16×1 de participación, que son 15+92+3+16 =
  **126**, exactamente su total. Lo mismo comprobado con el segundo y el tercero.
- El simulador del sistema de puntos hace lo que promete: con el sistema actual
  gana «el regular» con 25; poniendo el título a 100 y la victoria a 1, pasa a
  ganar «el que gana la final» con 104; subiendo la victoria a 10, vuelve a
  ganar «el regular» con 126.
- El filtro por juego devuelve cero clasificados en el universo 7 **y es
  correcto**: sus diecisiete competiciones se jugaron todas con Rounded Number,
  así que filtrar por Highest Number no debe devolver a nadie.
- El aviso de «ordenar no renumera» aparece solo al ordenar por otra cosa, y el
  podio se sustituye por su explicación.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 5. La corrección del podio y el repaso de la franja de campeones

Después de la primera entrega salieron a la luz cuatro cosas al medir el panel
con el navegador a 1440 px.

### El podio se comía la pantalla

Las tres tarjetas se dibujaban con `aspect-square` dentro de una rejilla de tres
columnas a todo el ancho, y sin un ancho máximo. El resultado medido: cada cara
ocupaba **353×353 px**, cada tarjeta **506 px** de alto, y el podio entero
**1575 px** — más de una pantalla completa para decir tres nombres.

La regla que deja esto: **una imagen decorativa no debe escalar con el ancho de
su columna**. El bloque se acota (`mx-auto max-w-3xl`) y la cara pasa a una banda
de alto fijo (`h-32 sm:h-36`). Medido de nuevo: cara de **246×144**, tarjeta de
**248×285**, y la página del universo 6 baja a 1536 px en total, de modo que el
podio y las primeras filas de la tabla caben juntos sin desplazarse.

Los escalones se suavizan en consecuencia (`pt-0 / pt-5 / pt-9`), y las insignias
y la tipografía bajan un punto para acompañar al nuevo tamaño.

### «Los últimos en ganar» enseñaba una sola competición

El servicio pide los seis últimos participantes con resultado `CHAMPION`. Eso
funciona mientras cada competición tenga un campeón, pero un torneo que se queda
en fase de grupos marca campeón **a todos los que clasifican**: en el universo 7,
«SOLO GRUPOS TORNEO — edición 2» tiene ocho, así que las seis filas pedidas
salían todas de esa única competición y las demás no se veían nunca.

La franja promete competiciones recientes, así que ahora eso es lo que enseña: se
piden sesenta filas, se agrupan por competición y se toman las seis últimas. El
universo 7 pasa de enseñar **una** competición a enseñar **seis**, con diez
ganadores en total. Y cuando una competición tiene más de un ganador se dice por
qué, en vez de dejar creer que hubo seis finales distintas: «8 ganadores: no hubo
una final, se llevaron el título todos los que clasificaron».

Conviene anotar que el recuento de títulos de la clasificación usa el mismo
criterio (`outcome = CHAMPION`), así que el panel no se contradice consigo mismo:
en ese torneo ocho competidores suman un título cada uno, y eso es lo que la
tabla refleja.

### La franja ignoraba los filtros sin decirlo

`recentChampions()` no acepta temporada, juego ni torneo: siempre devuelve los
campeones de todo el universo. Con un filtro puesto arriba, la franja parecía
responder a ese filtro. Ahora, cuando hay filtros, lo dice: «Esto es de todo el
universo, sin los filtros que tienes puestos».

### Detalles menores del repaso

- La temporada venía cargada del servicio (`tournamentInstance.season`) y no se
  enseñaba en ninguna parte. Ahora cada competición lleva su `T{n}` y su fecha.
- Si la entidad ganadora ya no está en el universo, se dibujaba un enlace a `#`.
  Ahora se dibuja un `<span>` apagado que dice «Ya no está aquí»: un enlace que
  no lleva a ningún sitio es peor que ninguno.
- Los bloques de campeones pasan a fila —competición a la izquierda, ganadores a
  la derecha— porque apilados gastaban 195 px de alto para enseñar una sola cara.
  La sección baja de **1293 px a 884 px** con las mismas seis competiciones.

### Lo que se comprobó

- Las tres caras del podio miden 246×144 y las tarjetas 248×285 en las tres
  posiciones, a 1440 px de ancho.
- Las caras de los ganadores miden todas lo mismo (banda de 80 px), tanto en la
  competición de ocho ganadores como en las de uno.
- Las doce URL de prueba siguen devolviendo 200 en los dos universos.
- El aviso de «ordenar no renumera» aparece en los cinco órdenes menos el de
  puntos, y las posiciones no se renumeran: ordenando por nombre, la columna de
  posición sale 9, 2, 1, 4, 8, 3, 7, 5.
- El desglose vuelve a cuadrar en los dos universos: Kakashi 1×10 + 5×3 + 5×1 =
  **30**, y Jura 3×5 + 46×2 + 3×1 + 16×1 = **126**.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

### Una trampa que volvió a aparecer

Al medir, el bloque de la izquierda de cada competición salía a todo el ancho en
vez de a los 192 px de `sm:w-48`. No era un problema de CSS: **Tailwind escanea
los ficheros fuente**, y `sm:w-48` era una clase nueva que todavía no existía en
el CSS generado. Reconstruir los assets lo resolvió. Cada vez que se estrena una
clase hay que volver a compilar antes de medir nada.

## 6. Lo que queda

El servicio calcula la clasificación entera en cada carga. Con veintidós
competidores y mil partidas va sobrado; si un universo creciera mucho, este es
el sitio donde haría falta una proyección guardada en vez de un cálculo al
vuelo.
