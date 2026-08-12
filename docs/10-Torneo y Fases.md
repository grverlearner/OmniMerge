DISEÑO Y PLANIFICACIÓN DEL SISTEMA DE FASES Y TORNEOS DE OMNIMERGE
1. Introducción
OmniMerge está evolucionando desde una plataforma centrada en la creación y organización de Entidades hacia un sistema capaz de utilizar esas Entidades dentro de estructuras competitivas, Universos, temporadas, rankings, recompensas y simulaciones.
Dentro de esta evolución, el módulo de Torneos tendrá una función fundamental: permitir a los usuarios diseñar sistemas de competición completamente configurables y reutilizables.
Sin embargo, un Torneo no debe entenderse únicamente como una lista de rondas que se ejecutan una detrás de otra.
Existen sistemas competitivos mucho más complejos:
torneos de eliminación directa;
torneos con fase de grupos y eliminatorias;
sistemas Round Robin;
sistemas suizos;
doble eliminación;
repechajes;
torneos donde los perdedores continúan;
ligas;
divisiones;
ascensos y descensos;
clasificaciones regionales;
múltiples caminos de clasificación;
fases que se ejecutan paralelamente;
fases que posteriormente convergen;
sistemas donde un tercer lugar continúa por otro camino;
competiciones donde ganar significa abandonar y perder significa continuar;
sistemas donde los participantes pueden regresar después de haber perdido;
torneos con varias finales o niveles de recompensa.
Debido a esta diversidad, la arquitectura no debe construirse suponiendo que un Torneo es una simple secuencia:
Fase 1
↓
Fase 2
↓
Fase 3
↓
Final

El sistema debe permitir construir estructuras mucho más flexibles.
La nueva decisión arquitectónica de OmniMerge será:
Una Fase define cómo funciona internamente una etapa competitiva. Un Torneo organiza y conecta esas Fases para construir un recorrido completo.
A partir de esta idea se diseñará primero el sistema de Fases reutilizables y, posteriormente, el constructor de Torneos.

2. Nueva separación conceptual
El sistema competitivo de OmniMerge debe dividirse en varias responsabilidades.
FASES
¿Qué ocurre dentro de una etapa?
        ↓

TORNEOS
¿Cómo se conectan las etapas?
        ↓

UNIVERSO
¿Quién participa realmente
y cuándo ocurre?
        ↓

SIMULACIÓN
¿Cómo se resuelven
los enfrentamientos?

Cada módulo tendrá una responsabilidad distinta.

3. Responsabilidad de una Fase
Una Fase representa un mecanismo competitivo autónomo.
La Fase sabe:
cuántos participantes puede recibir;
cómo organiza a los participantes;
cómo se producen los enfrentamientos;
cómo se generan las rondas;
cómo se calculan puntos;
cómo se construye un ranking;
cómo se resuelven empates;
qué competidores clasifican;
qué competidores pierden;
qué grupos de participantes salen de la Fase.
La Fase no debe decidir a qué lugar irán posteriormente esos participantes.

4. Responsabilidad de un Torneo
Un Torneo será el encargado de utilizar Fases y conectarlas entre sí.
El Torneo sabe:
qué Fases utilizar;
en qué momento se ejecutan;
qué salida de una Fase conduce a otra;
qué Fases se ejecutan paralelamente;
dónde empieza el recorrido;
dónde termina;
cuándo un resultado es terminal;
qué participantes van a repechaje;
qué participantes van a otra división;
qué participantes regresan a un camino principal;
qué participantes quedan eliminados;
quién alcanza una final.
Por tanto:
FASE
= funcionamiento interno

TORNEO
= recorrido competitivo


5. Regla principal del diseño
La regla central será:
Una Fase define un mecanismo competitivo autónomo con un contrato de entrada y uno o más canales de salida. El Torneo organiza instancias de esas Fases y conecta sus salidas con otras Fases o con resultados terminales.
Esta definición será una de las bases arquitectónicas del módulo.

6. Otra regla fundamental
También se establece:
Una salida expresa qué conjunto de competidores abandona una Fase, pero no determina su destino.
Ejemplo correcto:
Fase
↓
Salida:
LOSERS

La Fase solamente identifica a los perdedores.
Posteriormente el Torneo podrá decidir:
LOSERS
↓
Repechaje

o:
LOSERS
↓
Eliminados

o:
LOSERS
↓
Losers Bracket

La misma Fase puede utilizarse en distintos Torneos con destinos completamente diferentes.

7. Las Fases serán reutilizables
Actualmente se había planteado que cada TournamentPhase perteneciera directamente a una plantilla de Torneo.
La nueva arquitectura mejora este enfoque.
Existirá un recurso independiente:
PhaseTemplate

Una PhaseTemplate será una plantilla reutilizable de Fase.
Ejemplo:
Eliminación directa básica

podrá utilizarse dentro de:
Torneo A
Torneo B
Torneo C

sin tener que volver a crear su configuración.

8. Diferencia entre PhaseTemplate y TournamentPhaseNode
La arquitectura futura tendrá dos conceptos diferentes.
PhaseTemplate
Representa la definición reutilizable de una Fase.
Ejemplo:
PhaseTemplate

