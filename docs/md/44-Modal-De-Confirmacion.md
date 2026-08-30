# El modal de confirmación, en oscuro

El modal era una tarjeta blanca con cabecera degradada, heredada de cuando la
aplicación era clara. Hoy casi todo lo que confirma algo —la arena, la Super
Edición, los diseñadores— es oscuro, así que confirmar significaba que te
saltara a la cara un rectángulo blanco.

## Lo que cambia, y lo que no

**No cambia nada del comportamiento.** Mismo estado, mismos métodos, mismo
contrato de `data-confirm-*` en los formularios. Cualquier
`<form data-omni-confirm>` sigue funcionando sin tocarlo:

```
title  message  detail  subject  image  icon
actionLabel  cancelLabel
variant   danger | warning | primary | violet | success
open  submitting  close()  approveAndSubmit()
```

**Cambia la piel:** fondo casi negro, borde fino, esquinas de 16 px, y el color
reservado para lo único que importa —qué clase de acción vas a confirmar—.

## Cómo está armado

- Una **franja de color** de 1 px arriba, del color de la variante. Es lo único
  que grita, y grita poco.
- El **icono en su chip**, con borde y fondo teñidos por la variante, junto a la
  etiqueta —«Acción destructiva», «Configuración»…— y el título.
- La **ficha de lo que se va a tocar**: imagen y nombre, en su propia caja. Es
  media razón de existir del modal: confirmar «eliminar» sin ver *qué* se
  elimina no es confirmar nada.
- La **letra pequeña** con un borde izquierdo del color de la variante.
- Los **botones abajo**, separados por una línea, con el fondo un punto más
  oscuro: cancelar como borde y confirmar en el color de la variante.

Las clases de cada variante se escriben literales en cada sitio, nunca
compuestas: Tailwind lee el archivo y una clase armada con `'bg-' . $x` no
existiría en el CSS.

Se conserva el `x-if` del retrato en vez de `x-show`: un `<img>` oculto con
`src=""` hace al navegador volver a pedir la página entera, que fue un fallo
real de esta misma pantalla.

## Comprobado en el navegador

Abriendo el modal por el camino de verdad —enviando un `<form data-omni-confirm>`—
sobre la ficha de una entidad:

| Qué | Resultado |
|---|---|
| Se abre por el interceptor | sí, con título, mensaje, detalle y sujeto |
| Panel | 512 × 357, `slate-900`, borde `slate-800` de 1 px, radio 16 px |
| Franja y botón, variante `danger` | rose |
| Variantes `warning` / `primary` / `violet` / `success` | ámbar / índigo / violeta / esmeralda, con su etiqueta |
| Botones | «Cancelar» y la acción, con su hilandera al enviar |
| Suite de tests | 88 pasan / 99 fallan — igual que antes |
