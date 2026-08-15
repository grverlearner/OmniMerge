DOCUMENTACIÓN FUNCIONAL Y TÉCNICA
FASE DE ELIMINACIÓN SIMPLE EN OMNIMERGE
1. Introducción
La fase de Eliminación Simple de OmniMerge representa una estructura competitiva en la que los participantes ingresan a una serie de encuentros y, según sus resultados, avanzan, quedan eliminados o son enviados hacia otras rutas.
En su forma tradicional, este sistema funciona así:
Participantes
→ enfrentamientos de dos participantes
→ un ganador por enfrentamiento
→ siguiente ronda
→ un campeón

Sin embargo, OmniMerge no está pensado únicamente para torneos deportivos o brackets tradicionales. La plataforma debe permitir representar competencias, batallas, simulaciones y recorridos más complejos.
Por ello, la fase de Eliminación Simple debe evolucionar para representar también estructuras como:
Encuentros de tres o más participantes.
Encuentros con varios clasificados.
Varias puertas de entrada.
Entradas que transporten uno o varios participantes.
Distribución automática, sembrada, aleatoria o manual.
Diferentes rutas según el resultado de cada encuentro.
Eliminados que pasan a repechaje.
Participantes que salen por puertas diferentes.
Ramas internas que posteriormente pueden converger.
Fases que terminan con más de un superviviente.
Tipos de batalla personalizados.
Resultados distintos a solamente ganador y perdedor.
La esencia predeterminada seguirá siendo sencilla. Un usuario podrá configurar una eliminación tradicional sin entrar en opciones avanzadas. Las posibilidades complejas se encontrarán organizadas en un modo avanzado.

2. Propósito de la fase
La fase de Eliminación Simple debe encargarse de definir:
Cómo recibe participantes.
Cómo los distribuye.
Cuántos participan en cada encuentro.
Cómo se construyen las rondas.
Qué reglas utiliza cada encuentro.
Cuántos participantes clasifican.
Qué ocurre con cada resultado.
Cómo se conectan internamente los encuentros.
Cuándo termina la fase.
Por qué puertas salen sus participantes.
La fase debe funcionar como una unidad autónoma que posteriormente pueda conectarse con otras fases mediante el Tournament Graph.
Su flujo general será:
Puertas de entrada de la fase
→ distribución de participantes
→ encuentros de primera ronda
→ resultados de los encuentros
→ rutas internas
→ rondas posteriores
→ puertas de salida de la fase


3. Diferencia entre Tournament Graph y estructura interna
OmniMerge tendrá dos niveles de construcción visual.
3.1 Tournament Graph
El Tournament Graph conecta fases completas.
Por ejemplo:
Clasificatoria A
→ Eliminación Simple
→ Final

El Tournament Graph solamente necesita conocer el contrato externo de cada fase:
Puertas de entrada.
Puertas de salida.
Cantidades aceptadas.
Cantidades producidas.
Tipo de participante.
Reglas de conexión.
3.2 Estructura interna de Eliminación Simple
La configuración de Eliminación Simple muestra lo que sucede dentro de una fase:
Entrada
→ cuartos de final
→ semifinales
→ final
→ salida

Esta estructura incluye:
Rondas.
Encuentros.
Slots.
Resultados.
Avances.
BYEs.
Overrides.
Rutas internas.
Salidas de eliminados y clasificados.
El Tournament Graph no deberá mostrar permanentemente todos los encuentros internos, porque eso volvería demasiado compleja la interfaz general. En su lugar, podrá mostrar un resumen y permitir abrir la configuración interna de la fase.

4. Estado actual de la implementación
Actualmente OmniMerge ya cuenta con una base funcional para Eliminación Simple.
4.1 Configuración general existente
La interfaz permite definir:
Modo de finalización.
Cantidad objetivo de supervivientes.
Modo de seeding.
Modo de pairing.
Asignación de BYEs.
Best of predeterminado.
Reseed después de cada ronda.
Best of específico por ronda.
4.2 Modos de finalización existentes
La fase puede configurarse para terminar:
Con un ganador
WINNER

El motor continúa generando rondas hasta que queda un único participante.
Con una cantidad determinada de supervivientes
SURVIVORS

El motor se detiene cuando queda la cantidad configurada de clasificados.
Los objetivos permitidos actualmente siguen potencias de dos:
1, 2, 4, 8, 16, 32, 64, 128 y 256

