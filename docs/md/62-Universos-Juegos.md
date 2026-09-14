# El panel de juegos de un universo

El torneo decide **quiénes** se enfrentan. El juego decide **quién gana**. Esta
pantalla existe para entender lo segundo antes de elegirlo.

## 1. Qué había y qué faltaba

Dos tarjetas claras con el texto de cada motor. Correcto, y mudo sobre lo único
que hace falta para decidir:

- **Cómo funciona de verdad.** Un párrafo describiendo el mecanismo, no un
  dibujo. Dos juegos que se diferencian en un solo paso —uno redondea antes de
  comparar— parecían el mismo leyendo por encima.
- **Con qué valores se juega aquí.** La configuración del universo vivía solo
  dentro de la ficha de cada juego.
- **Si se usa.** Un número de enfrentamientos sin nada al lado: no se
  distinguía «nadie lo ha jugado» de «no hay con quién jugar».

## 2. Lo que hace ahora

**Cada juego trae su diagrama.** Los juegos viven en código y no tienen imagen
que subir, así que su «foto» es un dibujo que se construye **desde su propia
definición**: los tres pasos del mecanismo —cada uno tiene su rango, saca un
número, gana el más alto—, con el rango estrecho dibujado como fiable y el
amplio como impredecible. El juego que redondea añade solo su paso extra, con el
8,6 convirtiéndose en 9. Es la diferencia entre los dos, vista de un vistazo.

**Las reglas, paso a paso**, plegadas, numeradas y con el desempate aparte.

**Con qué entra un competidor nuevo en este universo**: cada estadística con su
valor de partida, el recorrido permitido dibujado, y una etiqueta «a medida»
cuando el universo ha ajustado el valor que trae el motor.

**Tres cifras por juego**: enfrentamientos jugados, competiciones que lo han
elegido y si está activo. Enfrentamientos altos con cero competiciones significa
que todo se ha jugado en el simulador, que es otra cosa.

**Elegir el juego por defecto** desde la propia tarjeta, con el aviso de que
cambiarlo no toca las competiciones que ya existen.

**El explicador** de dónde encaja un juego: el torneo empareja, el juego
resuelve, el competidor lleva sus números; y la aclaración de que un mismo
competidor juega distinto en cada juego sin que su ficha de la biblioteca se
entere.

**Y una tarjeta para lo que vendrá**, diciendo que los juegos viven en código y
que uno nuevo aparecerá aquí solo, con su diagrama y su configuración, sin
migración ni plantilla que tocar.

## 3. Lo que se añadió por detrás

`UniverseGameController::index()` pasa por juego su configuración resuelta en
este universo y cuántas competiciones lo han elegido, más el número de
competidores del mundo. Sin ese último, «0 partidas» no se distingue de
«todavía no hay con quién jugar».

El diagrama vive en `universes/games/partials/diagrama.blade.php` y se dibuja
desde el tipo del juego, no desde una lista de casos escrita a mano: un motor
que no sea numérico cae en un dibujo genérico con su propia condición de
victoria en vez de mentir.

## 4. Decisiones que conviene recordar

**No se ha implementado nada nuevo de configuración**, por encargo. El único
botón que escribe es el de «usar por defecto», que ya existía. Ajustar los
valores sigue llevando a la ficha del juego.

**El acento de cada juego viaja como color en `style`.** Tailwind solo genera
las clases que encuentra escritas enteras, así que un acento que viene del motor
no puede ser una clase compuesta.

**«A medida» solo sale cuando el universo ha guardado un ajuste.** Que los
valores no coincidan con los de otro juego no significa que estén tocados: cada
motor trae los suyos.

## 5. Verificación

- La pantalla responde 200 en el universo «Franquicia Naruto» y dibuja los dos
  diagramas, uno por motor.
- Las cifras cuadran con la base de datos: 37 enfrentamientos y 4 competiciones
  para Highest Number, 0 y 0 para Rounded Number, 9 competidores en el mundo.
- La etiqueta «a medida» no aparece en ninguno, y es correcto: ninguno de los
  dos tiene configuración guardada en este universo, así que los valores que se
  ven —1 a 10 y 0 a 3— son los que trae cada motor.
