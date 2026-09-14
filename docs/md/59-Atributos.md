# El índice de atributos

**Fecha:** 4 de septiembre de 2026
**Alcance:** el índice de atributos, sus filtros, sus modos de vista y las dos
cifras que señalan trabajo pendiente.

---

## 1. El problema

Un atributo de tipo catálogo **es** sus valores —«Clanes» no significa nada sin
Uzumaki, Uchiha y los otros cincuenta— y la pantalla no cargaba ninguno: se
distinguían por el nombre y por un número.

Y no decía nada de lo único que en esta pantalla está roto: **los catálogos sin
valores**, que no se pueden asignar a nada. En esta biblioteca son 20 de 23.

---

## 2. Cinco maneras de mirar

| modo | qué enseña |
|---|---|
| **galería** | la cara del atributo y su nombre |
| **valores** | las **caras de sus opciones** — la vista nueva |
| **cuadrícula** | la ficha con sus etiquetas y sus interruptores |
| **lista** | una línea por atributo, con sus primeros valores |
| **tabla** | para comparar tipo, uso e interruptores de un vistazo |

La de **valores** es la que faltaba: una cuadrícula de hasta ocho opciones por
atributo, con el nombre al pasar por encima y un «+N» que lleva a la ficha. Los
que no son catálogo dicen por qué están vacíos —«su valor se escribe a mano»— en
vez de parecer rotos, y los catálogos vacíos salen en rojo con el enlace para
llenarlos.

Con tamaño de 2 a 5 columnas y memoria entre visitas.

---

## 3. Dos cifras que señalan trabajo, y un aviso

- **Catálogos vacíos** — un catálogo sin valores sale en todas las listas y no
  hace nada. Además de la cifra hay un aviso en rojo arriba con su enlace.
- **Sin usar** — atributos que ninguna entidad tiene asignados.

Las seis cifras de la cabecera son **enlaces que filtran**, y hay una barra
apilada del **reparto por tipo** con su leyenda, también enlazada: «23
atributos» no dice nada; repartidos por tipo, sí.

---

## 4. Filtros

A los seis que había —búsqueda, tipo, estado, visibilidad, grupo, selección— se
suman tres:

- **usados o no** — `whereHas('entityAttributes')`
- **con o sin valores** — incluida la opción «catálogos vacíos»
- **destacados**

Y once órdenes, entre ellos por número de valores y por uso.

---

## 5. La ficha

Cada tarjeta enseña lo que hace falta saber sin abrirla: su tipo con su color,
cuántas entidades lo usan, cuántos valores tiene —en rojo si es un catálogo
vacío—, sus primeros valores con la cara, y los **cuatro interruptores que sí
cambian algo**: si admite varios, si es filtrable, comparable y buscable. Son
los que deciden si el atributo aparece en los filtros, en las comparaciones y en
las búsquedas de toda la aplicación, y no se veían en ninguna parte.

El color de cada atributo viaja en `style`, no en clases: una clase de Tailwind
compuesta a partir de la base de datos no existiría en el CSS.

---

## 6. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/attributes/index.blade.php` | reescrita: cinco modos, reparto, avisos |
| `resources/views/attributes/partials/library-card.blade.php` | **nueva**: la ficha con sus interruptores |
| `app/Http/Controllers/Attributes/AttributeController.php` | opciones cargadas, tres cifras, tres filtros, reparto |

---

## 7. Verificación

- El índice responde 200, y también con `filling=empty`, `usage=unused` y
  `data_type=OPTION&sort=catalog_desc`.
- Las cifras cuadran con lo que devuelve cada filtro: 23 atributos en total,
  **22 catálogos**, **22 sin usar** y **20 catálogos vacíos**, y cada página
  filtrada trae exactamente esas filas.
- Con datos reales, la vista de valores enseña «Anime» con sus cuatro carátulas
  y «Clanes» con sus imágenes y un «+44»; los veinte catálogos vacíos salen en
  rojo con «Añadirle valores →».
- Las imágenes de los atributos cargan (comprobado con `naturalWidth`).
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

**Un tropiezo del proceso, anotado para no repetirlo:** el primer script que
modificaba el controlador falló en una comprobación y, como escribe el archivo
al final, **no llegó a guardar nada**; el segundo sí dejó los filtros. El
resultado fue una pantalla que usaba `$usage` sin que existiera. Se detectó al
renderizar —500 en las cuatro rutas— y se reaplicó lo que faltaba.