4.3 Modos de seeding existentes
Actualmente se encuentran disponibles:
Orden de entrada.
Aleatorio.
Ranking.
Manual.
El seeding determina cómo se asigna el orden inicial de los participantes.
4.4 Modos de pairing existentes
Actualmente se encuentran disponibles:
Seeded estándar.
Secuencial.
Aleatorio.
El pairing determina cómo se enfrentan los seeds dentro del bracket.
4.5 BYEs
La configuración general de la plantilla determina si la fase permite BYEs.
Cuando están activados, pueden asignarse a:
Mejores seeds.
Aleatoriamente.
Manualmente.
Cuando están desactivados, la cantidad de participantes debe completar un bracket compatible.
4.6 Best of
El motor permite configurar:
BO1.
BO3.
BO5.
BO7.
BO9.
También permite crear overrides para que una ronda utilice un Best of diferente.
Ejemplo:
Cuartos de final → BO3
Semifinal → BO3
Final → BO5

4.7 Previsualización matemática
La interfaz actual calcula:
Tamaño del bracket.
BYEs iniciales.
Cantidad de rondas.
Cantidad de series.
Participantes eliminados.
Participantes supervivientes.
Información resumida de cada ronda.
La previsualización no crea participantes, encuentros persistentes ni historial. Solamente calcula cómo se comportaría la fase.
4.8 Componentes actuales del backend
La implementación se apoya principalmente en:
Controlador de Eliminación Simple.
Form Request de configuración.
Form Request de previsualización.
Servicio de configuración.
Calculador del bracket.
Validador de Eliminación Simple.
Servicio de reglas por ronda.
Modelo de configuración.
Modelo de overrides por ronda.
Esta base deberá conservarse y ampliarse sin romper las fases existentes.

4.9 Estado de la Etapa 3
La base avanzada se implementa de forma incremental y compatible.

Incluye:
Modo BASIC y ADVANCED.
Relación general K → Q mediante entrants_per_match y qualifiers_per_match.
Perfiles DUEL, MULTI_COMPETITOR y CUSTOM.
Políticas BYE, PRELIMINARY, BALANCED, INCOMPLETE_MATCH, MANUAL y REJECT.
Modos de entrada POOL, PER_SEED, GROUPED, HYBRID y CUSTOM.
Modos de enrutamiento AUTOMATIC, POSITIONAL, MANUAL y CUSTOM.
Overrides K → Q y perfil por tamaño de ronda.
Previsualización matemática sin persistir encuentros.
Validación del contrato y de la alcanzabilidad del objetivo.
Pronóstico más preciso de las salidas SURVIVORS y ELIMINATED en Tournament Graph.

El modo BASIC conserva el comportamiento tradicional 2 → 1 y los objetivos potencia de 2.

Las configuraciones avanzadas pueden guardarse y previsualizarse, pero todavía no se ejecutan en Competition Lab. Su ejecución depende de los encuentros, slots, resultados y rutas internas de las Etapas 4 y 6.

5. Limitaciones detectadas en la implementación actual
5.1 Guardado poco accesible
El formulario es extenso y el botón para guardar se encuentra al final. El usuario debe desplazarse constantemente para confirmar sus modificaciones.
5.2 Overrides incompatibles
Una fase configurada para exactamente ocho participantes puede permitir seleccionar una regla para una ronda de 512 participantes.
Esto ocurre porque las opciones de ronda no están suficientemente limitadas por:
Contrato de participantes.
Cantidad exacta.
Máximo permitido.
Objetivo de supervivientes.
Rondas matemáticamente posibles.
5.3 Previsualización demasiado limitada
La previsualización muestra valores matemáticos, pero no permite comprender visualmente:
De dónde viene cada participante.
En qué encuentro entra.
Cómo avanza.
A qué salida llega.
Qué reglas específicas afectan una ronda.
Qué sucede con los eliminados.
Cómo se relacionan entradas, encuentros y salidas.
5.4 Explicaciones extensas
Varias secciones muestran explicaciones permanentes que ocupan demasiado espacio vertical.
5.5 Modelo centrado en duelos
La lógica actual asume principalmente:
Dos participantes
→ una serie
→ un ganador
→ un perdedor

Esto limita los encuentros con más participantes y múltiples clasificados.
5.6 Entradas externas insuficientemente representadas
La fase acepta una cantidad de participantes, pero no permite configurar claramente:
Una entrada general.
Una entrada por seed.
Varias entradas.
Entradas por lotes.
Entradas híbridas.
Slots específicos alimentados por rutas anteriores.
5.7 Resultados internos demasiado simples
El modelo actual se concentra en ganador y eliminado. No representa de manera general resultados como:
Primer lugar.
Segundo lugar.
Mejores dos.
Supervivientes.
Clasificado por puntuación.
Repechaje.
Resultado personalizado.
5.8 Pairing personalizado insuficiente
Los modos actuales cubren casos comunes, pero no permiten construir emparejamientos o rutas especiales de manera explícita.