- El botón de juego por defecto se probó **dentro de una transacción que se
  deshizo**: pasó el defecto a Rounded Number y el anterior dejó de serlo; una
  clave inventada rebotó sin tocar nada. Al deshacer, el estado volvió a ser el
  de antes.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.

## 6. Lo que queda

La **ficha de cada juego** (`universes/games/show`) sigue en el diseño claro
anterior: es donde se ajusta la configuración, se ven los últimos
enfrentamientos y quién destaca.

---

# Anexo A — La ficha de un juego

## A.1 Qué había y qué faltaba

Las reglas, los últimos enfrentamientos, la configuración y un «quién destaca»
de ocho nombres. Bien, y sin lo único que hace que un juego se entienda de
verdad: **con qué números lo juega cada competidor**.

Las reglas explican el mecanismo. Los números explican el mundo: quién es
fiable, quién es una lotería, y quién sigue con los valores de partida porque
nadie le ha tocado nada.

## A.2 Lo que hace ahora

**Con qué números juega cada uno**, la sección nueva y la que ocupa el centro.
Los competidores del universo con sus valores en este juego, de **cinco formas**:

- **Rangos** —la especial, y la que se abre por defecto—: la barra de cada
  competidor dibujada **a la misma escala que las demás**. Una barra corta y a
  la derecha es alguien fiable; una larga, una lotería. Sin escala común cada
  barra usaría su propia regla y no se podrían comparar.
- Galería, cuadrícula, lista y tabla, con control de tamaño de 4 a 9 columnas.

**Quien no tiene números propios sale marcado «de partida»**, en gris, y la
cabecera dice cuántos son. No es lo mismo un competidor ajustado a 1–10 que uno
que nunca se tocó y hereda ese mismo 1–10: lo primero es una decisión, lo
segundo un hueco.

**Un filtro de «solo los que han jugado»**, porque en un universo grande la
mayoría de la lista todavía no ha competido.

**El diagrama del juego** también aquí, más grande, en su cabecera.

**Quién destaca** y **los últimos enfrentamientos** rehechos con las caras de
los competidores y lo que sacó cada uno, distinguiendo lo jugado en una
competición de lo jugado en el simulador.

**La configuración**, en una columna fija a la derecha: valor de partida, mínimo
y máximo por estadística, con los límites que admite el motor escritos debajo y
la casilla de reajustar a los que ya están —que avisa de que se les reescriben
las estadísticas—.

## A.3 Lo que se añadió por detrás

`UniverseGameController::show()` pasa ahora los competidores del universo con
sus estadísticas de este juego, cuántos enfrentamientos ha jugado y ganado cada
uno, si los números son suyos o los de partida, y el techo común para dibujar
todas las barras a la misma escala. Los participantes de cada enfrentamiento
cargan su competidor para poder enseñar su cara.

Se renombró `$sinTocar` a `$conEstadisticas`: contaba a los competidores que
**sí** tienen estadísticas de este juego, que es lo contrario de lo que decía su
nombre.

## A.4 Decisiones que conviene recordar

**La vista de rangos solo se dibuja cuando el juego tiene dos estadísticas
numéricas** (`min_value` y `max_value`). Un motor futuro con otras estadísticas
cae en una línea con sus valores en vez de dibujar una barra que no significaría
nada.

**Los valores de partida se pasan por el limitador del universo** antes de
enseñarlos, igual que hará el motor: enseñar un número que el mundo no permite
sería mentir sobre lo que va a pasar.

## A.5 Verificación

- Las dos fichas responden 200 —un juego con partidas y otro sin ninguna— y una
  clave inventada da 404.
- La vista de rangos dibuja los 9 competidores del universo de prueba con su
  rango y su marcador de ganados sobre jugados.
- La marca «de partida» se comprobó contra la base de datos: 5 competidores
  tienen fila de estadísticas para Highest Number y 4 no la tienen, que son
  exactamente los 4 que la pantalla marca, y coincide con el «4 siguen con los
  de partida» de la cabecera. Entre los que sí tienen fila, dos están ajustados
  de verdad —Mitsuki a 3–5 y Naruto Uzumaki a 1–3— y se distinguen a simple
  vista de los que están en 1–10.
- El juego sin partidas dice las dos cosas por separado: que nadie destaca
  todavía y que no se ha jugado nada con ese motor.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos.