---

# Anexo — Crear y editar un atributo

**Fecha:** 4 de septiembre de 2026

## 8. La decisión que mandaba, escondida en un desplegable

De qué **tipo** es un atributo cambia todo lo demás —qué campos tienen sentido,
cómo se rellenará después, si hace falta un catálogo de valores— y en la edición
ya no se puede cambiar. Aun así se elegía entre ocho palabras en un desplegable:
«DECIMAL» no dice cómo se verá el campo.

## 9. Ocho tarjetas, cada una con su dibujo

Cada tipo es ahora una tarjeta con **el esquema de cómo se rellenará**: el
desplegable con su flecha para catálogo, el interruptor para sí/no, la caja con
cursor para texto, el número con sus flechas, el calendario, la muestra de
color. Debajo, una frase de qué es y otra de para qué sirve.

## 10. «Así se rellenará»: el campo de verdad

No una descripción del campo, **el campo**: el desplegable, las etiquetas
múltiples con el color elegido, el interruptor encendido, el número con su
unidad al lado, la fecha, la muestra de color con su hexadecimal. Es lo que verá
quien rellene una entidad, y se actualiza al cambiar de tipo, al escribir el
nombre, al elegir color y al escribir la unidad.

## 11. El resto se pliega según el tipo

- **Admite varios valores** solo aparece en catálogo, y al cambiar de tipo se
  apaga solo: un número no puede tener dos valores.
- **Límites** aparece con su pareja de campos correcta —mínimo/máximo numérico y
  unidad para números, mínimo/máximo de caracteres para textos— y desaparece
  entero para sí/no, fecha, color y catálogo.
- **Texto de ejemplo dentro del campo** desaparece donde no se escribe nada.

## 12. Lo que faltaba explicar

**Dónde aparece.** Los cinco interruptores —filtrable, comparable, buscable,
visible, destacado— dicen ahora *dónde* actúan: «aparece como filtro en los
listados», «entra en la caja de búsqueda». Y se avisa de que filtrable y
comparable solo funcionan de verdad con catálogos, números y fechas.

**Los grupos.** Qué son —ordenan la ficha de una entidad— y que no pasa nada por
no usarlos. Si no hay ninguno, se dice y se enlaza a crearlos en vez de dejar un
hueco vacío.

**La jerarquía.** El explicador plegable de «nuevo» la sitúa donde está de
verdad: la tienen los **valores** de un catálogo —una aldea puede colgar de un
país—, no los atributos entre sí, y se monta al añadir los valores. Entre
atributos no hay jerarquía; lo que hay son grupos.

**Por qué el tipo está bloqueado.** Al editar, si el atributo ya tiene valores o
lo usa alguna entidad, un aviso lo dice con las cifras: cambiarlo dejaría esos
datos sin sentido.

## 13. Archivos

| archivo | qué cambió |
|---|---|
| `resources/views/attributes/partials/form.blade.php` | reescrito entero, con su motor |
| `resources/views/attributes/create.blade.php` | reescrita, con el explicador dibujado |
| `resources/views/attributes/edit.blade.php` | reescrita, con el aviso de tipo bloqueado y el borrado |
| `app/Http/Requests/Attributes/StoreAttributeRequest.php` | mensajes que faltaban |
| `app/Http/Requests/Attributes/UpdateAttributeRequest.php` | mensajes, que no tenía ninguno |

## 14. Verificación

- Crear y editar responden 200 —tanto un atributo con valores como uno vacío—.
- Cambiando de tipo en el navegador: al elegir «Número decimal» se resalta su
  tarjeta, la vista previa pasa a un campo numérico con **1,80** y la unidad
  **cm**, aparecen «texto de ejemplo» y la sección de límites, y desaparece
  «admite varios valores».
- Lo que se enviaría es completo y coherente: `data_type` sigue a la tarjeta
  elegida, `allows_multiple` vale 0 para los tipos que no lo admiten, y los
  cinco interruptores viajan con su pareja oculto+casilla.
- La validación real acepta los tres envíos típicos y deriva bien lo que el
  formulario no manda: catálogo múltiple → `value_source=CATALOG`,
  `display_style=MULTISELECT`; decimal → `FREE` + `NUMBER`; texto → `FREE` +
  `TEXTBOX`. Y rechaza en castellano el envío sin nombre, con tipo inventado y
  con color inválido.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

