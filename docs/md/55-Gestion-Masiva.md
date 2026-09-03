# Gestión masiva de entidades

**Fecha:** 3 de septiembre de 2026
**Alcance:** la pantalla de editar muchas entidades a la vez.

---

## 1. El problema

Es la pantalla más peligrosa de la aplicación: cambia decenas de entidades de
un golpe y varias de sus acciones no se deshacen. Y se usaba **a ciegas** —se
elegía un valor, se pulsaba, y el resultado se veía después—.

Además eran 6.010 líneas en un archivo, con el motor de Alpine (1.012) mezclado
con el marcado, y sobre fondo claro cuando el resto del módulo ya era oscuro.

---

## 2. Lo que va a pasar, antes de que pase

Es lo que le faltaba, y ahora está en **las siete acciones**: un bloque que
enseña las entidades marcadas **con su cara** y el cambio dibujado —lo que
tienen ahora, tachado, y lo que van a tener—.

```
CAMBIARÁ EL TIPO DE 3 ENTIDADES
  Akamaru          Personaje  →  Lugar
  Chōji Akimichi   Personaje  →  Lugar
  Ebisu            Personaje  →  Lugar
```

```
PUBLICARÁ 3 ENTIDADES
  Akamaru          Activo · Privado  →  Archivada · Pública
```

Es **un solo componente** para las siete (`partials/preview.blade.php`), que
recibe dos expresiones —el valor de antes y el de después— y el color de la
acción. Si cada acción tuviera el suyo, la mitad acabaría sin enseñar nada.
Enseña las diez primeras y dice cuántas más hay: cuarenta caras no informan
más que diez.

---

## 3. Elegir sobre quién se actúa

Es la mitad más importante de la pantalla, porque todo lo demás opera sobre lo
que se marque. Tiene **tres maneras de mirar**:

- **Fichas** — la cara grande, para reconocer de un vistazo.
- **Lista** — una línea por entidad, con su cara pequeña, su tipo con el punto
  de su color y su estado.
- **Tabla** — para comparar: código, tipo, estado, visibilidad y cuántas
  colecciones.

Y conserva los **dos niveles de agrupación** que ya tenía —tipo, estado,
visibilidad o colección— con un botón para marcar el grupo entero. En un lote
de doscientas, «por tipo y luego por estado» encuentra en dos clics lo que una
lista plana esconde.

Cada entidad se pinta con el color de su tipo. Para eso, `entity_type_color`
viaja ahora en el payload: es un dato del usuario, no un token del diseño.

---

## 4. Las siete acciones

| pestaña | qué hace |
|---|---|
| **Rápida** | cambiarles el tipo, o escribirles la misma descripción |
| **Matriz** | una hoja de cálculo: las marcadas en filas, las características elegidas en columnas |
| **Características** | poner, añadir o quitar un valor; vaciar la característica o retirarla |
| **Colecciones** | meterlas en una, sacarlas de una, o dejarlas solo en las elegidas |
| **Estructura** | cómo se presenta cada característica, y en qué orden se ven |
| **Publicación** | estado, visibilidad y permiso de copia, con «dejarlo como está» por defecto |
| **Peligro** | archivarlas o borrarlas, con el modal de confirmación de siempre |

Cada una es su propio formulario al mismo endpoint, con su `operation` en un
campo oculto y las marcadas en `entity_ids[]`. Eso ya era así y no se tocó.

En **Características**, las cinco operaciones pasaron de un desplegable a cinco
tarjetas que explican en una línea qué hace cada una —la diferencia entre
«vaciarla» y «quitarla del todo» no se adivina de un nombre—. Y el editor del
valor es el mismo componente que usa la creación masiva, así que soporta los
ocho tipos de dato sin duplicar nada.

---

## 5. Lo que no se tocó

El motor —`bulkEditManager`, 1.012 líneas— **está intacto**: filtra, agrupa,
marca, arma el JSON de la matriz y ordena características. Lo único que se le
añadió desde el marcado es `pickView`, la propiedad que decide cómo se ven las
entidades al elegirlas.

Y los campos son los que espera `BulkEditEntityRequest`: `entity_ids[]`,
`operation`, `property`, `property_value`, `attribute_id`,
`attribute_value_json`, `collection_id`, `collection_ids[]`,
`attribute_order[]`, `matrix_payload`, `custom_label`, `notes`,
`presentation_*` y `publication_*`. Las quince operaciones siguen ahí.

---

## 6. Archivos

| archivo | qué es |
|---|---|
| `resources/views/entities/bulk-edit/index.blade.php` | reescrito: de 6.010 a 1.065 líneas, de las cuales 1.012 son el motor intacto |
| `.../bulk-edit/partials/head.blade.php` | nuevo: cabecera, avisos y filtros |
| `.../bulk-edit/partials/selection.blade.php` | nuevo: elegir, en tres vistas y con dos agrupaciones |
| `.../bulk-edit/partials/actions.blade.php` | nuevo: las siete acciones |
| `.../bulk-edit/partials/preview.blade.php` | nuevo: lo que va a pasar, para las siete |
| `app/Http/Controllers/Entities/BulkEditEntityController.php` | el payload lleva el color del tipo |

---

## 7. Verificación

- La pantalla responde **200** y el motor arranca con sus datos: 22 entidades,
  6 características, 4 tipos.
- Marcar tres entidades sube el contador a 3 y las tres quedan marcadas en la
  ficha.
- **La vista previa reacciona de verdad.** Eligiendo «Lugar» en cambiar el
  tipo: `Akamaru · Personaje → Lugar`, y lo mismo para las otras dos. Eligiendo
  archivar y hacer públicas: `Akamaru · Activo · Privado → Archivada ·
  Pública`.
- Los campos ocultos llevan el contrato correcto: `operation=set_property`,
  `property=entity_type_id` y un `entity_ids[]` por cada marcada (47, 45, 50).
- La **matriz** produce su JSON real en `matrix_payload`, con las propiedades y
  los valores de cada entidad marcada.
- Las **tres vistas** alternan: en tabla salen las 22 filas con su cara, su
  código, su tipo, su estado y su visibilidad.
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
