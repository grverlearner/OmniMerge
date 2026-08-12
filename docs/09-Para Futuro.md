PLANIFICACIÓN DE LOS MÓDULOS DE TORNEOS, UNIVERSOS, COMUNIDAD Y SIMULACIÓN DE OMNIMERGE
1. Introducción
OmniMerge ha sido planteado como una plataforma flexible donde los usuarios pueden crear, organizar, versionar, relacionar y reutilizar diferentes tipos de entidades mediante una Biblioteca configurable basada en Entidades, Versiones, Atributos, Catálogos, Colecciones y reglas contextuales.
La siguiente etapa del proyecto consiste en avanzar desde la creación y organización de información hacia la utilización de dicha información dentro de sistemas dinámicos. Para ello se plantean tres grandes módulos futuros estrechamente relacionados:
Torneos.
Universos.
Simulación.
Estos módulos se complementarán además con el sistema de Comunidad ya existente, permitiendo que los usuarios no solamente compartan Entidades, Atributos, Catálogos o Colecciones, sino también sistemas completos de competición creados por ellos.
La arquitectura debe desarrollarse evitando que estos conceptos se mezclen entre sí.
La idea fundamental será:
Torneos define cómo funciona una competición. Universo define dónde, cuándo, con quiénes y bajo qué contexto ocurre dicha competición. Simulación determina cómo se resuelven automáticamente las interacciones que ocurren dentro de ella.
De esta manera, OmniMerge podrá evolucionar desde una Biblioteca de información hacia una plataforma capaz de construir ecosistemas completos y reutilizables.

2. Visión general de los módulos
La arquitectura conceptual futura puede entenderse de la siguiente manera:
BIBLIOTECA
¿Qué elementos existen?
│
├── Entidades
├── Versiones
├── Atributos
├── Catálogos
├── Colecciones
└── Relaciones
        │
        │
        ▼
TORNEOS
¿Cómo funciona una competición?
│
├── Plantillas
├── Fases
├── Formatos
├── Reglas
├── Clasificación
├── Emparejamientos
├── Premios
└── Laboratorio de pruebas
        │
        │
        ▼
UNIVERSOS
¿Dónde ocurre todo?
│
├── Competidores
├── Temporadas
├── Torneos programados
├── Reglas propias
├── Elegibilidad
├── Premios
├── Ranking
└── Historial
        │
        │
        ▼
SIMULACIÓN
¿Cómo se resuelven las interacciones?
│
├── Combates
├── Resultados
├── Eventos
├── Modificadores
└── Resolución automática

Además:
COMUNIDAD
        │
        ├── Compartir Entidades
        ├── Compartir Colecciones
        ├── Compartir Atributos
        ├── Compartir Catálogos
        └── Compartir Plantillas de Torneos


3. Separación de responsabilidades
Uno de los principios más importantes será mantener claramente separadas las responsabilidades.
3.1 Biblioteca
La Biblioteca responde:
¿Qué elementos existen?
Aquí se encuentran:
Entity.
EntityVersion.
EntityType.
Attribute.
AttributeOption.
AttributeGroup.
Collection.
Ejemplo:
Naruto Uzumaki
Sasuke Uchiha
Sakura Haruno
Gaara
Kakashi Hatake

Estas Entidades existen independientemente de cualquier Universo o Torneo.

3.2 Torneos
El módulo Torneos responde:
¿Cómo debe funcionar una competición?
Aquí el usuario diseña sistemas reutilizables.
Ejemplo:
Copa Eliminación Clásica

Participantes:
16

Formato:
Eliminación directa

Rondas:
Octavos
Cuartos
Semifinal
Final

Tercer puesto:
Sí

BYE:
Permitido

Premios:
1.º Oro
2.º Plata
3.º Bronce

Esto todavía no representa una competición histórica real.
Es solamente una plantilla.

3.3 Universo
El módulo Universo responde:
¿Dónde ocurre la competición, quién participa y bajo qué contexto?
Ejemplo:
Universo Naruto

Temporada 8

Torneo:
Exámenes Chūnin

Plantilla:
Copa Eliminación Clásica

Elegibilidad:
Aldea = Konoha

Participantes:
16

Premio:
Copa Hokage

Recurrencia:
Cada 2 temporadas

Aquí la plantilla de Torneo deja de ser únicamente una definición y pasa a utilizarse dentro de un contexto concreto.

3.4 Simulación
El módulo de Simulación responde:
¿Cómo se determina lo que ocurre en una interacción?
Por ejemplo:
Naruto
VS
Sasuke

El Torneo solamente necesita recibir:
Ganador: Naruto
Perdedor: Sasuke

No necesita conocer internamente cómo se calculó ese resultado.
Posteriormente el Motor de Simulación podrá analizar:
atributos;
versiones;
condiciones;
estadísticas;
contexto;
reglas;
modificadores;
aleatoriedad.
Y devolver el resultado al motor de Torneos.