**Sobre la verificación en el navegador, ya confirmado del todo:** el panel del
navegador **no ejecuta `requestAnimationFrame`** —comprobado pidiendo uno y
esperando 1,5 s sin que llegue—. Alpine oculta con `x-show` a través de rAF, así
que ahí un bloque puede quedarse visible después de que su condición pase a
falsa. No es un fallo de la aplicación: los bloques con `x-collapse` sí se
ocultan porque el plugin trae su propia rutina, y en un navegador real rAF
dispara sesenta veces por segundo y todo se comporta igual.

---

# Anexo A — La ficha de un atributo

## A.1 Qué había y qué faltaba

La ficha ya enseñaba la cabecera, unas cifras, los interruptores y el catálogo
de valores con su explorador de tres vistas. Todo correcto, y todo mudo sobre
las dos preguntas que uno se hace de verdad al abrir un atributo:

- **¿Esto lo usa alguien?** La respuesta era «17 entidades» sin enseñar ninguna.
  Un número no distingue un atributo vivo de un adorno.
- **¿Qué valores se usan?** La respuesta era una lista alfabética de cincuenta
  clanes, todos iguales, sin manera de saber cuáles sostienen la biblioteca y
  cuáles no ha tocado nadie nunca.

Y faltaba una vista: los valores de un catálogo **pueden colgar unos de otros**
—una aldea dentro de un país— y esa relación no se veía en ninguna de las tres
formas de mirar que había.

## A.2 Lo que hace ahora

**Así se rellena.** Debajo de la imagen del atributo se dibuja el campo real
que este atributo va a producir en la ficha de una entidad: un selector con las
caras de sus primeros valores si es catálogo múltiple, un desplegable si es de
uno solo, un interruptor si es un sí/no, un campo numérico con su unidad, un
selector de fecha, una muestra de color o una caja de texto con su texto de
ejemplo. Es la misma vista previa del formulario de creación, ahora también aquí:
lo que se configuró, visto tal como se usará.

**Quién lo usa.** Hasta dieciocho entidades con su cara, enlazadas a su ficha.
Si no lo usa nadie, se dice por qué: crear un atributo no se lo asigna a nadie,
eso se hace desde cada entidad o en lote.

**Los valores que se usan de verdad.** Los diez más usados, con una barra
proporcional y su contador. Solo aparecen los que tienen al menos un uso, y en
la cabecera del bloque se dice cuántos no usa nadie. En un catálogo de cincuenta
clanes, esto es lo único que permite distinguir el catálogo trabajado del
catálogo abandonado.

**Cinco formas de mirar el catálogo**, no tres. A galería, cuadrícula, lista y
tabla se suma **jerarquía**, que dibuja el árbol de valores con la sangría y el
contador de hijos de cada rama. La galería y la cuadrícula tienen control de
tamaño de 4 a 9 columnas, y la vista elegida se recuerda en el navegador.

**Añadir un valor sin salir de la ficha.** El formulario rápido gana la vista
previa de la imagen antes de subirla, el selector de qué valor es su padre
—que es como se monta la jerarquía—, su símbolo, su color y su descripción. El
botón queda deshabilitado mientras no haya nombre. Sigue existiendo el enlace a
la ficha completa del valor para lo que no cabe aquí.

## A.3 Decisiones que conviene recordar

**El árbol se monta con todos los valores activos, no con la página actual.** Un
árbol partido por la paginación no es un árbol: si el padre está en la página 1
y dos hijos en la página 3, lo que se dibuja miente. Por eso la vista de
jerarquía consume la colección completa de valores activos que ya se cargaba
para el selector de padre, y lo dice en pantalla para que nadie piense que el
filtro de arriba se le está aplicando.

**Los contadores no se maquillan.** Un valor con cero usos se pinta en gris, no
se esconde. Un catálogo vacío no enseña el explorador: enseña por qué está mal
—sin valores no se puede asignar a ninguna entidad, así que sale en las listas y
no hace nada—.

**Un atributo que no es catálogo lo dice y se calla.** No enseña un explorador
vacío ni un botón de añadir valores que no llevaría a ninguna parte: explica que
su valor se escribe a mano en cada entidad.

## A.4 Lo que se añadió por detrás

