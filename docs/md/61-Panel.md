# El panel

Lo primero que se ve al entrar. Su trabajo no es enseñar todo lo que hay, sino
contestar tres cosas en este orden:

1. **¿De qué va mi biblioteca?** — con las caras, no con un número.
2. **¿Qué estaba haciendo?** — lo último tocado, con un clic para seguir.
3. **¿Qué le falta?** — lo que está a medias, dicho y enlazado a su sitio.

## 1. Qué había y qué faltaba

El controlador ya calculaba casi todo: cifras, siete comprobaciones de salud,
lo último de cada clase, el reparto por tipo, los catálogos más grandes y un
«continuar trabajando» que mezcla las seis clases por fecha. Bien pensado, y
con dos cosas por resolver:

- **La pantalla era clara y plana**, sin formas de mirar ni control de tamaño.
- **El buscador existía en el servidor y no estaba enchufado a nada.** La ruta
  `dashboard.search` devolvía hasta veinticuatro resultados con su imagen, su
  clase y su URL, y ninguna pantalla los pedía.

## 2. Lo que hace ahora

**El mosaico de la biblioteca** abre la página: hasta veinticuatro caras de lo
último tocado, a media opacidad, cada una enlazada a su ficha. Un panel que abre
con cifras no recuerda de qué va la biblioteca; abriendo con las caras, sí.

**El buscador, funcionando.** Escribe dos letras y baja la lista con la cara de
cada resultado, su subtítulo y una etiqueta de qué clase es. Se maneja con el
teclado —flechas para moverse, Enter para abrir, Escape para cerrar— y la tecla
`/` lo enfoca desde cualquier parte de la página.

**Seis atajos** a lo que se hace todos los días, cada uno con su color y una
línea diciendo para qué sirve: nueva entidad, nueva colección, nuevo atributo,
nuevo valor, taller de versiones y comunidad.

**Seis cifras enlazadas**, una por clase, en gris cuando están a cero.

**«Sigue donde lo dejaste»** con cuatro formas de mirar —galería, cuadrícula,
lista y tabla— y control de tamaño de 4 a 9 columnas, que se recuerda.

**«Lo que está a medias»** enseña solo las comprobaciones que encuentran algo,
con su número grande y un enlace a donde se arregla, y dice cuántas de las otras
salen limpias. Cuando no hay ninguna pendiente, la sección entera se pone en
verde y lo dice.

**«Lo que más te han copiado»**, con la cara de cada pieza y su contador, más un
enlace a tu propio perfil público.

**«Lo último de cada clase»** en un solo bloque con seis botones, en lugar de
seis listas apiladas.

**En la columna de al lado**: «hasta dónde llega» —cuántas cosas tienes
públicas, cuántas veces te han copiado y cuánto te has traído de otros—, el
reparto por tipo con barras, tus catálogos con su cara y su tamaño, y los
enlaces a los otros dos paneles.

**Y si la biblioteca está vacía**, nada de lo anterior: cuatro pasos numerados
diciendo por dónde se empieza, y el recordatorio de que en la comunidad se puede
copiar lo de otros en vez de empezar de cero.

## 3. Lo que se añadió por detrás

En `DashboardController::__invoke()`:

- `$mosaico`: hasta veinticuatro entidades con imagen, de las sesenta últimas
  tocadas.
- `$alcance`: cuántas entidades son públicas, la suma de `clones_count` de todo
  lo suyo —veces que le han copiado— y cuántas cosas tiene copiadas de otros.
  Son los tres números que dicen si la biblioteca vive sola o está conectada con
  la comunidad, y ninguno se veía.
- `$loMasCopiado`: sus seis entidades más copiadas.

## 4. Decisiones que conviene recordar

**Cada búsqueda lleva su número.** Si vuelve la respuesta de una búsqueda
anterior a la última tecleada, se descarta: sin eso la lista parpadea con
resultados viejos, que es el fallo clásico de un buscador con retardo.

**La tecla `/` se ignora si ya estás escribiendo.** En un campo de texto, en un
área de texto o en algo editable, la barra es una barra.

**Los avisos de salud con cero no son avisos.** Se enseñan solo los que
encuentran algo, y del resto se dice cuántos son. Siete tarjetas en gris no
informan de nada.

## 5. Verificación

- El panel responde 200 en los dos estados: con biblioteca (@grvervortex, 97
  cosas) y vacío (@magnuscarslen), y cada uno enseña lo suyo —el segundo, los
  cuatro pasos de arranque—.
- El buscador del servidor devuelve resultados reales: `?q=nar` da siete, entre
  ellos «Naruto Uzumaki» (entidad), «Rol narrativo» (atributo) y «Naruto»
  (catálogo), cada uno con su URL. Con una sola letra devuelve cero, como debe.
- La lógica de la pantalla se comprobó en el navegador con la respuesta fingida:
  con una letra no abre; con tres abre con sus tres filas; las flechas recorren
  0→1→2→0 y vuelven; Escape cierra. La fila elegida sale resaltada.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

`resources/views/dashboard.blade.php` —la página «You're logged in!» que trae
Breeze— sigue en el repositorio y no la usa nadie: la ruta `dashboard` apunta a
`dashboard.index`. No se ha tocado por no retirar andamiaje que no formaba parte
del encargo, pero se puede borrar sin consecuencias.
