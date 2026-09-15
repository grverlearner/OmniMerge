# El inicio público — lo que ve quien todavía no tiene cuenta

## 1. El problema: la página se vendía por debajo de lo que es

Toda la página hablaba de la biblioteca —crear entidades, describirlas,
organizarlas—, y al final una sección titulada **«Futuro»** anunciaba cuatro
cosas como próximas:

| Anunciado como «Próximamente» | Realidad |
|---|---|
| Universos | Hechos. Mundos con calendario, mapa, historial y trofeos. |
| Torneos | Hechos. Fases, recorridos, salidas y premios. |
| Rankings | Hechos. Clasificación con sistema de puntos configurable. |
| Simulaciones | A medias: los enfrentamientos se resuelven, pero con rangos, no con atributos. |

Tres de las cuatro llevaban tiempo en uso. El titular remataba: *«OmniMerge es
un espacio donde puedes crear personajes, países, criaturas…»* — la mitad del
producto.

Además llevaba **46 emojis y glifos sueltos** (`🍥`, `🐉`, `✦`, `☷`, `◈`, `▤`,
`◎`, `🌌`, `🏆`, `⚡`, `📊`…), que cambian de forma según el sistema operativo y
no heredan el color del texto. Y el mockup de la aplicación enseñaba una barra
lateral con Dashboard, Entidades, Atributos, Opciones, Colecciones y Comunidad:
ni Universos ni Torneos aparecían.

## 2. Una decisión deliberada sobre la privacidad

La tentación era llenar la página de contenido real: las entidades públicas de
la comunidad son imágenes bonitas y auténticas.

**No se hizo, a propósito.** La comunidad vive detrás del login
(`community.index` lleva middleware `auth`), así que marcar algo como «público»
hoy significa *«visible para quien tenga cuenta»*. Sacarlo a una página anónima
ampliaría esa exposición a todo internet sin habérselo preguntado a nadie.

Si algún día se quiere, la conversación es «¿quieres que lo público sea público
de verdad?», y esa no es una decisión de diseño de una landing.

Por eso el mockup del héroe lleva los nombres y las caras **en blanco**, y lo
dice debajo: «son de quien los crea».

## 3. Lo que cuenta ahora

**El titular** pasa a describir el arco entero: *«Crea lo que quieras. Dale un
mundo. Hazlo competir.»* Es lo que distingue esto de un gestor de fichas — las
fichas compiten.

**Cuatro módulos, arriba y como parte del producto** (no al final como promesa):
Biblioteca *lo que existe*, Universos *dónde viven*, Torneos *cómo compiten*,
Comunidad *con quién*. Cada uno con cuatro cosas concretas que hace.

**Cómo funciona** — el recorrido de cinco pasos dibujado, y debajo cada paso con
un ejemplo de verdad, porque «Aldea: Hoja · Arena · Niebla» dice más que «un
atributo de catálogo».

**Cómo se decide quién gana** — la sección que no existía y es la que decide si
esto le interesa a alguien. Explica el motor real con un diagrama: dos
competidores con sus rangos sobre el mismo eje, el número que saca cada uno, y
el veredicto. Y la idea de fondo: *un rango estrecho y alto es fiable, uno
amplio es impredecible* — eso es una decisión de diseño del competidor, no azar
puro. Al lado, las cuatro reglas: los empates se repiten, el formato lo pone el
torneo, y el registro de juegos está abierto a más motores.

**Comunidad** — con un diagrama de cómo viaja una copia: llega a tu biblioteca,
a partir de ahí son dos cosas distintas, y la tuya guarda el crédito de quien la
hizo primero.

**«Lo que todavía no está»** sustituye a «Futuro», y ahora es cierto: solo dos
puntos, la simulación por atributos y compartir torneos en la comunidad, cada
uno con el matiz de qué sí hay de eso.

## 4. Los emojis

Los 46 glifos sueltos se van al juego de iconos SVG de la aplicación
(`<x-omni-icon>`). Queda **uno**: la flecha de `«Grupos → los dos primeros →
final»`, que es una flecha tipográfica dentro de una frase, no un icono
haciéndose pasar por otro.

El mockup del héroe enseña ahora la **navegación real de un universo** —Resumen,
Explorar, Entidades, Temporadas, Torneos, Competiciones, Clasificación— con la
clasificación abierta y un podio con sus puntos.

## 5. Lo que se comprobó

- La página devuelve 200 anónima y con sesión, y cambia los botones según
  corresponde: sin sesión ofrece «Empezar gratis» y «Crear cuenta»; con sesión,
  «Abrir OmniMerge» y «Mis universos», y ninguno de los de registro.
- **Cero apariciones** de «Próximamente», «más adelante», «En desarrollo»,
  «Módulo en desarrollo» y «Futuro» en el HTML servido.
- **Un solo glifo suelto** en todo el texto renderizado, y es la flecha de una
  frase.
- En móvil (375 px) no hay desbordamiento horizontal: `scrollWidth` es
  exactamente igual a `clientWidth`.
- La página baja de **6.542 a 4.862 píxeles** de alto contando lo mismo y algo
  más, porque desaparecen los bloques de relleno.
- Login y registro siguen a 200.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

La página es estática: no toca la base de datos. Cuando la comunidad deje de
estar detrás del login —si se decide que deba—, el sitio natural para enseñar
creaciones reales es entre «Qué puedes crear» y «Comunidad», y entonces sí
tendría sentido convertir la ruta en un controlador.