`AttributeController::show()` pasa seis datos nuevos: los diez valores más
usados con su cuenta, cuántos no usa nadie, cuántos cuelgan de otro, las
entidades que lo usan con su imagen, y la cuenta de usos en la lista de posibles
padres. Los tres primeros existen también cuando el atributo no es catálogo
—vacíos— para que la vista no tenga que preguntar dos veces por lo mismo.

La vista de jerarquía vive en `resources/views/attributes/partials/option-branch.blade.php`,
que se incluye a sí misma para bajar por los hijos. La colección entera viaja en
cada nivel a propósito: así el árbol se monta sin una consulta por nodo, que es
lo que pasaría recorriendo `->children` a pelo.

## A.5 Verificación

- Los tres casos responden 200: un catálogo con valores, un atributo que no es
  catálogo (booleano) y un catálogo vacío.
- Con el catálogo «Anime», que sí usan diecisiete entidades, salen las tres
  piezas nuevas: las caras de quién lo usa, el ranking de valores con su barra,
  y el aviso de cuántos valores no usa nadie.
- La jerarquía se comprobó colgando tres valores de un cuarto **dentro de una
  transacción que se deshizo al terminar**: el árbol dibujó la raíz a sangría
  cero y sus tres hijos a veinte píxeles, y al volver atrás la cuenta de valores
  con padre regresó a cero, igual que estaba.
- Lo que envía el formulario de añadir un valor lo acepta el request tal cual,
  con padre, símbolo, color y descripción incluidos; y rechaza en castellano el
  envío sin nombre y el que apunta a un padre que no existe.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

---

# Anexo B — La estructura

## B.1 Qué es esta pantalla

Se llega desde el botón **Estructura** del índice de atributos. Aquí se decide
cuándo un atributo aparece en la ficha de una entidad, cuándo es obligatorio, y
qué valores de un catálogo quedan disponibles según lo que se haya elegido en
otro. Es el motor que hace que la ficha de una entidad no sea siempre el mismo
formulario para todas.

Lo que había era correcto y prácticamente ilegible: tres pestañas de
desplegables de texto sobre fondo blanco, sin una sola imagen, y sin decir en
ningún sitio qué hacía cada cosa ni qué pasaba después de guardarla.

## B.2 Las tres cosas que la pantalla ahora dice en voz alta

Estas tres ya funcionaban así por dentro. Ninguna estaba escrita en ninguna
parte, y las tres había que adivinarlas.

**El mapa no se edita: se calcula.** Cada condición que escribes en una regla
registra una flecha entre dos atributos, y de esas flechas sale el nivel de cada
uno. Por eso la pestaña del mapa no tiene formulario, y ahora lo explica en vez
de limitarse a listar filas. Se comprobó guardando una regla de prueba: la
flecha apareció sola y el nivel del atributo objetivo pasó de 0 a 1 sin tocar
nada más.

**Una regla sobre un atributo que no tiene ninguna entidad no hace nada.** Es
correcta, se guarda, y no cambia nada hasta que alguna entidad use ese atributo.
Ahora se avisa en dos sitios: arriba, contando cuántas reglas están en esa
situación, y dentro del constructor en cuanto eliges un atributo así.

**Dos reglas que se contradicen se guardan las dos.** Si una dice «mostrar» y
otra «ocultar» sobre el mismo atributo, gana la de más prioridad y el resultado
deja de leerse de un vistazo. Lo mismo con un par de valores marcado a la vez
como permitido y bloqueado. Las dos situaciones ahora salen en rojo arriba, con
las caras de los implicados y un botón que lleva a la lista ya filtrada.

## B.3 Las tres pestañas

**Mapa.** El recorrido dibujado por niveles: el nivel 0 no depende de nadie y
siempre se ve; cada nivel siguiente aparece cuando el anterior tiene el valor que
pide la regla. Cada atributo va con su cara y con lo que hace —«2 reglas deciden
si sale», «manda en 3 reglas», «siempre se ve»—. Debajo, las flechas una a una
con las dos caras, y **los que se ven siempre**: los que no aparecen en ninguna
regla, que contestan de un vistazo a «¿por qué este sale siempre?».

**Reglas.** El constructor pasó de cuatro desplegables a tres pasos numerados:
a qué atributo le pasa algo —eligiendo por su cara, con buscador y con aviso de
cuántas entidades lo usan—, qué le pasa —tres tarjetas con un dibujo de qué
significa mostrar, ocultar y exigir—, y cuándo —el atributo que se mira elegido
por su cara, el operador en cuatro botones, y el valor elegido entre las
**imágenes reales** del catálogo—. Debajo, la frase entera montada en vivo con
las dos caras: «Si Anime es Naruto entonces OCULTAR Naturaleza». La lista de
reglas existentes se lee como frases, no como filas, con buscador, filtro por
atributo y un detalle plegado.

