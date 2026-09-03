# «Lo configuré a mano y salió como quiera»

En la edición 3 el reparto por puertas se hizo a mano —tres competidores en cada
una de las cuatro entradas— y al empezar salió otra cosa. Tres fallos distintos,
y el primero **no** era el reparto.

## 1. El reparto estaba bien. Lo rehacía la fase

Lo guardado era exacto:

| Entrada | Quién |
|---|---|
| Stock A | Hinata Hyūga, Kakashi Hatake, Ishiki |
| Stock B | Ino Yamanaka, Jura, Naruto Shippuden Básico |
| Stock C | Mitsuki, Perú, Sakura Haruno |
| Stock D | Sasuke Uchiha, Shikamaru Nara, Hiruzen Sarutobi |

Y las cuatro puertas de la fase recibieron a esos mismos doce. Pero los grupos
salieron mezclados —Grupo A con gente de la puerta 1 y de la puerta 3— porque la
fase tiene `distribution_mode = SNAKE_SEEDED`: reparte en serpiente por siembra
e **ignora por qué puerta entró cada uno**.

No era aleatorio, y no se perdió nada. Es que repartir por puertas dice **quién
entra**, y en qué grupo cae lo decide la fase.

### Lo que se hizo

No forzar el reparto por encima de un modo que el usuario eligió —eso sería
ignorar su configuración para respetar la otra—, sino **decirlo antes**, en la
misma pantalla donde se reparte:

> ⚠ **El reparto por puertas no decidirá los grupos**
> **Fase Única** reparte en grupos con **serpiente por siembra**, así que
> decidirá por su cuenta quién cae en cada grupo. Lo que marques aquí decide
> *quién entra*, no en qué grupo acaba.
>
> Para que cada puerta llene su grupo, cambia el reparto de esa fase a *orden de
> llegada* o *a mano* en su Super Edición.

Los dos modos que sí respetan la puerta son `INPUT_ORDER` —los participantes
llegan en orden de puerta— y `MANUAL`, donde el reparto por puertas ya llena los
grupos directamente.

## 2. Una edición nueva no heredaba el reparto

Todo lo demás —fases, juego, batalla, premios, reglas de entrada— se copia con
`$competition ?? $source`. El reparto por puertas usaba solo `$competition`, que
al crear es `null`: era **lo único** que no se heredaba, y había que rehacerlo a
mano cada vez.

Ahora se copia como el resto. Al crear una edición desde la anterior, las cuatro
entradas aparecen llenas con la misma gente, en modo «uno a uno», y sus doce
campos viajan al guardar.

## 3. El reparto a mano ganaba aunque eligieras reglas

Los campos ocultos del reparto vivían bajo `x-show`, que oculta pero no desmonta
—que es justo lo que se quiere en casi toda la aplicación, para que los campos
plegados sigan viajando—. Aquí era al revés: el servidor prefiere el reparto a
mano cuando llega relleno, así que un reparto viejo ganaba siempre, incluso
después de cambiar a «con una regla».

Pasan a `x-if`: en modo reglas no viajan.

## Comprobado

Creando una edición a partir de la 3:

| Qué | Resultado |
|---|---|
| Modo al abrir | `MANUAL` |
| Stock A / B / C / D | las mismas tres personas que en la edición anterior, «✓ llena» |
| Campos que viajan | 12 |
| Aviso | «Fase Única → serpiente por siembra» |
| En modo «con una regla» | 0 campos de reparto |

Suite de tests: 88 pasan / 99 fallan, igual que antes.