Nombre:
Eliminación directa básica

Tipo:
SINGLE_ELIMINATION

BYE:
Permitido

Best Of:
1


TournamentPhaseNode
Representará el uso de una PhaseTemplate dentro de un Torneo específico.
Ejemplo:
TournamentPhaseNode

Torneo:
Copa Ninja

Fase utilizada:
Eliminación directa básica

Nombre dentro del torneo:
Cuartos de Final

Por tanto:
PhaseTemplate
= definición reutilizable

TournamentPhaseNode
= uso contextual de esa definición


9. Relación conceptual con el sistema de Versiones
Esta separación sigue una filosofía parecida al sistema de Versiones existente en OmniMerge.
Conceptualmente:
Version
= definición reutilizable

EntityVersion
= aplicación concreta

De forma similar:
PhaseTemplate
= definición reutilizable

TournamentPhaseNode
= utilización dentro del Torneo

Esto mantiene coherencia arquitectónica dentro del proyecto.

10. Las Fases tendrán su propio módulo
Dentro del espacio de trabajo de Torneos, Fases dejará de ser solamente una sección interna de cada Torneo.
Existirá un panel independiente:
/tournaments/phases

La navegación se reorganizará.
Antes:
PLANTILLAS

Torneos
Crear plantilla

Después:
DISEÑO

🏆 Torneos

⌘ Fases

El botón “Crear plantilla” será retirado del sidebar.
La creación de Torneos y Fases se realizará dentro de sus propios paneles.

11. Nueva navegación del módulo Torneos
El sidebar se plantea así:
← Centro OmniMerge


🏆 OmniMerge
TORNEOS
Competition Designer


PRINCIPAL
────────────────────

▦ Dashboard


DISEÑO
────────────────────

🏆 Torneos

⌘ Fases


PRUEBAS
────────────────────

⚗ Laboratorio


RECURSOS
────────────────────

◇ Recompensas
  PRÓXIMAMENTE


DESCUBRIR
────────────────────

🌐 Comunidad


12. Panel principal de Fases
El panel será similar visualmente a otros recursos de Biblioteca.
Ejemplo:
⌘ FASES

Construye etapas competitivas
reutilizables dentro de tus torneos.

[ + Nueva fase ]

Podrá mostrar estadísticas como:
Fases creadas
12

Activas
8

Públicas
2

En uso
5


13. Listado de Fases
Ejemplo:
Eliminación directa básica

Tipo:
Single Elimination

Salidas:
WINNER
LOSER


Grupos de cuatro

Tipo:
Group Stage

Salidas:
TOP 2
THIRD
OUT


Liga divisional

Tipo:
League

Salidas:
PROMOTION
STAY
RELEGATION


14. Búsqueda y filtros
El panel podrá incluir:
Buscar
Tipo
Estado
Visibilidad
Orden

Posteriormente también podrá filtrarse por:
cantidad de salidas;
cantidad de usos;
propietario;
origen;
clonada/no clonada;
pública/privada.

15. PhaseTemplate como recurso principal
La nueva entidad principal será:
PhaseTemplate

Podrá almacenar conceptualmente:
id

user_id

source_phase_template_id

sequence_number

code

name

slug

description

image

phase_type

min_participants

max_participants

exact_participants

participant_multiple

allow_byes

status

visibility

allow_cloning

settings

metadata

published_at

created_at

updated_at

deleted_at


16. Identidad de una Fase
Una Fase podrá tener:
Nombre
Código
Descripción
Imagen
Tipo
Estado
Visibilidad

Ejemplo:
Código:
PHS000001

Nombre:
Eliminación directa básica

Descripción:
Fase donde los competidores se enfrentan
en cruces eliminatorios y los ganadores
continúan dentro del sistema competitivo.

Estado:
ACTIVE

Visibilidad:
PRIVATE


17. Tipos de Fase
El sistema deberá contemplar inicialmente los siguientes tipos:
SINGLE_ELIMINATION

ROUND_ROBIN

GROUP_STAGE

LEAGUE

SWISS

CUSTOM

Posteriormente podrían añadirse:
DOUBLE_ELIMINATION

LADDER

FREE_FOR_ALL

QUALIFIER

GAUNTLET

CUSTOM_ADVANCED


18. Orden recomendado de implementación
Los tipos no deben desarrollarse todos simultáneamente.
Orden recomendado:
1. SINGLE_ELIMINATION

2. ROUND_ROBIN

3. GROUP_STAGE

4. LEAGUE

5. SWISS

6. Tipos avanzados


19. Por qué comenzar con Single Elimination
SINGLE_ELIMINATION permitirá validar el concepto más simple de entrada y salida.
Ejemplo:
Competidor A
vs
Competidor B

↓
WINNER
LOSER

La Fase produce dos conjuntos.
Esto permitirá comprobar correctamente el nuevo sistema de puertas.

20. Concepto de entrada de una Fase
Hasta ahora se había hablado principalmente de salidas, pero una Fase también necesita declarar qué participantes puede recibir.
Se utilizará conceptualmente un:
Input Contract


21. Input Contract
El contrato de entrada puede incluir:
Mínimo de participantes

Máximo de participantes