**Catálogos.** Lo mismo para atar dos catálogos: el valor que manda a la
izquierda, permite o bloquea en el centro con su dibujo, y el valor al que afecta
a la derecha, los tres eligiendo por imagen. La frase también se lee antes de
guardar. Las dependencias existentes se agrupan por el valor que manda.

## B.4 Detalles de comportamiento que se añadieron

- Cambiar el operador a «tiene algún valor» o «está vacío» **tira** el valor que
  hubieras elegido antes, porque deja de tener sentido.
- Cambiar el atributo de una condición tira su valor, que pertenecía a otro
  catálogo.
- El botón de guardar está apagado hasta que la regla está completa, y debajo se
  dice exactamente qué falta.
- Si un atributo no es catálogo, el paso del valor lo dice y sugiere usar «tiene
  algún valor» en vez de dejar un desplegable vacío.
- La pestaña abierta se recuerda en el navegador.

## B.5 Lo que se añadió por detrás

`AttributeStructureController::index()` manda ahora, además de lo que ya
mandaba: la imagen, el color y el símbolo de cada atributo y de cada valor —sin
eso no hay pantalla con caras—, cuántas entidades usa cada atributo, los
atributos agrupados por nivel, los que no aparecen en ninguna regla, y las dos
familias de conflictos. El recuento de uso se hace de una vez para todos los
atributos, no uno por uno.

Se añadieron tres piezas de vista:
`attributes/partials/cara.blade.php` —la cara de un atributo o de un valor, que
en esta pantalla aparece una docena de veces—, y los dos constructores,
`rule-builder` y `catalog-link-builder`.

## B.6 Una trampa de Alpine que hay que respetar aquí

Las condiciones de `x-show` de esta pantalla se escriben **en línea**
—`['EQUALS','NOT_EQUALS'].includes(condicion.operator)`— y no metidas en un
método. Llamando a un método, la propiedad que decide se lee dentro de él y la
expresión no vuelve a evaluarse: el bloque se queda como estaba. El código que
había cometía exactamente ese fallo con `needsOption(condition)`, y por eso el
selector de valor no aparecía al cambiar el operador.

## B.7 Verificación

- La pantalla responde 200 para los tres usuarios de la base: uno con reglas y
  dependencias, y dos sin nada, que ven los estados vacíos explicados.
- Los dos avisos nuevos se comprobaron **fabricando los conflictos dentro de una
  transacción que se deshizo**: sin ellos no aparece ninguno; con una regla
  contraria y un par duplicado aparecen los dos, con sus botones. Al deshacer, los
  contadores volvieron exactamente a como estaban.
- Lo que envían los dos constructores se guardó de verdad, también dentro de una
  transacción deshecha: la regla quedó con su acción, su prioridad y su condición
  apuntando al valor correcto; **la flecha del mapa apareció sola** y el nivel del
  objetivo se recalculó a 1; la dependencia de catálogo quedó con su tipo y su
  prioridad. Al deshacer, todo volvió a su sitio.
- En el navegador se recorrió el flujo entero: elegir «Naturaleza» saca el aviso
  de que ninguna entidad lo usa, elegir «Ocultar» marca su tarjeta, elegir «Anime»
  carga las cuatro portadas reales del catálogo, y la frase queda montada con las
  dos caras. Cambiar el operador y cambiar el atributo tiran el valor viejo, y el
  formulario emite exactamente los campos que el controlador valida.
- Las 48 imágenes del mapa cargan sin ninguna rota.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

---

# Anexo B — La biblioteca de catálogos

## B.1 Qué había y qué faltaba

El índice de valores de catálogo tenía cuatro formas de mirar, un buen puñado
de filtros y ordenaciones, y dos agujeros de fondo:

- **Quinientos valores en fila, sin decir de qué está hecha la biblioteca.**
  Había un filtro por catálogo, pero nada que contestara «¿cuántos catálogos
  tengo, cuáles están a medio hacer, cuáles están vacíos?».
