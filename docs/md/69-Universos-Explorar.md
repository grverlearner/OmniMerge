# Explorar — el mapa del universo

## 1. Qué había

Una lista agrupada. Se elegía un atributo, el servidor recargaba la página y
devolvía secciones apiladas con las tarjetas dentro. Funcionaba, y no enseñaba
nada que no dijera ya la pantalla de Entidades: eran las mismas tarjetas, con
un encabezado distinto.

Además arrastraba dos cosas que convenía quitar:

- Un párrafo final que prometía que «la visualización avanzada llegará más
  adelante». Un aviso de obra que llevaba ahí desde agosto.
- Fondo claro, cuando el resto del workspace del universo ya es oscuro.

Y sobre todo: **agrupaba por un solo valor**. Si una entidad tenía dos aldeas,
`groupBy` la metía en un cubo con la etiqueta «Hoja, Arena», que no es ni Hoja
ni Arena. Justo el caso interesante era el que se perdía.

## 2. Qué es ahora

No una lista: un **mapa**. Cada entidad es una cara, y las caras se reparten en
cuadros según el criterio que se elija. Elegir «Tipo de entidad» forma los
cuadros de los tipos; elegir «aldea» los rehace por aldeas; elegir «¿tiene
título?» parte el mundo en dos. Todo el mundo se recoloca a la vez.

### El tamaño del cuadro es el tamaño del grupo

Un cuadro no mide lo mismo que otro: ocupa lo que pesa. En el Universo Anime,
«Personaje» se lleva 19 de 22 y por eso ocupa el ancho entero, mientras que
«Anime» y «Lugar», con una entidad cada uno, caben en una sexta parte. La forma
del reparto se ve antes de leer un número.

Las anchuras van por cuota: ≥45 % toma las seis columnas, ≥25 % cuatro, ≥12 %
tres, ≥5 % dos, y por debajo una.

### Los representativos

Dentro de cada cuadro, arriba, «los que más han hecho»: los tres primeros por
títulos, luego trofeos, luego competiciones jugadas. Detrás, todos los demás.

Con una condición: la franja solo aparece si el cuadro tiene cinco o más y
**alguien ha competido de verdad**. En un cuadro donde nadie ha jugado nada,
destacar a tres sería inventarse una jerarquía.

### El color dice a cuántos sitios perteneces

Cada valor tiene un color que sale de su propio nombre —un hash del texto a un
tono HSL—, así que «Hoja» es del mismo color en los cuatro modos y en todas las
visitas sin guardar nada en ninguna parte.

Y ahí está la respuesta al caso que el diseño anterior no sabía enseñar: cuando
una entidad está en **más de un cuadro**, su aro deja de ser un color y pasa a
ser un `conic-gradient` con todos sus colores repartidos en círculo. Sasuke
Uchiha, que consta en tres animes, sale en los tres cuadros con un aro de tres
colores y una insignia que dice «3».

La ausencia no recibe color: cualquier valor que empiece por «Sin…», «Todavía
no…» o «Aún no…» se pinta gris pizarra. Un «Sin título» en fucsia parecería un
logro.

## 3. Las cuatro formas de mirar

| Modo | Qué enseña |
|---|---|
| **Cuadros** | Un cuadro por valor, del tamaño de su población. El de entrada. |
| **Cruce** | Dos criterios a la vez en rejilla: de la Hoja, ¿cuáles han ganado algo? |
| **Compartidos** | Quién está en dos sitios y con quién los comparte. |
| **Todo junto** | El universo entero sin cuadros, teñido por grupo, en bandas de color. |

### El cruce

Filas de un criterio, columnas de otro, y en cada celda los que cumplen las dos
cosas. Las celdas vacías se quedan vacías **y se ven**: un hueco en la rejilla
es información —ahí no hay nadie— y rellenarlo sería mentir.

### Los puentes

Cada valor es un nodo en círculo, y entre los que comparten gente pasa una
curva por el centro cuyo **grosor** es cuántos comparten y cuyo **color va de
un valor al otro**. Debajo, una tarjeta por par con las caras.

Cuando el criterio de turno no comparte a nadie, no se deja en un «no hay
nada»: se dice por qué (los criterios de sí o no nunca comparten) y se ofrecen
los criterios que **sí** tienen gente en varios cuadros, con su cuenta.

## 4. Los criterios

Se puede repartir por dos familias:

**Propios de la entidad o de lo que ha hecho** — tipo, estado, y tres preguntas
que salen de datos que ya estaban guardados y nadie estaba mirando: ¿ha
competido?, ¿tiene título?, ¿tiene trofeo?

**Atributos copiados** — los que viajaron en el snapshot al importar la
entidad. No se consulta la Biblioteca: el universo tiene su propia copia.

### La cobertura se enseña antes de elegir

Cada criterio del panel lleva su barra: a cuántas entidades les consta ese
dato. «aldea» sale con 4/22 en ámbar, «Tipo de entidad» con 21/22. Elegir
«aldea» sin saber que va a dejar a dieciocho en «Sin dato» es elegir a ciegas.

