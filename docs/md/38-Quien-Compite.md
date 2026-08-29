# Quién compite

Decidir quién entra en un torneo tenía dos formas de combinarse —«todas» y
«al menos una»— y ninguna de decir «este sí, porque lo digo yo». Ahora tiene
cuatro modos, grupos anidados y la mano.

El mismo lenguaje sirve en tres sitios: las reglas de un **torneo**, el
reparto por **puertas** de una edición, y la elección de **versión** con la
que sale un competidor. No es reutilización por ahorro: los tres preguntan lo
mismo, y tenerlo escrito tres veces garantizaba que un día dijeran cosas
distintas.

---

## 1. La forma

```json
{
  "mode":    "ALL | ANY | NONE | ONE",
  "rules":   [ { "attribute": "aldea", "values": ["hoja"] } ],
  "groups":  [ { "mode": "ALL", "rules": [ … ] } ],
  "include": [ 134 ],
  "exclude": [ 141 ]
}
```

### Los cuatro modos

| | Qué pide | Cómo se dice |
|---|---|---|
| **ALL** | cumplirlas todas | Y |
| **ANY** | basta con una | O |
| **NONE** | no cumplir ninguna | NI |
| **ONE** | exactamente una, ni ninguna ni dos | XOR |

`NONE` y `ONE` no son adornos. El primero es como se dice «todos menos los de
Akatsuki»; el segundo, «o eres de Konoha o eres de Suna, pero no las dos».

### Los grupos

Un grupo **es una condición más**, con su propio modo dentro. El modo de
arriba combina todas las condiciones por igual —reglas sueltas y grupos—, así
que un grupo no es un ciudadano de segunda.

Con eso se escribe `(aldea hoja Y anime naruto) O (aldea arena)`, que con
reglas planas no se podía decir.

**Un solo nivel, a propósito.** Grupos dentro de grupos darían una pantalla
que nadie sabría leer, y hasta aquí llega lo que alguien quiere expresar de
verdad.

### La mano

`include` mete a alguien pase lo que pase; `exclude` lo saca pase lo que
pase. Ninguna regla escrita con atributos va a capturar «este sí, porque lo
digo yo».

**Excluir gana sobre incluir**, y los dos ganan sobre las reglas. Es el orden
que espera cualquiera: si alguien está en las dos listas es porque se le metió
y después se le sacó.

---

## 2. La pantalla

En el torneo (bloque 06) y en cada puerta de una edición (bloque 06 de la
competición), lo mismo:

- Los cuatro modos, con su símbolo y una frase que dice qué hacen.
- **`+ un grupo`** — con su propio selector de modo y sus condiciones.
- **`✋ elegir a dedo`** — una rejilla de caras. Un botón y **tres estados**:
  normal → dentro pase lo que pase → fuera pase lo que pase → normal. Uno
  para incluir y otro para excluir en cada ficha habría duplicado la rejilla
  entera.

Las fichas llevan imagen porque elegir competidores de una lista de texto es
peor que elegirlos mirándolos. Los excluidos salen en gris y desaturados; los
que ya entran por las reglas llevan un punto, para que meterlos a mano se vea
como lo que es: redundante.

Todo se recalcula en la pantalla, en el mismo clic. El servidor sigue siendo
la autoridad y se le sigue preguntando.

---

## 3. Una sola implementación de «cumple»

`UniverseTournamentEligibility::evaluate($atributos, $regla)` es pública y no
recibe una entidad: recibe atributos aplanados. Eso permite que la use tanto
quien decide **quién compite** como quien decide **con qué versión** sale.

`UniverseEntityVersionResolver` tenía su propia copia con dos modos. Al añadir
`NONE`, `ONE` y los grupos habría quedado atrás en silencio: un torneo de
«ninguna de» habría elegido la versión equivocada. Ahora delega.

---

## 4. Dos fallos que este cambio destapó

**`matching()` se saltaba los grupos.** Cortaba por lo sano con
`if ($rules['rules'] === []) return $entities;`, así que una regla hecha solo
de grupos —o solo de exclusiones— dejaba pasar a todo el mundo. Un torneo de
`(anime naruto Y aldea) O continente` admitía a los 21.

**El reparto por puertas perdía los grupos.** `CompetitionStartRouting`
copiaba campo a campo `mode` y `rules` al normalizar, así que lo que se
añadiese después se quedaba fuera sin decir nada. Ahora pasa la fila entera.

Y uno de arrastre: **`UniverseEntitySync` leía solo `eligibility['rules']`**
para saber de qué atributos depende un torneo. Una condición escrita dentro
de un grupo no contaba, así que sincronizar podía retirar un atributo del que
un torneo real dependía —justo la protección que existe para eso—. Ahora usa
`attributesUsed()`, que mira dentro.

---

## 5. Comprobado

Sobre un universo de 21 competidores:

| Regla | Entran |
|---|---|
| sin reglas | 21 |
| `ALL` tiene anime | 18 |
| `NONE` tiene anime | 3 |
| `ANY` anime o aldea | 19 |
| `ALL` anime y aldea | 2 |
| `ONE` anime o aldea (exclusivo) | 17 |
| `(anime=naruto Y aldea) O (continente)` | 2 |
| todos menos 3 excluidos | 18 |

| Qué | Resultado |
|---|---|
| `ONE` frente a `ALL` | 17 y 2, **sin solapar** — es exclusivo de verdad |
| Incluido a dedo contra una regla que lo excluye | Entra |
| Incluido **y** excluido | No entra — excluir gana |
| Guardar y volver a abrir el torneo | Modo, 2 grupos, 1 incluido y 1 excluido intactos |
| Los cuatro modos en la pantalla | 1 / 2 / 19 / 2 — cuentas distintas |
| El ciclo de la mano | `RULE → IN → OUT → RULE` |
| Los campos de una puerta | `start_rules[0][groups][0][rules][0][values][] = hoja` |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |

---

## 6. Lo que queda fuera

- **Sin grupos dentro de grupos.** Un nivel cubre `(A y B) o C`; para
  `((A y B) o C) y D` habría que anidar, y esa pantalla ya no se lee.
- **`describe()` no cuenta los grupos.** El resumen en texto de una regla
  sigue leyendo solo el primer nivel; la pantalla los enseña con su propio
  texto (`groupText`), así que no se nota, pero si algún día se usa
  `describe()` en otro sitio se quedará corto.