- **Un valor sin imagen no se distinguía de los demás.** Y no es un detalle
  estético: todas las pantallas donde se elige un valor —el constructor de
  reglas, las dependencias entre catálogos, el formulario de una entidad— lo
  enseñan por su cara. Un valor sin imagen aparece como un cuadro vacío y no hay
  forma de reconocerlo.

## B.2 Lo que hace ahora

**Seis formas de mirar**, dos de ellas nuevas:

- **Catálogos** — una tarjeta por catálogo con las caras de los suyos, una barra
  de cuántos tienen imagen, y tres contadores: cuántos se usan, cuántos cuelgan
  de otro, cuántos están activos. Es la vista que contesta de un vistazo de qué
  está hecha la biblioteca.
- **Jerarquía** — el árbol de cada catálogo, con la sangría y el contador de
  hijos de cada rama.
- Galería, cuadrícula, lista y tabla, rehechas en oscuro, con control de tamaño
  de 4 a 9 columnas y un interruptor de **agrupar por catálogo**.

**Los que no tienen imagen se marcan.** Cifra propia en la fila de arriba, borde
rojo en su ficha, etiqueta «sin imagen» en la lista, columna en la tabla, y un
aviso encabezando la página con un enlace que los filtra de una.

**Archivar y reactivar sin abrir la ficha**, de uno en uno desde su tarjeta o en
lote seleccionando varios. Entrar y salir de un formulario entero para tocar un
desplegable es el trabajo que nadie hace, y por eso los catálogos acumulan
valores muertos que siguen apareciendo en todos los selectores.

**El explicador**, plegado, con un dibujo de dónde encaja un valor: el catálogo,
sus valores —uno de ellos de puntos, el que no tiene imagen— y la entidad que se
queda con uno.

## B.3 Decisiones que conviene recordar

**Las dos vistas nuevas no obedecen a los filtros, y lo dicen.** El resumen por
catálogos y el árbol se calculan sobre la biblioteca entera, no sobre la página:
un resumen calculado sobre veinticuatro valores de una página sería un resumen
equivocado, y un árbol partido por la paginación no es un árbol. Ambas lo avisan
en pantalla para que nadie piense que el filtro de arriba se les aplica.

**La lista se pinta dos veces sobre la misma pieza.** Seguida —respetando el
orden elegido arriba— y separada por catálogo. Si la versión sin agrupar se
sacara de los grupos, el orden elegido se perdería sin que nadie lo dijera.

**Los botones del cambio en lote llevan su propio `name` y `value`.** El
navegador manda el del botón que se pulsa, así que el estado no depende de que
una escritura de Alpine llegue al DOM antes del envío. Y los ids se emiten una
sola vez desde la selección: si cada modo de vista llevara los suyos, el mismo
id viajaría seis veces.

**Un id ajeno colado en el envío en lote se queda fuera sin hacer ruido.** La
consulta filtra por propietario antes de actualizar, que es lo que tiene que
pasar.

## B.4 Lo que se añadió por detrás

`AttributeOptionController::index()` pasa tres datos nuevos —el uso de cada
valor, los valores agrupados por catálogo en versión ligera, y el resumen de
cada catálogo— y una cifra más, la de los que no tienen imagen. Los valores
ligeros se piden con solo las nueve columnas que hacen falta para pintarlos.

Dos métodos nuevos y sus rutas: `quickUpdate` (`PATCH
attribute-options/{id}/quick`) para un cambio suelto y `bulkUpdate` (`POST
attribute-options/bulk`) para varios. La ruta de lote va declarada antes que la
que lleva parámetro, para que «bulk» no se lea como el id de un valor.

Piezas nuevas: `partials/library-card.blade.php`, `partials/list-row.blade.php`,
`partials/branch.blade.php` y la cara compartida
`attributes/partials/cara.blade.php`. Se retiraron `index-card`,
`index-gallery-card` e `index-list-item`, que ya no usaba nadie.

## B.5 Verificación

- Seis direcciones responden 200: sin filtros, solo los que no tienen imagen,
  los que no usa nadie ordenados por nombre, una búsqueda sin resultados,
  filtrado a un catálogo concreto, y los noventa y seis de golpe por más usados.
- La vista de catálogos sale con datos reales: «Clanes» con sus 52 valores, ocho
  caras y la barra en 52/52; «Anime» con cuatro y uno en uso; y veinte catálogos
  vacíos diciendo que así no se pueden asignar a ninguna entidad.