Los que tienen un solo valor se marcan **«no reparte»**: formarían un cuadro
con todo el mundo dentro.

### Por qué el criterio de entrada no es el que más cubre

Porque los de sí o no cubren por definición al cien por cien. Puntuándolos
junto a los demás ganaban siempre, y el mapa de entrada quedaba en «con título
/ sin título», que no cuenta nada de este mundo.

Así que hay dos ligas: los **descriptivos** —tipo, estado, atributos: dicen qué
es cada entidad— van delante de las **preguntas**, y dentro de cada liga gana
el que más cubre y más reparte. En los dos universos con datos, el mapa de
entrada sale por «Tipo de entidad», que es lo que se espera.

## 5. Decisiones de construcción

### El reparto se hace en el navegador

El controlador prepara **una vez** el censo completo —cada entidad con su cara,
su tipo, sus atributos y lo que ha hecho compitiendo— y el navegador reagrupa
al vuelo. Pedirle al servidor un reparto nuevo por cada criterio mataría justo
la sensación que hace útil esta pantalla: que el mundo se recoloque delante de
los ojos.

Con veintidós entidades el censo pesa unos pocos kilobytes. Si un universo
creciera a miles, este es el sitio donde haría falta paginar o repartir en el
servidor.

### Ancho completo, pantalla completa a petición

El layout de universos gana una opción `bleed` que quita la columna de 1500 px:
un mapa no se lee, se recorre, y recortarlo le quita lo que lo hace útil. Pero
sigue **dentro del layout, con su sidebar**: la pantalla completa de verdad es
un botón que aprieta quien quiere mirar sin distracciones, y se sale de ella
igual de fácil.

### Buscar apaga, no borra

Lo que no coincide se atenúa y se queda en su sitio. Quitarlo cambiaría el
tamaño de los cuadros y la forma del reparto, que es justo lo que se está
mirando. Quien prefiera lo contrario tiene la casilla «al buscar, esconder el
resto» en el panel.

La búsqueda entra en el nombre, el tipo y **los valores de los atributos**:
buscar «hoja» encuentra a Mitsuki y a Sasuke Uchiha aunque ninguno se llame
así.

### La ficha no te saca del mapa

Pinchar una cara abre un panel lateral con su imagen grande, sus cifras y sus
atributos. Y cada atributo es un botón que **vuelve a repartir el mundo entero
por él**: desde una entidad se salta a «reparte a todos por aldea», que es la
forma natural de explorar, tirando del hilo de lo que acabas de ver.

### «Sin dato» no es «Otros»

El cuadro de los que no tienen el dato explica por qué, y el porqué cambia: un
atributo pudo no viajar al importar la entidad; el tipo, simplemente, nunca se
puso. Son dos frases distintas porque son dos situaciones distintas.

## 6. Una trampa que costó encontrar

El diagrama de puentes salía **vacío**, sin ningún error en consola.

La causa: dentro de un `<svg>`, los hijos están en el espacio de nombres de
SVG, así que un `<template>` ahí **no es** un `HTMLTemplateElement` y `x-for`
no tiene un `content` que clonar. Alpine no se queja, simplemente no dibuja
nada.

La salida fue armar el `<svg>` entero como cadena en un getter e inyectarlo con
`x-html`: al asignarlo al `innerHTML` de un `<div>`, el navegador usa el parser
de HTML, que sí sabe cambiar de espacio de nombres al entrar en `<svg>`. Los
nombres de los valores van escapados, porque salen de datos del usuario y ahí
se concatenan en marcado.

## 7. Lo que se comprobó

- Las doce URL de prueba devuelven 200 en los tres universos, incluida la del
  universo vacío y la de un `?criterio=` que no existe, que cae sin romperse al
  criterio por defecto.
- El reparto cuadra: por «Anime» en el universo 7 salen 16 + 2 + 1 + 1 = 20
  pertenencias sobre 18 entidades con el dato, más 4 sin dato. Los números no
  suman 22 **y es correcto**: Sasuke Uchiha cuenta tres veces porque está en
  tres cuadros.
- El modo «todo junto» dibuja 24 caras para 22 entidades, por la misma razón.
- El diagrama sale con 3 nodos y 3 puentes, y los tres pares llevan a la misma
  entidad compartida.
- El cruce «Tipo de entidad × ¿Tiene título?» da 4 filas por 2 columnas, con 8
  en la primera celda.
- Clic real sobre una cara abre su ficha; clic en la cabecera de un cuadro pone
  el foco; buscar «naruto» deja 19 coincidencias sobre 22 y, con «esconder el
  resto», los cuadros se rehacen a 16 + 2 + 1 + 1.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 8. Lo que queda

Los criterios numéricos se tratan hoy como texto: un atributo de poder
repartiría en un cuadro por cada número distinto. Cuando haya datos numéricos
de verdad, el sitio natural es agrupar por tramos, y el censo ya viaja con el
valor numérico dentro del snapshot.