6. Principios de la nueva implementación
La ampliación deberá respetar los siguientes principios.
6.1 Mantener la simplicidad predeterminada
Crear una eliminación tradicional debe continuar siendo rápido.
Configuración predeterminada:
Modo básico
Una entrada agrupada
Dos participantes por encuentro
Un clasificado por encuentro
Enrutamiento automático
Un ganador final

6.2 Separar configuración básica y avanzada
Las opciones complejas no deben mostrarse todas al iniciar.
La interfaz tendrá:
Modo básico.
Modo avanzado.
6.3 Mostrar consecuencias antes de guardar
Cuando se modifique una configuración, la vista deberá indicar:
Cuántas rondas se producirán.
Cuántos encuentros habrá.
Cuántos clasificados saldrán.
Cuántos participantes serán eliminados.
Cuántos BYEs serán necesarios.
Qué puertas se utilizarán.
Qué configuraciones quedaron incompatibles.
6.4 Validar en frontend y backend
No será suficiente ocultar opciones inválidas.
El backend deberá rechazar:
Rondas inexistentes.
Overrides incompatibles.
Conexiones imposibles.
Cantidades incorrectas.
Resultados sin destino obligatorio.
Configuraciones matemáticamente inviables.
6.5 Mantener compatibilidad
Las configuraciones actuales deberán interpretarse automáticamente como configuraciones básicas.

7. Nuevo diseño general de la interfaz
La interfaz se reorganizará para reducir desplazamientos, separar conceptos y mostrar una representación visual compacta.
No se utilizará como interfaz principal:
Lienzo libre.
Zoom obligatorio.
Navegación infinita.
Diagrama difícil de controlar.
Movimiento constante por un espacio grande.
La estructura principal se basará en bloques organizados.

8. Diseño mediante bloques
8.1 Bloques por sección
La configuración se dividirá en bloques plegables:
Participantes y finalización.
Entradas.
Distribución.
Encuentros.
Avance y rutas.
BYEs.
Best of y overrides.
Puertas de salida.
Revisión y validación.
Cada bloque mostrará:
Título.
Estado.
Resumen de configuración.
Icono de ayuda.
Botón para editar o expandir.
Indicador de errores o advertencias.
Ejemplo:
┌────────────────────────────────────────────┐
│ 01 · Participantes y finalización     ✓    │
│ 8 participantes · termina con 1 ganador    │
│                               [Configurar] │
└────────────────────────────────────────────┘

8.2 Estados visuales
Los bloques utilizarán estados claros:
Verde: configuración válida.
Amarillo: advertencia.
Rojo: error bloqueante.
Azul: información.
Violeta: configuración personalizada.
Gris: configuración pendiente.
Ámbar: elemento propio de Eliminación Simple.
Los colores estarán acompañados por texto e iconos.
8.3 Vista compacta
La vista compacta mostrará solamente:
Resumen de cada ronda.
Cantidad de encuentros.
Participantes por encuentro.
Clasificados.
Best of.
BYEs.
Salidas.
Ejemplo:
Cuartos
4 encuentros · 2 entran · 1 avanza · BO3

Semifinal
2 encuentros · 2 entran · 1 avanza · BO3

Final
1 encuentro · 2 entran · 1 avanza · BO5

8.4 Vista detallada
La vista detallada mostrará cada encuentro como tarjeta.
Ejemplo:
Ronda de 8

[Encuentro 1]
Entrada: Seed 1, Seed 8
Resultado: Ganador → Semifinal 1
Resultado: Eliminado → Salida Eliminados

[Encuentro 2]
Entrada: Seed 4, Seed 5
Resultado: Ganador → Semifinal 1
Resultado: Eliminado → Salida Eliminados

8.5 Vista por tabla
Para estructuras grandes se ofrecerá una vista tabular.
Ronda
Encuentro
Entradas
Clasifican
Destino
Regla
Cuartos
1
2
1
Semifinal 1
BO3
Cuartos
2
2
1
Semifinal 1
BO3
Cuartos
3
2
1
Semifinal 2
BO3
Cuartos
4
2
1
Semifinal 2
BO3
Semifinal
1
2
1
Final
BO3
Semifinal
2
2
1
Final
BO3
Final
1
2
1
Ganador
BO5

Esta vista será especialmente útil para fases con muchos encuentros.