4. Principio principal del desarrollo
Aunque Torneos y Universos están relacionados, no deben implementarse simultáneamente desde el inicio.
La estrategia recomendada será:
DISEÑAR TORNEOS Y UNIVERSOS
        ↓
IMPLEMENTAR TORNEOS
        ↓
CREAR LABORATORIO DE PRUEBAS
        ↓
ESTABILIZAR MOTOR DE COMPETICIÓN
        ↓
IMPLEMENTAR UNIVERSOS
        ↓
CONECTAR AMBOS MÓDULOS
        ↓
IMPLEMENTAR SIMULACIÓN

Por este motivo no se recomienda crear inicialmente todas las tablas relacionadas con Universos, Temporadas, Rankings o Simulaciones.
Primero debe estabilizarse el motor de Torneos.

5. Módulo Torneos
El apartado visible al usuario podrá llamarse simplemente:
Torneos
Internamente puede concebirse como:
Tournament Designer

Su finalidad será permitir diseñar sistemas completos de competición reutilizables.

6. TournamentTemplate
El elemento principal será:
TournamentTemplate

Representará una plantilla reutilizable de competición.
Ejemplo:
Nombre:
Copa Eliminación Clásica

Descripción:
Sistema competitivo mediante eliminación directa.

Participantes mínimos:
8

Participantes máximos:
32

Estado:
ACTIVE

Visibilidad:
PUBLIC

Podrá almacenar información como:
usuario creador;
código;
nombre;
slug;
descripción;
imagen;
estado;
visibilidad;
cantidad mínima de participantes;
cantidad máxima de participantes;
configuración general;
información de clonación;
estadísticas comunitarias;
metadata.
Estados posibles:
DRAFT
ACTIVE
ARCHIVED

Visibilidad:
PRIVATE
PUBLIC


7. Una plantilla no debe depender de un único tipo de torneo
No se recomienda utilizar únicamente una estructura como:
type = SINGLE_ELIMINATION

porque una competición puede contener diferentes formatos.
Ejemplo:
Fase de grupos
        ↓
Octavos
        ↓
Cuartos
        ↓
Semifinal
        ↓
Final

Por ello la arquitectura debe seguir el principio:
Un Torneo es una composición de Fases.

8. TournamentPhase
Cada plantilla podrá contener múltiples fases.
Ejemplo:
Copa Mundial
│
├── Fase 1
│   GROUP_STAGE
│
├── Fase 2
│   SINGLE_ELIMINATION
│
├── Fase 3
│   SINGLE_ELIMINATION
│
└── Fase 4
    SINGLE_ELIMINATION

Cada fase podrá almacenar:
nombre;
descripción;
orden;
tipo;
participantes de entrada;
participantes clasificados;
reglas;
configuración;
criterios de clasificación;
desempates.

9. Tipos de fase
Los primeros tipos considerados serán:
SINGLE_ELIMINATION
ROUND_ROBIN
GROUP_STAGE
SWISS

Posteriormente:
DOUBLE_ELIMINATION
LEAGUE
LADDER
FREE_FOR_ALL
CUSTOM

No se recomienda implementar todos desde el comienzo.

10. Primera implementación: Eliminación directa
El primer formato debe ser:
SINGLE_ELIMINATION

Ejemplo:
8 participantes

Cuartos
├── A vs B
├── C vs D
├── E vs F
└── G vs H

Semifinal
├── Ganador 1 vs Ganador 2
└── Ganador 3 vs Ganador 4

Final
└── Ganador semifinal 1
    vs
    Ganador semifinal 2

Este formato permitirá validar inicialmente:
generación de llaves;
participantes;
rondas;
matches;
avance;
ganador;
perdedor;
BYEs;
clasificación;
transición entre rondas;
premios.

11. BYE
El sistema deberá contemplar participantes que avanzan automáticamente cuando la cantidad de participantes no coincide con una llave ideal.
Ejemplo:
14 participantes

Llave requerida:
16

BYE:
2

La plantilla deberá definir:
BYE permitido
BYE no permitido


12. Seeding
El orden inicial de los participantes también debe ser configurable.
Métodos posibles:
RANDOM
MANUAL
RANKING
ATTRIBUTE
UNIVERSE_RANKING

Ejemplo:
Ranking

1. Naruto
2. Sasuke
3. Gaara
4. Neji

El sistema podrá distribuirlos siguiendo criterios de siembra.

13. Round Robin
Después de estabilizar eliminación directa se implementará:
ROUND_ROBIN

Ejemplo:
Grupo A

Naruto
Sasuke
Gaara
Neji

Todos participan contra todos.
Esto requerirá estadísticas como:
Played
Wins
Draws
Losses
Points
Score


14. Fase de grupos
Posteriormente:
GROUP_STAGE

Ejemplo:
32 participantes
        ↓
8 grupos
        ↓
4 por grupo
        ↓
clasifican 2
        ↓
16 participantes
        ↓
Eliminación directa

Aquí se comprobará realmente la capacidad de OmniMerge de conectar distintos tipos de fase.

