# Los trofeos de un universo

Un trofeo es lo que se lleva quien gana. Se crea aquí, se engancha a la
recompensa de un torneo, y se entrega solo cuando una competición termina.

## 1. Qué había y qué faltaba

La lista de trofeos y las doce últimas entregas. Correcto, y sin las dos
preguntas que una vitrina existe para contestar:

- **Quién tiene más trofeos de este mundo.**
- **Quién ha ganado cada uno.**

Los datos estaban —trece entregas con su competidor, su competición, su posición
y su temporada— y la pantalla enseñaba un contador.

## 2. Lo que hace ahora

**Cinco formas de mirar**:

- **Vitrina** —la que abre—: los trofeos en grande, con su imagen recortada
  sobre un halo de su color, el nivel, el contador de entregas y las caras de
  quienes lo han ganado. **Los que no ha ganado nadie salen apagados y en gris**:
  no es lo mismo una copa conquistada que una recién hecha.
- **Cuadrícula**: cada trofeo con su palmarés debajo —posición, cara, competidor
  y en qué competición lo ganó—.
- **Quién tiene qué**: la vitrina personal de cada competidor, ordenada por
  cuántos tiene, con barras y todos sus trofeos desplegados.
- **Lo último conquistado**: las doce entregas más recientes leídas como frase
  —«Jura ganó Copa Mundial, 1º, en Mundial Clásico, T1»— con la imagen del
  trofeo y la cara del ganador.
- **Tabla** para comparar.

Con control de tamaño de 4 a 9 columnas en las dos primeras.

**Filtros y orden**: búsqueda, nivel, alcance (del universo o de una edición),
entregados o no, y ordenar por nombre, por más conquistados, por nivel o por más
nuevos. Las cifras de arriba también filtran.

**Crear un trofeo sin salir de la vitrina**, con **vista previa**: un trofeo es
una imagen antes que un registro, así que lo que se ve mientras se rellena es la
copa tal como quedará en la estantería, cambiando de color al cambiar de nivel.
Y el alcance se explica: del universo lo reparte cualquier torneo; de una
edición nace y muere con ella, que es como se inventa un premio de aniversario
sin ensuciar la vitrina permanente.

**Corregirlo desde su propia tarjeta**, sin ir a otra pantalla.

**Un aviso de los que nadie ha ganado**, con enlace a su filtro: crear un trofeo
no lo reparte.

## 3. Lo que se añadió por detrás

`UniverseTrophyController::index()` acepta filtros y orden, trae todas las
entregas del universo una sola vez —son pocas filas— y las reparte en memoria en
dos direcciones: por competidor (quién tiene qué) y por trofeo (quién lo ha
ganado). También las cifras y la lista de ediciones a las que se puede atar un
trofeo nuevo.

## 4. Decisiones que conviene recordar

**Borrar solo se ofrece cuando nadie lo ha ganado.** Retirar una copa que ya
está en la vitrina de alguien reescribe su historia, y el controlador lo impide
desde antes. Ofrecer un botón que va a fallar es peor que no ofrecerlo: en su
lugar pone «Conquistado».

**El palmarés se cuenta sobre la historia entera**, no sobre lo filtrado arriba,
y lo dice.

## 5. Verificación

- Doce direcciones responden 200, en dos universos: uno con trofeos entregados
  y otro con uno solo sin entregar, más los filtros de nivel, alcance,
  entregados y una búsqueda sin resultados.
- Las cifras y el palmarés cuadran con la base de datos: 3 trofeos, 13 entregas
  y 8 premiados distintos; y la lista de la vista «quién tiene qué» reproduce
  fila a fila el reparto real —Kakashi Hatake 3, Jura 3, Hiruzen Sarutobi 2, y
  cinco competidores con 1—.
- **Crear, corregir y borrar se probaron dentro de una transacción que se
  deshizo**: se creó un trofeo del universo, se le cambiaron nombre y nivel, se
  creó otro atado a una edición concreta y quedó guardado con ella, un nivel
  inventado («PLATINO») rebotó sin crear nada, y **borrar un trofeo ya
  conquistado se rechazó y el trofeo siguió existiendo**. Al deshacer, el
  universo volvió a tener su único trofeo.
- No se subió ninguna imagen en las pruebas, y se comprobó que no quedó ningún
  archivo nuevo en `storage/app/public/universes/trophies` —que es lo único que
  una transacción no puede deshacer—.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

Las entregas se crean desde el motor de recompensas al terminar una competición;
desde este panel no se entrega ni se retira un trofeo a mano, y no se ha añadido
esa posibilidad por no inventar una vía paralela a la que ya existe.