9. Visualización compacta del bracket
La visualización principal no dependerá de zoom.
Tendrá tres modos:
9.1 Resumen por rondas
Muestra una tarjeta por ronda:
Entradas: 8
↓
Cuartos: 4 encuentros
↓
Semifinales: 2 encuentros
↓
Final: 1 encuentro
↓
Salidas: 1 ganador y 7 eliminados

9.2 Bloques de encuentros
Muestra los encuentros dentro de columnas o secciones por ronda.
En pantallas grandes, las rondas pueden aparecer en columnas.
En pantallas pequeñas, aparecerán verticalmente.
9.3 Tabla de rutas
Muestra las conexiones mediante filas y destinos textuales, sin depender de líneas complejas.
El usuario podrá cambiar entre:
Resumen.
Bloques.
Tabla.
La aplicación recordará la vista seleccionada.

10. Barra persistente de guardado
La interfaz incluirá una barra de acciones siempre accesible.
Deberá mostrar:
Estado actual.
Cantidad de cambios.
Botón Guardar.
Botón Descartar.
Botón Previsualizar.
Estado de validación.
Estados posibles:
Sin cambios
Cambios sin guardar
Guardando
Guardado correctamente
No se puede guardar

Si el usuario intenta abandonar la página con modificaciones pendientes, deberá aparecer una advertencia.
No se implementará guardado automático obligatorio en configuraciones avanzadas. La vista podrá actualizarse inmediatamente, pero la persistencia ocurrirá cuando el usuario confirme.

11. Ayudas compactas
Las explicaciones largas se sustituirán por:
Descripciones de una línea.
Iconos de ayuda.
Tooltips.
Paneles “Más información”.
Ejemplos desplegables.
Ejemplo:
Pairing (?)
Determina cómo se enfrentan los seeds.

Al pulsar el icono:
Seeded estándar enfrenta posiciones distribuidas para separar a los mejores clasificados.
Ejemplo: Seed 1 contra Seed 8.

Se utilizará este sistema para:
Seeding.
Pairing.
BYE.
Reseed.
Best of.
Slot.
Puerta.
Clasificado.
Override.
Resultado.
Tipo de batalla.
Enrutamiento.

12. Modos de configuración
12.1 Modo básico
Permitirá configurar:
Participantes.
Ganador o supervivientes.
Seeding.
Pairing.
BYEs.
Best of.
Overrides.
Reseed.
El bracket se generará automáticamente.
12.2 Modo avanzado
Permitirá configurar:
Entradas externas.
Participantes por encuentro.
Clasificados por encuentro.
Política de sobrantes.
Slots.
Resultados.
Rutas internas.
Salidas personalizadas.
Perfiles de encuentro.
Enrutamiento manual o personalizado.
Al activar el modo avanzado, la interfaz deberá explicar que algunas estructuras requerirán un motor de batalla compatible.

13. Presets
La interfaz ofrecerá configuraciones rápidas.
13.1 Eliminación clásica
2 participantes por encuentro
1 clasifica
1 ganador final
Pairing seeded estándar

13.2 Sorteo aleatorio
2 participantes por encuentro
1 clasifica
Pairing aleatorio

13.3 Clasificatoria Top 2
2 participantes por encuentro
1 clasifica
La fase termina con 2 supervivientes

13.4 Battle Royale
4 participantes por encuentro
1 clasifica

13.5 Clasificatoria múltiple
4 participantes por encuentro
2 clasifican

13.6 Personalizada
Permite configurar encuentros, resultados y rutas manualmente.
Antes de aplicar un preset, la interfaz mostrará qué valores serán reemplazados.

14. Puertas de entrada
Una puerta de entrada representa un punto por el que la fase recibe participantes desde otra parte del torneo.
14.1 Entrada agrupada
Todos los participantes entran por una puerta común.
Participantes de entrada
→ bolsa general
→ seeding
→ pairing

Este será el comportamiento predeterminado.
14.2 Entrada por seed
Cada seed puede tener una entrada explícita:
Seed 1
Seed 2
Seed 3
Seed 4
...

Esto permitirá conectar participantes específicos desde el Tournament Graph.
14.3 Entrada por lote
Una puerta puede recibir varios participantes:
Entrada Federación A → 4 participantes
Entrada Federación B → 2 participantes
Entrada Invitados → 2 participantes

14.4 Entrada híbrida
Combina entradas explícitas y agrupadas.
Ejemplo:
Seed 1 → Campeón anterior
Seed 2 → Invitado
Seeds 3–8 → bolsa general

