Resultado de la auditoría
Revisé la interfaz actual de fases y torneos cruzando cada control con sus rutas, validaciones, controladores, servicios y ejecución en Competition Lab.
La conclusión es esta:
La eliminación personalizada de varios participantes y varios clasificados sí está implementada.
Los botones CRUD principales funcionan.
Hay varias opciones que se guardan, pero no modifican el comportamiento.
Hay configuraciones que funcionan solamente en la previsualización, pero no igual durante la competencia.
Hay botones visibles que actualmente terminan en error.
Hay pantallas antiguas que ya no forman parte del flujo actual.
Hay tipos de fase seleccionables que todavía no tienen motor.
No modifiqué código durante esta revisión.
Clasificación utilizada
Estado
Significado
✅
Funciona desde la interfaz hasta el motor
🟡
Funciona solamente en ciertas condiciones
🟠
Se guarda o se muestra, pero está parcialmente implementado
🔴
No modifica el comportamiento, está roto o produce un resultado engañoso
🧹
Código o interfaz antigua que ya no debería utilizarse


1. Problemas más importantes encontrados
1. Botones de edición de Suizo rotos 🔴
En la configuración Suiza aparecen botones para editar:
Desempates.
Reglas por ronda.
Reglas de clasificación.
Sin embargo, sus Requests de actualización tienen:
authorize(): false
y no contienen reglas completas.
Resultado: el botón se muestra, permite abrir el formulario, pero al guardar devuelve 403.
Archivos relacionados:
UpdateSwissTiebreakerRequest.php
UpdateSwissRoundRuleRequest.php
UpdateSwissAdvancementRuleRequest.php
Crear, eliminar y reordenar sí funciona. Lo roto es la edición.

2. LEAGUE y CUSTOM pueden seleccionarse, pero no tienen motor 🔴
La creación de fases permite elegir:
Liga.
Personalizada.
Pero Competition Lab solamente tiene motores para:
Eliminación simple.
Round Robin.
Grupos.
Suizo.
Actualmente se puede guardar una fase LEAGUE o CUSTOM, agregarla al Tournament Graph e incluso validar parte del grafo. El error aparece cuando intenta ejecutarse, porque no existe un motor compatible.
Estas dos opciones deberían:
Ocultarse temporalmente; o
Mostrarse deshabilitadas con “Próximamente”.
No deberían permitirse en un torneo ejecutable.

3. La estructura avanzada automática de eliminación no se ejecuta correctamente 🟠
La opción “Generar desde reglas” puede crear rondas y enfrentamientos avanzados. Sin embargo, el laboratorio avanzado solamente acepta estructuras cuyo modo sea:
MANUAL
HYBRID
El generador automático no deja siempre configurado ese structure_mode.
Resultado:
La estructura puede verse.
Puede aparecer como válida.
Pero Competition Lab puede rechazarla como estructura avanzada no ejecutable.
El grafo personalizado que construimos sí funciona porque establece el modo manual correctamente.

4. Best of, series y cantidad de juegos no están implementados realmente 🔴
Se muestran opciones como:
Mejor de 3.
Mejor de 5.
Juegos fijos.
Formato de serie.
Configuración de serie por ronda.
Pero actualmente un solo envío de resultado completa el encuentro entero.
No existe todavía:
Registro individual de juegos.
Marcador acumulado de la serie.
Cálculo de victorias necesarias.
Estado de cada juego.
Cierre automático al alcanzar, por ejemplo, 2 victorias en un Bo3.
Por tanto, best_of y series_format son actualmente metadatos visuales.

5. Hay diferencias entre la previsualización y Competition Lab 🔴
El caso más importante es merge_policy de las puertas de entrada del Tournament Graph.
La previsualización contempla políticas como:
APPEND
WAIT_ALL
FIRST_AVAILABLE
PRIORITY
Pero durante la ejecución real, Competition Lab espera todas las conexiones entrantes y une los participantes, sin interpretar completamente esa política.
Por ello, una estructura puede parecer correcta en Preview y comportarse de otra manera al ejecutarla.

