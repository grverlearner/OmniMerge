# FASE 14 — Group Stage: estructura, entradas y salidas

> 22 de agosto de 2026.

## 1. Qué le faltaba

Group Stage era el único motor con una sola pestaña propia:

| | Estructura | Entradas y salidas | Simulador |
|---|---|---|---|
| Single Elimination | sí | sí | sí |
| Round Robin | (no la necesita) | sí | sí |
| **Group Stage** | **no** | **no** | sí |

Su estructura no se veía en ninguna parte y sus salidas vivían escondidas
dentro de «Reglas», mezcladas con la configuración de clasificación.

## 2. Caras prestadas

`PreviewCastService` toma prestadas entidades del usuario —nombre e
imagen— para dibujar previsualizaciones y simulaciones.

**No guarda nada.** No inscribe a nadie, no toca el Universo, no deja
rastro. Cada figurante lleva `is_borrowed` para que ninguna pantalla lo
confunda con un competidor real, y en el simulador `entity_id` sigue a
`null`.

Prefiere entidades de Universo (ya tienen copia propia de imagen) y cae a
la Biblioteca; si el usuario no tiene suficientes, rellena con figurantes
anónimos en vez de dejar huecos.

## 3. Pestaña «Estructura»

Muestra el flujo completo en tres columnas: **entran → se reparten en
grupos → salen**.

Cada grupo se dibuja con los retratos que le tocarían, su tamaño, sus
enfrentamientos y sus jornadas. El número de participantes es ajustable
para probar la configuración con distintos tamaños sin guardar nada.

Si la configuración no cuadra con ese número, la pantalla lo dice con los
errores concretos del validador en vez de dibujar un cuadro falso.

## 4. Pestaña «Entradas y salidas»

**Entradas**: cada puerta con su tipo, política de mezcla y —lo nuevo— el
**grupo destino**. Se guarda en el JSON `settings` de la puerta, que ya
existía; una columna nueva habría obligado a migrar para algo que solo
Group Stage entiende.

**Salidas**: cada puerta con su selector y su momento, y **qué regla de
clasificación la alimenta**. Una salida a la que no apunta ninguna regla
se señala explícitamente: no la cruzará nadie.

## 5. Simulador

Sigue usando el token cifrado efímero que ya tenía: **nunca toca la base
de datos**. Lo que cambia es que ahora los participantes tienen cara, en
la tabla de cada grupo y en cada enfrentamiento.

## 6. Todo se configura en su sitio

La seccion de entradas y salidas es **autosuficiente**: crea, edita y
borra puertas de entrada sin mandar al usuario a otra pestana, y crea y
borra puertas de salida devolviendolo aqui mismo.

Las puertas de entrada no reutilizan `PhaseInputGateService`: aquel
sincroniza slots del cuadro interno, marca la estructura como
desactualizada y refresca los puertos de Single Elimination. Nada de eso
existe en una fase de grupos, donde una puerta apunta a un grupo, no a un
slot. `GroupStageGateService` hace solo lo que esta fase necesita.

Una salida solo se puede borrar si ninguna regla de clasificacion la
alimenta: borrar la puerta por la que alguien iba a salir seria romper la
fase en silencio.

## 7. Fuera de alcance

El **grupo destino se guarda y se muestra, pero el reparto en tiempo de
ejecución todavía no lo honra**: el asignador trabaja con cantidades, no
con participantes identificados, y hacer que respete la puerta de origen
es un cambio de contrato del runtime que merece su propia fase.

Tampoco se implementó el simulador a pantalla completa estilo arena: el
simulador actual funciona, ya muestra retratos, y reescribir su interfaz
entera es trabajo independiente del contrato de la fase.