Cantidad exacta

Múltiplo requerido

BYE permitido

Ejemplo:
Minimum:
8

Maximum:
32

Exact:
null

Multiple Of:
null

BYE:
true


22. Ejemplo de entrada exacta
Una fase puede exigir:
exact_participants = 16

Esto significa:
16
✅ válido

15
❌ inválido

17
❌ inválido


23. Entrada por rango
Ejemplo:
mínimo:
8

máximo:
32

Permite:
8
16
20
24
32

si el formato interno puede manejarlo.

24. Entrada por múltiplos
Será especialmente importante en Fases de grupos.
Ejemplo:
participant_multiple = 4

Entonces:
8 ✅
12 ✅
16 ✅
20 ✅

10 ❌
14 ❌


25. BYE
Una Fase puede permitir BYE.
Ejemplo:
14 participantes

Bracket ideal:
16

BYE:
2

Dos participantes pueden avanzar automáticamente.
La Fase solamente define si esta posibilidad está permitida.
El algoritmo de asignación se implementará posteriormente en el Tournament Engine.

26. Funcionamiento interno
Cada Fase tendrá una configuración propia según su tipo.
Conceptualmente:
PhaseTemplate
│
├── Input Contract
│
├── Competition Configuration
│
├── Scoring
│
├── Ranking
│
├── Tie Breakers
│
└── Outputs

No todos los tipos utilizarán todas las secciones.

27. Single Elimination
Una Fase de eliminación directa podrá definir:
Tipo:
SINGLE_ELIMINATION

Match Format:
1 VS 1

Best Of:
1

BYE:
Sí

Draw:
No permitido


28. Salidas de Single Elimination
Una configuración básica puede producir:
WINNERS

LOSERS

Los ganadores y perdedores se identifican por separado.

29. La Fase no decide el destino
Ejemplo:
WINNER

no significa automáticamente:
→ Siguiente ronda

Puede significar:
→ Final

o:
→ División superior

o incluso:
→ Eliminado

Todo depende del Torneo.

30. Torneo invertido
Gracias a esta separación puede construirse un torneo donde los perdedores continúan.
Ejemplo:
A vs B

A gana
B pierde

Salida:
WINNER:
A

LOSER:
B

El Torneo puede conectar:
LOSER
↓
Next Phase

y:
WINNER
↓
END

permitiendo encontrar al “perdedor máximo”.

31. Round Robin
Una Fase ROUND_ROBIN permitirá que todos los participantes compitan contra todos.
Ejemplo:
A
B
C
D

Partidos:
A vs B
A vs C
A vs D

B vs C
B vs D

C vs D


32. Sistema de puntuación
Round Robin requerirá configuración de puntuación.
Ejemplo:
Victory:
3

Draw:
1

Loss:
0

Pero OmniMerge no debe asumir siempre esta puntuación.
Podrá configurarse:
Victory = 2

Draw = 0

Loss = -1

o cualquier esquema permitido.

33. Ranking interno
La Fase puede producir una tabla como:
Posición
Competidor
Puntos
Victorias
Empates
Derrotas
Diferencia

Ejemplo:
1 Naruto      9
2 Sasuke      6
3 Gaara       3
4 Neji        0


34. Tie Breakers
Cuando dos participantes tengan el mismo puntaje deberá existir un sistema de desempate configurable.
Ejemplo:
1. Points

2. Wins

3. Score Difference

4. Head to Head

5. Random

El orden importa.

35. Group Stage
Una Fase GROUP_STAGE permitirá dividir participantes en varios grupos.
Ejemplo:
16 participantes

4 grupos

4 participantes por grupo


36. Funcionamiento interno de grupos
Dentro de cada grupo puede utilizarse:
ROUND_ROBIN

Ejemplo:
Grupo A
A
B
C
D

Grupo B
E
F
G
H

Cada grupo utiliza las mismas reglas.

37. Salidas de una Fase de grupos
Ejemplo:
1.º-2.º
→ QUALIFIED

3.º
→ THIRD

4.º
→ OUT

La Fase solamente produce estos canales.

38. Torneo decide qué hacer con ellos
Por ejemplo:
QUALIFIED
↓
Cuartos de Final


THIRD
↓
Repechaje


OUT
↓
Eliminados


39. Otro Torneo puede reutilizar la misma Fase
La misma PhaseTemplate podría utilizarse así:
QUALIFIED
↓
Gold Bracket


THIRD
↓
Silver Bracket


OUT
↓
Bronze Bracket

No es necesario modificar la Fase.

40. Fase de Liga
Una Fase LEAGUE puede producir clasificación final de una liga o división.
Ejemplo:
1.º
2.º
3.º
...
10.º


41. División con ascenso y descenso
Ejemplo:
Top 2
→ PROMOTION

3.º-8.º
→ STAY

9.º-10.º
→ RELEGATION

Estas son puertas de salida.

42. Destino definido por Torneo
El Torneo puede conectar:
PROMOTION
↓
División A

STAY
↓
División B

RELEGATION
↓
División C


43. División y Temporadas
Debe distinguirse una regla de clasificación de su aplicación temporal.
La regla:
Top 2 → Promotion