- El cambio rápido y el cambio en lote se probaron **dentro de una transacción
  que se deshizo**: el suelto dejó su valor archivado, el de lote dejó cinco
  inactivos, un estado inventado fue rechazado, y un valor de otro usuario metido
  a propósito en el envío quedó intacto. Al deshacer, los 56 estados volvieron a
  ser exactamente los de antes.
- El árbol se comprobó igual, colgando tres valores de un cuarto y deshaciéndolo:
  dibujó la jerarquía y la cuenta de valores con padre regresó a cero.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

---

# Anexo C — Crear y editar un valor de catálogo

## C.1 Qué había y qué faltaba

Cinco secciones numeradas en blanco, un desplegable para el padre y una vista
previa lateral. Correcto, y mudo sobre las tres cosas que de verdad se preguntan
mientras se rellena:

- **¿Ya existe algo así?** Nada lo decía hasta guardar. Un catálogo con
  «Uzumaki» y «uzumaki» no da error en ningún sitio: simplemente queda mal para
  siempre.
- **¿De qué cuelga?** Un desplegable con cincuenta nombres, sin caras y sin
  enseñar dónde iba a quedar el valor.
- **¿Cómo se verá al elegirlo?** La vista previa enseñaba otra cosa distinta de
  la ficha real.

## C.2 Lo que hace ahora

**Elegir catálogo, si no venías con uno.** Tarjetas con la cara de cada
catálogo, cuántos valores tiene ya, y **los vacíos primero** —son los que están
pidiendo valores a gritos—, con buscador.

**Aviso de nombre repetido mientras se escribe.** Compara en minúsculas y sin
espacios sobrantes, porque «Uzumaki» y «uzumaki » son el mismo clan para
cualquiera menos para la base de datos. No bloquea: avisa de que al elegirlo
nadie sabrá cuál es cuál. Y por debajo, una línea con los **parecidos** que ya
existen, para darse cuenta de que «Uchiha» ya está antes de crear «Uchiha
(clan)».

**El padre se elige por su cara**, con buscador, y al lado un dibujo de **dónde
quedará**: el catálogo, el padre elegido y el valor nuevo, con sus tres
imágenes, sangrados como quedarán en el árbol.

**La vista previa es la ficha real.** Lo que se ve en la barra lateral es
exactamente la tarjeta que sale en la galería del catálogo y en el selector de
una entidad, más la fila que sale en las listas. Debajo, un «qué le falta» con
el nombre en verde y la imagen en rojo mientras no la haya.

**Seguir añadiendo.** Una casilla que, al guardar, devuelve al formulario con el
catálogo —y el padre, si lo había— ya elegidos. Llenar un catálogo de cincuenta
clanes son cincuenta veces el mismo formulario; con esto son cincuenta nombres.

**Los campos raros, explicados.** El valor numérico dice para qué serviría y
reconoce que hoy no se enseña en ninguna parte. El estado deja de ser un
desplegable de tres siglas y pasa a tres opciones con su consecuencia escrita:
se puede elegir / de momento no / archivado, con la aclaración de que archivar
no borra nada.

**Al editar**, un aviso de a quién afecta lo que se toque —cuántas entidades lo
llevan, cuántos valores cuelgan de él— y una zona de peligro que, cuando el
valor está en uso, propone archivar en vez de borrar.

## C.3 Decisiones que conviene recordar

**El campo del padre viaja siempre**, incluso cuando el catálogo no tiene ningún
candidato. Si solo se pintara junto a los candidatos, editar un valor en un
catálogo que se quedó sin otros activos dejaría de decir de qué cuelga.

**Las condiciones se escriben enteras en la expresión.** El aviso de repetido
lee `nombre.trim()` en la propia condición, y el dibujo del árbol lee `padre`,
aunque los dos usen un captador: si la condición fuera solo el captador, Alpine
no registraría de qué depende y ninguno de los dos se actualizaría al teclear.

**«Seguir añadiendo» no se valida.** Se lee con `boolean()` fuera de los datos
validados, así que no puede colarse en la asignación masiva del modelo.

## C.4 Verificación

- Los cuatro estados responden 200: sin catálogo elegido, con un catálogo lleno
  —«Clanes», 52 valores—, con uno vacío, y editando uno existente.