15. Sistema suizo
Después se podrá implementar:
SWISS

Este sistema deberá considerar:
número de rondas;
puntos;
ranking;
emparejamientos;
evitar rivales repetidos;
historial de enfrentamientos;
criterios de desempate;
Buchholz;
clasificación final.
Debido a su complejidad no debe ser uno de los primeros formatos implementados.

16. Doble eliminación
Posteriormente podrá existir:
DOUBLE_ELIMINATION

con:
Winners Bracket
Losers Bracket
Grand Final

Esto podrá construirse una vez que el motor de Matches y transiciones esté suficientemente estable.

17. TournamentPhaseTransition
Las fases deben poder conectarse mediante reglas.
Ejemplo:
Fase de grupos
        ↓
Top 2 de cada grupo
        ↓
Octavos

Conceptualmente:
TournamentPhase
        ↓
TournamentPhaseTransition
        ↓
Next TournamentPhase

Esto evitará codificar reglas específicas directamente en PHP.

18. Participantes mínimos y máximos
Las plantillas podrán definir distintas políticas.
Cantidad fija
16 participantes

Rango
mínimo = 8
máximo = 32

Flexible
mínimo = 4
máximo = ilimitado

Las fases deberán poder validar si la cantidad seleccionada es compatible.

19. Tipo de combate
La plantilla podrá configurar cómo se estructura un enfrentamiento.
Ejemplos:
1 VS 1
2 VS 2
3 VS 3
TEAM VS TEAM
FREE FOR ALL

También:
BEST OF 1
BEST OF 3
BEST OF 5

Sin embargo, esto no significa que Torneos determine automáticamente quién gana.

20. Separación entre Torneo y Simulación
El Torneo solamente administra la estructura competitiva.
Ejemplo:
Match

Naruto
VS
Sasuke

El resultado puede proceder de:
MANUAL
RANDOM
SIMULATION
EXTERNAL

Por tanto se puede plantear conceptualmente un:
MatchResultProvider

El Tournament Engine solamente recibe el resultado.

21. Competition Lab
Dentro del módulo Torneos existirá un espacio especial destinado a probar las plantillas.
Puede llamarse:
Competition Lab

o visualmente:
Competición de prueba

Este apartado será muy importante antes de desarrollar Universo.

22. Finalidad del laboratorio
Permitirá comprobar si una plantilla funciona correctamente sin crear información persistente de Universo.
Ejemplo:
PROBAR TORNEO

Plantilla:
Copa Eliminación Clásica

Participantes:
8

Fuente:

○ Mi Biblioteca
○ Participantes ficticios
○ Mezclar ambos


23. Participantes de Biblioteca
El usuario podrá seleccionar temporalmente Entidades ya existentes.
Ejemplo:
Naruto
Sasuke
Gaara
Neji
Rock Lee
Kakashi
Itachi
Minato

La prueba no deberá modificar dichas Entidades.

24. Participantes ficticios
También podrán crearse participantes únicamente para probar la estructura.
Ejemplo:
Competidor A
Competidor B
Competidor C
Competidor D
Competidor E
Competidor F
Competidor G
Competidor H

Estos participantes:
no se guardarán como Entity;
no aparecerán en Biblioteca;
no generarán historial;
no generarán estadísticas;
desaparecerán al finalizar la prueba.

25. Pruebas no persistentes
Inicialmente los datos del Competition Lab podrán mantenerse:
en memoria;
mediante Alpine/JavaScript;
mediante Session;
o mediante estructuras temporales.
No deberían generar registros oficiales de:
TournamentInstance
Universe
History
Statistics


26. Resolución manual de pruebas
El usuario podrá indicar manualmente el ganador.
Ejemplo:
Naruto vs Sasuke

[ Naruto gana ]
[ Sasuke gana ]

El Tournament Engine:
recibe ganador
        ↓
actualiza llave
        ↓
genera siguiente ronda


27. Random Test Resolver
También podrá existir:
Simular ronda

pero inicialmente no utilizará atributos ni inteligencia.
Simplemente seleccionará resultados aleatorios para probar el funcionamiento técnico del torneo.
Debe identificarse claramente como:
Random Test Resolver

y no confundirse con el futuro Simulation Engine.

28. TournamentParticipant
El motor de Torneos no debería depender directamente de Entity.
Debe existir conceptualmente:
TournamentParticipant

Esto permitirá que un participante pueda proceder de distintas fuentes.
Ejemplo:
ENTITY
MOCK
UNIVERSE_COMPETITOR
TEAM

Esto hace posible reutilizar el mismo motor para:
Competition Lab;
Universe;
equipos;
participantes ficticios.

29. Equipos
Aunque inicialmente se trabaje con competencias individuales, la arquitectura debe dejar preparada la posibilidad de equipos.
Ejemplo:
Participant
│
├── INDIVIDUAL
└── TEAM

Posteriormente podrían existir:
Equipo 7
VS
Equipo 10

sin tener que modificar completamente la estructura del motor.

