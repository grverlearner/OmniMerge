# EL TORNEO OFICIAL DE UN UNIVERSO

Un **torneo** es una marca: «la Copa del Fuego». Una **competición** es lo que
ocurre en un momento: la Copa del Fuego de la temporada 4.

Lo que se configura en el torneo es **lo que todas sus ediciones heredan**
salvo que una diga otra cosa. Por eso el torneo decide y la competición
matiza.

---

## 1. La pantalla

Seis bloques plegables, uno abierto cada vez. Son largos, y verlos todos a
la vez convierte una decisión en un muro.

Cada bloque lleva en su pastilla del índice un **resumen de lo que hay
dentro** —«4 por batalla · al mejor de 5», «2 de 9 competidores»— así que
plegado sigue diciendo algo. Es la única forma de que plegar no signifique
esconder.

| | Bloque | Qué decide |
|---|---|---|
| 01 | Identidad | Nombre, descripción, cara, contexto, forma por defecto |
| 02 | El juego | Uno fijo para siempre, o uno por edición |
| 03 | La batalla | Cuántos caben, cuántos juegos, y **quién gana** |
| 04 | Temporadas | Cada cuánto aparece |
| 05 | Trofeo y premios | Qué se lleva quien gana |
| 06 | Quién compite | Reglas por atributos de los competidores |

Crear y editar comparten el mismo diseñador. Dos formularios para la misma
entidad acaban ofreciendo cosas distintas sin que nadie lo decida.

---

## 2. El juego se presenta entero

Elegir un juego por su nombre es elegir a ciegas. Al seleccionarlo se abre
lo que el propio motor declara: su descripción, **cómo se gana**, qué pasa
si hay empate, **qué mide de cada competidor**, sus reglas numeradas, y si
lleva anotaciones.

Eso último no es decoración: el bloque siguiente ofrece decidir por
anotaciones, y un juego que no las lleva no puede decidirse así. Cuando esa
combinación se elige, se avisa.

**Cuántos caben en una batalla sale del juego**, no de una lista fija: cada
motor declara su mínimo y su máximo. Ofrecer un número que el juego no
admite es ofrecer una batalla que no se puede jugar.

---

## 3. Marcador contra anotaciones

Esta era la parte que no estaba clara, y la razón de que el bloque lleve un
**ejemplo dibujado** en vez de una frase.

Una batalla produce **dos cuentas distintas**:

| | Qué cuenta |
|---|---|
| **Marcador** | Cuántos enfrentamientos ganó cada uno |
| **Anotaciones** | Cuántos puntos sumó en total dentro de ellos |

Y no siempre coinciden. Se puede ganar el marcador perdiendo las
anotaciones: ganar dos apretados y perder uno por goleada.

El ejemplo de la pantalla está elegido a propósito para que **los dos modos
den ganadores distintos** —con uno donde coinciden no se vería qué se está
eligiendo—:

```
juego 1   A gana 3–2
juego 2   B gana 0–3
juego 3   A gana 2–1

marcador     2–1  → gana A
anotaciones  5–6  → gana B
```

Al pulsar cada modo, la tarjeta correspondiente se enciende y el pie de la
tabla dice quién ganaría. Dos modos:

- **Manda el marcador** — gana quien se lleve más enfrentamientos; solo si
  empatan deciden las anotaciones.
- **Mandan las anotaciones** — solo cuenta el total, da igual el marcador.

El primero **ya es lo que hace el motor**: `MatchSeriesRuntime` decide por
juegos ganados y usa los puntos como desempate (`decided_on_points`). El
segundo se guarda y se explica, pero **el runtime todavía no lo aplica** —
ver «Qué falta».

---

## 4. Quién puede competir

Las reglas se escriben con los **atributos de los competidores**, y el
catálogo no se inventa: sale de las entidades que ya viven en ese universo.
Si nadie tiene «doujutsu», «doujutsu» no aparece. Ofrecer un filtro que no
puede casar con nadie es ofrecer un callejón sin salida.

Una regla tiene dos formas:

| Forma | Significa |
|---|---|
| Solo el atributo | Cualquiera que lo **tenga**, con el valor que sea |
| Con valores marcados | Solo los que lo tengan con **uno de esos** valores |