14.5 Entrada personalizada
Permitirá configurar:
Nombre.
Código.
Tipo.
Capacidad mínima.
Capacidad máxima.
Cantidad exacta.
Prioridad.
Orden.
Destinos permitidos.
Comportamiento cuando esté vacía.
Si acepta varias conexiones.
Si recibe un participante o un lote.

15. Puerta externa y slot interno
Estos conceptos deberán mantenerse separados.
15.1 Puerta de entrada
Recibe participantes desde fuera de la fase.
15.2 Slot
Es una posición dentro de un encuentro.
Ejemplo:
Puerta Federación A
→ distribución
→ Slot 1 del Encuentro 2

Una puerta puede alimentar varios slots si recibe un lote. Un slot individual normalmente recibirá un único participante.

16. Distribución de entradas
Cuando una puerta reciba varios participantes, se deberá elegir cómo distribuirlos.
Opciones:
Mantener el orden.
Ordenar por ranking.
Aleatorizar.
Sembrar equilibradamente.
Distribuir entre extremos.
Evitar participantes del mismo origen.
Asignación manual.
Regla personalizada.
La distribución deberá mostrar una previsualización antes de guardarse.

17. Encuentros con múltiples participantes
Cada configuración avanzada podrá determinar:
Participantes por encuentro: K
Clasificados por encuentro: Q

Restricciones:
K >= 2
Q >= 1
Q < K

Ejemplos:
Participantes
Clasificados
Eliminados
2
1
1
3
1
2
4
1
3
4
2
2
6
2
4

17.1 Configuración global
Todas las rondas utilizan el mismo formato.
Ejemplo:
4 participantes por encuentro
2 clasifican

17.2 Override por ronda
Una ronda puede utilizar un formato distinto.
Ejemplo:
Primera ronda: 4 entran y 2 avanzan
Semifinal: 2 entran y 1 avanza
Final: 3 entran y 1 gana

17.3 Override por encuentro
En modo personalizado, un encuentro concreto podrá diferenciarse de los demás.
Esto debe utilizarse con precaución porque puede cambiar la cantidad de participantes de las siguientes rondas.

18. Resultados de los encuentros
Los resultados internos no estarán limitados a ganador y perdedor.
Tipos posibles:
Ganador.
Perdedor.
Posición.
Top N.
Clasificado.
Eliminado.
Superviviente.
Puntuación mínima.
Selección manual.
Resultado personalizado.
Ejemplo:
Encuentro de cuatro participantes

1.º → siguiente ronda
2.º → repechaje
3.º → salida Eliminados A
4.º → salida Eliminados B

Cada resultado deberá indicar:
Cantidad producida.
Criterio.
Destino.
Si es obligatorio.
Si puede dividirse.
Si puede conectarse a varias rutas.

19. Avance a la siguiente ronda
19.1 Avance automático
El motor construye las conexiones.
19.2 Avance por posición
Ejemplo:
Primer lugar → ronda siguiente
Segundo lugar → repechaje

19.3 Mejores N
Ejemplo:
Los dos mejores participantes avanzan.

19.4 Avance manual
El organizador selecciona quién avanza.
19.5 Avance personalizado
Cada resultado se conecta a un destino concreto.
Destinos posibles:
Otro encuentro.
Un slot.
Una salida de la fase.
Una ruta secundaria.
Fin de participación.

20. Pairing ampliado
Se conservarán:
Seeded estándar.
Secuencial.
Aleatorio.
Se agregarán progresivamente:
Balanceado.
Por origen.
Por grupos.
Por atributos.
Manual.
Personalizado.
20.1 Pairing por origen
Podrá evitar enfrentamientos entre participantes procedentes de la misma entrada.
20.2 Pairing por atributos
Podrá utilizar atributos de las entidades:
Federación.
Equipo.
Región.
Universo.
Categoría.
Ranking.
Ejemplos:
Evitar misma federación en primera ronda.
Separar miembros del mismo equipo.
Emparejar rankings cercanos.

20.3 Pairing personalizado
El usuario decidirá qué entradas alimentan cada encuentro.

21. Política de sobrantes
Cuando la cantidad no complete todos los encuentros, se podrá elegir:
Asignar BYEs.
Crear ronda preliminar.
Permitir encuentros incompletos.
Distribuir equilibradamente.
Asignar manualmente.
Rechazar la configuración.
Ejemplo:
10 participantes
4 participantes por encuentro

Posibilidades:
4 + 4 + 2

o:
Ronda preliminar para reducir a 8

o:
Completar hasta 12 posiciones con BYEs

La interfaz deberá explicar el resultado de cada política.