6. Salidas “al ser eliminado” no ocurren en ese momento 🔴
Las salidas permiten seleccionar:
Al finalizar la fase.
Al ser eliminado.
Al activarse una regla.
Pero el runtime del torneo resuelve las salidas cuando finaliza el nodo completo.
Es decir, ON_ELIMINATION y ON_RULE_TRIGGER se guardan, pero no producen actualmente transferencias en tiempo real.

7. “Eliminados en una ronda” no filtra por ronda 🔴
Existe el selector:
ELIMINATED_IN_ROUND
En la previsualización se intenta aproximar el resultado utilizando el tamaño de ronda. Pero en la ejecución real se trata igual que ELIMINATED.
Resultado: devuelve todos los eliminados, no solamente los eliminados en la ronda configurada.

8. Varias opciones “Manual” no son realmente manuales 🔴
Este es uno de los principales problemas de claridad de la interfaz. Muchas opciones llamadas MANUAL fueron preparadas pensando en una futura interfaz de asignación, pero esa interfaz todavía no existe.
Más abajo incluyo una tabla completa.

9. Visibilidad y clonación no corresponden totalmente con lo que dice la interfaz 🔴
Las fases y plantillas de torneo tienen:
Privado.
No listado.
Público.
Permitir clonación.
Problemas:
UNLISTED se comporta prácticamente como privado para un usuario que no sea propietario.
allow_cloning no se comprueba correctamente al duplicar.
Un usuario autenticado puede duplicar una plantilla pública visible aunque allow_cloning=false.
En algunas vistas públicas se muestran botones de Editar, Archivar o Eliminar a usuarios que no son propietarios. Al presionarlos reciben 403.
La interfaz debería ocultar esos botones con autorización, no dejar que el usuario descubra el permiso mediante un error.

10. La cantidad de fases mostrada puede ser incorrecta 🔴
La lista de plantillas de torneo sigue contando la relación antigua:
TournamentTemplate -> phases
Pero el sistema actual utiliza:
TournamentPhaseNode
dentro del Tournament Graph.
Consecuencias:
Una plantilla con nodos actuales puede aparecer con 0 fases.
El orden “Más fases” puede ser incorrecto.
Se mezcla el sistema antiguo con el nuevo.

11. El sistema antiguo de fases todavía existe, pero está huérfano 🧹
Existe un CRUD anterior completo en:
resources/views/tournaments/phases/
Este sistema utiliza TournamentPhase, mientras que el flujo actual utiliza:
Phase Templates.
Tournament Phase Nodes.
Tournament Graph.
Competition Lab.
No encontré navegación normal hacia esas pantallas antiguas. Solamente quedan rutas, archivos y enlaces internos.
Ese módulo antiguo no debería continuar influyendo en conteos ni presentarse como parte del sistema actual.

12. Falta control de tipos de fase durante la validación del grafo 🔴
El Tournament Graph puede considerar estructuralmente válido un nodo de tipo LEAGUE o CUSTOM, aunque no exista motor para ejecutarlo.
La validación debería detectar:
Esta fase no tiene un motor ejecutable en Competition Lab.
antes de iniciar la competencia.