Y se combinan de dos maneras: **todas** (Y) o **al menos una** (O).

Se lee del `attribute_snapshot` de cada `UniverseEntity`, por **nombre** y no
por id: el snapshot ya guarda nombres, y así un torneo no se rompe porque
alguien reordene la Biblioteca. Los nombres viajan normalizados, para que
«Hoja» y «hoja» sean lo mismo.

**A la derecha, siempre, quiénes quedan dentro.** Sin verlo, elegir
atributos es escribir a ciegas: la diferencia entre 19 y 2 competidores no
se adivina leyendo el nombre de un filtro. La cuenta la hace el servidor —el
mismo servicio que la aplicará al montar la competición— así que la pantalla
y el torneo no pueden discrepar.

Si ninguna entidad cumple, se dice en rojo: ese torneo no podría celebrarse.

---

## 5. Temporadas

Tres modos —cada temporada, cada N, o a mano— y debajo **una línea de doce
temporadas dibujada** que enciende las que tocarían. Un intervalo es fácil
de escribir y difícil de imaginar: «cada 3 desde la 2» se entiende de golpe
al verlo.

La programación dice cuándo **tocaría**, no crea las ediciones. Crearlas
sigue siendo un acto deliberado, porque una temporada puede saltarse.

---

## 5bis. Trofeos y premios, sin salir de la pantalla

Ni un enlace que te saque de aquí. Estás en medio de configurar un torneo, y
mandarte a otra ventana te haría perder lo que llevas escrito.

Son dos cosas distintas, y por eso viajan por caminos distintos:

| | Qué es | Cómo viaja |
|---|---|---|
| **Trofeos** | Del universo, compartidos entre torneos | Se guardan solos, con `FormData` |
| **Recompensas** | De ESTE torneo | Dentro del formulario grande |

### Los trofeos que se ven son los de ESTE torneo

La pantalla no lista los trofeos del universo entero. Eso no es una
decisión, es un catálogo. Lista los que **este** torneo entrega, y el resto
queda detrás de un «elegir de los N del universo» que se abre a petición.

Un trofeo es de este torneo cuando **algún premio suyo lo otorga**: no hay
otra forma de que se entregue. Por eso la lista sale de las recompensas
—`tournamentTrophies`— y no de una selección aparte. Guardar dos sitios
donde decir lo mismo garantiza que acaben discrepando.

Consecuencia directa: elegir un trofeo del universo **crea el premio que lo
entrega**, y la pantalla lo dice antes de que ocurra.

### Por qué los trofeos van por su cuenta

Llevan imagen, y **un formulario dentro de otro no existe en HTML**. Así que
el taller del trofeo no es un `<form>`: es un bloque que se envía por su
cuenta y devuelve el trofeo guardado, que entra en la vitrina sin recargar.

Se añadió `update` al controlador de trofeos, que **no existía**: un trofeo
se creaba y se borraba, pero no se corregía. Y borrarlo está prohibido en
cuanto alguien lo ha ganado, así que un trofeo con la foto mal puesta se
quedaba así para siempre.

### Por qué las recompensas van dentro

Al **crear** un torneo todavía no hay fila a la que colgarlas. Un formulario
que funciona al editar pero no al crear obliga a guardar dos veces para
dejar algo completo, así que viajan como las reglas de participación: en el
mismo envío.

El servicio las borra y las reescribe en vez de casarlas una a una: no
tienen identidad propia —nadie enlaza a «el premio número 3»— y el orden en
que se escribieron **es** su orden. Los trofeos sí tienen identidad, y esos
no se tocan.

Una fila que no da **nada** —ni trofeo ni estadística— se descarta sola: es
una fila que se abrió y no se llegó a rellenar, no un premio que no otorga
nada.

### Lo que la pantalla resuelve por ti

- **El podio de golpe.** Un botón crea campeón, subcampeón y tercer puesto.
  Los tres primeros son lo que casi todo torneo tiene, y rellenar el mismo
  campo tres veces es trabajo que la pantalla puede ahorrarse.
- **Las estadísticas salen del juego elegido arriba.** Premiar una que el
  juego no lleva sería prometer algo que nadie puede cobrar.