30. Sistema de Recompensas
Las recompensas también deberán ser reutilizables.
Se propone:
RewardTemplate

Ejemplos:
Copa Hokage
Medalla de Oro
500 puntos
Título Campeón
Objeto especial
Recompensa monetaria


31. RewardTemplate
Podrá almacenar:
nombre;
descripción;
imagen;
tipo;
valor;
configuración;
metadata;
propietario;
visibilidad.
Tipos posibles:
TROPHY
MEDAL
POINTS
TITLE
ITEM
CURRENCY
CUSTOM


32. TournamentRewardSlot
La plantilla de torneo podrá definir posiciones premiadas.
Ejemplo:
CHAMPION
        ↓
Copa de Oro

RUNNER_UP
        ↓
Medalla de Plata

THIRD_PLACE
        ↓
Medalla de Bronce

El Universo posteriormente podrá modificar estas asignaciones.

33. Configuración modificable dentro del Universo
Las plantillas podrán definir qué propiedades pueden sobrescribirse.
Ejemplo:
Formato:
SINGLE_ELIMINATION
🔒 LOCKED

Participantes:
8–32
✎ OVERRIDABLE

Premios:
✎ OVERRIDABLE

Best Of:
3
🔒 LOCKED

Esto permitirá crear plantillas flexibles sin perder sus reglas esenciales.

34. Overrides
La configuración tendrá varias capas.
TournamentTemplate
        ↓
Configuración por defecto

UniverseTournament
        ↓
Overrides

TournamentInstance
        ↓
Configuración final congelada

Ejemplo:
Plantilla:
Participantes:
16

Premio:
100 puntos

Universo:
Participantes:
32

Premio:
500 puntos

Instancia final:
32 participantes
500 puntos


35. Versionado de plantillas
Las plantillas deben contar con una estrategia para conservar su historial.
Ejemplo:
Temporada 1 utiliza:
Copa Ninja v1

Después el creador modifica la plantilla:
Copa Ninja v2

El torneo de la Temporada 1 no debe cambiar automáticamente.
Por ello se recomienda utilizar:
TournamentTemplateRevision

o snapshots.

36. Snapshot de TournamentInstance
Cuando un Universo genere una competición real:
TournamentTemplate
        ↓
Revision actual
        ↓
TournamentInstance

se guardará una copia congelada de su configuración.
Ejemplo:
{
    "participants": 16,
    "phases": [],
    "rules": {},
    "rewards": {}
}

Esto permitirá conservar correctamente el historial.

37. Comunidad aplicada a Torneos
El módulo Comunidad deberá ampliarse para incluir las plantillas de Torneos.
Actualmente Comunidad permite compartir diferentes recursos de Biblioteca.
A futuro deberá poder mostrar también:
TournamentTemplate


38. Explorar plantillas públicas
Los usuarios podrán navegar por:
Comunidad
        ↓
Torneos

y encontrar plantillas creadas por otras personas.
Ejemplo:
Copa Eliminación Clásica
Creado por Usuario A

16–32 participantes
Eliminación directa
4 fases

♡ 152
⧉ 78 copias
👁 1,520 vistas

[ Ver plantilla ]
[ Copiar a mi Biblioteca ]


39. Clonación de TournamentTemplate
Cuando otro usuario copie una plantilla:
Usuario A
        ↓
crea
        ↓
Copa Eliminación Clásica
        ↓
PUBLIC
        ↓
COMUNIDAD
        ↓
Usuario B
        ↓
Copiar
        ↓
TournamentTemplate independiente

La copia no deberá depender de la plantilla original.

40. Información de procedencia
Al igual que otros recursos públicos, se podrá conservar información sobre el origen.
Ejemplo:
source_tournament_template_id
source_user_id
cloned_at

Esto permitirá conocer:
quién creó el original;
cuántas veces fue clonado;
cuál fue la fuente;
estadísticas comunitarias.

41. Qué debe copiarse
Al clonar una plantilla se deberá copiar también su estructura.
Ejemplo:
TournamentTemplate
│
├── TournamentPhases
├── PhaseTransitions
├── Rules
├── RewardSlots
└── Configuración

La copia resultante pertenecerá completamente al nuevo usuario.

42. RewardTemplates en Comunidad
También podrá considerarse posteriormente compartir:
RewardTemplate

Ejemplo:
Copa Hokage
Medalla Mundial
Trofeo de Campeones
Sistema de puntos competitivo

Otro usuario podría copiar estas recompensas y utilizarlas dentro de sus propios Torneos o Universos.

43. Futuro Marketplace conceptual
Sin necesariamente convertirse en una tienda, Comunidad podrá funcionar como un repositorio de contenido reutilizable.
Ejemplo:
COMUNIDAD
│
├── Entidades
├── Colecciones
├── Atributos
├── Catálogos
├── Plantillas de Torneos
├── Recompensas
└── posteriormente:
    └── Plantillas de Universo

Esto fortalecerá uno de los principios principales de OmniMerge:
crear una vez y reutilizar muchas veces.

