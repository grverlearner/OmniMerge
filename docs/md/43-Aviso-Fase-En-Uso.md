# Avisar antes de tocar una fase que ya se usa

Una plantilla de fase deja de ser un borrador propio en cuanto entra en una
plantilla de torneo. A partir de ahí es una pieza de otra cosa: cambiarle las
rondas, las salidas o las puertas cambia el recorrido de todos los torneos que
la montaron, y puede dejar conexiones apuntando a una salida que ya no existe
—algo que no se ve hasta que alguien intenta jugar—.

No se bloquea. La plantilla es del usuario y sabrá lo que hace; lo que no puede
pasar es que lo haga **sin saberlo**.

## El aviso

Aparece en las pantallas donde se edita una fase, y dice tres cosas:

- **Cuántas plantillas de torneo la usan**, y sus nombres, enlazados. Si además
  está montada varias veces dentro del mismo recorrido, lo añade: «2 plantillas
  de torneo, en 7 sitios».
- **Qué se arriesga**: que el cambio alcance a esos recorridos y descuadre sus
  conexiones.
- **Cuántas competiciones se han jugado ya** con ellas. Esas no corren peligro
  —se juegan sobre un snapshot congelado— pero saber que una plantilla ya se
  jugó cambia las ganas de tocarla, y las ediciones futuras sí saldrán con lo
  que se deje ahí.

Y al lado, la salida razonable: **⧉ Duplicar y editar la copia**, que reutiliza
la acción de duplicar que ya existía. Un aviso que solo dice «cuidado» deja al
usuario con el problema; este trae el camino.

## Dónde sale

- En las **trece pantallas de edición** de una fase. Va dentro de
  `workspace-navigation`, la barra común a todas: ponerlo en cada una habrían
  sido trece sitios donde olvidarse de ponerlo.
- En la **Super Edición**, en su versión oscura. Es donde de verdad cambia la
  forma de una fase, así que es donde más falta hace.

Si la fase no la usa nadie, no se dibuja nada.

## Comprobado

| Fase | En uso | Pantalla | Aviso |
|---|---|---|---|
| 74 · ZZZ FASE ELIMINACION | 3 torneos · 8 jugadas | edición / ficha / Super | **sí** |
| 68 · Fase eliminación prueba | ninguno | edición / ficha / Super | no |

Texto real de la 74:

> ⚠ **Esta fase ya está en uso.** La usan **3** plantillas de torneo. Lo que
> cambies aquí —rondas, salidas, puertas— cambia **esos recorridos**, y puede
> dejar conexiones apuntando a algo que ya no existe.
> *ELIMINACION TORNEO · ELIMINAICON CCC PRUEBA · ZZZ TORNEO ELIMINACION*
> Ya se han jugado **8** competiciones con ellas.

Suite de tests: 88 pasan / 99 fallan, igual que antes.

## Lo que no hace

No impide guardar, y no avisa al revés —desde la plantilla de torneo— de que una
de sus fases cambió. Eso segundo requeriría registrar cuándo se editó cada fase
y compararlo, y es una función distinta: esto es el aviso que se pidió, en el
momento en que se puede hacer algo con él.
