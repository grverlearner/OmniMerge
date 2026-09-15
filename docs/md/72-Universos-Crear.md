# Crear un universo — dejar el mundo montado de una vez

## 1. Qué había

Cuatro campos: nombre, descripción, portada y estado. Fondo claro.

El problema no era el formulario, era lo que venía después. Un universo recién
creado **no funciona**: no tiene calendario, así que un torneo que se repite
«cada dos temporadas» no sabe si le toca; no tiene gente, así que no puede
jugarse nada; usa el sistema de puntos y el motor de fábrica sin que nadie los
haya mirado. El Resumen del mundo recién nacido se llenaba de avisos de cosas a
medias, y arreglarlos era un recorrido por cuatro pantallas más.

Todo eso ya existía —el servicio de temporadas en lote, los ajustes de puntos,
el juego por defecto, el importador de entidades—, suelto.

## 2. Lo que se decide ahora

Cinco bloques, y **ninguno es obligatorio**: un universo se sigue creando con su
nombre y nada más.

### Quién es este mundo

Nombre, descripción y portada con vista previa. El estado deja de ser un
desplegable y pasa a ser tres opciones que se explican, porque «Borrador» y
«Archivado» no significan lo mismo para todo el mundo y aquí deciden si el mundo
pedirá tu atención o no.

### Cómo se mide el tiempo aquí

Cuántas temporadas se crean ya (0, 1, 2, 4, 6, 12 o a mano hasta 24), cómo se
llaman con el patrón `{n}`, cuándo empieza la primera, cuánto dura cada una y si
la primera arranca en curso o queda planificada.

A la derecha, **cómo quedarán**: las seis primeras con su nombre y sus fechas ya
encadenadas.

Usa el mismo `UniverseSeasonService::createMany()` que el panel de Temporadas,
así que las reglas son idénticas.

### Qué premia este mundo

Los cinco valores del sistema de puntos, con tres arquetipos que los rellenan de
golpe —**manda el que gana finales**, **equilibrado**, **manda el constante**— y
el mismo simulador de tres trayectorias que usa el panel de Clasificación.

El simulador es lo que convierte cinco números en una decisión: con el arquetipo
de constancia gana «el regular» con 114 puntos; con el de títulos gana «el que
gana la final» con 113. Se ve antes de crear nada.

### Con qué se juega

Las tarjetas salen del `GameRegistry`, así que cuando haya más motores aparecen
aquí solos. Cada una dice su condición de victoria, desde cuántos competidores
funciona y si admite empates.

### Quiénes lo habitan

La Biblioteca entera con la cara delante, buscador, filtro por tipo y «elegir los
N que se ven». Se dice con todas las letras que **traer es copiar**: la copia
vive en este mundo y editar el original en la Biblioteca ya no la afecta.

### Y tres mundos ya pensados

Arriba del todo, tres plantillas que rellenan todo lo de abajo: **una liga
larga** (seis temporadas encadenadas, la primera en marcha, premia la
constancia), **una copa corta** (una temporada activa, el título por encima de
todo) y **un mundo de pruebas** (borrador, sin calendario). Solo rellenan
campos; después se puede cambiar cualquiera.

## 3. El resumen, a la derecha

No una descripción de lo que va a pasar: **la cosa**. El mundo dibujado tal y
como se verá en la estantería, con su portada, su estado, su nombre, las caras
elegidas de fondo y sus contadores.

Debajo, la lista de lo que quedará montado, con un ✓ por cada paso resuelto y,
lo importante, **qué pasa si no se hace**: «sin calendario — los torneos que se
repiten no sabrán cuándo les toca», «sin habitantes — un mundo vacío no puede
jugar nada todavía».

El botón de crear está desactivado mientras no haya nombre, y lo dice: «Ponle un
nombre primero».

## 4. Dos errores que salieron al medir

### La vista previa de fechas mentía por un día

Con fecha de inicio 1 de marzo, la previa escribía «28 feb». La causa:
`new Date('2026-03-01')` interpreta la cadena como medianoche **UTC**, y
`toLocaleDateString` la escribe en hora local, que aquí va por detrás. La
solución es armar la fecha por partes: `new Date(2026, 2, 1)`.

### Y luego mentía por otro día, en el otro extremo

Corregido lo anterior, la previa decía «01 mar → 01 may» donde el servidor crea
«01 mar → 30 abr». `UniverseSeasonService::createMany()` termina cada temporada
**el día antes** de que empiece la siguiente, porque dos temporadas no pueden
compartir un día. La previa ahora resta ese día y las dos coinciden exactamente:

| | Servidor | Vista previa |
|---|---|---|
| Era 1 | 01/01 → 28/02 | 01 ene → 28 feb |
| Era 2 | 01/03 → 30/04 | 01 mar → 30 abr |
| Era 3 | 01/05 → 30/06 | 01 may → 30 jun |

Una vista previa que no coincide con lo que se va a guardar es peor que no
tenerla, así que las reglas de las dos van juntas a propósito y con un comentario
que lo dice.

## 5. Editar, de paso

`universe-form.blade.php` lo comparten crear y editar, así que al pasarlo a
oscuro la pantalla de ajustes vino detrás. Editar **no** ofrece temporadas,
puntos, juego ni habitantes: eso ya tiene su propio panel dentro del universo, y
duplicarlo aquí sería una segunda forma de hacer lo mismo.

Lo que sí mejoró es el aviso de borrado. Antes decía «esta acción no se puede
deshacer», que no informa de nada. Ahora dice exactamente qué se pierde, con sus
números y bien concordado: «Se van con él 9 competidores, 1 temporada, 3 torneos
y 5 competiciones con toda su historia».

## 6. Lo que se comprobó

- Las tres pantallas (crear, editar, estanteria) devuelven 200.
- **El guardado monta el mundo entero**, comprobado ejecutando el `store()`
  dentro de una transacción que se deshizo: salió el universo `UNI000004` en
  estado ACTIVE, con tres temporadas «Era 1/2/3» encadenadas (01/01→28/02,
  01/03→30/04, 01/05→30/06) y la primera en curso, los cinco valores de puntos
  guardados exactamente como se enviaron, `HIGHEST_NUMBER` como juego por
  defecto y cuatro competidores importados. Al revertir, la base de datos volvió
  exactamente a como estaba (3 universos, 31 entidades, 7 temporadas).
- La frase de confirmación se arma en el orden en que se lee bien: «Universo
  creado con sus 3 primeras temporadas y 4 competidores dentro. Se juega a
  Highest Number».
- Los tres arquetipos rellenan lo que prometen y el simulador cambia de líder al
  cambiarlos.
- Elegir los 22 de la Biblioteca actualiza el contador, las caras del preview y
  la lista de pasos.
- Los avisos de borrado concuerdan en los tres universos, incluido el vacío.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 7. Lo que queda

Los cinco pasos posteriores a crear el universo se ejecutan fuera de la
transacción que crea el universo, a propósito: que falle importar una entidad no
debe impedir que el mundo exista. La consecuencia es que un fallo a mitad deja el
mundo creado y algún paso sin hacer — y eso el Resumen lo detecta y lo dice, que
es justo para lo que está.