44. Universos
Después de estabilizar Torneos comenzará la implementación de:
Universe

Un Universo será un espacio independiente donde las Entidades adquieren contexto competitivo e histórico.

45. Universe
Podrá almacenar:
usuario;
código;
nombre;
descripción;
imagen;
estado;
visibilidad;
configuración;
temporada actual;
metadata.
Ejemplo:
Universo Shinobi

Temporada:
12

Competidores:
184

Torneos configurados:
7

Torneos realizados:
35


46. La Biblioteca no se copia dentro del Universo
Una Entity continuará siendo canónica.
Ejemplo:
Entity
Naruto Uzumaki
        ↓
se agrega a
        ↓
Universe
        ↓
UniverseCompetitor

No se creará otra Entity.

47. UniverseCompetitor
Este concepto permitirá almacenar la situación de una Entity dentro de un Universo concreto.
Ejemplo:
Naruto Uzumaki

Universo A
├── Ranking: 1
├── Puntos: 950
├── Victorias: 50
└── Campeonatos: 8

Universo B
├── Ranking: 16
├── Puntos: 200
├── Victorias: 5
└── Campeonatos: 0

La Entity continúa siendo la misma.

48. Representación de un competidor
Posteriormente un UniverseCompetitor podrá decidir qué representación de una Entity utilizar.
Posibles políticas:
ORIGINAL
BASE_ACTIVE
RESOLVER_DEFAULT
SPECIFIC_VERSION
AUTO

Ejemplo:
Universo Naruto Clásico
Naruto → Niño

Universo Shippuden
Naruto → Shippuden

Universo Boruto
Naruto → Adulto

Esta funcionalidad puede desarrollarse después de la primera versión del módulo Universo.

49. Agregar competidores mediante filtros
El sistema actual de Atributos será una pieza fundamental.
Ejemplo:
Agregar participantes al Universo

Condiciones:

Anime = Naruto

AND

Tipo = Personaje

Resultado:
185 Entidades encontradas

El usuario podrá seleccionar cuáles agregar.

50. Elegibilidad para un Torneo
Los filtros también podrán utilizarse dentro de un torneo de Universo.
Ejemplo:
Universo Naruto

Competidores:
500

Torneo:
Exámenes Chūnin

Elegibilidad:

Aldea = Konoha

AND

Rango = Genin

Resultado:
500 competidores
        ↓
Filtro
        ↓
42 elegibles
        ↓
32 seleccionados


51. Elegible y seleccionado son conceptos distintos
Debe diferenciarse:
ELIGIBLE

de:
SELECTED

Una Entity puede cumplir las reglas pero no necesariamente formar parte del torneo final.

52. Estrategias de selección
Posteriormente se podrán utilizar métodos como:
MANUAL
RANDOM
RANKING
TOP_N
ATTRIBUTE
PREVIOUS_RESULT

Ejemplo:
50 elegibles

Slots:
16

Selection Strategy:
TOP_N_UNIVERSE_RANKING


53. EligibilityRuleSet
Para evitar duplicar lógica se propone un concepto reutilizable:
EligibilityRuleSet

Ejemplo:
Ninjas de Konoha

ALL

Aldea = Konoha
Tipo = Ninja
Estado = Vivo

Este RuleSet podrá utilizarse posteriormente en:
Universo;
Torneo;
Evento;
Simulación;
selección de participantes.

54. Temporadas
Los Universos se organizarán mediante:
UniverseSeason

Ejemplo:
Universo Shinobi
│
├── Season 1
├── Season 2
├── Season 3
├── Season 4
└── Season 5


55. Estados de Temporada
Posibles estados:
PLANNED
ACTIVE
COMPLETED
ARCHIVED


56. Información de Temporada
Una Season podrá contener:
número;
nombre;
descripción;
inicio;
fin;
estado;
competidores;
torneos;
resultados;
recompensas;
ranking;
estadísticas.

57. UniverseTournamentDefinition
No se recomienda ejecutar directamente el TournamentTemplate.
Debe existir una configuración intermedia.
Ejemplo:
TournamentTemplate
        ↓
UniverseTournamentDefinition

Esta definición podrá indicar:
Nombre:
Exámenes Chūnin

Template:
Copa Eliminación Clásica

Participantes:
32

Elegibilidad:
Aldea = Konoha

Premios:
Copa Hokage

Recurrencia:
Cada 2 temporadas


58. Recurrencia de Torneos
Un Universo podrá decidir cada cuántas temporadas se repite una competición.
Ejemplo:
Torneo Mundial

Inicio:
Season 1

Frecuencia:
Cada 4 temporadas

Resultado:
Season 1 → Torneo
Season 2 → -
Season 3 → -
Season 4 → -
Season 5 → Torneo

dependiendo de cómo se defina el intervalo.

59. UniverseTournamentSchedule
Conceptualmente:
TournamentTemplate
Start Season
Frequency Type
Interval
Enabled

