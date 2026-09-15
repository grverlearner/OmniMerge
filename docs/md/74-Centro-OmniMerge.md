# El Centro — la puerta de entrada

## 1. Qué había, y qué mentía

La primera pantalla de toda la aplicación: un hero, cinco contadores, seis
tarjetas de módulo con un emoji cada una y una descripción genérica, y la lista
de lo último creado con un icono por tipo (`✦`, `☷`, `▤`, `🏆`).

Dos cosas eran directamente falsas:

- El saludo decía que desde aquí se puede entrar a la biblioteca y explorar la
  comunidad «y, **más adelante**, administrar universos, torneos y mucho más».
  Universos y Torneos llevan tiempo hechos y en uso.
- Una tarjeta entera anunciaba **«Rankings y analítica — Próximamente — Módulo
  en desarrollo»**. La clasificación de universo existe, tiene su panel, su
  sistema de puntos configurable y su desglose.

Una promesa caducada en la puerta de entrada es peor que no decir nada, porque
enseña una versión del producto que ya no existe.

## 2. El principio: un módulo se reconoce por lo que contiene

Las seis tarjetas iguales se convierten en **cuatro tarjetas con lo que hay
dentro de verdad**:

**Biblioteca** — una tira con las diez últimas entidades **con su cara**, cada
una enlazando a su ficha, y cinco cifras enlazadas: entidades, tipos, atributos,
valores de catálogo y colecciones.

**Universos** — las portadas de tus últimos cuatro mundos, cada una con sus
habitantes y un contador verde si algo se está jugando ahí, más cuatro cifras:
mundos, habitantes, partidas jugadas y en juego.

**Torneos** — las seis últimas plantillas con su imagen, y las dos cifras que
importan: plantillas de torneo y fases diseñadas.

**Comunidad** — las entidades que has hecho públicas, y tres cifras que antes se
sumaban en una sola («contenido público»): **lo tuyo público**, **traído de
otros** y **te han copiado**. Son tres cosas distintas y merecían tres números.

La tarjeta de «Rankings y analítica, próximamente» ya no está: la clasificación
existe y vive dentro de cada universo, que es donde tiene sentido.

## 3. Lo que espera por ti, en toda la cuenta

El bloque nuevo. Junta lo de los universos con la salud de la Biblioteca y los
torneos sin estrenar, y **cada punto dice de qué módulo viene**, con su color:

| Aviso | Módulo |
|---|---|
| Competiciones paradas esperando una decisión | Universos |
| Competiciones listas y sin empezar | Universos |
| Mundos vacíos | Universos |
| Entidades sin imagen | Biblioteca |
| Atributos de catálogo sin valores | Biblioteca |
| Plantillas de torneo que ningún universo ha adoptado | Torneos |

Son los mismos criterios que usan los paneles de dentro: si la puerta de entrada
dijera una cosa y el panel otra, uno de los dos estaría mintiendo. Y cada uno
lleva a donde se resuelve — un aviso que no se puede atender no es un aviso.

En los datos de prueba salen cinco: una competición parada, dos listas, un mundo
vacío, dos catálogos sin valores y cuatro plantillas sin estrenar.

## 4. Lo último que has hecho, con su cara

La lista de actividad dejaba de tener iconos genéricos por tipo y pasa a
enseñar **la imagen real de cada cosa**: una entidad se reconoce por su cara
antes que por la palabra «Entidad». El tipo se dice con el color del borde y una
etiqueta pequeña, que es suficiente para no confundir una colección con un
torneo. Mezcla entidades, atributos, colecciones, torneos, fases y universos.

## 5. Con la cuenta a cero

En el sitio del saludo con la promesa caducada hay ahora **cómo funciona
OmniMerge**: el recorrido dibujado —Entidades → Atributos → Universos → Torneos
→ Clasificación—, la nota de que también se puede empezar copiando de la
comunidad, y cuatro botones para entrar por donde se quiera.

## 6. Dos arreglos de navegación

**Universos y Torneos no estaban en el menú.** La barra del Centro tenía Centro,
Biblioteca, Comunidad e Inicio público: a dos módulos enteros solo se llegaba
por las tarjetas. Ahora están en la barra y en el menú móvil.

**Los emojis del menú desaparecen.** El desplegable de usuario usaba `🏠`, `📚`,
`🌐` y `👤`. Van al juego de iconos SVG de la aplicación, por la razón de
siempre: un emoji cambia de forma según el sistema operativo y no hereda el
color del texto, así que nunca combina con el resto. Comprobado después: **cero
emojis** en el texto renderizado de la página, para los dos usuarios.

## 7. Un error que costó un rato

El bloque de «atributos de catálogo sin valores» reventaba con
`Unknown column 'type'`. La tabla `attributes` no tiene columna `type`: tiene
**`data_type`**, y el valor que indica catálogo es `OPTION` —es lo que mira
`Attribute::usesCatalog()`—. Merece recordarlo porque `type` es el nombre que
uno escribe de memoria.

## 8. Lo que se comprobó

- El Centro devuelve 200 para dos usuarios reales con datos muy distintos, y las
  nueve pantallas que usan el layout siguen a 200 después de tocar la barra.
- Las cifras salen de la base de datos: 22 entidades, 4 tipos, 6 atributos, 13
  valores de catálogo, 1 colección, 12 plantillas de torneo, 25 fases, 3 mundos,
  31 habitantes, 22 competiciones, 10 en juego.
- Los cinco avisos coinciden con lo que hay: 1 competición parada, 2 listas, 1
  mundo vacío, 2 catálogos sin valores, 4 plantillas sin estrenar.
- «Lo último que has hecho» trae doce cosas de cuatro tipos distintos
  (Entidad, Colección, Torneo, Fase).
- La barra de navegación enseña Centro, Biblioteca, **Universos**, **Torneos**,
  Comunidad e Inicio público.
- El estado de cuenta a cero se renderizó a propósito con las cifras forzadas a
  cero —ningún usuario real lo está— y sale el recorrido completo con sus cinco
  pasos y los cuatro botones de entrada.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 9. Lo que queda

El Centro hace unas cuantas consultas de conteo, una por cifra. Con esta escala
es instantáneo; si algún día hay que apretarlo, el sitio es agrupar los conteos
de la Biblioteca en una sola consulta agregada en vez de seis `count()`.