- **Cada premio se lee en una frase.** Seis campos sueltos no dicen qué va a
  pasar; «Quien acabe en el puesto 1 recibe el trofeo «Copa Doujutsu» y suma
  2.5 en Rango mínimo» sí.
- **Un premio ya configurado se muestra plegado.** No necesita seis campos a
  la vista: necesita decir qué hace. La fila resumida lleva su trofeo,
  cuándo se otorga y qué da, con un «✎ editar» que despliega el detalle.
  Los recién creados nacen abiertos, porque están vacíos.

  Se pliega con `x-show`, nunca con `x-if`: los campos tienen que seguir
  viajando en el envío aunque nadie los esté mirando. Y al borrar un premio
  hay que recolocar los índices abiertos —los de encima se desplazan uno—,
  o borrar el primero deja abierto al que ocupa su sitio.

### La galería de participantes

El bloque «quién compite» tenía un contador y una lista estrecha al lado.
Un número no dice quiénes son, y eso es justo lo que hay que ver para saber
si el filtro está bien puesto.

Debajo de las reglas hay ahora una **galería**: la cara de cada competidor
en cuadrado, su nombre, y sus atributos como etiquetas. Las etiquetas que le
dejan entrar van **encendidas**, porque el número de los que pasan no dice
POR QUÉ pasan, y con dos reglas encima eso deja de ser evidente.

Un conmutador enseña a los que entran o a los que quedan fuera —estos en
gris—, y una barra fina dice qué proporción del universo compite.

### Por qué el filtrado se hace en la pantalla

Antes cada clic pedía la lista al servidor con 250 ms de espera. Marcar un
valor del catálogo y esperar no es «al toque».

Ahora la pantalla recibe el **plantel completo** —`roster()`, con los
atributos ya aplanados en las mismas claves con las que se escriben las
reglas— y filtra ella misma. Marcar un atributo tarda unos 2 ms: es
síncrono, ocurre en el mismo clic.

Eso duplica en JavaScript lo que hace `passes()` en el servidor, y la
duplicación es deliberada. El servidor **sigue siendo la autoridad**: se le
sigue preguntando, y si su recuento no coincidiera con el de la pantalla, se
dice en un aviso en vez de esconderlo.

### El fallo que borraba los premios

Abrir un torneo y pulsar guardar **borraba sus premios**. No fallaba nada
visible: se guardaba, volvía a la lista, y el premio ya no estaba.

La cadena era esta:

1. Un `<select>` con `x-model` se enlaza **antes** de que su `<template
   x-for>` haya insertado los `<option>`.
2. No encuentra el valor guardado, así que se queda en `""` — y `x-model`
   no vuelve a intentarlo, porque el valor enlazado no ha cambiado.
3. Al enviar, ese `""` viaja como si hubieras elegido «ninguno».
4. `rewardsPayload()` descarta las filas que no dan **ni trofeo ni
   estadística**, y esa fila ya no daba ninguna de las dos.

Es decir: la limpieza que evita guardar filas vacías estaba borrando filas
llenas, porque la pantalla se las entregaba vacías.

Afectaba a siete selects entre las dos pantallas: el trofeo y la estadística
de cada premio, la fase de un premio de edición, el juego de una fase y el
atributo de una regla de puerta.

**La solución: `x-keep-selected`**, una directiva de Alpine que reaplica el
valor sobre el DOM. Tiene dos disparadores y hacen falta los dos:

| | Cuándo | Qué caso cubre |
|---|---|---|
| un efecto | cambia el valor enlazado | el primer render |
| un `MutationObserver` | cambian las **opciones** | elegir otro juego repinta las estadísticas, y el valor volvería a perderse |

Solo toca el DOM: el estado de Alpine no se altera nunca, y si el valor
guardado no está entre las opciones el select se queda como está. No puede
inventar una selección que nadie hizo.

Medido sobre el torneo real: con el envío arreglado el premio sobrevive con
su trofeo y su estadística; con el envío de antes —los dos selects vacíos—
quedaban **0 premios**.

### Crear un trofeo no lo entregaba

«Trofeos de este torneo» sale de los premios que los otorgan, porque no hay
otra forma de que un trofeo se entregue. Crear uno lo metía en el catálogo y
lo dejaba fuera de esa lista: parecía que no se había guardado, cuando lo que
faltaba era el premio que lo da. Ahora crear un trofeo crea también ese
premio, igual que ya hacía elegir uno del catálogo.