Tipos posibles:
ONCE
EVERY_SEASON
EVERY_N_SEASONS
SPECIFIC_SEASONS
MANUAL


60. TournamentInstance
Cuando finalmente llegue una Season donde debe realizarse el Torneo se generará:
TournamentInstance

Ese sí será un torneo histórico real.
Ejemplo:
Exámenes Chūnin
Season 8

Participantes:
32

Estado:
IN_PROGRESS


61. Diferencia fundamental
TournamentTemplate
        ↓
¿Cómo funciona?


UniverseTournamentDefinition
        ↓
¿Cómo se utiliza dentro de este Universo?


TournamentInstance
        ↓
¿Qué ocurrió realmente?

Esta separación deberá conservarse durante todo el desarrollo.

62. Contenido de TournamentInstance
Podrá contener:
Universo;
Season;
TournamentTemplateRevision;
configuración congelada;
participantes;
fases;
matches;
resultados;
campeón;
premios;
estado;
fechas.

63. Historial del Universo
Al completar un torneo se generará información histórica.
Ejemplo:
Naruto Uzumaki

Season 1
🥇 Copa Ninja

Season 2
Semifinalista

Season 3
🥈 Liga Shinobi

Season 4
🥇 Torneo Mundial


64. Premios dentro de Universo
Cuando una competición finalice podrán crearse registros de recompensa.
Conceptualmente:
UniverseRewardGrant

Ejemplo:
Competidor:
Naruto Uzumaki

Recompensa:
Copa Hokage

Temporada:
5

Torneo:
Exámenes Chūnin

Posición:
1


65. Ranking de Universo
El ranking debe estar contextualizado al Universo.
No debería almacenarse como un atributo global de Entity.
Ejemplo:
Naruto

Universo A:
#1

Universo B:
#18

Posteriormente podrán existir estadísticas como:
puntos;
victorias;
derrotas;
empates;
campeonatos;
finales;
participaciones;
rachas.

66. Tournament Engine
El motor central será independiente del dominio de las Entidades.
No debe conocer conceptos específicos como:
Naruto
Konoha
Anime
Ninja

Solo trabajará con:
TournamentParticipant
TournamentMatch
TournamentResult

Esto permitirá utilizarlo con cualquier tipo de contenido.

67. Matches
Un Match deberá utilizar participantes abstractos.
No:
entity_a_id
entity_b_id

sino conceptualmente:
participant_a_id
participant_b_id

Esto permitirá:
Entidades;
UniverseCompetitors;
Teams;
participantes ficticios.

68. Motor de resultados
La arquitectura futura debe ser:
Tournament Engine
        ↓
requiere resultado
        ↓
Match Result Provider
        │
        ├── Manual
        ├── Random Test
        ├── External
        └── Simulation Engine

Así el desarrollo de Simulación no obligará a rehacer Torneos.

69. Simulation Engine
El Motor de Simulación será desarrollado mucho después.
Podrá recibir información como:
Entity
+
EntityVersion
+
Effective Attributes
+
Universe Modifiers
+
Tournament Rules
+
Context

y producir:
MatchResult

Ejemplo:
Naruto vs Sasuke
        ↓
Simulation Engine
        ↓
Naruto gana
        ↓
Tournament Engine
        ↓
Naruto avanza


70. Simulación no debe desarrollarse todavía
Antes deben estar completamente estabilizados:
TournamentTemplate;
TournamentPhase;
Matches;
TournamentParticipant;
TournamentResult;
Competition Lab;
Universe;
UniverseCompetitor;
TournamentInstance.
Solo después tendrá sentido definir cómo se resuelve automáticamente un enfrentamiento.

71. Estructura conceptual futura
LIBRARY
│
├── Entity
├── EntityVersion
├── Attribute
└── Collection
        │
        ▼
UNIVERSE
│
├── UniverseCompetitor
├── UniverseSeason
├── Ranking
└── Rules
        │
        ▼
UNIVERSE TOURNAMENT
│
├── TournamentTemplate
├── Eligibility
├── Overrides
├── Rewards
└── Schedule
        │
        ▼
TOURNAMENT INSTANCE
│
├── TournamentParticipants
├── TournamentPhases
├── TournamentMatches
├── TournamentResults
└── Rewards
        │
        ▼
SIMULATION ENGINE


72. Plan de desarrollo recomendado
Sprint T0 — Diseño del dominio
Antes de programar:
documentar responsabilidades;
diseñar relaciones;
definir Template;
Phase;
Participant;
Match;
Result;
Rewards;
Competition Lab;
revisar compatibilidad futura con Universe.

73. Sprint T1 — TournamentTemplate
Implementar CRUD de plantillas.
Funciones:
crear;
editar;
mostrar;
duplicar;
archivar;
eliminar;
cambiar visibilidad.
Campos iniciales:
nombre;
descripción;
imagen;
participantes mínimos;
participantes máximos;
estado;
visibilidad.

74. Sprint T2 — Phase Builder
Crear sistema de Fases.
Primera fase soportada:
SINGLE_ELIMINATION