pertenece a la Fase.
Pero:
Naruto asciende de División B
a División A en la Temporada 8

pertenece a Universo y Temporadas.
Esto se desarrollará posteriormente.

44. Sistema Suizo
Una Fase SWISS será considerablemente más compleja.
Requerirá conceptos como:
Cantidad de rondas

Puntos

Emparejamientos por score

No repetir rivales

Bye

Floaters

Ranking

Tie Breakers


45. Criterios avanzados de Suizo
Posteriormente podrán considerarse:
Buchholz

Median Buchholz

Sonneborn-Berger

Head-to-head

Performance

No se implementarán durante la primera etapa de Fases.

46. PhaseExit
Una pieza central de la arquitectura será:
PhaseExit

Representará una puerta de salida de una PhaseTemplate.
Relación:
PhaseTemplate
│
├── PhaseExit
├── PhaseExit
└── PhaseExit


47. Datos conceptuales de PhaseExit
Podría almacenar:
id

phase_template_id

code

name

description

selector_type

priority

min_count

max_count

sort_order

settings


48. Selectores de salida
Inicialmente pueden soportarse:
MATCH_WINNERS

MATCH_LOSERS

TOP_N

BOTTOM_N

RANK_POSITION

RANK_RANGE

ALL

REMAINING

Posteriormente:
CUSTOM_RULE


49. Ejemplo: Match Winners
Exit:
WINNERS

Selector:
MATCH_WINNERS

Todos los ganadores salen por esa puerta.

50. Ejemplo: Match Losers
Exit:
LOSERS

Selector:
MATCH_LOSERS

Todos los perdedores salen por esa puerta.

51. Ejemplo: Top N
Exit:
QUALIFIED

Selector:
TOP_N

N:
2

Los dos mejores clasifican.

52. Ejemplo: posición concreta
Exit:
REPECHAGE

Selector:
RANK_POSITION

Position:
3

Solo el tercer lugar utiliza esa salida.

53. Ejemplo: rango
Exit:
SILVER_GROUP

Selector:
RANK_RANGE

From:
5

To:
8

Los participantes en posiciones 5 a 8 salen por ese canal.

54. Ejemplo Remaining
Exit:
OUT

Selector:
REMAINING

Todos los participantes que no hayan sido capturados por otra salida entran allí.

55. Prioridad de salidas
Puede existir una fase donde varias reglas podrían coincidir.
Por ello las salidas podrán tener prioridad.
Ejemplo:
1. TOP_2

2. RANK_POSITION 3

3. REMAINING

Se evalúan en ese orden.

56. Salidas terminales
Una salida no necesariamente debe conectarse a otra Fase.
El Torneo puede marcarla como:
TERMINAL

Ejemplo:
OUT
↓
END


57. Tipos de resultado terminal
Posteriormente un Torneo podrá utilizar terminales como:
CHAMPION

RUNNER_UP

THIRD_PLACE

ELIMINATED

QUALIFIED

RELEGATED

PROMOTED

Estos pertenecen al Torneo, no a la PhaseTemplate.

58. Salidas opcionales
Una Fase puede tener determinadas salidas que un Torneo no necesariamente utilizará.
Ejemplo:
THIRD_PLACE

El Torneo podría decidir:
THIRD_PLACE
↓
Third Place Match

o simplemente:
THIRD_PLACE
↓
END


59. Torneo como grafo
La consecuencia más importante de esta arquitectura es que un Torneo no debe modelarse como una lista de Fases.
Debe modelarse como un:
Grafo dirigido de competición.

60. Ejemplo lineal
Start
↓
Fase A
↓
Fase B
↓
Final

Este es el caso más sencillo.

61. Ejemplo con bifurcación
            ┌── Fase B
Fase A ──────┤
             └── Fase C

Una salida envía participantes a B.
Otra salida a C.

62. Ejemplo con convergencia
Fase A ──┐
         │
         ├── Fase C
         │
Fase B ──┘

Fase C recibe participantes desde dos caminos distintos.

63. Ejemplo completo con repechaje
Fase de grupos
│
├── QUALIFIED ────────────────┐
│                            │
└── THIRD                     │
      ↓                       │
  Repechaje                   │
      │                       │
      └── WINNER ─────────────┤
                              ↓
                           Playoffs


64. Doble eliminación
El sistema puede representar doble eliminación mediante conexiones.
Ejemplo:
Main Bracket
│
├── WINNER
│      ↓
│   Main Bracket
│
└── LOSER
       ↓
   Losers Bracket

Después:
Losers Bracket
│
├── WINNER
│      ↓
│   continúa
│
└── LOSER
       ↓
     END

Finalmente:
Main Winner ───────┐
                   ├── Grand Final
Losers Winner ─────┘


65. Ventaja de esta arquitectura
No es necesario tratar siempre:
DOUBLE_ELIMINATION

como un sistema completamente diferente.
Gran parte puede construirse mediante:
PhaseTemplates
+
PhaseExits
+
Connections


66. Torneo con camino de perdedores
También será posible:
Main Phase
│
├── Winner
│      ↓
│   Main Path
│
└── Loser
       ↓
    Recovery Path

y posteriormente:
Recovery Winner
↓
Main Final


