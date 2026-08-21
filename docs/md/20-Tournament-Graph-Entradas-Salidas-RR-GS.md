# Cierre de Round Robin y Group Stage como ciudadanos reales del Tournament Graph

## 1. Objetivo de esta tarea

El usuario reportó, con razón, que Round Robin y Group Stage no se sentían
como motores completamente integrados al Tournament Graph — específicamente
señaló que "Round Robin no tiene implementadas correctamente las puertas de
entrada/salida y Group Stage todavía no las tiene". El encargo fue: auditar
la arquitectura real de Single Elimination como referencia, auditar el estado
real de RR y GS, identificar qué falta, documentar el plan, e implementar sin
esperar aprobación intermedia.

Este documento registra esa auditoría completa, incluyendo el hallazgo más
importante: **la arquitectura de entradas/salidas del Tournament Graph ya
era genérica y ya soportaba los 4 motores por igual antes de esta tarea**.
Lo que sí estaba roto era un bug real y concreto, encontrado por ejecución
empírica (no por lectura de código), que afectaba a los 4 motores por igual
— no solo a RR/GS. Ambos hechos se explican en detalle abajo.

## 2. Metodología

A diferencia de las Fases 1-3 (donde se auditó código y después se construyó
UI nueva), aquí la auditoría inicial de código sugería que el backend ya
estaba completo. En vez de confiar en esa lectura, se construyó un torneo
real de punta a punta usando la interfaz (cuenta de prueba, Fases GS/RR/SE,
Tournament Templates, Tournament Graph, Competition Lab) y se ejecutó el
Runtime completo. Esa verificación empírica reveló el problema real, que la
sola lectura de código no habría mostrado con certeza.

## 3. Auditoría: arquitectura de Single Elimination como referencia

SE tiene dos conceptos de entrada que a menudo se confunden:

- **`PhaseInputGate`**: una definición **a nivel de Fase** (no de grafo),
  exclusiva de Single Elimination, que mapea explícitamente qué participante
  llega a qué **slot** del bracket persistido (`PhaseSingleEliminationSlot`).
  Existe porque SE es el único motor que persiste su estructura interna
  (rondas/encuentros/slots) y necesita decidir posición por posición. Se
  gestiona desde `PhaseInputGateController`/`PhaseInputGateService`, ambos
  con `abort_unless($phaseTemplate->phase_type === 'SINGLE_ELIMINATION', 404)`
  — es decir, **deliberadamente exclusivo de SE**, no una abstracción que RR/GS
  deban replicar.
- **`PhaseEntryPort`**: el concepto **genérico y real** de entrada al nivel
  del grafo (`TournamentPhaseNode`). Es completamente independiente del
  motor — cualquier `TournamentPhaseNode`, sin importar su `phase_type`,
  recibe uno automáticamente. `TournamentGraphNodeService::create()`
  contiene la lógica exacta:

  ```php
  if ($phaseTemplate->inputGates()->exists()) {
      // Solo ocurre en SE: proyecta cada PhaseInputGate a un PhaseEntryPort
      $this->entryPortSynchronizer->syncNode($node, $phaseTemplate->inputGates()->get());
  } else {
      // RR, GS y Swiss (y SE sin gates definidos) caen aquí:
      // se crea una única puerta "Entrada principal" genérica.
      $node->entryPorts()->create([...]);
  }
  ```

  Esa puerta genérica (`merge_policy: APPEND`, sin restricciones de
  cantidad) es exactamente lo que Round Robin y Group Stage necesitan,
  porque ninguno de los dos reparte participantes a posiciones específicas
  — ambos reciben una bolsa de participantes y la procesan internamente
  (RR los enfrenta a todos entre sí; GS los distribuye a grupos según su
  propio `distribution_mode`).