22. BYEs
Los BYEs deberán ser aplicables a estructuras tradicionales y múltiples.
Configuraciones:
Mejores seeds.
Aleatorios.
Manuales.
Distribuidos equilibradamente.
Por puerta de origen.
Prohibidos.
Un BYE significa que un participante o grupo avanza sin disputar un encuentro completo.
La previsualización deberá distinguir:
Slots vacíos.
BYEs.
Participantes ausentes.
Encuentros incompletos permitidos.

23. Best of y tipos de batalla
Best of continuará representando la cantidad máxima de partidas de una serie.
Sin embargo, no todos los encuentros necesariamente utilizarán Best of.
Por ello se incorporará un perfil de encuentro.
23.1 Perfil de duelo
2 participantes
1 ganador
Best of disponible

23.2 Perfil multicompetidor
3 o más participantes
Uno o varios resultados
Best of opcional

23.3 Perfil personalizado
Preparado para un futuro tipo de batalla.
El tipo de batalla definirá:
Campos de resultado.
Reglas de victoria.
Empates.
Puntuación.
Posiciones.
Salidas disponibles.
Cantidad de participantes.
Compatibilidad con Best of.

24. Puertas de salida de la fase
Las puertas de salida externas representarán los resultados disponibles para el Tournament Graph.
Ejemplos:
Campeón.
Subcampeón.
Finalistas.
Supervivientes.
Clasificados.
Eliminados.
Eliminados por ronda.
Eliminados por posición.
Repechaje.
Resultado personalizado.
Cada salida deberá definir:
Nombre.
Código.
Tipo.
Cantidad mínima.
Cantidad máxima.
Cantidad exacta.
Selector.
Orden.
Estado.
Descripción.
Compatibilidad de conexión.

25. Representación visual de entradas y salidas
La vista compacta puede mostrar:
ENTRADAS

Entrada general
8 participantes

RONDAS

Cuartos
4 encuentros · 8 entran · 4 avanzan

Semifinal
2 encuentros · 4 entran · 2 avanzan

Final
1 encuentro · 2 entran · 1 avanza

SALIDAS

Ganador: 1
Eliminados: 7

En modo detallado:
Entrada general
├── Seeds 1–8
├── Cuartos de final
├── Semifinales
├── Final
├── Salida Ganador
└── Salida Eliminados

No será necesario utilizar zoom para comprender la estructura.

26. Inspector de bloques
Cuando el usuario seleccione un bloque, se abrirá un inspector lateral o inferior.
El inspector permitirá editar:
Nombre.
Capacidad.
Tipo.
Fuente.
Destino.
Best of.
Participantes por encuentro.
Clasificados.
Regla de avance.
Estado.
Configuración avanzada.
En móvil, el inspector aparecerá como panel inferior o pantalla secundaria.

27. Backend propuesto
La implementación actual deberá mantenerse y ampliarse.
27.1 Configuración general ampliada
La configuración de Eliminación Simple necesitará conceptos como:
configuration_mode
input_mode
routing_mode
entrants_per_match
qualifiers_per_match
encounter_profile
remainder_policy

Valores posibles:
configuration_mode:
BASIC
ADVANCED

input_mode:
POOL
PER_SEED
GROUPED
HYBRID
CUSTOM

routing_mode:
AUTOMATIC
POSITIONAL
MANUAL
CUSTOM

remainder_policy:
BYE
PRELIMINARY
BALANCED
INCOMPLETE_MATCH
MANUAL
REJECT

27.2 Puertas de entrada
Se necesitará una estructura para almacenar:
phase_template_id.
name.
code.
input_type.
min_capacity.
max_capacity.
exact_capacity.
accepts_batch.
sort_order.
status.
configuration.
27.3 Encuentros internos
Se necesitará representar:
phase_template_id.
round_number.
position.
name.
entrants_count.
qualifiers_count.
encounter_profile.
best_of.
sort_order.
configuration.
27.4 Slots
Cada encuentro necesitará slots con:
encounter_id.
position.
source_type.
source_id.
capacity.
assignment_rule.
required.
configuration.
27.5 Resultados internos
Cada encuentro tendrá resultados:
encounter_id.
name.
code.
result_type.
position_from.
position_to.
quantity.
destination_type.
configuration.
27.6 Conexiones internas
Las conexiones representarán:
Puerta de entrada → slot
Resultado de encuentro → slot
Resultado de encuentro → salida de fase

Necesitarán:
source_type.
source_id.
target_type.
target_id.
quantity.
priority.
condition.
configuration.