2. Auditoría de todas las opciones “Manual”
Opción manual
Estado
Comportamiento actual
Modo Manual de Competition Lab
✅
Permite probar una fase aislada con participantes seleccionados
Grafo personalizado de eliminación
✅
Permite crear etapas, encuentros, slots y rutas manualmente
Selección manual de clasificados
✅
El usuario selecciona quiénes clasifican en un encuentro
Resolución MANUAL_SELECTION
🟠
Funciona, pero es prácticamente igual a RANKING; no hay ranking numérico
Resto MANUAL en eliminación
🟡
Detiene la generación automática y obliga a construir el grafo personalizado
Sembrado MANUAL
🔴
Se comporta como orden de entrada; no hay editor de posiciones
Asignación manual de BYE
🔴
Se guarda, pero no existe una pantalla para asignar los BYE
Distribución manual de una puerta
🔴
Se guarda, pero los participantes siguen distribuyéndose por orden de entrada
Comportamiento manual de puerta vacía
🔴
Se guarda, pero el runtime no lo interpreta
Distribución manual en grupos
🔴
Preview muestra asignación pendiente; Lab termina distribuyendo secuencialmente
Orden manual en Round Robin
🔴
Se comporta igual que orden de entrada
Desempate manual de corte
🔴
No pausa ni solicita decisión manual
BYE manual en Suizo
🔴
No permite elegir; termina asignando al participante de menor posición
Fallback manual en Suizo
🔴
Se guarda, pero no se utiliza
Routing mode manual
🟠
Solo tiene efecto real cuando se utiliza el editor de grafo
Modo personalizado de entradas
🟠
Cambia etiquetas/metadatos, no el algoritmo de distribución

La recomendación es no eliminar necesariamente todos estos valores del modelo, porque sirven como contrato futuro. Pero en la interfaz deberían estar deshabilitados hasta contar con su editor correspondiente.

3. Configuración general de una fase
Opción
Estado
Observación
Nombre, descripción e imagen
✅
Se guardan y se muestran
Capacidad exacta
✅
Se valida en motores y estructuras
Capacidad mínima/máxima
✅
Se valida
Múltiplo de participantes
✅
Se usa durante la validación
Estado
✅
Activa, borrador y archivada funcionan
Visibilidad
🟠
Público funciona; “No listado” no tiene comportamiento de compartir mediante enlace
Permitir clonación
🔴
Se guarda, pero no protege correctamente la duplicación
Tipo individual/equipo/flexible
🔴
Es metadato; no cambia validación ni ejecución
Permitir BYE
🟡
Se usa en Eliminación y Suizo; no tiene efecto útil en otras fases
Best of
🔴
Se muestra, pero no existe motor de series
Tipo League/Custom
🔴
Se puede guardar, pero no ejecutar

También existe un filtro de visibilidad en el controlador de listado, pero la interfaz no muestra el selector correspondiente.

4. Eliminación simple
Lo que sí funciona
Eliminación básica.
Orden aleatorio.
Redistribución o reseeding por ronda.
Cantidad de participantes por encuentro.
Cantidad de clasificados por encuentro.
Configuración diferente por ronda.
Modos de finalización por ganador o sobrevivientes.
BYE.
Preliminares.
Enfrentamientos incompletos.
Rechazar una cantidad inválida.
Grafo manual K → Q.
Rutas desde una posición clasificatoria hacia otro encuentro.
Salidas personalizadas del grafo.
Opciones parciales o sin efecto
Opción
Estado
Qué sucede
encounter_profile=CUSTOM
🔴
El calculador lo marca como no ejecutable
RANKING en sembrado
🔴
Se comporta como orden de entrada
MANUAL en sembrado
🔴
Se comporta como orden de entrada
STANDARD_SEEDED
🟠
En Lab básico termina usando primero contra último
SEQUENTIAL
🟠
También termina usando primero contra último
bye_assignment=TOP_SEEDS
🔴
No cambia la asignación
bye_assignment=RANDOM
🔴
No cambia la asignación
bye_assignment=MANUAL
🔴
No cambia la asignación
routing_mode
🟠
Se guarda, pero el generador no diferencia realmente sus variantes
GROUPED, HYBRID, CUSTOM como entrada
🟠
Automáticamente terminan creando una sola puerta
Series por ronda
🔴
No existe ejecución juego por juego