**Conclusión de esta parte de la auditoría**: no existe una carencia
arquitectónica de "puertas de entrada" para RR/GS. Lo que existe es una
puerta de entrada **más simple, y correctamente más simple**, porque su
naturaleza no requiere direccionamiento posición por posición. Replicar el
sistema de `PhaseInputGate` para RR/GS habría sido forzar una abstracción
que no les corresponde — exactamente el tipo de error que el propio pedido
del usuario advertía evitar ("si una lógica es conceptualmente diferente...
debe permanecer separada").

## 4. Auditoría: routing y runtime (`TournamentGraphRuntimeService`)

Se leyó por completo `TournamentGraphRuntimeService`, `RuntimeConnectionRouter`
y `RuntimeOutcomeResolver`. Los tres son **100% genéricos por motor**:

- `evaluateNode()` arma `$participantIds` desde `node['entry_ports']` (sin
  ningún `if ($phaseType === ...)`) y llama a
  `LabPhaseEngineManager::prepare($nodeModel->phaseTemplate, ...)`, que
  despacha al motor correcto.
- `routeNode()` resuelve las Phase Exits con `RuntimeOutcomeResolver`, que ya
  distingue automáticamente entre motores que resuelven sus propias salidas
  (RR y GS, via `$runtime['outcomes']`) y los que no (SE, via selectores
  genéricos TOP_N/RANK_RANGE/etc.) — sin duplicar resolución.
- `loadGraph()` ya eager-carga las relaciones de RR y GS
  (`roundRobinSetting`, `groupStageSetting`, `groupStageAdvancementRules`,
  etc.) exactamente igual que las de SE.
- El constructor de nodos en el grafo (`TournamentGraphController::show()`)
  filtra las Fases disponibles únicamente con
  `$this->engineManager->supports($phaseTemplate->phase_type)`, verdadero
  para los 4 motores — no hay ninguna lista blanca que excluya a RR/GS.

**Conclusión**: esta capa tampoco tenía ninguna carencia real.

## 5. El bug real: configuración ausente al ejecutar desde el grafo

Se construyó un Tournament Graph real: `Inicio (4) → Group Stage (2 grupos
de 2, Top 1 clasifica) → Single Elimination (final 2→1) → Terminal`, y por
separado `Inicio (4) → Round Robin (Top 2 / resto eliminado) → 2 Terminales`.
Ambos grafos se validaron como **"Válido"** en el constructor. Al ejecutar el
primero (GS → SE) en el Laboratorio, terminó correctamente. Al ejecutar el
segundo (RR solo) el Runtime se quedó congelado en estado `RUNNING` para
siempre, sin ningún diagnóstico visible en la UI del grafo.

Investigando el estado interno del Lab se encontró el error real:
`"La fase no tiene una configuración Round Robin."` — lanzado por
`RoundRobinLabEngine::prepare()`:

```php
$settings = $phase->roundRobinSetting;
if (! $settings) {
    $this->fail('La fase no tiene una configuración Round Robin.');
}
```

**Causa raíz**: la fila `PhaseRoundRobinSetting` de una Fase solo se crea
quien visita la pestaña "Reglas" de esa Fase (`RoundRobinController::show()`
llama a `RoundRobinSettingsService::ensure()`, que hace `firstOrCreate`). Si
un diseñador crea la Fase y la coloca directamente en un Tournament Graph
sin haber abierto nunca su pestaña "Reglas" — algo perfectamente razonable,
porque nada en la interfaz obliga a ese paso — la fila de configuración
simplemente no existe, y el motor revienta en tiempo de ejecución. Como la
excepción se lanza a mitad de `TournamentGraphRuntimeService::step()`, el
estado nunca se persiste correctamente: el cliente queda con el token previo
al fallo, la cola de operaciones sigue teniendo `DISPATCH_START` pendiente, y
el grafo **parece congelado en vez de mostrar un error claro**.

Se confirmó que este mismo patrón — exactamente el mismo `if (! $settings)
{ fail(...) }` — existe **en los 4 motores por igual**:
`SingleEliminationLabEngine`, `SingleEliminationGraphRuntime`,
`RoundRobinLabEngine`, `GroupStageLabEngine` y `SwissLabEngine`. No es un
problema de RR o de GS específicamente: es un problema general del sistema
de Competition Lab que solo se manifestaba con más frecuencia en RR/GS
porque, a diferencia de SE, nada en su flujo de configuración obliga a
generar una "estructura" antes de poder usarlos (SE, sin querer, se protegía
de este bug porque su propio flujo de "Generar estructura" ya garantiza la
fila de configuración como efecto secundario).

## 6. La corrección

Se reemplazó, en los 5 puntos de entrada (`SingleEliminationLabEngine`,
`SingleEliminationGraphRuntime`, `RoundRobinLabEngine`, `GroupStageLabEngine`,
`SwissLabEngine`), el patrón "leer la relación y fallar si es null" por una
llamada directa al `*SettingsService::ensure()` correspondiente — el mismo
servicio, ya probado, que la pestaña "Reglas" de cada motor usa desde hace
tiempo. Por ejemplo:

```php
// Antes
$settings = $phase->roundRobinSetting;
if (! $settings) { $this->fail('La fase no tiene una configuración Round Robin.'); }

// Después
$settings = $this->settingsService->ensure($phase);
```

`ensure()` es idempotente (`firstOrCreate`) y ya se ejecuta en producción
cada vez que alguien visita la pestaña "Reglas" — reutilizarlo aquí no
introduce lógica nueva, solo elimina la dependencia de que ese visiteo haya
ocurrido antes de que el Tournament Graph intente ejecutar la Fase. Para SE
en modo STRUCTURE_GRAPH (`SingleEliminationGraphRuntime`) se conservó intacta
la verificación de `structure_status !== 'VALID'`, porque esa sí es una
decisión real que no debe auto-generarse (la estructura del bracket no tiene
un "default" seguro).

Verificado en navegador tras la corrección: el mismo Tournament Graph de
Round Robin que antes se congelaba ahora termina en `COMPLETED`, con las
puertas de salida entregando exactamente los participantes esperados
(2 clasificados, 2 eliminados). El grafo GS → SE se re-verificó también para
confirmar que no hubo regresión.

## 7. Cambios de interfaz

Aunque el backend ya funcionaba, la interfaz de RR y GS nunca mencionaba
nada sobre "entrada" — su pestaña "Entrada y salida" (RR) y "Reglas" (GS)
solo mostraban salidas. Esa asimetría visual frente a la página de SE
(`Entradas y salidas`, que sí muestra `PhaseInputGate`s con gran detalle) es,
en la práctica, la razón más probable por la que RR/GS "se sentían" sin
entradas reales aunque funcionalmente sí las tuvieran. Se agregó una sección
"Entrada de la fase" en ambas páginas explicando: (a) el contrato de
participantes de la propia Fase, y (b) cómo se genera y edita la puerta de
entrada real una vez que la Fase se coloca en un Tournament Graph. No se
construyó una réplica del editor de `PhaseInputGate` de SE porque, como se
explicó en la sección 3, esa réplica sería una abstracción forzada — RR y GS
no tienen "slots" que direccionar.

## 8. Qué NO se hizo (y por qué)

- **No se creó un sistema de múltiples puertas de entrada por grupo para
  Group Stage.** GS distribuye internamente con un solo algoritmo
  (`GroupStageAllocator`) sobre un único conjunto de entrada; introducir
  múltiples puertas de entrada físicas rompería esa distribución y no
  corresponde a ningún caso de uso real hoy.
- **No se tocó `PhaseInputGate` ni su sincronizador.** Siguen siendo
  exclusivos de SE, correctamente.
- **No se agregaron tests automatizados**, por instrucción explícita del
  usuario. La verificación fue 100% manual/navegador, incluyendo la
  reproducción exacta del bug antes de corregirlo y su confirmación después.
- **No se modificó el modelo de datos.** La corrección reutiliza servicios
  existentes; no hay migraciones nuevas.

## 9. Archivos modificados

- `app/Services/Tournaments/CompetitionLab/Engines/RoundRobinLabEngine.php`
- `app/Services/Tournaments/CompetitionLab/Engines/GroupStageLabEngine.php`
- `app/Services/Tournaments/CompetitionLab/Engines/SwissLabEngine.php`
- `app/Services/Tournaments/CompetitionLab/Engines/SingleEliminationLabEngine.php`
- `app/Services/Tournaments/CompetitionLab/Engines/SingleEliminationGraphRuntime.php`
- `resources/views/tournaments/phase-templates/round-robin-io.blade.php`
- `resources/views/tournaments/phase-templates/group-stage.blade.php`

## 10. Guía de pruebas manuales

1. Crea una Fase Round Robin nueva (o Group Stage) y **NO abras su pestaña
   "Reglas"** — ve directo a "Entrada y salida"/"Reglas" solo para agregar
   una puerta de salida (ej. "Clasificados", Mejores N = 2), luego a "Entrada
   y salida" para confirmar que ahora aparece la sección "Entrada de la
   fase" con el contrato de participantes.
2. Crea una segunda Fase (Single Elimination u otra) para recibir la salida.
3. Crea un Tournament Template nuevo, ve a su pestaña "Camino", agrega un
   Inicio, agrega ambas Fases como Nodes, agrega un destino final.
4. Conecta: Inicio → entrada de la primera Fase; salida de la primera Fase →
   entrada de la segunda; salida de la segunda → destino final. Verifica que
   el estado del grafo quede "Válido".
5. Ve a "Laboratorio", pulsa "Preparar Competition Lab", elige "Simulación
   completa" y pulsa "Iniciar recorrido".
6. Resultado esperado: el Runtime avanza y termina en `COMPLETED` sin
   quedarse congelado — incluso si nunca visitaste la pestaña "Reglas" de
   ninguna de las dos Fases antes de este flujo. Antes de esta corrección,
   este mismo caso dejaba el Runtime colgado en `RUNNING` para siempre.
7. Repite el mismo flujo combinando Group Stage con Single Elimination (o
   con Round Robin) para confirmar que el bug también estaba presente y
   ahora está resuelto en ese motor.

## 11. Criterio de finalización (contra la lista original del usuario)

- ✓ Configuración funcional (ya lo estaba).
- ✓ Estructura funcional (ya lo estaba; RR/GS deliberadamente no persisten
  estructura interna, por diseño ya documentado en Fases 2-3).
- ✓ Entrada real: confirmada como ya funcional a nivel de grafo (`PhaseEntryPort`
  genérico); ahora además visible y explicada en la interfaz de cada motor.
- ✓ Salida real: ya funcional (Fases 1-3), reconfirmada aquí en un grafo
  multi-fase real.
- ✓ Participan correctamente en Tournament Graph: confirmado por ejecución
  real de dos grafos distintos.
- ✓ Routing correcto: confirmado (participantes contados exactamente en cada
  terminal).
- ✓ Runtime coherente: el bug que lo rompía está corregido y verificado.
- ✓ Simulador propio: ya existía desde Fases 2-3, sin cambios necesarios.
- ✓ La interfaz ahora refleja la capacidad de entrada real (antes no la
  mencionaba).
- ✓ No dependen de crear previamente un torneo para poder probar la Fase
  (el Simulador de Fases 2-3 sigue funcionando igual).
- ✓ Documentación actualizada (este documento).