Funciones:
crear fase;
editar;
reordenar;
eliminar;
configurar participantes;
configurar avance.

75. Sprint T3 — Competition Lab
Crear laboratorio de pruebas.
Permitirá:
seleccionar plantilla;
usar Entidades de Biblioteca;
crear participantes ficticios;
generar competición;
introducir ganadores manuales;
visualizar llaves;
comprobar avance.
Sin persistencia histórica.

76. Sprint T4 — Random Test Resolver
Agregar:
Simular ronda

para validar rápidamente el motor.
No utilizará atributos reales.

77. Sprint T5 — Round Robin
Implementar:
todos contra todos;
resultados;
puntos;
tabla;
clasificación;
desempates básicos.

78. Sprint T6 — Group Stage
Implementar:
Grupos
        ↓
Clasificación
        ↓
Siguiente fase

Conectar con eliminación directa.

79. Sprint T7 — Swiss
Implementar sistema suizo.
Incluir:
rondas;
emparejamientos;
puntos;
rivales previos;
clasificación;
desempates.

80. Sprint T8 — Rewards
Implementar:
RewardTemplate
TournamentRewardSlot


81. Sprint T9 — Comunidad de Torneos
Ampliar Comunidad.
Agregar:
TournamentTemplate

Funciones:
explorar;
buscar;
filtrar;
visualizar;
publicar;
clonar;
métricas;
procedencia.

82. Sprint T10 — Revisions
Implementar:
TournamentTemplateRevision

para asegurar que las futuras ejecuciones históricas no cambien.

83. Inicio del módulo Universo
Solo después de completar el núcleo de Torneos.

84. Sprint U1 — Universe
CRUD básico:
crear;
editar;
mostrar;
eliminar;
archivar;
visibilidad.

85. Sprint U2 — UniverseCompetitor
Agregar Entidades de Biblioteca.
Funciones:
manual;
búsqueda;
filtros;
selección masiva.

86. Sprint U3 — UniverseSeason
Implementar Temporadas.
Funciones:
crear;
activar;
cerrar;
archivar;
navegar historial.

87. Sprint U4 — UniverseTournamentDefinition
Permitir seleccionar:
TournamentTemplate

y configurar su uso dentro del Universo.

88. Sprint U5 — Eligibility Rules
Permitir:
Attribute
Operator
Value
AND / OR

para seleccionar competidores.

89. Sprint U6 — TournamentInstance
Crear competiciones persistentes reales.
Utilizar el mismo Tournament Engine desarrollado para el Competition Lab.

90. Sprint U7 — Recurrence
Implementar Torneos recurrentes.
Ejemplo:
Cada N temporadas


91. Sprint U8 — Rewards e Historial
Registrar:
ganador;
posiciones;
recompensas;
participación;
historial de competidor.

92. Sprint U9 — Rankings
Agregar sistema inicial de ranking por Universo.
Posteriormente podrá ser configurable.

93. Fase posterior — Simulación
Solo después:
Simulation Engine

El primer objetivo será producir un MatchResult compatible con Tournament Engine.

94. Tablas conceptuales propuestas para Torneos
No significa que todas deban crearse inmediatamente.
tournament_templates
tournament_template_revisions

tournament_phases
tournament_phase_transitions

tournament_rules

reward_templates
tournament_reward_slots

Para ejecución futura:
tournament_instances
tournament_instance_phases
tournament_participants
tournament_matches
tournament_results
tournament_reward_grants


95. Tablas conceptuales futuras de Universo
universes

universe_competitors

universe_seasons

universe_tournament_definitions
universe_tournament_schedules

universe_rankings
universe_competitor_stats

universe_reward_grants

Su diseño final deberá definirse cuando Tournament Engine esté estabilizado.

96. Relación general de datos
User
│
├── Library
│   ├── Entity
│   ├── EntityVersion
│   ├── Attribute
│   └── Collection
│
├── TournamentTemplate
│   │
│   ├── Revision
│   ├── Phases
│   ├── Rules
│   └── Rewards
│
└── Universe
    │
    ├── Competitors
    ├── Seasons
    │
    └── Tournament Definitions
        │
        └── TournamentInstance
            │
            ├── Participants
            ├── Phases
            ├── Matches
            ├── Results
            └── Rewards


97. Ejemplo completo
Biblioteca
Naruto Uzumaki
Sasuke Uchiha
Sakura Haruno
Gaara
Neji
Rock Lee

Plantilla de Torneo
Copa Eliminación Clásica

Formato:
Single Elimination

Participantes:
8–32

BYE:
Sí

Comunidad
El creador publica:
Copa Eliminación Clásica
PUBLIC

Otro usuario:
Comunidad
        ↓
Torneos
        ↓
Copa Eliminación Clásica
        ↓
Copiar a mi Biblioteca

Ahora tiene su propia copia independiente.
Universo
Universo Naruto

Competidores:
Naruto
Sasuke
Sakura
Gaara
Neji
Rock Lee