67. Clasificatorias regionales
Un Torneo complejo puede tener varias ramas iniciales.
Ejemplo:
Clasificatoria Sudamérica ────┐
Clasificatoria Europa ────────┤
Clasificatoria Asia ──────────┤
Clasificatoria África ────────┼→ Mundial
Clasificatoria Norteamérica ──┘

Cada clasificatoria puede utilizar una PhaseTemplate distinta.

68. Fases paralelas
El Torneo debe permitir fases simultáneas.
Ejemplo:
         ┌─ Phase A ──┐
Start ────┼─ Phase B ──┼→ Final
          └─ Phase C ──┘


69. Diferencia entre grupos internos y fases paralelas
Si varios grupos utilizan exactamente las mismas reglas:
Grupo A
Grupo B
Grupo C
Grupo D

es preferible que sean gestionados dentro de una única:
GROUP_STAGE

con:
groups = 4


70. Cuándo usar varias Fases
Si los grupos o clasificatorias utilizan reglas diferentes, sí deben ser Nodes independientes.
Ejemplo:
CONMEBOL
→ Round Robin

UEFA
→ Group Stage

Asia
→ Swiss

Todos pueden converger después en:
World Stage


71. Posibles SubTournaments
En el futuro puede plantearse incluso:
SubTournament Node

para situaciones donde una clasificatoria es tan compleja que en realidad constituye otro Torneo.
No se desarrollará actualmente.

72. Punto de inicio
Todo Torneo necesitará definir cómo ingresan inicialmente los participantes.
Puede existir:
START
↓
Phase A


73. Múltiples puntos de inicio
También:
START A
↓
Clasificatoria A


START B
↓
Clasificatoria B


START C
↓
Clasificatoria C

Posteriormente todos convergen.

74. TournamentPhaseConnection
Para conectar Fases se utilizará posteriormente un concepto como:
TournamentPhaseConnection


75. Estructura conceptual de una conexión
Podrá contener:
tournament_template_id

source_node_id

source_exit_id

target_node_id

target_entry

connection_type


76. Funcionamiento conceptual
Ejemplo:
source_node:
Group Stage

source_exit:
QUALIFIED

target_node:
Quarterfinals

Significa:
Group Stage
QUALIFIED
↓
Quarterfinals


77. Conexión a terminal
Una conexión también podrá apuntar a:
TERMINAL

Ejemplo:
LOSERS
↓
ELIMINATED


78. Varias entradas en una Fase
Una Fase puede recibir competidores desde múltiples lugares.
Ejemplo:
Direct Qualifiers ───┐
                     │
Repechage Winners ───┼→ Playoffs
                     │
Seeded Competitors ──┘


79. Entrada múltiple y validación
El sistema deberá calcular cuántos participantes recibe una Phase.
Ejemplo:
8 directos
+
4 repechaje
+
4 sembrados
=
16 participantes


80. Compatibilidad entre conexiones
El Tournament Builder deberá validar que una salida pueda conectarse con la entrada de otra Phase.
Ejemplo:
Phase A produce:
8 participantes

Pero:
Phase B requiere:
16 exactamente

Resultado:
⚠ Conexión incompatible


81. Contratos de salida
Para poder hacer estas validaciones, una Fase deberá proporcionar información sobre sus salidas.
Ejemplo:
WINNERS
≈ 50% de participantes

LOSERS
≈ 50%

Otro:
TOP_2
=
2 × número de grupos

Estas reglas se perfeccionarán progresivamente.

82. Rondas internas
Debe distinguirse entre:
Phase
Round
Match


83. Phase
Bloque competitivo general.
Ejemplo:
Fase de grupos


84. Round
Iteración interna de una Fase.
Ejemplo:
Jornada 1
Jornada 2
Jornada 3

O en eliminación:
Octavos
Cuartos
Semifinal
Final


85. Match
Interacción puntual.
Ejemplo:
Naruto
vs
Sasuke

Por tanto:
Phase
↓
Round
↓
Match


86. Las Rounds no se persistirán todavía
En esta etapa, PhaseTemplate solamente definirá cómo deben generarse.
Posteriormente el Tournament Engine creará instancias reales de:
Round
Match

durante una competición.

87. Fases simples y compuestas
Una Phase puede ser internamente compleja.
Ejemplo:
Group Stage

Puede contener:
4 grupos

Round Robin interno

3 jornadas

Pero para Tournament Builder debe verse simplemente como:
[ GROUP STAGE ]

El Torneo no necesita conocer todos los detalles internos.

88. El Torneo solamente consume resultados
El Torneo recibe:
QUALIFIED
THIRD
OUT

La lógica de cómo se obtuvieron pertenece a la Fase.

89. Tournament Graph Builder
Después de completar Phase Designer se desarrollará el constructor del grafo del Torneo.
Permitirá:
Agregar Fase

pero en lugar de crear una nueva Fase desde cero mostrará:
Seleccionar PhaseTemplate


90. Ejemplo de selección
Mis Fases

○ Eliminación directa básica

○ Grupos de cuatro

○ Round Robin

○ Liga divisional

Al seleccionar una:
TournamentPhaseNode

será creado.

