# COMPETICIONES

Un **torneo** es una marca: «la Copa». Una **competición** es lo que ocurre
en un momento — la Copa de la temporada 4.

Esa distinción ya existía en el modelo (`UniverseTournament` frente a
`TournamentInstance`), pero dos cosas la contradecían en la práctica.

---

## 1. Una competición no está atada a una sola plantilla

`UniverseTournament` tiene una `tournament_template_id`: la forma con la que
ese torneo suele jugarse. Al crear una competición se usaba **siempre** esa,
sin alternativa.

Y las temporadas cambian. La cuarta edición puede necesitar una fase previa
que la primera no tenía, porque ahora se apunta el triple de gente.

Ahora la competición **elige** con qué forma se juega: la del torneo por
defecto —que es lo habitual— o cualquier otra plantilla activa del mismo
dueño. La elección se congela con el resto: cambiarla después no altera una
competición en curso, ni las ya jugadas.

La comprobación de dueño está en el servicio y no es paranoia: el id viaja
en un formulario, y sin ella cualquiera podría jugar con la plantilla de
otra persona.

---

## 2. El formato de batalla se decide en la competición

Cuántos juegos dura un enfrentamiento **no describe la forma de un torneo**.
Cuántas rondas tiene un cuadro o cómo se cruzan sus puestos es la misma cosa
cada año; que se juegue al mejor de 3 o al mejor de 5 cambia entre
ediciones.

Es la misma frontera que ya sacó los premios de las plantillas: si un dato
cambia entre dos ediciones del mismo torneo usando la misma plantilla, no va
en la plantilla.

### Dónde se decide ahora

| Nivel | Qué dice |
|---|---|
| `tournament_instances` | El formato de toda la competición |
| `tournament_instance_phases` | La excepción de una fase concreta |

Una fase con `NULL` hereda el de su competición. Es lo normal: lo habitual
es «todo al mejor de 3», y lo excepcional «menos la final, que es al 5». Con
un valor por defecto, cada fase nacería con una excepción que nadie pidió.

Un «al mejor de» par se sube al impar siguiente: al mejor de 4 se empata a 2
y no hay forma de decidirlo. Se corrige en vez de rechazarlo porque esto
corre en ejecución, y detener una competición por un número par sería peor
que jugar un juego más.

### El motor no se tocó

Cada motor lee `series_format`, `default_best_of` y `fixed_games` del modelo
de ajustes de su fase, y son **cinco motores**. Cambiarlos todos era invitar
a que uno se quedara atrás.

En su lugar, `CompetitionBattleFormat` reescribe esos ajustes **en memoria**
justo donde el torneo se reconstruye para jugarse: `TournamentSnapshotHydrator`.
Un solo sitio, y ningún motor puede quedarse fuera.

No se guarda nada. Los modelos que salen del hidratador son `Snapshot*` y
tienen `save()` bloqueado, así que esto no puede tocar la plantilla de nadie
ni aunque quisiera — y dos competiciones sobre la misma plantilla pueden
jugarse con formatos distintos a la vez.

Al **crear** una competición todavía no hay fila ni fases, así que viaja una
competición en memoria que solo lleva el formato elegido: lo único que hace
falta ahí es que el estado inicial se construya con el mismo formato con el
que después se jugará.

### Se quitó de todas partes

Los cuatro formularios de ajustes de fase —liga, grupos, eliminación
directa, suizo— ya no lo editan. En su lugar queda un aviso que dice dónde
vive ahora: desaparecer sin explicación habría sido peor que no quitarlo.

Las columnas siguen en la base de datos y el motor las lee como valor por
defecto. Lo que se retiró es la posibilidad de editarlas ahí.

---

## 3. Casos probados

| Caso | Resultado |
|---|---|
| Crear una competición al mejor de 5 | Se guarda `BEST_OF 5` |
| Lo que lee el motor | `BEST_OF best_of=5` — la competición manda |
| La plantilla real después | Intacta: seguía en `FIXED_GAMES best_of=1` |
| Una fase con excepción | La competición dice «al mejor de 5», la fase «3 juegos fijos» |
| Los cuatro formularios de fase | 0 controles de formato · el aviso aparece en los cuatro |
| Las cuatro pantallas de ajustes | Siguen abriendo (200) |
| Crear competición | Selector de plantilla y formato presentes |

Suite completa: 88 pasan / 99 fallan — idéntico al baseline anterior.

---

## 4. Qué falta

1. **La excepción por fase no tiene interfaz todavía.** El dominio la
   resuelve y se puede escribir a mano en la fila, pero la pantalla de la
   competición aún no ofrece el control. Es lo siguiente.
2. **Cambiar de plantilla no migra participantes.** Elegir otra forma es una
   decisión de creación; una competición ya empezada mantiene la suya, que
   es lo correcto, pero tampoco hay forma de rehacerla con otra.
3. **No hay aviso al elegir una plantilla con distinto contrato.** Si la
   nueva admite 32 y la anterior 16, se descubre al asignar competidores.