Tournament Definition
Nombre:
Exámenes Chūnin

Plantilla:
Copa Eliminación Clásica

Participantes:
8

Elegibilidad:
Rango = Genin

Premio:
Copa Hokage

Recurrencia:
Cada 2 temporadas

Temporada 4
Season 4
        ↓
Exámenes Chūnin
        ↓
8 competidores
        ↓
TournamentInstance

Match
Naruto
VS
Neji

Resultado:
Naruto gana

Tournament Engine:
Naruto avanza

Finalmente:
Naruto
CAMPEÓN

Se registra:
Season 4
Exámenes Chūnin
1.er lugar
Copa Hokage


98. Principios arquitectónicos finales
Torneos no conoce dominios específicos
Nunca deberá existir lógica como:
si anime = Naruto
hacer torneo ninja

Tournament Engine solamente trabaja con participantes y resultados.

Entity sigue siendo la identidad canónica
No se crearán copias de Entidades únicamente por agregarlas a un Universo.

UniverseCompetitor añade contexto
La historia competitiva pertenece al Universo, no a la Entity global.

Template no es Instance
Una plantilla describe.
Una instancia registra algo ocurrido.

Simulation no controla Tournament Structure
Simulación solamente produce resultados.

Comunidad distribuye configuraciones reutilizables
Los usuarios podrán compartir y clonar sistemas completos sin modificar los originales.

99. Resultado esperado a largo plazo
El ecosistema completo de OmniMerge podrá funcionar así:
BIBLIOTECA
        ↓
Crear Entidades

TORNEOS
        ↓
Crear sistemas competitivos

COMUNIDAD
        ↓
Compartir y reutilizar plantillas

UNIVERSOS
        ↓
Construir mundos independientes

TEMPORADAS
        ↓
Organizar el tiempo

TORNEOS DEL UNIVERSO
        ↓
Programar competiciones

COMPETIDORES
        ↓
Seleccionar Entidades

SIMULACIÓN
        ↓
Resolver enfrentamientos

RESULTADOS
        ↓
Actualizar torneo

RECOMPENSAS
        ↓
Actualizar competidores

HISTORIAL
        ↓
Construir historia del Universo


100. Orden definitivo recomendado
El desarrollo deberá seguir este orden:
1. Diseñar completamente Tournament Domain.

2. Crear TournamentTemplate.

3. Crear TournamentPhase.

4. Implementar Single Elimination.

5. Crear Competition Lab.

6. Permitir participantes ficticios.

7. Permitir Entidades de Biblioteca.

8. Implementar resultados manuales.

9. Implementar Random Test Resolver.

10. Implementar Round Robin.

11. Implementar Group Stage.

12. Implementar Swiss.

13. Implementar RewardTemplate.

14. Implementar TournamentRewardSlot.

15. Integrar TournamentTemplate con Comunidad.

16. Implementar clonación de plantillas.

17. Implementar TournamentTemplateRevision.

18. Crear Universe.

19. Crear UniverseCompetitor.

20. Crear UniverseSeason.

21. Crear UniverseTournamentDefinition.

22. Implementar filtros de elegibilidad.

23. Implementar selección de participantes.

24. Crear TournamentInstance.

25. Implementar recurrencia entre temporadas.

26. Implementar recompensas reales.

27. Implementar historial.

28. Implementar ranking.

29. Diseñar Simulation Engine.

30. Integrar Simulation Engine como Match Result Provider.


101. Conclusión
La nueva etapa de OmniMerge debe construirse manteniendo una separación estricta entre diseño, ejecución y simulación.
El módulo de Torneos será el lugar donde los usuarios diseñen sistemas de competición reutilizables, configurando fases, formatos, reglas, participantes, clasificación, recompensas y demás características.
El Competition Lab permitirá probar estas plantillas utilizando participantes ficticios o Entidades de la Biblioteca sin generar información histórica.
La Comunidad permitirá publicar estas plantillas y que otros usuarios creen copias privadas e independientes para modificarlas y reutilizarlas.
Posteriormente, los Universos utilizarán estas plantillas dentro de un contexto real compuesto por Competidores, Temporadas, filtros de elegibilidad, reglas propias, recompensas, recurrencias e historial.
Finalmente, el Simulation Engine será responsable exclusivamente de resolver interacciones y devolver resultados al motor de Torneos.
La estructura conceptual principal será:
TournamentTemplate
        ↓
define cómo competir


Competition Lab
        ↓
permite probar


Community
        ↓
permite compartir y reutilizar


UniverseTournamentDefinition
        ↓
define cómo utilizar la plantilla
dentro de un Universo


TournamentInstance
        ↓
registra una competición real


Simulation Engine
        ↓
resuelve enfrentamientos

Esta separación permitirá que OmniMerge evolucione de una plataforma de organización de información hacia un sistema completo para construir Universos dinámicos, sistemas competitivos reutilizables, temporadas, historias, rankings, recompensas y simulaciones sin sacrificar la flexibilidad que caracteriza al proyecto.