### Dos tropiezos que costaron encontrar

**El `this` de un componente anidado.** `gameStats` era un getter que leía
`this.game` del padre. Alpine encadena scopes para **evaluar expresiones**,
pero no para el `this` de un método declarado en un `x-data` anidado: el
bloque no ofrecía ninguna estadística. Ahora es una propiedad que llena un
`x-effect` desde la vista, que sí llega al padre —y que además vuelve a
bajarlas al cambiar de juego—.

**El 405 al editar un trofeo.** La imagen obliga a enviar por POST con
`_method=PUT`, pero Laravel convierte ese POST en un PUT **antes** de
enrutar: una ruta declarada solo como POST respondía 405 justo a la petición
que la necesitaba. Ahora acepta las dos.

---

## 6. La ficha

Dark, y ordenada como una respuesta: portada con cifras · cómo se pelea ·
qué se gana · quién puede competir · las ediciones.

Las ediciones van abajo, cada una con su estado, su temporada, cuántos
compitieron y las caras de quienes jugaron.

---

## 7. Casos probados

| Caso | Resultado |
|---|---|
| Catálogo de atributos del universo | Sale de sus 21 entidades: Aldea(2), Anime(18) |
| Regla «tiene Aldea» | 2 de 21 |
| Regla «Aldea → Hoja» | 1 de 21 |
| ALL frente a ANY sobre dos atributos | 1 frente a 19 |
| Crear torneo con todo configurado | Guarda juego, batalla, decisión, temporadas y reglas |
| El filtro guardado se aplica | 1 de 9 · Mitsuki |
| Editar y vaciar las reglas | Vuelve a abierto |
| «Al mejor de» par | Rechazado con su motivo |
| El ejemplo de la batalla | marcador → A · anotaciones → B |
| Cuántos caben en una batalla | Sale del juego: 2–8 en Highest Number |
| Crear competición | **Hereda «al mejor de 7»** del torneo |
| Las tres pantallas | Oscuras, con sidebar, sin desbordamiento, consola limpia |
| Crear un trofeo con imagen, sin salir | Devuelve JSON y entra en la lista |
| Filtrar la galería por «anime → boruto» | 21 → 18 → 1 competidores, en ~2 ms y sin ir al servidor |
| Abrir un torneo y guardar sin tocar nada | El premio sobrevive con su trofeo y su estadística |
| El mismo envío sin el arreglo | 0 premios: es el fallo que se corrigió |
| Crear un trofeo desde el diseñador | Entra en el catálogo **y** en los que entrega el torneo |
| Editar un trofeo del torneo desde una edición | 422, y el nombre no cambia |
| Ver a los que quedan fuera | 20 fichas en gris |
| Elegir un trofeo del universo | Crea el premio que lo entrega; sale del selector |
| Premio plegado | 344 px abiertos → 52 px; sus 7 campos siguen en el formulario |
| Editarlo sin salir | Cambia nombre y categoría; **conserva la imagen** |
| Guardar 3 premios, uno vacío | Se guardan 2; el vacío se descarta solo |
| Vaciar los premios | Quedan 0 |
| Los campos de premio | Todos dentro del formulario del torneo |
| Las estadísticas premiables | Salen del juego: `min_value`, `max_value` |

Suite completa: 88 pasan / 99 fallan — idéntico al baseline anterior.

---

## 8. Qué falta

1. **`POINTS_ONLY` se guarda pero el runtime no lo aplica todavía.** El
   motor siempre decide por marcador con las anotaciones como desempate, que
   es el otro modo. Aplicarlo requiere que el formato viaje hasta
   `MatchSeriesRuntime`, y hoy `series_format` llega ahí construido por cada
   uno de los cinco motores de fase: hace falta el mismo tratamiento.
2. **`battle_participants` se guarda pero los motores no lo imponen.** Las
   fases siguen decidiendo el tamaño de sus enfrentamientos.
4. **Las reglas de participación no se aplican todavía al asignar
   competidores** en una competición: se guardan, se explican y se
   previsualizan, pero el selector de participantes sigue ofreciendo el
   universo entero.