91. Reutilización dentro de un mismo Torneo
La misma PhaseTemplate puede utilizarse varias veces.
Ejemplo:
PhaseTemplate:
Eliminación directa básica

puede aparecer como:
Octavos

Cuartos

Semifinal

Final

cada una como un Node distinto.

92. Arquitectura conceptual final
PhaseTemplate
│
├── Input Contract
├── Internal Configuration
└── PhaseExits
        │
        │
        ▼
TournamentTemplate
│
├── TournamentPhaseNode
│      └── PhaseTemplate
│
├── TournamentPhaseNode
│      └── PhaseTemplate
│
└── TournamentPhaseConnections


93. Tournament Builder como editor visual
El objetivo futuro será ofrecer una interfaz visual.
Conceptualmente:
┌───────────────────┐
│ Fase de grupos    │
│                   │
│ QUALIFIED ●───────┼────────────┐
│ THIRD     ●───────┼──────┐     │
│ OUT       ●       │      │     │
└───────────────────┘      │     │
                           │     │
                           ▼     ▼
                    ┌─────────┐ ┌─────────┐
                    │Repechaje│ │Playoffs │
                    └────┬────┘ └────┬────┘
                         │           │
                         └─────┬─────┘
                               ▼
                             Final


94. Primera versión del Builder
No se desarrollará inicialmente mediante drag & drop.
Primero se utilizarán selectores.
Ejemplo:
Fase:
Group Stage


Salida:
QUALIFIED

Destino:
[ Playoffs ▼ ]


Salida:
THIRD

Destino:
[ Repechaje ▼ ]


Salida:
OUT

Destino:
[ Eliminated ▼ ]

Cuando la arquitectura funcione correctamente se podrá convertir en editor gráfico.

95. Ciclos
Los sistemas de divisiones introducen posibles ciclos.
Ejemplo:
Division B
↓
STAY
↓
Division B

Sin embargo, normalmente este ciclo ocurre en otra temporada.
Por ello en el futuro deberá diferenciarse:
INTRA_TOURNAMENT

de:
NEXT_SEASON


96. Intra Tournament
Conexión que sucede durante la misma competición.
Ejemplo:
Group Stage
↓
Playoffs


97. Next Season
Conexión que cambia la posición del competidor para una temporada futura.
Ejemplo:
Division B
PROMOTION
↓
Division A
next season

Esta funcionalidad corresponderá en gran parte al módulo Universo.

98. Separación con Universo
La Fase puede producir:
PROMOTION

pero no debe modificar directamente:
UniverseCompetitor

La actualización real se ejecutará posteriormente en:
Universe
Season


99. Separación con Simulation Engine
Phase Designer tampoco determina automáticamente quién gana una interacción concreta.
La Fase puede decir:
Match
1v1

Winner:
WINNER Exit

Loser:
LOSER Exit

Pero el resultado puede venir de:
Manual

Random Test

Simulation Engine


100. Separación de responsabilidades
La arquitectura completa será:
PHASE

Define reglas internas


TOURNAMENT

Conecta Fases


COMPETITION ENGINE

Ejecuta las estructuras


UNIVERSE

Proporciona contexto,
competidores y temporadas


SIMULATION ENGINE

Resuelve enfrentamientos


101. Competition Lab
El Competition Lab se mantendrá en el módulo, pero su desarrollo completo se pospondrá.
Actualmente funcionará como espacio conceptual.
Posteriormente permitirá probar:
Phase Graph

Mock Participants

Library Entities

Manual Results

Random Test Resolver


102. Cuándo construir Competition Lab completo
Se recomienda hacerlo después de tener:
PhaseTemplate

PhaseExit

TournamentPhaseNode

TournamentPhaseConnection

porque entonces ya existirá una estructura real que ejecutar.

103. Comunidad de Fases
Al igual que las futuras plantillas de Torneos, PhaseTemplate podrá integrarse posteriormente con Comunidad.
Un usuario podrá publicar una Fase:
Eliminación directa avanzada

Otro usuario podrá encontrarla y copiarla.

104. Clonación
Conceptualmente:
Usuario A
↓
PhaseTemplate pública
↓
Comunidad
↓
Usuario B
↓
Clonar
↓
PhaseTemplate independiente


105. Información de procedencia
Podrá almacenarse:
source_phase_template_id

source_user_id

cloned_at


106. Copia independiente
La copia deberá incluir:
PhaseTemplate

PhaseExits

Configuración interna

Scoring

Ranking

Tie Breakers

según las funcionalidades disponibles.

107. Estado y visibilidad
PhaseTemplate podrá utilizar estados similares a otros recursos:
DRAFT

ACTIVE

ARCHIVED

Visibilidad:
PRIVATE

PUBLIC

UNLISTED


108. Soft Deletes
También puede utilizar eliminación lógica:
deleted_at

para mantener coherencia con el resto de OmniMerge.

109. Sprint actual recomendado
El siguiente Sprint se denominará:
Sprint T1 — Phase Designer Foundation

110. Objetivo del Sprint
Crear el sistema base que permita definir Fases reutilizables antes de seguir ampliando Tournament Builder.
Al finalizar deberá ser posible:
Torneos
↓
Fases
↓
Crear Fase
↓
Configurar entrada
↓
Configurar comportamiento básico
↓
Crear puertas
↓
Guardar
↓
Reutilizar posteriormente