5. Grafo personalizado de eliminación
La funcionalidad central que solicitaste sí es real:
Un encuentro puede recibir varios participantes.
Puede clasificar más de uno.
Cada posición clasificatoria puede dirigirse a un slot concreto.
Se detectan slots ocupados.
Se impide eliminar elementos que aún tienen dependencias.
Se pueden producir salidas distintas.
El runtime selecciona y enruta múltiples clasificados.
Problemas pendientes:
Opción
Estado
Observación
Rama MAIN/SECONDARY/REPECHAGE/CUSTOM
🟠
Es etiqueta; no existe lógica especial de repechaje
Editar una etapa
🔴
Solo se puede crear o eliminar; no renombrar ni cambiar número
Editar una ruta
🟠
Hay que eliminarla y crearla nuevamente
Resolución RANKING
🟠
Usa la misma selección que MANUAL_SELECTION
qualifier_ordering=UNORDERED
🔴
El orden de selección continúa determinando las rutas
Series del encuentro
🔴
Solo etiqueta
Slots requeridos
✅
El encuentro se activa al llenarse exactamente
Salidas internas del grafo
✅
Funcionan con múltiples clasificados
Selector genérico MATCH_WINNERS
🔴
En K→Q solo toma correctamente el primer ganador
Selector genérico MATCH_LOSERS
🔴
Fue diseñado alrededor de encuentros de dos participantes

Para estructuras K→Q conviene utilizar las salidas y rutas internas del grafo, no los selectores genéricos de ganador/perdedor.

6. Puertas de entrada
Funciona correctamente
Capacidad exacta o por rango.
Selección de slots destino.
Visualización de slots ocupados.
Orden explícito de slots.
Estado activo/inactivo.
Protección contra regeneración cuando la puerta está bloqueada.
Restricción de múltiples conexiones en el Tournament Graph.
Validación de puertas requeridas.
Se guarda, pero no se ejecuta
Opción
Estado
Observación
Tipo POOL/PER_SEED/GROUPED/HYBRID/CUSTOM
🟠
Principalmente etiqueta
Distribución RANKING
🔴
Usa orden de entrada
Distribución RANDOM
🔴
Usa orden de entrada en el runtime personalizado
Distribución BALANCED
🔴
Usa orden de entrada
Distribución EXTREMES
🔴
Usa orden de entrada
Distribución MANUAL/CUSTOM
🔴
Usa orden de entrada
empty_behavior
🔴
No se consulta durante la ejecución
accepts_batch
🔴
No cambia el comportamiento
Prioridad de puerta
🟠
El orden real utiliza primero sort_order, que no puede editarse en la interfaz
merge_policy
🔴
Preview y ejecución real no coinciden


7. Salidas de una fase
Selectores funcionales
Sobrevivientes.
Eliminados.
Top N.
Bottom N.
Posición específica.
Rango de posiciones.
Todos.
Restantes.
Reglas del motor.
Ganadores/perdedores, con limitaciones en K→Q.
Opciones problemáticas
Opción
Estado
Problema
Eliminados en una ronda
🔴
Devuelve todos los eliminados
Al ser eliminado
🔴
Espera al final de la fase
Al activarse una regla
🔴
Espera al final de la fase
Ganadores de encuentros
🟠
Significa “ganó al menos un encuentro”, no necesariamente campeón
Perdedores de encuentros
🟠
Significa “perdió al menos uno”
Prioridad de salida
✅
Importante: una salida consume participantes antes que la siguiente
Salidas del grafo personalizado
✅
Tienen prioridad y son la opción correcta para K→Q


8. Round Robin
Funciona
Cantidad de ciclos.
Permitir empates.
Puntos por victoria, empate y derrota.
Calendario balanceado.
Orden aleatorio inicial.
Envío y simulación de resultados.
Clasificación.
Reordenamiento de criterios.
Salidas.
No funciona o funciona parcialmente
Opción
Estado
Observación
Orden por ranking
🔴
Igual que orden de entrada
Orden manual
🔴
Igual que orden de entrada
Política de empate en el corte
🔴
Siempre termina resolviendo por desempates y seed
HEAD_TO_HEAD
🔴
Está disponible, pero el runtime no lo calcula
GAME_DIFFERENCE
🟠
Es alias de diferencia de puntuación
GAME_WINS
🟠
Es alias de puntos anotados
Best of
🔴
No hay motor de series