- El aviso de repetido se probó en el navegador: escribiendo `«  hatake »` en
  minúsculas y con espacios reconoce a «Hatake»; escribiendo «Hyū» ofrece
  «Hyūga» como parecido; y un nombre nuevo no dispara ninguno de los dos.
- El dibujo de «dónde quedará» dibujó los tres niveles —Clanes → Hōki → el valor
  nuevo— al elegir padre.
- El envío se probó **dentro de una transacción que se deshizo**: un envío
  completo creó el valor con su padre, su número y su código generado; la casilla
  de seguir añadiendo devolvió a `attribute-options/create?attribute=11&parent=24`;
  y un envío sin nombre rebotó sin crear nada. Al deshacer, el catálogo volvió a
  tener sus 52.
- No se subió ninguna imagen en las pruebas de escritura, así que no quedó nada
  en disco —que es lo único que una transacción no puede deshacer—.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

---

# Anexo D — La ficha de un valor de catálogo

## D.1 Qué había y qué faltaba

Migas, un héroe grande, cifras, la tarjeta de su catálogo, su apariencia, su
jerarquía… y al final una sección que prometía que este valor «podrá ser
referenciado desde Universos, Torneos, filtros y reglas». Es decir: todo menos
lo único que un valor de catálogo existe para contestar.

- **¿Quién lo lleva?** «17 usos» como número, sin enseñar ni una cara.
- **¿Es de los que se usan?** Nada lo situaba entre sus hermanos de catálogo.
- **¿Puedo borrarlo?** Nada decía si hay una regla montada encima.

## D.2 Lo que hace ahora

**Quién lo lleva, con caras.** Hasta veinticuatro entidades enlazadas a su
ficha. Y aparte, cuántas veces aparece en **versiones de entidad**, que es otro
sitio donde el mismo valor puede estar puesto y no contarlo hacía parecer que no
se usaba.

**Qué se apoya en él.** La sección que no existía: las dependencias entre
catálogos y las reglas contextuales que nombran este valor, leídas en castellano
—«Cuando *Anime* es *Naruto: Shippūden*, se muestra *Rango Ninja*»— y con las
caras de los valores implicados. Solo aparece cuando hay algo que decir, y es lo
que convierte la zona de peligro en un aviso honesto en vez de un botón.

**Sus hermanos, de cuatro formas.** Galería, cuadrícula, lista y **ranking**. El
ranking es el que contesta «¿es este de los que sostienen el catálogo?»: barras
por uso con el propio valor marcado en su posición, en vez de escondido entre
los demás. Galería y cuadrícula con control de tamaño, y la vista elegida se
recuerda.

**Saltar al anterior y al siguiente** del mismo catálogo, con su cara, sin
volver al índice.

**Su sitio, dicho.** «nº 2 de 4 por uso» en la cabecera, y las migas con la cara
del catálogo y la de cada antepasado.

**Archivar desde aquí**, junto a eliminar, porque casi siempre es lo que se
quiere de verdad: deja de poder elegirse y quien ya lo llevaba lo conserva.

**Se fue el folleto.** La sección «Referencias futuras» no decía nada
comprobable sobre este valor. Una ficha no es sitio para una promesa.

## D.3 Decisiones que conviene recordar

**Las dos cuentas de uso son distintas y se enseñan separadas.** Las entidades
que lo llevan salen de `entity_attribute_values`; las versiones, de
`entity_version_attribute_values`. Sumarlas daría un número más lucido y menos
cierto.

**La zona de peligro cuenta las ataduras antes de ofrecer el botón.** Si hay
entidades, hijos o reglas encima, lo dice y propone archivar. Solo cuando no hay
nada de eso afirma que borrarlo no rompe nada.

## D.4 Verificación

- Un valor en uso —«Naruto», 17 entidades— responde 200 y enseña sus caras, sus
  hermanos y el ranking.
- Un valor sin uso —«Naruto: Shippūden»— responde 200, dice que no lo lleva nadie
  **y aun así no ofrece borrarlo alegremente**: tiene una dependencia de catálogo
  hacia «Uchiha» y una regla que muestra «Rango Ninja», y las dos salen escritas
  en la sección nueva. Comprobado contra la base de datos: una fila en
  `attribute_option_relationships` y otra en `attribute_context_rule_conditions`.
  Eso es exactamente lo que la ficha anterior ocultaba.
- El ranking ordenó los cuatro valores de «Anime» —17, 0, 0, 0— con el actual
  marcado en el segundo puesto.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.