111. Backend del Sprint
Se crearán inicialmente conceptos como:
PhaseTemplate

PhaseExit

junto con:
Models

Migrations

Controllers

Form Requests

Policies

Services

Relationships


112. Frontend del Sprint
Se crearán vistas como:
Fases Dashboard

Listado

Crear

Editar

Detalle

Dentro del detalle:
Resumen

Configuración

Salidas

Posteriormente:
Puntuación

Desempates

Pruebas


113. Primera Phase a implementar completamente
Será:
SINGLE_ELIMINATION


114. Configuración inicial Single Elimination
Ejemplo:
Nombre:
Eliminación directa básica

Input:
2–128

Best Of:
1

BYE:
Sí


115. Outputs iniciales
WINNERS

Selector:
MATCH_WINNERS

LOSERS

Selector:
MATCH_LOSERS

Con esto se validará el sistema de puertas.

116. Segundo tipo
Después:
ROUND_ROBIN

Permitirá validar:
Scoring

Ranking

TOP_N

BOTTOM_N

Tie Breakers


117. Tercer tipo
Después:
GROUP_STAGE

Permitirá validar:
Subgrupos

Round Robin interno

Clasificación por grupo

Salidas múltiples


118. Cuarto tipo
Después:
LEAGUE

Permitirá validar:
PROMOTION

STAY

RELEGATION


119. Quinto tipo
Después:
SWISS

Permitirá validar:
Rondas dinámicas

Emparejamiento

Ranking

Historial de rivales

Tie Breakers avanzados


120. Sprint posterior
Después de Phase Designer se desarrollará:
Sprint T2 — Tournament Graph Foundation
Incluirá:
TournamentPhaseNode

TournamentPhaseConnection

TournamentTerminal

TournamentStart


121. Sprint Tournament Builder
Posteriormente:
Agregar PhaseTemplate

Crear Nodes

Conectar salidas

Validar entradas

Crear bifurcaciones

Crear convergencias


122. Sprint Competition Lab
Después:
Mock Participants

Library Entities

TournamentParticipant

TournamentMatch

TournamentResult

Manual Result

Random Test Resolver


123. Tipos avanzados
Posteriormente:
League Divisions

Swiss

Double Elimination

Advanced Qualification

Custom


124. Relación con las tablas actuales
Actualmente existe:
tournament_phases

ligada directamente a:
tournament_templates

Con la nueva arquitectura esta estructura ya no será suficiente como definición reutilizable.
No se recomienda seguir ampliándola como si fuera PhaseTemplate.

125. Evolución de la tabla existente
En el futuro conceptual podría transformarse en:
tournament_phase_nodes

mientras se crea:
phase_templates

como catálogo independiente.

126. Nuevas tablas conceptuales
phase_templates

phase_exits

Posteriormente:
tournament_phase_nodes

tournament_phase_connections

tournament_terminals


127. No utilizar migrate:fresh
La transición se realizará mediante migraciones normales.
No se eliminará toda la base de datos para implementar este cambio.
Se debe proteger el contenido existente de OmniMerge.

128. Ejemplo completo: Copa América
La estructura podría representarse:
Tournament:
Copa América

START
↓
Group Stage
│
├── QUALIFIED
│      ↓
│   Quarterfinal
│
└── OUT
       ↓
     END


Quarterfinal
│
├── WINNER
│      ↓
│   Semifinal
│
└── LOSER
       ↓
     END


Semifinal
│
├── WINNER
│      ↓
│     Final
│
└── LOSER
       ↓
   Third Place Match


Final
│
├── WINNER
│      ↓
│   CHAMPION
│
└── LOSER
       ↓
   RUNNER_UP


129. Ejemplo con repechaje
Group Stage
│
├── TOP_2
│      ↓
│   Main Bracket
│
├── THIRD
│      ↓
│   Repechage
│      │
│      └── WINNER
│             ↓
│         Main Bracket
│
└── OUT
       ↓
     END


130. Ejemplo divisional
Division B
│
├── PROMOTION
│      ↓
│   Division A
│   next season
│
├── STAY
│      ↓
│   Division B
│   next season
│
└── RELEGATION
       ↓
    Division C
    next season


131. Ejemplo invertido
Reverse Elimination
│
├── WINNERS
│      ↓
│     END
│
└── LOSERS
       ↓
    Next Reverse Round

El último competidor que continúa será el perdedor definitivo.

132. Ejemplo clasificación mundial
CONMEBOL Qualifier ─────┐
UEFA Qualifier ─────────┤
AFC Qualifier ──────────┤
CAF Qualifier ──────────┼→ World Stage
CONCACAF Qualifier ─────┘

Cada región puede utilizar reglas completamente distintas.

133. Principios que se deben conservar
Principio 1
Una Fase debe ser reutilizable.

Principio 2
Una Fase no conoce su siguiente Fase.

Principio 3
Una salida identifica competidores, no destinos.

Principio 4
El Torneo conecta las salidas.

Principio 5
Un Torneo es un grafo, no una lista.