Las políticas INCLUDE_ALL, RANDOM, MANUAL o PLAYOFF para empates de clasificación actualmente no cambian nada.

9. Fase de grupos
Funciona
Cantidad fija de grupos.
Tamaño objetivo.
Capacidades personalizadas.
Distribución aleatoria.
Distribución snake.
Pot draw.
Ciclos y puntos.
Reglas de clasificación.
Reglas específicas por grupo.
Clasificación comparada entre grupos.
Normalización de resultados.
Problemas
Opción
Estado
Observación
Distribución manual
🔴
Lab distribuye secuencialmente
Política de empate en el corte
🔴
Se guarda, pero no se consulta
Best of interno
🔴
Solo etiqueta
BALANCED vs FIRST_GROUPS
🟠
En muchos casos producen la misma distribución de sobrantes
Pot draw
🟡
Funciona, pero no existe una fuente de ranking o editor de bombos
“Desempates entre grupos”
🟠
La misma cadena también se usa dentro de cada grupo
GAME_*
🟠
Son aliases de estadísticas de puntos


10. Sistema Suizo
Es la fase con más opciones visibles todavía sin implementación completa.
Funciona
Número fijo de rondas.
Primera ronda aleatoria.
Primera ronda por mitades sembradas.
Top contra bottom.
Prevención o penalización de revancha.
Empates y puntuación.
Puntos por BYE.
Máximo de BYE.
BYE por peor posición, seed bajo o aleatorio.
Clasificación.
Reglas de avance.
Reglas por número de ronda.
Varios desempates reales.
Problemas importantes
Opción
Estado
Observación
Clasificar por cantidad de victorias
🔴
Los campos existen, pero no cambian el estado del participante
Eliminar por cantidad de derrotas
🔴
Tampoco se aplica automáticamente
Algoritmo de emparejamiento
🔴
Las tres alternativas usan el mismo algoritmo
Base WIN_LOSS
✅
Usa victorias
Base MATCH_POINTS
✅
Usa puntos
Base PAIRING_SCORE
🟠
También usa puntos; no hay pairing score independiente
Política de flotantes
🔴
Se guarda, pero no se usa
Balance de lados
🔴
No llega al runtime
Puntuación externa inicial
🔴
No hay forma de introducirla
Aceleración
🔴
Existe en Preview, pero no en Competition Lab
BYE manual
🔴
Termina asignando al peor clasificado
Política de corte
🔴
No se consulta
Política fallback
🔴
No se consulta
Reglas “partido de clasificación/eliminación”
🔴
Solo funciona realmente la regla por número de ronda
HEAD_TO_HEAD
🔴
No se calcula
Omitir peor rival
🔴
No omite al peor; usa la suma completa
Best of
🔴
Solo etiqueta
Botones Editar
🔴
Devuelven 403

Para que Suizo sea confiable, necesita un sprint específico.

