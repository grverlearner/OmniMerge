# Elegir a dedo, y verlo

La elección a mano —de competidores en un torneo, de quién entra por cada
puerta de una edición— **funcionaba**. Lo comprobé antes de tocar nada: marcar
guardaba, el contador subía, los campos ocultos viajaban al servidor.

Lo que no había era ninguna forma de saberlo. Y eso, en la práctica, es lo
mismo que si no funcionara.

## El hueco

Marcabas una cara, un número subía de 2 a 3, y seguías sin saber si subió por
quien tú querías. La única confirmación era un contador, y un contador no
confirma una elección: confirma una cantidad.

En las puertas de una edición se sumaba otra cosa. El aviso decía **«19 no
caben»**. Era exacto y no servía de nada: no decía por qué —tu condición
captura a más gente que plazas hay— ni a quiénes deja fuera.

## Lo que ahora se ve

### Quién está en cada entrada

Debajo de cada puerta, siempre —abierta o cerrada—, las **caras y los nombres**
de quien ha quedado dentro, con una × para sacarlo sin abrir nada. Vale igual
repartiendo con reglas, que lo calcula el servidor, que a mano: la pregunta es
la misma.

Vacía lo dice, y dice qué hacer: «pulsa ✎ elegir y márcalos», o «escribe una
condición».

### Si está llena

Un solo sitio decide el estado de una puerta —`doorState`— y de él salen el
color, la palabra y la barra. Antes cada uno lo calculaba por su cuenta.

| Estado | Cuándo | Qué se lee |
|---|---|---|
| `FREE` | sin límite de plazas | sin límite |
| `EMPTY` | tiene límite, no hay nadie | faltan 3 |
| `PARTIAL` | van entrando | faltan 1 |
| `FULL` | justo las que caben | ✓ llena |
| `OVER` | hay más de los que caben | sobra 1 |

El recuento pasa a ser **«2/3»**, no «2»: el número solo se entiende contra su
capacidad. Y mientras marcas caras aparece ahí mismo un **✓ completa** o un
**⚠ te pasaste por N** — decirlo solo en la cabecera no sirve, porque marcando
no se mira arriba.

Pasarse no se bloquea a propósito: a veces quieres meter uno más y quitar otro
después. Pero se ve en rojo y no se escapa.

### Quiénes no caben

En lugar de «19 no caben»:

> Tu condición elige a **7**, y esta entrada tiene **3** plazas. Estos **4** se
> quedan fuera: [caras y nombres]
>
> Afina la condición para que elija justo a 3, o usa ✋ a dedo para decidir cuáles.

### Y en el torneo

Lo elegido a dedo se enseña **fuera del selector**, que era el problema: al
cerrarlo desaparecía todo rastro y solo quedaba un número en un botón. Ahora
quedan dos filas —«Entran pase lo que pase» y «Fuera pase lo que pase»— con las
caras, y una × que deshace.

Esa × llama a `unsetHand()`, no a `cycleHand()`. El ciclo va RULE → IN → OUT →
RULE, que está bien mientras marcas mirando la ficha; en un botón «quitar»
sería una trampa, porque pulsar sobre alguien que está dentro lo dejaría fuera.

## Comprobado en el navegador

Sobre el torneo real de 4 entradas de 3 plazas y 22 competidores:

| Situación | Lo que se lee | Caras |
|---|---|---|
| entrada vacía | faltan 3 | ninguna |
| 2 marcados | faltan 1 | 2 |
| 3 marcados | ✓ llena · ✓ completa | 3 |
| 4 marcados | sobra 1 | 4 |
| con reglas, 7 casan en 3 plazas | ✓ llena | 3 dentro **y los 4 que no caben, por su nombre** |

Y en el torneo: marcar uno dentro y otro fuera produce las dos filas con sus
caras, los campos `eligibility_include[]` / `eligibility_exclude[]` viajan, y la
× los retira dejando el otro intacto.

Suite de tests: 88 pasan / 99 fallan, igual que antes.
