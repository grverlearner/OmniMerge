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

---

# Y se aplicó en todas partes

Había **29 llamadas a `confirm()`** del navegador repartidas por la aplicación.
El modal existía, y aun así la mitad de las confirmaciones seguían siendo el
cuadro gris del sistema. Ya no queda ninguna.

## Cómo se sustituyó cada una

### Formularios: sin una línea de JavaScript

El interceptor global ya abre el modal ante cualquier `<form data-omni-confirm>`
y lee su copia de los atributos `data-confirm-*`. Así que un
`onsubmit="return confirm('¿Eliminar este trofeo?')"` pasa a ser:

```html
data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
data-confirm-title="Eliminar trofeo"
data-confirm-message="Este trofeo dejará de estar disponible…"
data-confirm-subject="{{ $trophy->name }}"
data-confirm-image="{{ $trophy->image_url }}"
data-confirm-action="Sí, eliminar trofeo"
```

Y de paso deja de ser una pregunta seca: dice **qué** se borra, con su cara, y
**qué se pierde**.

Dentro de los bucles de Alpine los atributos van **enlazados**
—`:data-confirm-subject="gate.name"`— porque el nombre sale del bucle. El
interceptor lee el dataset en el momento del envío, así que ve el valor ya
resuelto.

### JavaScript: preguntar y esperar

`window.OmniConfirm.request({...})` devuelve una `Promise<boolean>`. El método
pasa a ser `async` y la guarda queda igual de legible:

```js
if (! await window.OmniConfirm.request({
    variant: 'primary',
    title: 'Simular la fase entera',
    message: 'Se resolverán todos los enfrentamientos de la liga.',
    detail: 'Podrás reiniciarla después: esto no toca el diseño de la fase.',
})) {
    return;
}
```

### El caso raro: un submit síncrono

`deleteWithConfirmation()` del Tournament Graph se ata a `@submit`, y un modal
no puede responder a tiempo para decidir si el envío sigue. Ahora **siempre**
detiene el envío y lo relanza si el usuario acepta, marcando el formulario como
ya aprobado para no volver a preguntar en el segundo paso.

## Dónde estaban

| Sitio | Cuántas |
|---|---|
| Super Edición de fases · puertas, salidas, grupos, criterios | 7 |
| Tournament Graph · rutas, puertas, piezas del recorrido | 5 |
| Simuladores de liga, grupos y eliminación | 7 |
| Universo · trofeos, recompensas, competiciones | 4 |
| Competition Lab · reiniciar y cerrar | 2 |
| Arena · resolver todo sin jugar | 1 |
| Grafo · borrado genérico | 1 |
| Formulario de borrado del recorrido | 1 |

## Comprobado

Ocho pantallas renderizadas, todas 200, y **ninguna** con un `confirm(` nativo
en su HTML. Abriendo el modal por el camino real —enviando el formulario de
cancelar una competición— llega con su copia completa:

> ⚠ **ACCIÓN DESTRUCTIVA · Cancelar la competición**
> Se conservará su historial, pero no podrá continuar.
> *SOBRE · Torneo Doble entrada — edición 1*
> Lo ya jugado se queda como está. Lo que falte no se jugará nunca.
> `Cancelar` `Sí, cancelarla`

Suite de tests: 88 pasan / 99 fallan, igual que antes. `npm run build` compila.

## El modal faltaba en un layout

Sustituidos los `confirm()`, pulsar la × de una puerta o de una salida en la
**Super Edición** dejó de hacer absolutamente nada.

El motivo: `layouts/arena` —el que usan la arena y la Super Edición— **nunca
incluyó el modal**. Los otros cinco layouts sí. Sin el modal en la página, el
interceptor detiene el envío del formulario y lanza un evento que no escucha
nadie: el formulario no se manda y no aparece ningún diálogo.

Con `confirm()` no se notaba, porque el cuadro del sistema no necesita estar en
el DOM. El hueco llevaba ahí desde siempre y solo se hizo visible al quitarlo.

Añadido `<x-omni-confirm-modal />` a ese layout.

| Pantalla | Modal en la página | Formularios que lo usan |
|---|---|---|
| Super Edición · eliminación directa | sí | 2 |
| Super Edición · grupos | sí | 4 (8 en el DOM con sus repeticiones) |
| Super Edición · liga | sí | 2 |
| Arena · jugar la competición | sí | por JavaScript |

Probados los ocho formularios de la Super Edición de grupos, uno a uno: cada uno
abre el modal con su propia copia —«Entrada X», «Entrada Y», «Entrada XX»,
«Entrada YY», «Clasificados», «Eliminados» y los dos criterios de avance—, con
el nombre resuelto desde el bucle de Alpine.