Principio 6
Una PhaseTemplate puede utilizarse varias veces dentro de un mismo Torneo.

Principio 7
Las Fases pueden tener varias entradas.

Principio 8
Las Fases pueden tener múltiples salidas.

Principio 9
Varias ramas pueden volver a converger.

Principio 10
Una salida puede terminar la participación.

Principio 11
Las reglas temporales de ascenso y descenso pertenecen principalmente al Universo.

Principio 12
La resolución de combates pertenece al futuro Simulation Engine.

134. Recorrido general actualizado
El desarrollo se reorganiza de la siguiente manera:
T1
PHASE DESIGNER FOUNDATION
────────────────────────

PhaseTemplate
PhaseExit
Input Contract
CRUD
Sidebar
Configuración básica


T2
SINGLE ELIMINATION
────────────────────────

Winner
Loser
BYE
Best Of


T3
ROUND ROBIN
────────────────────────

Scoring
Ranking
Top N
Bottom N
Tie Breakers


T4
GROUP STAGE
────────────────────────

Groups
Distribution
Internal Round Robin
Multiple Outputs


T5
TOURNAMENT GRAPH FOUNDATION
────────────────────────

TournamentPhaseNode
TournamentPhaseConnection
Start
Terminal


T6
TOURNAMENT BUILDER
────────────────────────

Agregar Fases
Conectar salidas
Bifurcar
Converger
Validar caminos


T7
COMPETITION LAB
────────────────────────

Mock Participants
Library Entities
Execution
Manual Results
Random Test Resolver


T8
ADVANCED PHASES
────────────────────────

League
Divisions
Promotion
Relegation

Swiss

Double Elimination


135. Etapas futuras
Después de estabilizar Torneos:
UNIVERSE

utilizará estas estructuras.
Posteriormente:
SIMULATION ENGINE

resolverá enfrentamientos.

136. Arquitectura completa futura
BIBLIOTECA

Entity
EntityVersion
Attributes
Collections
        │
        ▼

PHASE LIBRARY

PhaseTemplate
│
├── Input Contract
├── Internal Rules
├── Scoring
├── Ranking
└── PhaseExits
        │
        ▼

TOURNAMENT BUILDER

TournamentTemplate
│
├── Phase Nodes
├── Connections
├── Start Points
└── Terminals
        │
        ▼

COMPETITION LAB

Temporary Participants
Temporary Matches
Manual Results
Random Results
        │
        ▼

UNIVERSE

Competitors
Seasons
Tournament Definitions
Tournament Instances
        │
        ▼

SIMULATION ENGINE

Automatic Match Resolution


137. Objetivo final
El objetivo no será simplemente permitir elegir:
Tipo de torneo:
Eliminación directa

sino construir un sistema donde el usuario pueda diseñar prácticamente cualquier estructura competitiva.
Ejemplo:
Inicio
│
├── Región A
│   └── Round Robin
│
├── Región B
│   └── Swiss
│
└── Región C
    └── Group Stage
        │
        ▼
Clasificación Global
│
├── Top 8
│      ↓
│   Main Bracket
│
├── 9–16
│      ↓
│   Repechage
│      │
│      └── Winners
│             ↓
│         Main Bracket
│
└── Remaining
       ↓
     Eliminated
        │
        ▼
Semifinal
│
├── Winner
│      ↓
│    Final
│
└── Loser
       ↓
   Third Place

Todo construido utilizando componentes reutilizables.

138. Conclusión
La decisión de desarrollar primero Fases mejora significativamente la arquitectura del módulo Torneos.
En lugar de programar cada tipo de Torneo como una estructura cerrada, OmniMerge construirá un sistema compuesto por piezas reutilizables.
Las PhaseTemplate definirán cómo funciona una etapa competitiva.
Las PhaseExit definirán qué grupos de competidores salen de ella.
Los futuros TournamentPhaseNode representarán la utilización de esas Fases dentro de un Torneo.
Las TournamentPhaseConnection definirán cómo se conectan las salidas con otros Nodes.
Como consecuencia, un Torneo se modelará como un grafo dirigido capaz de contener recorridos lineales, bifurcaciones, convergencias, repechajes, clasificaciones regionales, doble eliminación, divisiones, ascensos, descensos y sistemas competitivos invertidos.
La arquitectura principal será:
PHASE TEMPLATE
¿Qué ocurre aquí?

        ↓

PHASE EXITS
¿Quién sale de aquí?

        ↓

TOURNAMENT GRAPH
¿A dónde va cada salida?

        ↓

COMPETITION ENGINE
¿Qué ocurre durante la ejecución?

        ↓

UNIVERSE
¿Quién participa y cuándo?

        ↓

SIMULATION ENGINE
¿Cómo se resuelve cada enfrentamiento?

La siguiente etapa de desarrollo deberá concentrarse exclusivamente en:
SPRINT T1
PHASE DESIGNER FOUNDATION

construyendo correctamente la Biblioteca de Fases y sus puertas antes de continuar con el grafo de Torneos.
De esta forma OmniMerge podrá soportar sistemas competitivos simples y extremadamente complejos sin quedar atado a una estructura específica de Torneo.

