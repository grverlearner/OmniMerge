# Ocho campeones, y un puesto que no cuadraba

La lista de la fase decía que la primera era **Ino Yamanaka**. La pantalla de
resultado y los premios decían **Kakashi Hatake**, y a Ino la ponían novena.
Ninguna de las dos mentía por su cuenta: leían dos datos distintos, y el que
leían los premios estaba mal.

Son dos fallos encadenados.

## 1. Un terminal de campeón con ocho dentro

El torneo tiene un terminal «Mejor de todos» de tipo **CHAMPION**, y la salida
«Clasificados» de la fase de grupos manda a **ocho** competidores ahí. El
proyector hacía lo que le decía la configuración: marcar `CHAMPION` a los ocho.

Cumplir eso al pie de la letra no es cumplirlo, es romper el resultado:

- la pantalla proclama a uno **al azar** —el primero que encuentre—;
- los premios de campeón se reparten ocho veces;
- el palmarés registra ocho títulos por un torneo.

Un campeón es **uno**. Cuando el terminal trae varios, el proyector ya no elige:
los deja como `FINALIST` —que es lo que son, finalistas— y deja la decisión a
quien sabe ordenarlos.

## 2. «Hasta dónde llegó» no significa nada en una fase de grupos

`TournamentPlacementResolver` ordenaba por `round_reached` antes que por nada
más. En un cuadro eso es la profundidad —cuartos, semis, final— y es un buen
criterio. En una fase de grupos mide **cuántas jornadas tuvo tu grupo**.

Con grupos de tres y de cuatro, quien ganó su grupo de tres (2 jornadas)
aparecía por debajo de quien fue último en uno de cuatro (3 jornadas). De ahí
que Ino, con 6 puntos y primera de su grupo, saliera novena.

Ahora manda **el puesto que se ganó en la última fase**, y `round_reached` queda
como respaldo para quien no tenga puesto. Y si esa fase produjo una **lista
única** —el caso de una fase de grupos, con el modo que tenga elegido— manda
esa, así que el puesto final del torneo respeta la elección en vez de
contradecirla.

Con el orden ya resuelto, el resolver cierra lo que el proyector dejó abierto:
el primero es `CHAMPION` y el resto quedan colocados.

## Lo que produce ahora

| # | Antes | Ahora |
|---|---|---|
| 1 | Kakashi Hatake · 6p | **Ino Yamanaka · 6p** |
| 2 | Kiba Inuzuka · **1p** | Kakashi Hatake · 6p |
| 3 | Akamaru · 6p | Akamaru · 6p |
| 8 | — | Kiba Inuzuka · 1p |
| 9 | **Ino Yamanaka · 6p** | Ishiki · 1p |
| Campeones | **8** | 1 |

## No venía de copiar la edición anterior

Copiar la edición trae fases, configuración, participantes y reparto, y lo hace
bien: la edición 2 tiene sus 12 participantes y sus 12 batallas, sin duplicados
ni restos. El fallo estaba en cómo se leía el resultado **después** de jugar, y
le pasaba igual a una edición creada desde cero.

## Y una sola lista en pantalla

Había dos, una encima de otra: la tira «Cómo va esta fase» y la lista general de
la fase de grupos. Mismo orden, ninguna completa.

Se quedó la primera, porque sirve para todos los motores, y absorbió lo que
decía la otra. Cada ficha lleva ahora:

> `1` 🖼 **Ino Yamanaka** `B1` **6**

el puesto, la cara, el nombre, de dónde sale —su grupo y su puesto en él— y sus
puntos. Y la etiqueta de arriba dice con qué criterio está ordenada.

Suite de tests: 88 pasan / 99 fallan, igual que antes.