28. Motor matemático
El calculador actual deberá conservarse para el modo tradicional.
Para el modo general se necesitará un motor capaz de resolver:
K participantes por encuentro
Q clasificados por encuentro

El motor deberá calcular:
Tamaño de la primera ronda.
Cantidad de encuentros.
Participantes sobrantes.
BYEs.
Clasificados.
Eliminados.
Siguiente ronda.
Número total de rondas.
Número total de encuentros.
Compatibilidad con el objetivo final.
También deberá detectar cuando una reducción no puede alcanzar exactamente el objetivo.

29. Validaciones
29.1 Errores bloqueantes
Override para una ronda inexistente.
Más clasificados que participantes.
Cero clasificados.
Encuentro con menos de dos participantes cuando no esté permitido.
Entrada obligatoria sin destino.
Slot obligatorio sin fuente.
Resultado obligatorio sin destino.
Ruta circular.
Conexión duplicada incompatible.
Cantidad fuera del contrato.
Objetivo imposible.
Salida que promete más participantes de los producidos.
Tipo de batalla incompatible.
Configuración personalizada incompleta.
29.2 Advertencias
Puerta sin conectar.
Salida no utilizada.
Override redundante.
Pairing manual pendiente.
BYE manual sin asignar.
Encuentro sin nombre.
Resultado personalizado sin tipo de batalla.
Ruta que termina sin salida explícita.
29.3 Recomendaciones
Utilizar entrada agrupada cuando no se necesitan seeds específicos.
Usar un preset para estructuras tradicionales.
Eliminar overrides iguales al valor general.
Simplificar rutas equivalentes.
Crear una salida común para eliminados cuando no necesitan destinos distintos.

30. Corrección de overrides
Los tamaños de ronda deberán calcularse dinámicamente.
Para ocho participantes y un ganador:
8
4
2

Solo esas opciones deberán estar disponibles.
La validación deberá comprobar:
Que la ronda existe.
Que no esté por debajo del objetivo.
Que no supere el máximo del contrato.
Que no exista otro override para la misma ronda.
Que siga siendo válida después de cambiar la fase.
Si una configuración cambia y deja una regla incompatible, se mostrará:
La regla “Ronda de 16 → BO3” ya no pertenece a esta estructura.

Opciones:
Eliminar regla.
Ajustar regla.
Cancelar cambio.
Guardar como borrador inválido, solamente si la política del sistema lo permite.
Una fase inválida no podrá ejecutarse en Competition Lab.

31. Compatibilidad con configuraciones existentes
Las fases actuales se interpretarán así:
configuration_mode: BASIC
input_mode: POOL
routing_mode: AUTOMATIC
entrants_per_match: 2
qualifiers_per_match: 1
encounter_profile: DUEL

No se deberán eliminar:
Configuración general.
Overrides.
Puertas existentes.
Conexiones del Tournament Graph.
Estado.
Visibilidad.
Código de la fase.
La migración deberá ser incremental y permitir valores predeterminados.

32. Integración con Competition Lab
Competition Lab deberá ejecutar la fase según su modo.
32.1 Modo básico
Continuará utilizando el motor tradicional.
32.2 Modo avanzado
El proceso será:
Recibir participantes por las puertas de entrada.
Validar capacidades.
Distribuirlos en slots.
Crear los encuentros disponibles.
Simular o registrar resultados.
Resolver las salidas de cada encuentro.
Enviar clasificados a los encuentros siguientes.
Enviar eliminados a sus destinos.
Activar los siguientes encuentros cuando sus entradas estén completas.
Emitir las salidas externas de la fase.
registrar el recorrido de cada participante.
Ejemplo de recorrido:
Entrada Federación A
→ Seed 3
→ Encuentro de Cuartos 2
→ Ganador
→ Semifinal 1
→ Segundo resultado
→ Repechaje
→ Ganador
→ Salida Clasificados


33. Historial y trazabilidad
Cada movimiento deberá poder registrarse como evento:
Participante recibido.
Seed asignado.
Slot asignado.
Encuentro creado.
Resultado registrado.
Participante clasificado.
Participante eliminado.
BYE concedido.
Ruta recorrida.
Salida emitida.
Esto permitirá reconstruir completamente la historia de cada competidor.

34. Plan de implementación
Etapa 1 — Mejora inmediata de la interfaz
Barra persistente de guardado.
Indicador de cambios pendientes.
Ayudas compactas.
Secciones plegables.
Separación visual por colores.
Resumen más claro.
Vistas resumen, bloques y tabla.
Corrección de textos desactualizados.
Mejor comportamiento responsive.
Etapa 2 — Corrección funcional
Limitar overrides.
Validar rondas en backend.
Detectar overrides obsoletos.
Validar contrato y objetivo.
Actualización inmediata del preview.
Mensajes de error específicos.