11. Tournament Graph
Funciona
Crear inicios.
Crear nodos de fases.
Crear terminales.
Crear conexiones.
Eliminar componentes.
Duplicar nodos.
Asignación ALL, TAKE_N, PERCENTAGE y REMAINDER.
Validación de contratos de puertos.
Múltiples conexiones.
Prioridad de conexiones.
Presets básicos.
Ejecución en Competition Lab.
Faltantes o problemas
Elemento
Estado
Observación
Editar nodo
🔴
El backend tiene parte de la actualización, pero la interfaz activa no ofrece formulario
Editar conexión
🔴
Igual: existe soporte parcial, no UI
Editar inicio
🔴
Request de actualización bloqueado
Editar terminal
🔴
Request bloqueado
Editar puerto
🔴
Request bloqueado
Crear/eliminar puertos desde builder activo
🔴
Las rutas existen, pero faltan controles
Source type
🟠
Es etiqueta; los participantes se comportan igual
Terminal type
🟠
Salvo Champion y su cantidad esperada, el resto es principalmente etiqueta
Auto layout
🟠
Actualiza coordenadas, pero la interfaz actual es una lista por etapas, no un canvas arrastrable
Posición X/Y
🟠
No se aprovecha visualmente en el builder actual
Preset Groups → Knockout
🟠
No obliga a que las fases seleccionadas sean realmente Grupos y Eliminación
Preset Swiss → Playoffs
🟠
Tampoco verifica los tipos
Salida usada por preset
🟠
Escoge automáticamente la primera salida activa; el usuario no puede elegir
Validación de motores
🔴
No rechaza League/Custom
Límites globales en Preview
🟠
Competition Lab los valida, Preview no siempre


12. Interfaz, navegación y archivos sobrantes
Elementos obsoletos
resources/views/tournaments/phases/*: sistema anterior de fases.
resources/views/tournaments/graph/show.blade.php: vista anterior del grafo, no es la que renderiza el controlador actual.
builder-flow.blade.php: parcial sin uso en la interfaz activa.
builder-toolbar.blade.php: parcial sin uso.
single-elimination-internal-diagnostic.blade.php: diagnóstico antiguo sin referencias activas.
Textos desactualizados
El Dashboard todavía indica que:
Tournament Graph está planificado.
Phase Node está planificado.
Connections están planificadas.
Competition Lab está planificado.
Esas funcionalidades ya existen.
Sidebar
“Recompensas — Próximo”: correcto porque está deshabilitado.
“Comunidad — Próximo”: engañoso, porque el enlace y la pantalla ya funcionan parcialmente.

13. Lo que no encontré roto
En el visualizador de eliminación personalizada, los controles principales sí están conectados:
Resumen.
Bloques.
Tabla.
Filtros.
Densidad.
Inspector.
Trazado de rutas.
Diagnósticos.
Cambio entre Auto, Personalizado y Revisión.
Conservación del número de participantes con el ajuste UX actual.
Los botones principales de Competition Lab también están conectados:
Inicializar.
Pausar.
Continuar.
Reiniciar.
Simular ronda.
Simular encuentro.
Enviar resultado.
Seleccionar clasificados.
Cerrar laboratorio.

Decisión recomendada
El siguiente entregable no debería agregar todavía más configuraciones. Debería ser un sprint de integridad funcional de la interfaz.
Prioridad 0: evitar engaños y errores
Reparar o esconder los botones Editar de Suizo.
Deshabilitar League y Custom hasta tener motores.
Ocultar acciones no autorizadas en vistas públicas.
Aplicar realmente allow_cloning.
Corregir la cantidad de fases para usar los nodos actuales.
Sacar el sistema antiguo de fases del flujo actual.
Validar motores antes de ejecutar un Tournament Graph.
Marcar Best of y las opciones manuales no implementadas como “Próximamente”.
Prioridad 1: hacer coincidir Preview y ejecución
Implementar merge_policy en Competition Lab.
Corregir ELIMINATED_IN_ROUND.
Implementar o retirar temporalmente ON_ELIMINATION.
Hacer ejecutable la estructura avanzada generada automáticamente.
Unificar el calculador Suizo con el runtime real.
Prioridad 2: completar funcionalidades prometidas
Editor manual de grupos.
Editor manual de seed y BYE.
Emparejamientos Suizos reales.
Clasificación/eliminación Suiza por récord.
Políticas de empate.
Motor de series Best of.
Edición completa de etapas, puertas, conexiones y terminales.
Mi recomendación concreta es empezar por la Prioridad 0. Es el cambio que más confianza dará al usuario: cada opción visible funcionará, indicará claramente sus requisitos o aparecerá deshabilitada hasta estar disponible.

