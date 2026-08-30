# El editor de características estaba muerto

En la Biblioteca, abrir una entidad y entrar en **Características originales**
mostraba la pantalla entera —el buscador, los filtros, la rejilla de atributos,
el botón de añadir— y nada respondía. Crear una entidad nueva daba lo mismo: no
había forma de asignarle un atributo ni elegir un valor de su catálogo.

No faltaba nada por construir. Faltaban **dos llaves**.

## Qué pasaba

`selectedContextOptionIds()` —la función que reúne qué opciones hay elegidas,
para poder aplicar las reglas contextuales— tenía el cuerpo descuadrado:

```js
if (Array.isArray(value)) {
    for (const optionId of value) {
        if (optionId !== null && optionId !== '') {
            result.push(String(optionId));
        }
    }                    // ← esta cerraba el for…
} else if (…) {          // ← …y no quedaba ninguna para el if
```

El `else` acababa colgando del `for`, que no admite ninguno. Y más abajo, el
`return` quedaba **dentro** del bucle, con lo que la llave que debía cerrarlo
terminaba cerrando la función y dejando suelta la coma que la separa del
siguiente miembro del objeto.

## Por qué se rompía todo, y no solo esa función

Alpine evalúa el objeto `x-data` **entero, de una vez**. Un error de sintaxis en
cualquier punto no deja una función rota: deja el componente **sin existir**.
La consola lo decía con toda claridad, una línea por cada expresión de la
pantalla:

```
ReferenceError: isSelected is not defined
ReferenceError: attributeSearch is not defined
ReferenceError: matchesOption is not defined
```

De ahí el síntoma tan desconcertante: todo dibujado, nada vivo. El HTML lo pinta
el servidor y llegaba perfecto —los cuatro atributos y sus catálogos estaban en
la respuesta—; lo que no llegaba a arrancar era la parte que los enseña y
permite pulsarlos.

Y por eso fallaba en **las tres pantallas** a la vez: crear entidad, editar
entidad y «Características originales» incluyen el mismo parcial.

## Comprobado en el navegador

Sirviendo la página real y ejecutándola:

| Qué | Antes | Ahora |
|---|---|---|
| `x-data` evalúa | `Unexpected token 'else'` | sin error |
| Componente montado | no existe | montado |
| Atributos en el selector | ninguno utilizable | aldea, Elemento, Color cabello |
| Añadir uno | no hacía nada | pasa a la lista de asignados |
| Su catálogo | no aparecía | las 4 opciones, visibles y marcables |
| Campo que se envía | — | `attributes[2][]` |

Suite de tests: 88 pasan / 99 fallan, igual que antes.

## Nota para la próxima

Un `x-data` de trescientas líneas escrito dentro de un atributo HTML no tiene
quien lo revise: ni el linter de PHP, ni el de Blade, ni el build de Vite miran
ahí dentro. El único aviso llega en la consola del navegador, en tiempo de
ejecución, y solo si alguien la abre. Si este bloque vuelve a crecer, merece
salir a un componente de `resources/js`, donde una herramienta pueda leerlo.