Etapa 3 — Fundamento avanzado (implementada)
Modo básico y avanzado.
Participantes por encuentro.
Clasificados por encuentro.
Perfil de encuentro.
Política de sobrantes.
Modos de entrada.
Modos de enrutamiento.

Etapa 4 — Puertas y estructura interna
Puertas de entrada.
Slots.
Encuentros.
Resultados internos.
Conexiones internas.
Puertas de salida.
Validación del grafo interno.

Etapa 5 — Visualizador por bloques
Bloques por ronda.
Tarjetas de encuentros.
Vista compacta.
Vista detallada.
Vista de tabla.
Inspector.
Indicadores de rutas y estados.
Sin dependencia de zoom.

Etapa 6 — Competition Lab
Resolución de entradas.
Ejecución multicompetidor.
Múltiples clasificados.
Rutas internas.
Puertas de resultados.
Historial completo.

Etapa 7 — Biblioteca de tipos de batalla
Tipos reutilizables.
Esquemas de entrada.
Reglas de resultado.
Campos de puntuación.
Empates.
Posiciones.
Compatibilidad con fases.

35. Pruebas necesarias
35.1 Pruebas tradicionales
8 participantes, un ganador.
16 participantes, un ganador.
8 participantes, dos supervivientes.
BYEs activados.
BYEs desactivados.
Best of general.
Override de final.
35.2 Pruebas multicompetidor
3 entran y 1 avanza.
4 entran y 1 avanza.
4 entran y 2 avanzan.
Cantidad sobrante.
Encuentro incompleto.
Ronda preliminar.
35.3 Pruebas de entradas
Entrada agrupada.
Una entrada por seed.
Entrada por lote.
Entrada híbrida.
Entrada vacía.
Varias conexiones hacia una puerta.
Capacidad excedida.
35.4 Pruebas de rutas
Ganador a ronda siguiente.
Segundo a repechaje.
Eliminado a salida.
Resultado sin destino.
Ruta circular.
Conexión duplicada.
Ramas que convergen.
35.5 Pruebas visuales
Escritorio.
Tablet.
Móvil.
Vista compacta.
Vista detallada.
Vista tabular.
Bloques plegados.
Barra persistente.
Mensajes de validación.

36. Criterios de aceptación
La ampliación se considerará correcta cuando:
Una eliminación tradicional siga siendo fácil de configurar.
Los datos actuales continúen funcionando.
No puedan crearse overrides para rondas inexistentes.
El botón de guardado esté siempre accesible.
La interfaz reduzca explicaciones permanentes.
La configuración pueda entenderse mediante bloques.
No sea necesario utilizar zoom.
Exista una vista compacta para estructuras grandes.
Las entradas estén diferenciadas de los slots.
Puedan definirse varias puertas de entrada.
Una puerta pueda recibir uno o varios participantes.
Puedan existir encuentros de más de dos participantes.
Puedan clasificarse varios participantes.
Los resultados puedan seguir destinos diferentes.
Las salidas externas sean compatibles con Tournament Graph.
Competition Lab pueda registrar el recorrido.
Las configuraciones imposibles sean detectadas.
El modo avanzado no complique el modo básico.
La interfaz mantenga los colores y estilo de OmniMerge.
La arquitectura quede preparada para tipos de batalla.

37. Resultado esperado
El usuario principiante podrá realizar:
Seleccionar Eliminación clásica
→ indicar ocho participantes
→ elegir BO3
→ guardar
→ bracket listo

El usuario avanzado podrá construir:
Varias puertas de entrada
→ algunas reciben lotes
→ otras alimentan seeds concretos
→ encuentros de cuatro participantes
→ dos clasifican por encuentro
→ el primero sigue por la rama principal
→ el segundo entra a repechaje
→ los demás salen como eliminados
→ las ramas internas pueden encontrarse posteriormente
→ los clasificados salen por puertas diferentes

La fase conservará un contrato externo comprensible:
Entradas
→ proceso interno
→ salidas

La decisión principal será:
Eliminación Simple será una fase configurable con estructura interna de encuentros, entradas, resultados y rutas, representada mediante bloques compactos y vistas alternativas, mientras mantiene una conexión externa sencilla con el Tournament Graph.
De esta forma, OmniMerge podrá soportar tanto una eliminación tradicional como competencias complejas sin abandonar su diseño modular, reutilizable y escalable.

