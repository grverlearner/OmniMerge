# El taller de torneos

**Fecha:** 2 de septiembre de 2026
**Alcance:** el panel de entrada al módulo de Torneos.

---

## 1. El problema

El panel contaba seis cifras, enseñaba las tres últimas plantillas y las tres
últimas fases, y explicaba en un diagrama cómo encajan las piezas. Todo
correcto y todo inútil para lo único que se hace al abrirlo: **decidir en qué
trabajar ahora**.

Un número no dice nada por sí solo. «25 fases» no distingue un taller sano de
uno con once fases que ningún torneo usa y tres que no dejan salir a nadie.

---

## 2. Cuatro preguntas, no una lista de cifras

El panel se reorganizó alrededor de cuatro preguntas, en este orden:

| pregunta | bloque |
|---|---|
| **qué falta** | lo que está a medias y no se puede jugar |
| **qué está vivo** | competiciones reales montadas sobre tus plantillas |
| **qué tocaste** | por dónde ibas la última vez |
| **de qué va todo** | el reparto por motor y por tipo, para ver los huecos |

Las cifras siguen arriba porque se leen de un vistazo, pero han dejado de ser
lo importante.

---

## 3. Qué falta por terminar

Es el bloque nuevo que de verdad cambia el panel. No son avisos de estilo:
cada línea describe **algo que impide jugar**, o una pieza que se quedó
suelta. Van ordenadas por gravedad y cada una lleva el enlace a la biblioteca
ya filtrada.

| gravedad | qué detecta |
|---|---|
| 🔴 | Torneos sin ninguna fase — el recorrido está vacío |
| 🔴 | Torneos sin ningún final — nadie llega a ninguna parte |
| 🔴 | Torneos sin ninguna entrada — nadie puede empezar |
| 🟠 | Fases sin salidas — se juegan, pero desde ellas no avanza nadie |
| 🟠 | Fases sin puertas de entrada — ningún torneo puede conectarles gente |
| 🔵 | Fases que no usa ningún torneo — hechas y esperando |
| 🔵 | Borradores parados — sin tocar desde hace más de un mes |

Lo que ya está bien **no ocupa sitio**: las líneas con cuenta cero se retiran,
y si no queda ninguna el bloque dice «Nada pendiente» en vez de enseñar siete
ceros. La portada resume el estado en una frase, con el color de la peor
gravedad que haya.

---

## 4. Tus plantillas, en juego

Una plantilla deja de ser un ejercicio cuando alguien la juega. Este bloque
lista las competiciones reales montadas sobre plantillas propias —las que
están en curso primero, porque son las únicas donde se puede intervenir— con
su universo, su plantilla de origen, cuánta gente compite y su estado, con un
punto que late cuando está en marcha.

Al pie, la cuenta completa: cuántos torneos de universo usan tus plantillas y
cuántas competiciones se han montado en total.

Si no se ha jugado nada, el bloque explica **por qué** —una plantilla se juega
desde un universo, aquí solo se diseña— en vez de enseñar un hueco.

---

## 5. Por dónde ibas

Ordenado por última **modificación** y no por creación: al volver, lo que
interesa es lo que se estaba tocando. Torneos y fases van mezclados en una
sola lista porque el trabajo se hace saltando de una biblioteca a otra. Cada
ficha lleva su imagen, su tipo, su código, un resumen de su forma y cuánto
hace que se tocó, en español (`->locale('es')->diffForHumans()`).

---

## 6. De qué está hecho el taller

- **Reparto por motor**: cuántas fases de eliminación directa, todos contra
  todos, grupos y suizo, con su barra y su porcentaje. Cada una enlaza a la
  biblioteca ya filtrada por ese motor. Un taller con un solo motor es un
  taller con un hueco, y el reparto lo enseña de un vistazo.
- **Reparto por tipo de torneo**: copa, liga, clasificatorio, amistoso,
  ranking, especial o sin clasificar.
- **Las fases que más se repiten**: una fase usada por cinco torneos es
  infraestructura, y tocarla toca cinco recorridos. Verlo evita el susto.

La categoría vive en `settings`, así que se cuenta en PHP sobre una proyección
mínima en lugar de con un `GROUP BY` sobre JSON: son decenas de filas, no
millones, y así funciona igual en MySQL y en SQLite.

---

## 7. Lo demás

- **Portada** con degradado, dos luces de fondo y los tres accesos que se usan
  de verdad: nuevo torneo, nueva fase, laboratorio.
- **Ocho cifras**, todas enlazadas a la vista que las contiene ya filtrada
  —«Activos» abre la biblioteca filtrada por estado activo, «Salidas» la abre
  ordenada por salidas—. Una cifra que no lleva a ninguna parte es decoración.
- **Cómo se monta un torneo**: las cinco piezas en orden —la fase, sus
  salidas, el torneo, el laboratorio, el universo—, cada una con su color, su
  número y su enlace.
- **Atajos** a las dos bibliotecas, el laboratorio, universos y entidades.

Todos los iconos son del juego `<x-omni-icon>`, no caracteres sueltos.

---

## 8. Archivos

| archivo | qué cambió |
|---|---|
| `app/Http/Controllers/Tournaments/TournamentDashboardController.php` | reescrito: siete métodos, uno por bloque de datos |
| `resources/views/tournaments/dashboard.blade.php` | reescrito: oscuro, diez secciones |

---

## 9. Verificación

- El panel responde **200** y dibuja sus diez secciones: portada, cifras, qué
  falta, en juego, por dónde ibas, cómo se monta, motores, tipos, más
  repetidas y atajos.
- Con datos reales detecta 3 fases sin salidas, 10 sin puertas de entrada y 11
  que ningún torneo usa; lista 5 competiciones en curso; reparte 25 fases en
  cinco motores.
- Las fechas relativas salen en español («hace 39 minutos», «hace 4 horas»).
- Renderizado con un usuario sin nada, los **seis** estados vacíos aparecen y
  ninguno rompe: «El taller está vacío», «Nada pendiente», «Todavía no se ha
  jugado nada», «Nada todavía», «Todavía no hay fases» y «Ninguna fase se usa
  todavía».
- La batería de pruebas se mantiene en 88 pasadas y 99 fallos —los mismos
  fallos de SQLite que ya existían—.
