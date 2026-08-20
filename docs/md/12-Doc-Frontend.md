# DOCUMENTACIÓN TÉCNICA Y FUNCIONAL DEL FRONTEND DE OMNIMERGE

## 1. Introducción

El frontend de OmniMerge constituye la capa mediante la cual el usuario crea, administra, organiza, explora y posteriormente conectará todos los recursos proporcionados por el backend. Su función no se limita a presentar formularios CRUD. Conforme el proyecto ha evolucionado, la interfaz también ha pasado de pantallas administrativas convencionales a verdaderos **espacios de trabajo interactivos**, con diferentes modos de visualización, filtros, navegación contextual, personalización persistente, previsualizaciones, constructores visuales y herramientas específicas para dominios complejos como Versiones y Torneos.

La estructura actual mantiene el enfoque tradicional de Laravel mediante vistas Blade renderizadas desde el servidor, pero incorpora Alpine.js para proporcionar reactividad localizada en aquellos lugares donde realmente resulta necesaria. Esta combinación permite que la mayor parte de la aplicación continúe utilizando formularios, enlaces, rutas y validaciones convencionales de Laravel, mientras componentes más avanzados funcionan como pequeñas aplicaciones interactivas dentro de las páginas.

La presente documentación describe exclusivamente la **arquitectura frontend de OmniMerge**, tomando como referencia el estado actual de la rama `main` del repositorio al 13 de agosto de 2026. El repositorio organiza actualmente las vistas principales en módulos como `attribute-groups`, `attribute-options`, `attributes`, `auth`, `collections`, `community`, `dashboard`, `entities`, `entity-types`, `entity-versions`, `hub`, `profile`, `tournaments` y `versions`, además de layouts, componentes y parciales reutilizables.

---

# 2. Filosofía general del frontend

El frontend actual puede describirse mediante el siguiente modelo:

```text
Laravel
   │
   ▼
Controller
   │
   ▼
Blade View
   │
   ├── HTML
   ├── Tailwind CSS
   ├── Blade Components
   ├── Blade Partials
   │
   └── Alpine.js
           │
           ├── estados locales
           ├── modales
           ├── tabs
           ├── filtros visuales
           ├── previews
           ├── localStorage
           ├── drag & drop
           └── fetch selectivo
```

No existe actualmente una SPA construida con React, Vue o Angular.

Tampoco existe una separación total entre frontend y backend mediante API.

En su lugar se utiliza una arquitectura de:

> **Server-rendered Laravel + reactive islands.**

Es decir, Laravel genera la página completa y Alpine.js toma control únicamente de determinados componentes interactivos.

Esta arquitectura es especialmente apropiada para OmniMerge en su etapa actual porque la mayoría de las operaciones siguen siendo:

```text
Mostrar formulario
        ↓
Enviar POST / PUT / DELETE
        ↓
Laravel valida
        ↓
Redirect
        ↓
Mostrar resultado
```

mientras solamente determinadas herramientas necesitan comportamiento más complejo en el navegador.

---

# 3. Tecnologías utilizadas

El `package.json` actual incorpora como principales tecnologías frontend:

| Tecnología          | Función                             |
| ------------------- | ----------------------------------- |
| Blade               | Sistema de plantillas               |
| Tailwind CSS 3      | Estilos y diseño                    |
| Alpine.js 3         | Reactividad ligera                  |
| Vite 7              | Bundling y servidor de desarrollo   |
| Laravel Vite Plugin | Integración Laravel/Vite            |
| Axios               | Peticiones HTTP JavaScript          |
| PostCSS             | Procesamiento CSS                   |
| Autoprefixer        | Compatibilidad CSS                  |
| @tailwindcss/forms  | Normalización visual de formularios |
| Figtree             | Tipografía principal                |

El proyecto declara `tailwindcss ^3.1.0`, Alpine `^3.4.2`, Axios `^1.11.0`, Vite `^7.0.7` y Laravel Vite Plugin `^2.0.0`.

---

# 4. Vite como sistema de construcción

La configuración de Vite es deliberadamente sencilla.

Los dos puntos principales de entrada son:

```text
resources/css/app.css
resources/js/app.js
```

La configuración también activa `refresh`, permitiendo recarga automática durante desarrollo cuando cambian archivos relacionados con Laravel.

Conceptualmente:

```text
resources/css/app.css
           │
           ├─────────┐
           │         │
resources/js/app.js  │
           │         │
           ▼         ▼
              VITE
               │
               ▼
        assets compilados
```

Los layouts llaman:

```php
@vite([
    'resources/css/app.css',
    'resources/js/app.js'
])
```

por lo que toda la aplicación comparte la misma entrada principal de estilos y JavaScript.

---

# 5. Tailwind CSS

Tailwind constituye prácticamente todo el sistema visual de OmniMerge.

`resources/css/app.css` contiene solamente:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

No existe actualmente una hoja de estilos propia extensa con cientos de clases personalizadas.

Esto significa que la identidad visual está escrita fundamentalmente mediante clases como:

```text
bg-slate-950
text-slate-500
rounded-2xl
border-slate-200
shadow-sm
bg-indigo-600
hover:bg-indigo-700
```

directamente dentro de Blade.

Esta decisión aporta mucha velocidad de desarrollo, aunque también tendrá consecuencias de mantenimiento conforme aumenten las vistas.

---

# 6. Configuración de Tailwind

`tailwind.config.js` analiza:

```text
resources/views/**/*.blade.php
```

además de las vistas de paginación de Laravel y las vistas compiladas almacenadas.

También extiende la familia tipográfica `sans` utilizando Figtree y activa el plugin `@tailwindcss/forms`.

La configuración todavía es mínima.

No existe actualmente algo como:

```text
colors.omnimerge.primary
colors.library.primary
colors.tournaments.primary
borderRadius.omniCard
```

Los colores semánticos se expresan directamente mediante clases Tailwind.

---

# 7. Observación sobre las dependencias de Tailwind

Existe una pequeña inconsistencia técnica que conviene documentar.

`package.json` contiene simultáneamente:

```text
tailwindcss ^3.1.0
@tailwindcss/vite ^4.0.0
```

pero `vite.config.js` solamente registra `laravel-vite-plugin`; el plugin `@tailwindcss/vite` no está siendo utilizado allí.

Esto no significa necesariamente que la aplicación esté fallando.

Sí significa que existe una dependencia que debería revisarse en una futura limpieza.

Si OmniMerge permanece en Tailwind 3:

```text
@tailwindcss/vite
```

probablemente no sea necesario.

Si se decide migrar a Tailwind 4, debería realizarse una migración intencionada y completa.

---

# 8. Alpine.js

Alpine.js constituye la principal herramienta de interacción cliente.

Se importa globalmente en:

```text
resources/js/app.js
```

y se expone mediante:

```javascript
window.Alpine = Alpine;
```

antes de ejecutar:

```javascript
Alpine.start();
```

Esto permite utilizar directamente en Blade:

```text
x-data
x-show
x-model
x-if
x-transition
x-cloak
@click
@keydown
@click.outside
```

sin necesidad de construir componentes Vue o React.

---

# 9. Filosofía de Alpine en OmniMerge

Alpine se utiliza principalmente para **estado efímero del navegador**.

Ejemplos:

```text
¿Está abierto el menú?

¿Qué pestaña está activa?

¿Qué modo de visualización eligió el usuario?

¿Debe mostrarse este campo?

¿Qué imagen acaba de seleccionar?

¿Qué nodo del grafo está seleccionado?

¿Qué versión se está previsualizando?

¿Está abierto un modal?

¿Qué salida del grafo está esperando conexión?
```

Esta es una utilización adecuada de Alpine.

No intenta reemplazar Laravel como backend.

---

# 10. Axios

`resources/js/bootstrap.js` importa Axios y lo expone mediante:

```javascript
window.axios
```

También establece:

```text
X-Requested-With = XMLHttpRequest
```

como cabecera predeterminada.

Sin embargo, el frontend no depende exclusivamente de Axios.

Algunas herramientas avanzadas utilizan también `fetch()` directamente.

Por tanto, actualmente existen dos mecanismos disponibles:

```text
Axios
fetch()
```

No supone un problema inmediato, aunque en una futura estandarización podría elegirse uno como convención para nuevas interacciones.

---

# 11. Arquitectura general de vistas

La estructura frontend principal es:

```text
resources/
├── css/
│   └── app.css
│
├── js/
│   ├── app.js
│   └── bootstrap.js
│
└── views/
    ├── auth/
    ├── dashboard/
    ├── hub/
    ├── profile/
    │
    ├── entities/
    ├── entity-types/
    ├── attributes/
    ├── attribute-options/
    ├── attribute-groups/
    ├── collections/
    │
    ├── community/
    │
    ├── versions/
    ├── entity-versions/
    │
    ├── tournaments/
    │
    ├── components/
    ├── layouts/
    ├── partials/
    │
    └── welcome.blade.php
```

Esta organización muestra una transición desde simples vistas CRUD hacia vistas organizadas por **dominio funcional**.

---

# 12. Una característica importante: OmniMerge no tiene un único layout

Una de las decisiones más importantes del frontend actual es que no todas las partes de la aplicación utilizan la misma estructura visual.

Existen varios shells o entornos.

Conceptualmente:

```text
PUBLIC WEBSITE
      │
      ▼
welcome.blade.php


AUTH
      │
      ▼
guest.blade.php


CENTRO OMNIMERGE
      │
      ▼
hub.blade.php


BIBLIOTECA
      │
      ▼
app.blade.php


TORNEOS
      │
      ▼
tournaments.blade.php
```

Esto es importante porque cada módulo cumple una función diferente para el usuario.

---

# 13. Razón de tener varios layouts

No es simplemente duplicación estética.

Cada layout comunica un contexto.

## Área pública

Debe:

```text
explicar el producto
atraer
presentar posibilidades
invitar a registrarse
```

## Auth

Debe:

```text
guiar al usuario
hacia el acceso
o creación de cuenta
```

## Hub

Debe:

```text
mostrar OmniMerge como plataforma
y permitir entrar a los módulos
```

## Biblioteca

Debe:

```text
maximizar productividad
administrando recursos
```

## Torneos

Debe:

```text
comunicar que el usuario
ha entrado a un Designer competitivo
```

Por ello no sería aconsejable forzar todos estos contextos dentro de una única navegación genérica.

---

# 14. Layout principal de Biblioteca

`resources/views/layouts/app.blade.php` constituye el shell principal de las herramientas de Biblioteca.

Su estructura es aproximadamente:

```text
<body>
    ├── Sidebar
    │
    └── Área principal
        ├── Header
        └── Main
            ├── Alert
            └── contenido de la vista

    └── OmniConfirm modal
</body>
```

Utiliza:

```text
bg-slate-100
text-slate-900
```

y reserva `lg:pl-72` para el sidebar fijo de escritorio. El contenido se limita generalmente mediante `max-w-7xl`. También monta globalmente el sistema de alertas y el modal de confirmación de OmniMerge.

---

# 15. Sidebar de Biblioteca

El sidebar principal es una pieza importante de navegación.

Su diseño utiliza:

```text
slate-950
```

como fondo oscuro y aproximadamente:

```text
w-72
```

como anchura.

La navegación está orientada a conceptos, no solamente a tablas.

La jerarquía funcional puede entenderse como:

```text
Centro OmniMerge
       ↑

Biblioteca

Dashboard

Entidades
├── Entidades
├── Tipos
└── Colecciones

Características
├── Atributos
└── Grupos

Catálogos

Comunidad
```

El sidebar contiene además acceso al usuario y su contexto.

---

# 16. Navegación móvil de Biblioteca

El sidebar no permanece permanentemente visible en dispositivos pequeños.

El layout mantiene:

```text
sidebarOpen
```

mediante Alpine.

En escritorio:

```text
lg:
sidebar fijo
```

En móvil:

```text
overlay
+
panel lateral
```

Esta estrategia es apropiada porque conserva el espacio útil de la pantalla.

---

# 17. Header de Biblioteca

El header principal funciona como segunda capa de contexto.

Permite mostrar el nombre de la sección actual y dispone de controles relacionados con navegación y usuario.

Visualmente mantiene:

```text
fondo blanco
border inferior
sticky
backdrop blur
```

lo que produce una interfaz de herramienta productiva.

---

# 18. Layout de autenticación

El layout `guest.blade.php` está considerablemente más personalizado que el Breeze original.

Actualmente utiliza una estructura de dos paneles en pantallas grandes:

```text
┌──────────────────────────┬───────────────────────┐
│                          │                       │
│   BRAND / EXPLICACIÓN    │      FORMULARIO       │
│                          │                       │
│   OmniMerge              │                       │
│   Create Connect Evolve  │                       │
│   descripción            │                       │
│   ventajas               │                       │
│                          │                       │
└──────────────────────────┴───────────────────────┘
```

El panel izquierdo utiliza fondo `slate-950`, luces difuminadas indigo/violet y mensajes sobre entidades, atributos, colecciones y comunidad. En móvil, el contenido de marca se reduce y el formulario ocupa prácticamente toda la experiencia.

---

# 19. Identidad de Auth

El usuario que entra a OmniMerge no se encuentra con un login genérico.

El layout comunica desde el primer momento:

> “Tus ideas no deberían estar limitadas por un formulario.”

También presenta conceptos como:

```text
Entidades libres
Atributos dinámicos
Colecciones
Comunidad
```

Esto es importante porque enseña el producto incluso antes del registro.

---

# 20. Vistas de autenticación

El repositorio posee:

```text
confirm-password.blade.php
forgot-password.blade.php
login.blade.php
register.blade.php
reset-password.blade.php
verify-email.blade.php
```

Funcionalmente:

| Vista            | Objetivo                                          |
| ---------------- | ------------------------------------------------- |
| Login            | Acceso de usuario                                 |
| Register         | Registro de nueva cuenta                          |
| Forgot Password  | Solicitud de recuperación                         |
| Reset Password   | Definición de nueva contraseña                    |
| Verify Email     | Verificación de email                             |
| Confirm Password | Confirmación de identidad para acciones sensibles |

Estas vistas reutilizan el mismo lenguaje visual de autenticación.

---

# 21. Layout Hub

`hub.blade.php` constituye el layout más claramente orientado a mostrar OmniMerge como **plataforma**.

Utiliza:

```text
bg-slate-950
text-slate-100
```

junto con luces ambientales grandes:

```text
indigo
violet
fuchsia
```

y navegación translúcida mediante:

```text
bg-slate-950/80
backdrop-blur-xl
border-white/10
```

Este lenguaje se diferencia deliberadamente de la Biblioteca clara.

---

# 22. Propósito del Hub

La Biblioteca dice:

> “Administra tus datos.”

El Hub dice:

> “Estás dentro de OmniMerge.”

Esto es importante para la escalabilidad futura.

Con el tiempo pueden aparecer:

```text
Biblioteca
Torneos
Universos
Simulación
Comunidad
Estadísticas
```

El Hub podrá convertirse en el punto desde el cual el usuario cambia entre todos esos módulos.

---

# 23. Navegación del Hub

El layout contiene navegación hacia:

```text
Centro
Biblioteca
Comunidad
Inicio público
```

y un menú de usuario con:

```text
Perfil y cuenta
Página pública
Cerrar sesión
```

Esto establece una jerarquía global distinta de la navegación interna de Biblioteca.

---

# 24. Dashboard del Hub

`hub/index.blade.php` es actualmente una de las vistas grandes del frontend, con aproximadamente 1446 líneas.

Su función no es solamente mostrar números.

Funciona como un **selector de módulos y estado global de la plataforma**.

Puede presentar:

```text
Biblioteca
Comunidad
módulos disponibles
módulos futuros
estadísticas globales
actividad de creación
```

La interfaz utiliza grandes superficies oscuras, gradientes indigo/violet/fuchsia y tarjetas de alto impacto visual.

---

# 25. Página pública principal

`welcome.blade.php` es otra superficie completamente diferenciada.

Actualmente tiene aproximadamente **2156 líneas**, lo que demuestra que ya no es la pantalla de bienvenida estándar de Laravel.

Su función es marketing y explicación del producto.

El mensaje principal gira alrededor de:

```text
Crea cualquier cosa.
Conecta todo.
```

y explica OmniMerge como plataforma de construcción y conexión de ideas.

---

# 26. Diferencia entre Landing, Hub y Biblioteca

Esta distinción debe conservarse.

```text
LANDING
↓
“¿Qué es OmniMerge?”


HUB
↓
“¿Qué módulo quiero utilizar?”


BIBLIOTECA
↓
“¿Qué recurso quiero administrar?”


TORNEOS
↓
“¿Qué competición quiero diseñar?”
```

No son cuatro pantallas duplicadas.

Representan cuatro niveles diferentes de relación entre el usuario y el producto.

---

# 27. Layout de Torneos

El módulo Torneos dispone de su propio:

```text
layouts/tournaments.blade.php
```

que mantiene la estructura funcional de Biblioteca pero utiliza:

```text
partials.tournaments.sidebar
partials.tournaments.header
```

y monta también el sistema global de confirmación.

Conceptualmente:

```text
Tournament Sidebar
        │
Tournament Header
        │
Tournament Workspace
```

Esto permite que Torneos tenga identidad propia sin cambiar toda la arquitectura frontend.

---

# 28. Sidebar de Torneos

El sidebar de Torneos utiliza:

```text
slate-950
```

como fondo, pero cambia el acento principal hacia:

```text
amber
orange
```

La navegación incluye actualmente áreas como:

```text
Dashboard

Diseño
├── Torneos
└── Fases

Pruebas
└── Laboratorio

Recursos
└── Recompensas

Descubrir
└── Comunidad
```

Algunas áreas aparecen explícitamente como próximas funciones.

---

# 29. Header de Torneos

El header del módulo mantiene el aspecto de herramienta productiva:

```text
fondo blanco
sticky
blur
borde inferior
```

pero incorpora la identidad:

```text
OmniMerge · Torneos
```

y utiliza `amber` como color contextual.

También permite salir hacia otros módulos, por lo que Torneos no se convierte en una aplicación aislada.

---

# 30. Sistema visual general

La paleta actual puede dividirse en capas.

## Neutros

La base utiliza intensivamente:

```text
slate-50
slate-100
slate-200
slate-300
slate-400
slate-500
slate-600
slate-700
slate-800
slate-900
slate-950
```

## Blanco

Se utiliza especialmente para:

```text
tarjetas
formularios
paneles
workspace productivo
```

## Negro azulado

`slate-950` actúa como fondo oscuro principal.

---

# 31. Color principal de Biblioteca

La Biblioteca utiliza fundamentalmente:

```text
indigo-500
indigo-600
indigo-700
```

con `violet` como acompañante frecuente.

Conceptualmente:

```text
INDIGO
=
creación
biblioteca
acción principal
identidad del workspace
```

---

# 32. Colores secundarios de Biblioteca

Los recursos reciben colores secundarios para facilitar reconocimiento.

Se observan patrones como:

```text
Entidad       → indigo
Atributo      → violet
Catálogo      → fuchsia
Colección     → cyan
Tipo          → amber
Grupo         → emerald
Comunidad     → violet
```

El dashboard, por ejemplo, utiliza diferentes fondos y colores para distinguir métricas de cada tipo de recurso.

Esto ayuda a que la interfaz no se convierta en un conjunto homogéneo de tarjetas idénticas.

---

# 33. Color principal de Torneos

Torneos adopta:

```text
amber
orange
```

como identidad general.

Es un cambio perceptible respecto al indigo de Biblioteca.

Visualmente comunica que el usuario ha cambiado de módulo.

---

# 34. Colores por motor competitivo

Dentro de Torneos existe además una codificación semántica por motor.

La vista de `PhaseTemplate` diferencia los tipos aproximadamente así:

```text
Single Elimination → amber
Round Robin        → cyan
Group Stage        → indigo
League             → emerald
Swiss              → violet
Custom             → rose
```

Esta convención se refleja también en los formularios y previews específicos.

Debe conservarse porque ofrece información sin obligar al usuario a leer continuamente el tipo de motor.

---

# 35. Colores semánticos de estado

El frontend utiliza también colores funcionales:

```text
emerald
→ éxito
→ activo
→ entrada válida
→ avance

red / rose
→ peligro
→ eliminación
→ acciones destructivas

amber
→ advertencia
→ pendiente
→ configuración

violet
→ configuración avanzada
→ relación
→ conexión
```

Los colores suelen acompañarse de texto o iconos, lo cual es mejor que depender únicamente del color.

---

# 36. Tipografía

La tipografía predominante es **Figtree**.

Se registra dentro del `fontFamily.sans` de Tailwind.

Los patrones tipográficos más frecuentes son:

```text
font-black
→ títulos importantes

font-bold
→ acciones y encabezados

font-semibold
→ etiquetas secundarias

text-slate-500
→ explicación

text-[9px] / text-[10px]
uppercase
tracking-wider
→ microetiquetas
```

---

# 37. Jerarquía tipográfica

Una pantalla típica utiliza:

```text
MICROLABEL
text-[10px] uppercase tracking-wider

Título principal
text-2xl / text-3xl font-black

Descripción
text-sm leading-6 text-slate-500

Subsección
text-xl font-black

Datos
text-sm / text-base

Código
font-mono
```

Esta jerarquía es bastante consistente.

---

# 38. Observación sobre textos muy pequeños

El uso de:

```text
text-[9px]
text-[10px]
```

aporta una estética técnica y compacta.

Sin embargo, debe vigilarse en términos de accesibilidad.

Para metadatos secundarios funciona bien.

Para información indispensable debería utilizarse preferiblemente:

```text
text-xs
```

o superior.

No conviene que una instrucción importante dependa de un texto de 9 px.

---

# 39. Formas y bordes

OmniMerge utiliza superficies redondeadas de manera muy consistente.

La escala aproximada es:

```text
rounded-lg
→ controles pequeños

rounded-xl
→ botones e inputs

rounded-2xl
→ tarjetas y paneles

rounded-3xl
→ contenedores principales

rounded-[28px]
rounded-[30px]
rounded-[32px]
→ heroes o componentes destacados
```

Este lenguaje crea una identidad visual moderna y amable.

---

# 40. Sombras

Las sombras se utilizan de forma moderada.

```text
shadow-sm
→ tarjetas normales

shadow-lg
→ acciones destacadas

shadow-xl
→ paneles flotantes

shadow-2xl
→ heroes, dropdowns, modales
```

También se utilizan sombras coloreadas:

```text
shadow-indigo-500/20
shadow-amber-500/20
```

para reforzar la identidad de botones principales.

---

# 41. Bordes

Muchos paneles utilizan:

```text
border
border-slate-200
```

en lugar de sombras fuertes.

Esto es particularmente frecuente en las vistas productivas.

El resultado es una interfaz limpia donde la jerarquía se produce mediante:

```text
espaciado
bordes
tipografía
fondos
```

y no únicamente mediante sombras.

---

# 42. Gradientes

Los gradientes se reservan especialmente para:

```text
heroes
portadas
estados destacados
módulos
```

Ejemplo conceptual de Hub:

```text
indigo
→ violet
→ fuchsia
```

Ejemplo Torneos:

```text
slate-950
→ amber-950
→ orange-950
```

Ejemplo Swiss:

```text
slate-950
→ violet-950
→ fuchsia-950
```

Esto crea personalidades diferentes sin modificar el sistema base.

---

# 43. Glassmorphism moderado

En las áreas oscuras se utiliza:

```text
bg-white/5
border-white/10
backdrop-blur-xl
```

principalmente en:

```text
Hub
Landing
Auth
```

Esta técnica no domina las áreas de productividad, donde se prefieren superficies blancas más sencillas.

La separación es acertada.

---

# 44. Animaciones y microinteracciones

La aplicación utiliza frecuentemente:

```text
transition
hover:bg-...
hover:text-...
hover:-translate-y-1
hover:shadow-xl
x-transition
```

Las tarjetas visuales suelen elevarse ligeramente al pasar el cursor.

Los dropdowns y paneles Alpine utilizan transiciones suaves.

No existe un sistema de animaciones excesivamente complejo.

---

# 45. Responsividad

El diseño utiliza los breakpoints estándar de Tailwind:

```text
sm
md
lg
xl
2xl
```

Los patrones frecuentes son:

```text
grid-cols-1
sm:grid-cols-2
lg:grid-cols-3
xl:grid-cols-4
```

Los sidebars pasan de overlay móvil a fijo mediante `lg`.

Los formularios que muestran varias columnas regresan a una sola columna en pantallas pequeñas.

---

# 46. Patrón de tarjetas responsivas

Una estructura recurrente es:

```text
grid
gap-4 / gap-6
sm:grid-cols-2
xl:grid-cols-3
```

Esto permite reutilizar prácticamente el mismo contenido en escritorio y móvil.

Las tablas suelen recurrir a:

```text
overflow-x-auto
```

para evitar romper la página.

---

# 47. Componentes Blade

El proyecto mantiene una carpeta:

```text
resources/views/components
```

con componentes como:

```text
alert
application-logo
auth-session-status
danger-button
dropdown
dropdown-link
input-error
input-label
modal
nav-link
omni-confirm-modal
omni-image-upload
omni-multi-image-upload
primary-button
responsive-nav-link
secondary-button
status-badge
text-input
user-avatar
```

Se distinguen claramente dos generaciones.

---

# 48. Primera generación de componentes

Algunos componentes provienen del patrón tradicional de Laravel Breeze:

```text
primary-button
secondary-button
danger-button
text-input
input-label
input-error
dropdown
modal
nav-link
```

Son pequeños y genéricos.

---

# 49. Segunda generación: Omni components

Otros son claramente específicos de OmniMerge:

```text
omni-confirm-modal
omni-image-upload
omni-multi-image-upload
status-badge
user-avatar
```

Estos componentes reflejan una transición hacia un verdadero **design system del producto**.

Esta segunda dirección debería continuar.

---

# 50. Sistema global de alertas

`<x-alert />` se monta en los layouts principales.

Su responsabilidad es presentar:

```text
success
error
validation errors
```

utilizando colores semánticos.

Esto evita que cada página necesite inventar completamente cómo mostrar el resultado de una operación.

---

# 51. Sistema global OmniConfirm

Una de las implementaciones frontend más interesantes es el sistema propio de confirmaciones.

Cualquier formulario puede incorporar:

```html
data-omni-confirm
```

y configurar mediante `data-*`:

```text
confirm-title
confirm-message
confirm-detail
confirm-subject
confirm-image
confirm-action
confirm-cancel
confirm-variant
confirm-icon
```

`app.js` intercepta el evento `submit`, abre el modal y solamente permite continuar cuando el usuario confirma.

---

# 52. Ventaja de OmniConfirm

Esto evita escribir en cada vista:

```javascript
confirm('¿Seguro?')
```

y permite mantener la identidad visual de OmniMerge.

Además puede ofrecer contexto.

Por ejemplo, al eliminar una colección puede mostrarse:

```text
Eliminar Colección

[imagen]

Nombre de la colección

Las entidades contenidas no se eliminan.
```

La vista de detalle de colecciones ya utiliza este patrón mediante atributos `data-confirm-*`.

---

# 53. Implementación segura del submit confirmado

Un detalle técnico importante es que OmniConfirm no llama directamente a:

```javascript
form.submit()
```

sino a:

```javascript
form.requestSubmit()
```

Esto conserva:

```text
validación HTML
evento submit
CSRF
_method de Laravel
```

Además utiliza un `WeakSet` para distinguir el segundo submit autorizado del submit original.

Es una solución correctamente pensada.

---

# 54. Variantes de OmniConfirm

El componente contempla variantes como:

```text
danger
warning
primary
violet
success
```

y asigna iconos/etiquetas contextuales.

Esto permite que una confirmación de:

```text
Eliminar
```

no tenga el mismo aspecto que una confirmación de:

```text
Publicar
```

---

# 55. Omni Image Upload

`omni-image-upload` es uno de los componentes frontend más avanzados.

Su función no se limita a:

```html
<input type="file">
```

Gestiona visualmente:

```text
selección
drag and drop
preview
validación de tipo
validación de tamaño
imagen actual
remoción
restauración
información del archivo
```

El componente trabaja con formatos como JPEG, PNG y WebP y utiliza Object URLs para la previsualización local.

Este enfoque es especialmente importante porque en OmniMerge las imágenes forman parte fundamental de:

```text
Entidades
Atributos
Opciones
Versiones
Torneos
```

---

# 56. Omni Multi Image Upload

La versión múltiple permite manejar una galería de archivos.

Está especialmente orientada a contextos donde una versión de entidad puede poseer varias representaciones visuales.

La interfaz controla:

```text
múltiples archivos
límite máximo
tamaño máximo
preview
remoción individual
drag and drop
```

La validación del navegador mejora la experiencia, aunque la validación definitiva debe seguir realizándose en backend.

---

# 57. User Avatar

El componente de avatar se reutiliza en:

```text
Hub
Perfil
headers
menús de usuario
```

y permite evitar que cada layout reconstruya manualmente la misma representación del usuario.

El uso consistente de un componente de avatar será todavía más importante cuando aparezcan:

```text
creadores
competidores
usuarios comunitarios
equipos
```

---

# 58. Dashboard de Biblioteca

`dashboard/index.blade.php` es actualmente la vista más grande del frontend, con aproximadamente **3804 líneas**.

Esto demuestra que el Dashboard ha dejado de ser una simple colección de estadísticas.

Actualmente funciona como un auténtico **workspace de Biblioteca**.

---

# 59. Contenido del Dashboard

El dashboard muestra métricas sobre:

```text
Entidades
Atributos
Catálogo
Colecciones
Tipos
Grupos
```

y ofrece acciones rápidas como:

```text
Nueva entidad
Nuevo atributo
Nuevo Catálogo
Nueva colección
Nuevo tipo
Nuevo grupo
```

La intención es que el usuario pueda continuar trabajando desde una sola pantalla.

---

# 60. Modos del Dashboard

El frontend permite alternar entre:

```text
Resumen
Compacto
Visual
```

Esto introduce un principio importante:

> La misma información puede tener diferentes representaciones según la intención del usuario.

Este principio posteriormente se repite en casi toda la Biblioteca.

---

# 61. Personalización del Dashboard

El Dashboard permite controlar qué secciones se muestran.

Conceptualmente puede contener áreas como:

```text
Continuar trabajando
Acciones rápidas
Actividad
Estado
Entidades
Atributos
Catálogos
Colecciones
Insights
```

Las preferencias se guardan mediante `localStorage`.

Esto permite personalización sin requerir persistencia inmediata en base de datos.

---

# 62. Buscador del Dashboard

El dashboard incorpora búsqueda dinámica global de la Biblioteca.

Utiliza una URL proporcionada desde Laravel y realiza búsqueda en segundo plano mediante JavaScript.

Existe incluso protección para que una respuesta antigua no reemplace a una búsqueda más reciente si ambas peticiones regresan fuera de orden.

Esto es un nivel de comportamiento superior a un simple `<form method="GET">`.

---

# 63. LocalStorage como capa de preferencias

Una convención muy extendida en OmniMerge consiste en guardar preferencias visuales mediante claves:

```text
omnimerge.*
```

Ejemplos conceptuales:

```text
omnimerge.dashboard.view
omnimerge.dashboard.sections

omnimerge.entities.view
omnimerge.entities.density

omnimerge.attributes.view
omnimerge.attributeOptions.view

omnimerge.community.view

omnimerge.versions.view

omnimerge.entityVersions.view
```

Esto permite que al regresar a una pantalla el usuario conserve su modo preferido.

---

# 64. Ventajas del uso actual de localStorage

Es:

```text
rápido
simple
sin llamadas al servidor
sin nuevas tablas
adecuado para preferencias puramente visuales
```

Ejemplo:

> “Prefiero ver mis entidades como galería.”

No es necesario persistir esa decisión en backend para que el frontend funcione correctamente.

---

# 65. Limitación futura de localStorage

Conforme crezca la aplicación, las preferencias pueden fragmentarse.

Además:

```text
localStorage
```

pertenece al navegador y dispositivo.

Si el usuario usa:

```text
PC
Laptop
Tablet
```

sus preferencias no estarán sincronizadas.

Una evolución futura podría crear:

```text
UserInterfacePreferences
```

o una pequeña capa de preferencias en backend.

No es necesario todavía.

---

# 66. Módulo de Entity Types

`entity-types` representa visualmente la clasificación principal de las entidades.

El usuario puede:

```text
listar tipos
crear tipo
editar tipo
consultar detalle
```

y existen parciales reutilizables para formulario y tarjetas de galería.

La intención visual es tratar cada tipo como una pieza de la Biblioteca, no simplemente como una fila de configuración.

---

# 67. Módulo de Entidades

La carpeta `entities` contiene:

```text
attributes.blade.php
create.blade.php
edit.blade.php
index.blade.php
presentation.blade.php
show.blade.php
bulk/
bulk-edit/
partials/
```

Esto demuestra que el frontend de Entidades ya supera ampliamente las cuatro pantallas CRUD tradicionales.

---

# 68. Entities Index

La página principal de entidades funciona como explorador de Biblioteca.

Permite organizar el contenido de diferentes maneras y utiliza preferencias de vista/densidad.

Su objetivo es soportar tanto:

```text
búsqueda rápida
```

como:

```text
exploración visual
```

El usuario puede elegir una representación más gráfica o una representación más informativa según la cantidad de datos que desee revisar.

---

# 69. Múltiples modos de visualización

Este patrón se repite en numerosos módulos.

Las variantes utilizadas incluyen combinaciones como:

```text
Galería
Cuadrícula
Lista
Tabla
Árbol
Timeline
Masonry
```

No todas aparecen en todos los módulos.

Cada dominio recibe los modos que tienen sentido para sus datos.

Esta es una buena decisión UX.

---

# 70. Densidad de información

Además del modo de vista, algunas pantallas ofrecen:

```text
Compacta
Media
Amplia
```

o equivalentes.

La idea es separar:

```text
estructura visual
```

de:

```text
cantidad de información por elemento
```

Por ejemplo:

```text
Galería + Compacto
```

puede ser diferente de:

```text
Galería + Amplio
```

---

# 71. Create y Edit de Entidades

Las pantallas de creación y edición reutilizan un parcial común de formulario.

Esta es una práctica importante.

En lugar de:

```text
create.blade.php
→ formulario completo

edit.blade.php
→ copia del formulario completo
```

se utiliza:

```text
entities/partials/form.blade.php
```

con el contexto correspondiente.

Esto reduce inconsistencias entre creación y edición.

---

# 72. Bulk Create

La existencia de:

```text
entities/bulk
```

demuestra una consideración importante de experiencia de usuario.

OmniMerge puede manejar bibliotecas grandes.

Crear:

```text
50 personajes
100 países
300 objetos
```

uno por uno sería demasiado lento.

Por ello el frontend introduce un flujo específico para creación masiva.

---

# 73. Bulk Edit

También existe:

```text
entities/bulk-edit
```

Esto permite pensar en operaciones de Biblioteca a gran escala.

El frontend, por tanto, no está diseñado únicamente para usuarios con cinco recursos.

Está comenzando a prepararse para colecciones considerablemente más grandes.

---

# 74. Vista de detalle de Entidad

La vista `show` no se limita a mostrar:

```text
Nombre: ...
Descripción: ...
```

La interfaz está diseñada como una ficha editorial.

Puede combinar:

```text
imagen
tipo
código
estado
descripción
características
versiones
acciones
```

La imagen y la identidad de la entidad adquieren bastante peso visual.

---

# 75. Entity Attributes

`entities/attributes.blade.php` constituye una pantalla específica para administrar las características asociadas a una entidad.

Separar esta función del formulario general evita convertir `edit.blade.php` en una pantalla gigantesca.

Conceptualmente:

```text
Editar entidad
→ identidad básica


Características
→ estructura y valores dinámicos
```

Esta separación debería conservarse.

---

# 76. Entity Presentation Builder

`entities/presentation.blade.php` tiene aproximadamente **1043 líneas** y constituye otra de las herramientas interactivas importantes.

Permite decidir cómo debe mostrarse una entidad cuando existen distintas versiones y recursos multimedia.

Conceptualmente puede resolver:

```text
BASE
VERSION_PRIMARY
VERSION_MEDIA
```

y cambiar:

```text
nombre mostrado
descripción mostrada
imagen mostrada
versión utilizada
imagen de versión
```

---

# 77. Previsualización de presentación

La pantalla no obliga al usuario a guardar continuamente para comprender el resultado.

Alpine mantiene un preview reactivo.

Conceptualmente:

```text
Configuración
      │
      ▼
Live Preview
```

Esto es particularmente adecuado para decisiones visuales.

---

# 78. Módulo de Atributos

La carpeta `attributes` incluye:

```text
index
create
edit
show
structure
partials
```

La presencia de `structure.blade.php` es particularmente significativa.

Significa que la definición de atributos posee una segunda capa dedicada a comportamiento contextual.

---

# 79. Attributes Index

La pantalla funciona como biblioteca de características.

El usuario puede:

```text
buscar
filtrar
ordenar
cambiar vista
cambiar densidad
crear atributo
abrir estructura
```

El diseño mantiene la identidad indigo/violet de Biblioteca.

---

# 80. Attribute Show

La pantalla de detalle muestra información del atributo y, cuando corresponde, su catálogo de opciones.

Esto permite que el usuario entienda el atributo como un recurso completo:

```text
definición
tipo
configuración
catálogo
uso
estructura
```

y no solamente como una columna.

---

# 81. Attribute Structure Builder

`attributes/structure.blade.php` tiene aproximadamente **1523 líneas** y constituye uno de los módulos frontend más avanzados.

Su propósito es administrar el llamado:

```text
Motor contextual
```

permitiendo configurar reglas y relaciones entre atributos y opciones.

---

# 82. Interacción contextual

La interfaz permite construir condiciones conceptualmente como:

```text
SI
Anime = Naruto

ENTONCES
Mostrar Aldea Ninja
```

o relaciones de catálogo.

Alpine controla dinámicamente:

```text
atributo fuente
operador
opción fuente
acción
condiciones
```

y muestra u oculta campos según el operador seleccionado.

Esta es una implementación de un **rule builder ligero** dentro de Blade.

---

# 83. Por qué Attribute Structure es importante para el frontend futuro

Este patrón será reutilizable.

Otros dominios podrán necesitar:

```text
reglas de elegibilidad
reglas de simulación
condiciones de torneos
reglas de universo
filtros avanzados
```

Por ello conviene conservar las enseñanzas de esta pantalla y eventualmente convertir parte de su lógica en componentes reutilizables.

---

# 84. Módulo Attribute Options

`attribute-options` constituye el frontend de Catálogos.

Posee:

```text
index
create
edit
show
partials
```

y diferentes parciales para:

```text
card
gallery card
list item
form
```

Esto demuestra una mayor componentización que otros módulos.

---

# 85. Explorador de Catálogos

El índice permite trabajar con catálogos grandes.

Entre las dimensiones de visualización aparecen:

```text
búsqueda
atributo/catálogo
estado
imagen
jerarquía
uso
orden
densidad
agrupación
```

También existen diferentes modos:

```text
gallery
grid
list
table
```

Esta pantalla es una de las herramientas de exploración más maduras de Biblioteca.

---

# 86. Agrupación por Catálogo

Una característica especialmente útil es poder agrupar opciones por el atributo/catálogo al que pertenecen.

Ejemplo:

```text
Aldea
├── Konoha
├── Suna
└── Kiri

Clan
├── Uchiha
├── Hyuga
└── Uzumaki
```

Esto resulta mucho más útil para el usuario que una lista global de opciones sin contexto.

---

# 87. Módulo Attribute Groups

Los grupos de atributos disponen de:

```text
index
create
edit
show
partials
```

y varios componentes de representación.

Su función frontend es ayudar al usuario a organizar características.

Ejemplo:

```text
Información General
Información Ninja
Poderes
Afiliaciones
```

La interfaz sigue el mismo paradigma de Biblioteca para mantener consistencia.

---

# 88. Módulo Collections

Las colecciones disponen de:

```text
index
create
edit
show
partials
```

Su objetivo visual es agrupar entidades.

Ejemplo:

```text
Equipo 7
Akatsuki
Favoritos
Participantes
```

El detalle de una colección utiliza una gran cabecera visual con imagen o gradiente de color, badges de visibilidad/estado y una cuadrícula de las entidades contenidas.

---

# 89. Eliminación contextual de Colecciones

El frontend explica explícitamente antes de eliminar que:

> Las entidades contenidas en la colección no se eliminan.

Este detalle es importante.

Una buena interfaz no solamente pregunta:

> “¿Seguro?”

sino que explica:

> “¿Qué consecuencias tiene esta operación?”

Este patrón debería repetirse en las acciones destructivas futuras.

---

# 90. Comunidad

La carpeta Community constituye otra familia propia de vistas.

Está orientada a:

```text
descubrir
explorar
consultar
clonar
```

y no a editar recursos de terceros.

La interfaz comunica esta intención mediante mensajes como:

```text
Descubre.
Copia.
Evoluciona.
```

La vista principal tiene aproximadamente **843 líneas**.

---

# 91. Tipos de contenido comunitario

La Comunidad contempla diferentes superficies públicas:

```text
entity
attribute
catalog
collection
creator
index
```

y parciales para distintas representaciones de resultados.

Esto permite que Community sea un explorador transversal, no solamente una página de entidades.

---

# 92. Community Explorer

La vista principal utiliza un controlador Alpine propio para:

```text
vista
densidad
búsqueda
resultados
modal de clonación
estado del explorador
```

Las vistas disponibles pueden incluir:

```text
gallery
grid
masonry
list
table
```

dependiendo de la representación.

Esta es otra muestra del patrón de **workspace interactivo**.

---

# 93. Búsqueda dinámica en Comunidad

La búsqueda puede actualizar resultados sin una navegación completa.

Esto permite una experiencia más parecida a un marketplace/explorador.

No obstante, la lógica continúa siendo suficientemente ligera como para no justificar una SPA completa.

---

# 94. Community Creator

La página del creador utiliza el layout del Hub en lugar del layout de Biblioteca.

Esta decisión es correcta.

El perfil público de un creador pertenece conceptualmente a:

```text
experiencia comunitaria / pública
```

y no al área privada de administración.

Esto demuestra que los layouts están empezando a utilizarse según función real y no simplemente según conveniencia técnica.

---

# 95. Clonación como interacción central

El frontend comunitario debe dejar clara la diferencia entre:

```text
ver
```

y:

```text
copiar a mi Biblioteca
```

La experiencia está orientada a que el usuario comprenda que está obteniendo su propia copia, no modificando el contenido original.

Este concepto será reutilizable cuando en el futuro se publiquen:

```text
PhaseTemplates
TournamentTemplates
Universos
```

---

# 96. Sistema de Versiones

El frontend de Versiones constituye probablemente el área de Biblioteca más avanzada después de Torneos.

Existen dos conceptos visuales diferentes:

```text
Version
```

como definición reutilizable,

y:

```text
EntityVersion
```

como versión concreta de una entidad.

El frontend refleja esta separación.

---

# 97. Version Index

El índice de Versiones permite visualizar las definiciones mediante múltiples modos, incluyendo:

```text
gallery
grid
list
table
tree
```

También permite filtrar por tipos conceptuales como:

```text
ERA
AGE
FORM
TRANSFORMATION
OUTFIT
TIMELINE
OTHER
```

y distinguir alcance y activación.

La interfaz utiliza una identidad visual predominantemente violet/indigo.

---

# 98. Árbol de Versiones

La existencia de una vista de árbol es particularmente importante.

Una lista plana como:

```text
Naruto niño
Naruto adulto
Modo Sabio
Modo Kurama
```

no expresa necesariamente las relaciones.

Una representación:

```text
Base
├── Niño
│
└── Adulto
    ├── Modo Sabio
    └── Kurama
```

ayuda al usuario a entender jerarquía y herencia.

---

# 99. Version Show

`versions/show.blade.php` tiene aproximadamente **1211 líneas**.

Funciona como centro de administración de una definición de versión.

No es simplemente una pantalla de lectura.

La estructura del módulo incluye navegación de workspace y herramientas relacionadas.

---

# 100. Workspace de Versiones

Dentro de `versions/workspace` existen áreas especializadas orientadas a conceptos como:

```text
Coverage
Entities
Media
Resolver
```

La decisión de utilizar un workspace propio es significativa.

Versiones ya posee suficiente complejidad como para necesitar navegación interna.

---

# 101. Coverage

La idea de Coverage permite mostrar en qué contexto se aplica una versión.

Esto resulta relevante porque las versiones pueden ser:

```text
compartidas
exclusivas
automáticas
manuales
```

El usuario necesita comprender su alcance, no solamente editar su nombre.

---

# 102. Media Workspace

La existencia de una sección específica para media confirma que una versión puede poseer varias representaciones visuales.

Este flujo trabaja conjuntamente con:

```text
omni-multi-image-upload
```

y los componentes de gestión multimedia.

---

# 103. Resolver Workspace

La existencia de un Resolver representa otra evolución de la experiencia.

No basta con configurar reglas.

El usuario necesita poder comprobar:

> “¿Qué versión elegiría OmniMerge en esta situación?”

Ese principio de **preview/test** aparece posteriormente también en los motores competitivos.

---

# 104. Entity Versions

La carpeta `entity-versions` contiene:

```text
index
show
form
compare
attributes/
partials/
```

además de componentes relacionados con media y árbol.

Esto demuestra que EntityVersion posee una experiencia propia.

---

# 105. Timeline de Entity Versions

La pantalla principal de versiones de una entidad utiliza como uno de sus enfoques principales una vista temporal.

Esto es especialmente adecuado para casos como:

```text
Naruto niño
        ↓
Naruto adolescente
        ↓
Naruto adulto
        ↓
Naruto Hokage
```

La línea temporal comunica evolución mejor que una tabla.

---

# 106. Base activa

La interfaz muestra también cuál versión actúa actualmente como base.

Esto evita que el usuario tenga que inferir el estado efectivo.

Visualmente debe continuar diferenciándose claramente:

```text
versión existente
```

de:

```text
versión activa/base
```

---

# 107. Entity Version Show

`entity-versions/show.blade.php` tiene aproximadamente **1084 líneas**.

La pantalla funciona como workspace de una versión concreta.

Puede gestionar:

```text
resumen
características
media
relaciones
estado
```

y guía al usuario para completar una versión recién creada.

---

# 108. Compare Versions

La vista `compare.blade.php` permite comparar múltiples versiones.

El usuario puede seleccionar hasta varias versiones simultáneamente para observar diferencias.

Conceptualmente:

```text
Base
vs
Versión A
vs
Versión B
vs
Versión C
```

Esta función será muy importante posteriormente en:

```text
simulación
balance
evolución
comparación histórica
```

---

# 109. Navegación contextual

Una característica avanzada del frontend es la presencia de navegaciones internas adicionales.

El usuario puede encontrarse dentro de:

```text
Biblioteca
    ↓
Versiones
    ↓
Workspace de Version
```

sin perder la navegación global de Biblioteca.

Este patrón es mejor que llenar el sidebar principal con cada subfunción existente.

---

# 110. Módulo Torneos

La carpeta `tournaments` contiene actualmente:

```text
dashboard.blade.php

graph/
lab/
partials/
phase-templates/
phases/
templates/
```

Esto refleja exactamente la evolución funcional del backend:

```text
TournamentTemplate
PhaseTemplate
Competition Lab
Tournament Graph
```

---

# 111. Dashboard de Torneos

El Dashboard del módulo funciona como entrada al Competition Designer.

Su hero utiliza un gradiente oscuro:

```text
slate
→ amber
→ orange
```

y comunica al usuario una idea importante:

> construir el lenguaje de sus competiciones.

Las métricas incluyen plantillas, fases y salidas.

El usuario puede entrar rápidamente a:

```text
Torneos
Fases
```

---

# 112. Tournament Templates Index

La vista de plantillas de torneo está orientada a explicar que una plantilla representa:

```text
estructura competitiva reutilizable
```

y no participantes reales.

Esta distinción de dominio también se comunica en frontend.

El usuario está diseñando:

> “Cómo funciona un torneo.”

No:

> “Quién participa ahora.”

---

# 113. Tournament Template Show

La vista de detalle del TournamentTemplate funciona como centro de diseño.

Desde aquí el usuario puede comprender la configuración general y acceder a la estructura competitiva.

Una sección importante enlaza directamente con:

```text
Tournament Graph
```

explicando conceptos como:

```text
nodos
salidas
entradas
ramas
convergencias
repechaje
finales
```

---

# 114. Phase Templates Index

La biblioteca de fases explica al usuario otra distinción fundamental:

> una Fase define su mecanismo y sus salidas, pero no decide dónde continúan.

La interfaz necesita mantener esta pedagogía porque el modelo arquitectónico es más avanzado que el de un creador de torneos convencional.

---

# 115. Phase Template Show

`phase-templates/show.blade.php` tiene aproximadamente **1279 líneas**.

Es una de las páginas más importantes de Torneos.

Su responsabilidad es reunir:

```text
identidad de la fase
contrato
salidas
tipo de motor
configuración
preview
reglas especiales
acciones
```

---

# 116. Identidad visual de PhaseTemplate

La vista adapta:

```text
icono
acento
gradiente
mensaje
```

según el tipo de fase.

Esto permite que un usuario reconozca visualmente:

```text
Swiss
```

antes incluso de leer todas las configuraciones.

---

# 117. Single Elimination Frontend

El formulario de eliminación simple está dividido conceptualmente en secciones.

Entre ellas:

```text
Finalización
Distribución
BYE
Series
Reseed
```

El usuario puede decidir si la fase termina:

```text
con un ganador
```

o:

```text
cuando queden N supervivientes
```

También puede seleccionar seeding, pairing, asignación de BYEs, `Best of` y reseeding entre rondas.

---

# 118. Seeding y Pairing

La interfaz distingue dos conceptos que un usuario menos técnico podría confundir.

```text
Seeding
→ cómo se ordenan los participantes

Pairing
→ cómo esos seeds se enfrentan
```

Opciones visibles incluyen enfoques como:

```text
Orden de entrada
Aleatorio
Ranking
Manual
```

para seeding, y:

```text
Seeded estándar
Secuencial
Aleatorio
```

para pairing.

Esto demuestra que el frontend no solamente expone datos: también enseña la semántica del motor.

---

# 119. Reglas específicas por ronda

Single Elimination permite sobrescribir `Best of` dependiendo de la ronda.

Ejemplo:

```text
Ronda de 16 → BO1
Cuartos      → BO3
Semifinal    → BO5
Final        → BO7
```

La interfaz reconoce automáticamente nombres como:

```text
Final
Semifinal
Cuartos de final
Ronda de 16
```

según la cantidad de participantes.

---

# 120. Preview de Single Elimination

La previsualización es matemática.

No crea:

```text
participantes
partidos
historial
```

sino que permite probar una cantidad hipotética.

Muestra métricas como:

```text
Bracket
BYEs iniciales
Rondas
Series
```

y un blueprint de cada ronda con:

```text
participantes
series
BYEs
Best of
supervivientes
```

Esta separación entre:

```text
Preview
```

y:

```text
Runtime
```

debe conservarse.

---

# 121. Round Robin Frontend

El formulario permite definir:

```text
cantidad de ciclos
orden inicial
empates
Best of
scoring
desempates
```

Un ciclo significa que cada participante se enfrenta una vez contra todos; dos ciclos equivalen a Double Round Robin.

---

# 122. Calendario equilibrado

La propia interfaz explica que utiliza una rotación tipo:

```text
Circle Method
```

para generar jornadas sin repetir emparejamientos dentro del ciclo.

Esta clase de explicación es valiosa porque transforma OmniMerge en herramienta de diseño y no únicamente en panel de configuración.

---

# 123. Desempates Round Robin

La UI permite administrar una lista ordenada de criterios de desempate.

Cada criterio puede establecer:

```text
Automática
Mayor primero
Menor primero
```

La posibilidad de ordenar criterios será fundamental para presentar cómo se resuelve una tabla.

---

# 124. Preview Round Robin

La previsualización utiliza participantes ficticios.

Puede mostrar:

```text
Ciclos
Jornadas
Series
Series por jornada
Descansos
Best of
```

sin crear partidos históricos.

Esto mantiene la misma filosofía de preview utilizada por Single Elimination.

---

# 125. Group Stage Frontend

Group Stage añade todavía más complejidad.

El usuario necesita diseñar:

```text
estructura de grupos
distribución
política de sobrantes
motor interno
grupos personalizados
desempates
reglas de clasificación
```

La UI organiza estas preocupaciones en paneles especializados.

---

# 126. Construcción de grupos

El formulario permite enfoques como:

```text
cantidad fija de grupos
tamaño objetivo
grupos personalizados
```

Cuando se utilizan grupos personalizados, cada grupo puede definir su propia capacidad.

---

# 127. Distribución de participantes

El usuario puede elegir cómo se distribuyen los participantes.

La interfaz contempla incluso estructuras como:

```text
Pot Draw
```

con cantidad configurable de pots.

Esto prepara el sistema para competiciones similares a sorteos deportivos reales.

---

# 128. Motor interno de grupos

La fase utiliza actualmente todos-contra-todos como motor interno configurable.

Puede definir:

```text
ciclos
Best of
permitir empates
```

Esto muestra cómo un motor puede estar compuesto conceptualmente por otro comportamiento competitivo.

---

# 129. Grupos personalizados

Existe un formulario específico para agregar grupos con:

```text
nombre
capacidad
```

Ejemplo:

```text
Grupo A → 4
Grupo B → 4
Grupo C → 6
```

---

# 130. Desempates Group Stage

El formulario de desempates añade un concepto adicional:

```text
normalización
```

que puede ser:

```text
configuración general
valor total
por partido
```

además de la dirección.

Esto resulta especialmente relevante cuando se comparan participantes que provienen de grupos con características distintas.

---

# 131. Reglas de avance de grupos

El frontend permite configurar reglas que conectan posiciones con una:

```text
PhaseExit
```

y cambia dinámicamente los campos según el tipo de regla.

Puede seleccionar, por ejemplo:

```text
posición de cada grupo
rango de posiciones
posición específica
comparación entre grupos
Top N
Bottom N
```

Esta es una de las formas más claras en que el frontend expresa la arquitectura del backend.

---

# 132. Preview Group Stage

El preview muestra métricas como:

```text
Cantidad de grupos
Tamaño mínimo
Tamaño máximo
Series
Ventanas de ronda
Descansos
```

y avisa cuando una distribución requerirá asignación manual durante la ejecución.

---

# 133. Swiss Frontend

Swiss es actualmente el formulario competitivo más complejo.

Su archivo de settings por sí solo tiene alrededor de **589 líneas**.

Está dividido en conceptos como:

```text
Format
Pairing Engine
Scoring & Match
BYE
Acceleration
```

además de:

```text
desempates
reglas por ronda
reglas de avance
preview
```

---

# 134. Finalización Swiss

El usuario puede elegir entre diferentes modos de terminación.

La propia interfaz explica dos enfoques conceptuales:

```text
rondas fijas
```

o:

```text
clasificación/eliminación
según victorias y derrotas
```

Esto permitirá reproducir distintos estilos de Swiss.

---

# 135. Pairing Engine de Swiss

La pantalla expone conceptos como:

```text
algoritmo
base de emparejamiento
primera ronda
floaters
orientación
```

La interfaz también explica que la primera ronda puede previsualizarse completamente, mientras las siguientes dependen de resultados anteriores.

Esto es una comunicación correcta de una limitación matemática real.

---

# 136. BYEs Swiss

El usuario puede definir:

```text
política de BYE
puntos de BYE
máximo por participante
```

Este nivel de detalle demuestra que el frontend está pensado para un diseñador de formatos serio.

---

# 137. Aceleración Swiss

La interfaz contempla mecanismos de aceleración.

Por ejemplo, una modalidad puede utilizar:

```text
rondas de aceleración
seeds beneficiados
puntos virtuales
```

Esto va mucho más allá de una simple opción “Swiss sí/no”.

---

# 138. Reglas por ronda Swiss

Existe un formulario que permite activar reglas según eventos como:

```text
número de ronda
clasificación/eliminación
```

y modificar comportamiento en diferentes etapas.

Esto introduce comportamiento contextual dentro del motor.

---

# 139. Advancement Rules de Swiss

Las reglas de avance conectan resultados con `PhaseExit`.

La interfaz adapta campos según el tipo de regla, por ejemplo:

```text
umbral de victorias
Top N final
otros criterios de clasificación
```

---

# 140. Swiss Preview

La previsualización mantiene la misma filosofía de los demás motores.

La interfaz advierte que:

```text
Ronda 1
→ puede construirse

Ronda 2+
→ depende de resultados reales
```

Esto evita vender una falsa previsualización completa de un sistema inherentemente dinámico.

---

# 141. Consistencia entre motores

Aunque los motores tienen reglas completamente diferentes, todos siguen un patrón UX parecido:

```text
IDENTIDAD
      ↓
CONFIGURACIÓN
      ↓
REGLAS ESPECÍFICAS
      ↓
SALIDAS
      ↓
PREVIEW
      ↓
VALIDACIÓN
```

Esta coherencia es una de las mejores decisiones actuales del frontend de Torneos.

---

# 142. Competition Lab

El módulo dispone de una vista:

```text
tournaments/lab
```

orientada a pruebas.

El laboratorio comunica explícitamente que es un entorno temporal.

Su objetivo conceptual es permitir probar un formato utilizando:

```text
entidades de Biblioteca
o
competidores ficticios
```

sin generar todavía estadísticas/historial permanente.

---

# 143. Importancia UX del Lab

El Lab proporciona un puente entre:

```text
DISEÑAR
```

y:

```text
EJECUTAR
```

Antes de que existan Universos, el usuario podrá comprobar:

> “¿Mi torneo funciona?”

Este patrón también debería conservarse cuando exista Simulation Engine.

---

# 144. Tournament Graph

`resources/views/tournaments/graph/show.blade.php` constituye actualmente la herramienta frontend más compleja.

Tiene aproximadamente **1918 líneas**.

Ya no funciona como una vista CRUD.

Se comporta como una pequeña aplicación visual embebida dentro de Laravel.

---

# 145. Estructura visual del Graph Builder

La pantalla puede representarse así:

```text
┌─────────────────────────────────────────────────────────────────────┐
│ HERO / ESTADO / VALIDACIÓN / ACCIONES                              │
├───────────────┬────────────────────────────────────┬────────────────┤
│               │                                    │                │
│   TOOLBOX     │              CANVAS                │   INSPECTOR    │
│               │                                    │                │
│ + Fase        │  START ──► NODE ──► NODE           │ Selección      │
│ + Start       │                 │                  │ Configuración  │
│ + Terminal    │                 └──► TERMINAL       │ Validación     │
│               │                                    │                │
└───────────────┴────────────────────────────────────┴────────────────┘
```

Esta distribución utiliza tres columnas en pantallas grandes.

---

# 146. Toolbox del grafo

La columna izquierda permite incorporar:

```text
PhaseTemplate como Node
Start
Terminal
```

Para un nodo se puede seleccionar una plantilla de fase y asignar información contextual.

Los Starts pueden utilizar tipos conceptuales como:

```text
MAIN_POOL
SEEDED_POOL
QUALIFIER_POOL
INVITED_POOL
CUSTOM
```

Los terminales pueden representar conceptos como:

```text
CHAMPION
QUALIFIED
ELIMINATED
PLACEMENT
CUSTOM
```

La interfaz traduce, por tanto, la estructura matemática del backend a bloques manipulables.

---

# 147. Canvas del grafo

El canvas actual utiliza un espacio grande, aproximadamente:

```text
2600 × 1600
```

dentro de un viewport desplazable.

Utiliza:

```text
CSS grid visual
posicionamiento absoluto
SVG
transform scale
```

para dibujar nodos y conexiones.

---

# 148. Zoom del Graph

La barra del canvas permite:

```text
reducir zoom
aumentar zoom
volver a 100%
```

El zoom se aplica sobre el lienzo interno.

Esto es esencial porque un torneo complejo puede superar fácilmente el tamaño visible de la pantalla.

---

# 149. Drag de nodos

Los nodos pueden desplazarse mediante Pointer Events.

Conceptualmente:

```text
pointerdown
    ↓
drag state
    ↓
pointermove
    ↓
actualizar posición
    ↓
pointerup
    ↓
persistir
```

Esto funciona tanto con mouse como con dispositivos que implementen pointer events.

---

# 150. Conexiones visuales

Las conexiones se dibujan mediante SVG.

Se utiliza una identidad violeta para las relaciones, con flechas que permiten ver la dirección.

El flujo visual es:

```text
START
  │
  ▼
ENTRY
  │
PHASE
  │
 EXIT
  │
  ▼
ENTRY / TERMINAL
```

---

# 151. Construcción de conexiones

El usuario puede seleccionar primero un origen:

```text
Start
o
PhaseExit
```

y después un destino:

```text
EntryPort
o
Terminal
```

La interfaz mantiene un estado:

```text
pendingSource
```

mientras espera el destino.

Este modelo es intuitivo para una herramienta de diagramación.

---

# 152. Colores del Tournament Graph

El grafo utiliza colores semánticos específicos.

Aproximadamente:

```text
Start       → emerald

Entry Port  → emerald

Phase Node  → slate
Seleccionado→ amber

Exit        → violet

Connection  → violet

Terminal    → rose
```

Esta paleta ayuda a reconocer la función de cada objeto sin depender exclusivamente del texto.

---

# 153. Inspector

La columna derecha permite trabajar con el objeto seleccionado.

El inspector es una pieza fundamental para evitar llenar cada tarjeta del grafo con todos sus campos editables.

El patrón es:

```text
Canvas
→ estructura

Inspector
→ propiedades
```

Este enfoque debería conservarse.

---

# 154. Validación visual del grafo

El frontend muestra los resultados del sistema de validación.

Puede advertir sobre problemas como:

```text
inicio inexistente
nodo inaccesible
entrada sin conexión
salida sin destino
terminal sin entrada
ciclo
```

La idea correcta es que los errores estructurales sean visibles **antes de ejecutar el torneo**.

---

# 155. Auto Layout

El grafo incorpora una acción de auto-layout.

Esto resulta especialmente útil después de:

```text
crear muchos nodos
duplicar ramas
importar plantilla
```

El usuario puede reorganizar automáticamente la estructura y después ajustar posiciones manualmente.

---

# 156. Graph Builder como “mini aplicación”

Esta pantalla contiene:

```text
estado cliente
CRUD dinámico
drag
zoom
SVG
modales
conexiones
persistencia
validación
auto-layout
```

Por ello ya se encuentra en el límite de lo que resulta cómodo mantener completamente dentro de un solo Blade.

Esto constituye una de las principales áreas de refactorización futura.

---

# 157. Patrón actual de JavaScript embebido

Varios grandes workspaces definen directamente funciones JavaScript dentro de la vista.

Ejemplos:

```text
dashboardWorkspace(...)
attributeStructureBuilder(...)
entityPresentationBuilder(...)
communityExplorer(...)
tournamentGraphBuilder(...)
```

Esto funcionó correctamente para construir rápidamente cada módulo.

Sin embargo, está empezando a generar archivos Blade muy extensos.

---

# 158. Archivos Blade de gran tamaño

Actualmente destacan aproximadamente:

| Archivo                                    | Líneas |
| ------------------------------------------ | -----: |
| dashboard/index.blade.php                  |   3804 |
| welcome.blade.php                          |   2156 |
| tournaments/graph/show.blade.php           |   1918 |
| attributes/structure.blade.php             |   1523 |
| hub/index.blade.php                        |   1446 |
| tournaments/phase-templates/show.blade.php |   1279 |
| versions/show.blade.php                    |   1211 |
| entity-versions/show.blade.php             |   1084 |
| entities/presentation.blade.php            |   1043 |
| community/index.blade.php                  |    843 |

El número de líneas no significa automáticamente que el código esté mal.

Sí indica que esos archivos han empezado a asumir varias responsabilidades.

---

# 159. Principal deuda técnica del frontend

La mayor deuda que observo no es el uso de Blade.

Es:

> **demasiado comportamiento y demasiado markup en determinados Blade individuales.**

No recomiendo cambiar inmediatamente a React.

Recomiendo primero modularizar la arquitectura actual.

---

# 160. Extracción recomendada de JavaScript

Una estructura futura podría ser:

```text
resources/js/
├── app.js
│
├── core/
│   ├── confirm.js
│   └── preferences.js
│
├── dashboard/
│   └── workspace.js
│
├── entities/
│   └── presentation-builder.js
│
├── attributes/
│   └── structure-builder.js
│
├── community/
│   └── explorer.js
│
└── tournaments/
    └── graph-builder.js
```

Después:

```javascript
Alpine.data(
    'tournamentGraphBuilder',
    tournamentGraphBuilder
);
```

podría registrarse desde un módulo externo.

---

# 161. Ventaja de extraer Alpine

Actualmente:

```text
Blade
=
HTML
+ PHP
+ Tailwind
+ Alpine state
+ JavaScript methods
```

Con extracción:

```text
Blade
=
HTML + PHP + Tailwind


JS Module
=
estado + comportamiento
```

Esto facilitaría:

```text
mantenimiento
testing
reutilización
lectura
```

sin cambiar la tecnología.

---

# 162. Extracción recomendada de partials

El markup grande también puede dividirse.

Por ejemplo, Graph:

```text
tournaments/graph/
├── show.blade.php
└── partials/
    ├── summary.blade.php
    ├── toolbar.blade.php
    ├── toolbox.blade.php
    ├── canvas.blade.php
    ├── node.blade.php
    ├── inspector.blade.php
    ├── validation.blade.php
    └── modals/
```

No todas las divisiones deben realizarse inmediatamente.

La regla debería ser:

> extraer componentes cuando representen una unidad conceptual estable.

---

# 163. No sobrecomponentizar

Tampoco conviene convertir cada:

```text
<div>
```

en un Blade Component.

Eso haría difícil seguir el HTML.

Los mejores candidatos son patrones como:

```text
ResourceCard
StatusBadge
EmptyState
SectionHeader
ViewSwitcher
DensitySwitcher
FilterPanel
StatCard
ConfirmAction
ImageUploader
```

que aparecen repetidamente.

---

# 164. Necesidad futura de Design System

Actualmente existe un design system **implícito**.

Está expresado mediante cientos de clases Tailwind repetidas.

Por ejemplo:

```text
rounded-2xl
border border-slate-200
bg-white
p-5
shadow-sm
```

aparece constantemente.

El siguiente paso debería ser convertir parte de ese sistema implícito en componentes explícitos.

---

# 165. Design Tokens semánticos

También podría ampliarse `tailwind.config.js`.

Por ejemplo, conceptualmente:

```text
library.primary
community.primary
tournament.primary
```

Sin embargo, no es necesario convertir cada color de Tailwind en una abstracción.

La prioridad debería ser documentar reglas:

```text
Biblioteca → indigo
Community → violet
Tournament → amber/orange
Swiss → violet
Round Robin → cyan
Group Stage → indigo
```

antes que crear decenas de tokens innecesarios.

---

# 166. Sistema de iconos

El frontend utiliza actualmente una mezcla de:

```text
SVG inline
caracteres Unicode
símbolos
emoji
```

Ejemplos:

```text
✦
☷
◆
▤
◇
⚙
👤
```

Esto tiene dos ventajas:

```text
no requiere librería externa
proporciona personalidad propia
```

pero también dos riesgos:

```text
consistencia visual
accesibilidad
```

---

# 167. Evolución del sistema de iconos

Una mejora futura podría introducir:

```text
<x-icon name="entity" />
<x-icon name="attribute" />
<x-icon name="tournament" />
```

sin necesariamente instalar una librería pesada.

Internamente podrían utilizarse SVG propios.

Esto permitiría:

```text
tamaño uniforme
stroke uniforme
aria-hidden
accesibilidad
```

---

# 168. Estados vacíos

Los módulos utilizan paneles de estado vacío con:

```text
border-dashed
texto explicativo
acción sugerida
```

Este patrón debería mantenerse.

Una buena pantalla vacía no debería decir solamente:

> “No hay registros.”

Debería decir:

> “Todavía no tienes entidades. Crea la primera.”

Especialmente en OmniMerge, donde muchos conceptos son nuevos para el usuario.

---

# 169. La interfaz como herramienta educativa

Uno de los rasgos más positivos observados es que muchas pantallas explican el significado de opciones técnicas.

Por ejemplo:

```text
¿Qué significa Best of?

¿Qué hace un BYE?

¿Cómo funciona Round Robin?

¿Por qué las rondas Swiss futuras dependen de resultados?
```

Esto es particularmente importante porque OmniMerge maneja conceptos que no todos los usuarios conocerán.

---

# 170. Microcopy

El proyecto utiliza bastante microcopy descriptiva.

En lugar de presentar:

```text
reseed_each_round [ ]
```

la interfaz explica:

> Los supervivientes podrán volver a ordenarse antes de construir la siguiente ronda.

Este estilo debe conservarse.

La complejidad del backend debe traducirse a lenguaje comprensible.

---

# 171. Mezcla español-inglés

Actualmente la interfaz utiliza principalmente español, pero conserva determinados términos técnicos:

```text
Best of
Seeding
Pairing
Round Robin
Swiss
Tournament Graph
Engine
Preview
Start
Terminal
```

Esta mezcla no es necesariamente incorrecta.

Muchos de estos términos son ampliamente reconocidos dentro del dominio.

Sin embargo, debería elaborarse en el futuro un pequeño **glosario de producto** para evitar inconsistencias como:

```text
Start
Inicio
Source
Origen
Entry
Entrada
```

utilizados de manera aleatoria.

---

# 172. Convención recomendada de idioma

Podría mantenerse:

```text
Nombre técnico del concepto
+
explicación en español
```

Ejemplo:

```text
Seeding
Orden inicial de participantes
```

o:

```text
Tournament Graph
Diseñador visual de la estructura competitiva
```

Esto permite conservar terminología profesional sin perder claridad.

---

# 173. Formularios

Los formularios utilizan principalmente:

```text
rounded-xl
border-slate-300
focus:border-{accent}
focus:ring-{accent}
```

Los módulos cambian el color de foco según contexto.

Ejemplo:

```text
Single Elimination → amber
Round Robin        → cyan
Group Stage        → indigo
Swiss              → violet
```

Esto refuerza el contexto visual.

---

# 174. Formularios reactivos

Alpine se utiliza frecuentemente para mostrar campos condicionales.

Ejemplo:

```text
completionMode = SURVIVORS
        ↓
mostrar target_survivors
```

o:

```text
groupMode = CUSTOM_GROUPS
        ↓
mostrar configuración específica
```

o:

```text
byePolicy ≠ DISABLED
        ↓
mostrar puntos y límites
```

Este patrón mejora mucho la experiencia porque evita mostrar al usuario opciones irrelevantes.

---

# 175. Regla para formularios futuros

Debe conservarse:

```text
Frontend:
oculta opciones irrelevantes
y explica decisiones

Backend:
valida absolutamente todo
```

El frontend nunca debería convertirse en la única protección.

La visibilidad Alpine es experiencia de usuario, no seguridad.

---

# 176. Datos de Laravel hacia Alpine

Un patrón utilizado correctamente es:

```php
@js($data)
```

para transferir estructuras PHP a JavaScript.

Esto es preferible a construir manualmente:

```javascript
const data = '{{ json_encode(...) }}';
```

porque Blade/Laravel realizan el escapado apropiado.

---

# 177. Payloads frontend

Actualmente determinadas herramientas reciben arrays completos desde el servidor.

Esto funciona bien en:

```text
Presentation Builder
Attribute Structure
Tournament Graph
```

mientras el volumen es moderado.

En el futuro, si un grafo posee:

```text
500 nodos
miles de conexiones
```

o una biblioteca contiene decenas de miles de recursos, será necesario cargar información de manera más selectiva.

---

# 178. Performance del DOM

Existe una consideración importante con los múltiples modos de vista.

Si una pantalla renderiza simultáneamente:

```text
Galería
Cuadrícula
Lista
Tabla
```

y simplemente oculta tres mediante `x-show`, todos esos elementos continúan existiendo en el DOM.

Con:

```text
20 registros
```

esto no importa.

Con:

```text
1000 registros
```

sí puede afectar rendimiento.

---

# 179. Estrategia futura para grandes colecciones

Podría utilizarse:

```text
paginación
server-side filtering
lazy loading
x-if
partial requests
```

en lugar de mantener varias representaciones completas simultáneamente.

No hace falta optimizar prematuramente mientras los volúmenes actuales sean bajos.

---

# 180. Rendimiento de imágenes

OmniMerge será una aplicación muy visual.

Por ello debería mantenerse especial atención sobre:

```text
loading="lazy"
dimensiones
thumbnails
formatos WebP
compresión
object-cover
```

A largo plazo no será eficiente mostrar una imagen original de varios megabytes dentro de una tarjeta de 250 × 150 píxeles.

---

# 181. Sistema futuro de thumbnails

Una arquitectura futura podría generar:

```text
original
large
medium
thumbnail
```

y permitir que cada superficie solicite el tamaño apropiado.

Ejemplo:

```text
Gallery Card
→ thumbnail

Entity Detail
→ large

Download / original view
→ original
```

Esto será especialmente importante para Version Media.

---

# 182. Accesibilidad

El frontend muestra buenas bases, pero el crecimiento de herramientas interactivas hace necesario prestar más atención a accesibilidad.

Áreas relevantes:

```text
botones solamente con icono
modales
dropdowns
drag
canvas
tabs
colores
texto pequeño
focus
```

---

# 183. Botones de icono

Todo botón que muestre solamente:

```text
×
+
−
⋮
```

debería tener:

```text
aria-label
```

o texto accesible equivalente.

Esto resulta especialmente importante dentro del Graph Builder.

---

# 184. Modales

Los modales deberían garantizar progresivamente:

```text
role="dialog"
aria-modal="true"
focus inicial
focus trap
Escape
retorno del foco
```

OmniConfirm ya establece foco sobre la acción principal y evita scroll del body, lo cual constituye una buena base.

---

# 185. Accesibilidad del Graph Builder

El Graph actualmente depende bastante de interacción mediante puntero.

Un usuario de teclado no dispone del mismo nivel de control para:

```text
mover nodo
conectar
reposicionar
```

Una evolución futura podría ofrecer:

```text
Mover arriba
Mover abajo
Mover izquierda
Mover derecha
```

desde inspector o comandos de teclado.

No necesariamente necesita reproducir exactamente el drag.

Debe ofrecer una alternativa funcional.

---

# 186. Color y significado

La aplicación generalmente acompaña colores con:

```text
texto
badge
icono
```

Esto es positivo.

Debe seguir evitándose una regla del tipo:

```text
verde = clasificado
rojo = eliminado
```

sin ninguna etiqueta textual.

---

# 187. Responsive del Graph

El resto de OmniMerge posee una responsividad bastante fuerte.

El Graph Builder, sin embargo, es inherentemente una herramienta de escritorio.

Intentar hacer que un lienzo de:

```text
2600 × 1600
```

funcione exactamente igual en un teléfono de 360 px puede producir una experiencia mala.

---

# 188. Estrategia móvil recomendada para Graph

En móvil podría ofrecerse:

```text
modo lectura
+
inspector simplificado
+
lista de conexiones
```

mientras la edición visual completa permanece recomendada para tablet grande/escritorio.

Esto es mejor que forzar un editor de nodos diminuto.

---

# 189. SEO

La página pública `welcome.blade.php` posee un rol distinto de las páginas privadas.

La landing debería concentrar:

```text
title
meta description
estructura semántica
heading hierarchy
contenido indexable
```

Mientras las herramientas privadas no necesitan priorizar SEO.

Esta separación debe mantenerse.

---

# 190. Fuentes externas

Los layouts oscuros utilizan Figtree mediante Bunny Fonts.

Esto funciona correctamente y proporciona una tipografía consistente.

Si en el futuro se requiere:

```text
funcionamiento offline
mayor control de privacidad
eliminar dependencia externa
```

podría considerarse self-hosting.

Actualmente no constituye una prioridad.

---

# 191. Testing frontend

El frontend está adquiriendo suficiente lógica como para que las pruebas de backend ya no sean la única protección necesaria.

Especialmente:

```text
Graph Builder
Dashboard Workspace
Attribute Structure
Community Explorer
Presentation Builder
```

contienen comportamiento cliente significativo.

---

# 192. Pruebas JavaScript futuras

Una evolución natural sería incorporar algo como:

```text
Vitest
```

para lógica JavaScript pura.

Ejemplos:

```text
cálculo de posiciones
normalización de estado
transformación de payloads
gestión de preferencias
helpers de conexiones
```

No sería necesario probar Tailwind.

---

# 193. Pruebas end-to-end

Posteriormente podrían utilizarse herramientas como:

```text
Laravel Dusk
Playwright
Cypress
```

para flujos críticos.

Ejemplos:

```text
crear entidad
subir imagen
crear versión
comparar versiones
crear PhaseTemplate
configurar Swiss
crear nodo
conectar nodo
validar grafo
```

No recomiendo incorporar todas estas herramientas simultáneamente.

Debe elegirse una estrategia cuando la complejidad de interacción lo justifique.

---

# 194. Consistencia visual entre módulos

Existe actualmente una identidad general fuerte, pero varias interfaces fueron evolucionando en momentos distintos.

Esto genera pequeñas diferencias en:

```text
botones
badges
paneles
headers
espaciados
empty states
```

La siguiente fase de frontend debería centrarse progresivamente en consolidar estos patrones.

---

# 195. No perder las identidades de dominio

Estandarizar no significa que todo deba ser indigo.

La estructura futura correcta sería:

```text
COMPONENTES COMPARTIDOS
        +
TOKENS DE MÓDULO
```

Por ejemplo:

```text
Card
Button
Modal
FilterPanel
```

pueden compartir estructura,

pero:

```text
Biblioteca
→ indigo

Torneos
→ amber
```

pueden mantener su propia identidad.

---

# 196. Posible shell modular futuro

Si aparecen varios módulos, podría existir un componente base conceptual:

```text
<x-module-layout
    module="tournaments"
    accent="amber"
>
```

o una arquitectura equivalente.

No es necesario implementarlo todavía.

Pero evitaría copiar layouts completos cuando aparezcan:

```text
Universos
Simulación
Estadísticas
```

---

# 197. Futuro frontend de Universos

Universos probablemente necesitará su propia identidad.

Conceptualmente tendrá que permitir:

```text
ver competidores
organizar temporadas
seleccionar versiones
crear torneos
ver calendario
seguir ranking
consultar historial
```

No debería introducirse directamente dentro del sidebar de Biblioteca como si fuera otro CRUD.

---

# 198. Workspace de Universo

Podría seguir el patrón aprendido en Versiones y Torneos:

```text
UNIVERSE
│
├── Overview
├── Competitors
├── Seasons
├── Tournaments
├── Rankings
└── History
```

El Hub permitiría entrar al módulo.

Dentro del Universo existiría navegación contextual propia.

---

# 199. Color futuro de Universos

Conviene seleccionar una identidad distinta.

Por ejemplo, conceptualmente podría utilizar:

```text
sky
cyan
teal
```

o alguna otra familia que no compita con:

```text
Biblioteca → indigo
Torneos → amber
```

El color exacto debe decidirse durante diseño.

Lo importante es conservar el sistema de identidad modular.

---

# 200. Futuro frontend de Simulation Engine

Simulación será probablemente otra herramienta especializada.

La UI necesitará representar:

```text
participantes
versiones
atributos utilizados
reglas
probabilidades
resultado
explicación
```

No debería limitarse a:

```text
Naruto ganó.
```

El usuario necesitará comprender **por qué**.

---

# 201. Simulation Preview

Puede reutilizar el patrón desarrollado en Phase Engines:

```text
CONFIGURACIÓN
      ↓
PREVIEW
      ↓
VALIDACIÓN
      ↓
EJECUCIÓN
```

Antes de utilizar una regla de simulación dentro de un torneo, debería poder probarse de manera aislada.

---

# 202. Futuro historial

Las futuras páginas de historial deberán diferenciar:

```text
información editable actual
```

de:

```text
evento histórico inmutable
```

Visualmente podría utilizarse:

```text
timeline
activity feed
match log
season timeline
```

El trabajo realizado en versiones ya proporciona experiencia útil en este tipo de visualización temporal.

---

# 203. Futuro ranking

Ranking no debería ser solamente una tabla.

Podría ofrecer:

```text
Tabla
Tarjetas
Evolución temporal
Comparación
Filtros por temporada
Filtros por torneo
```

reutilizando el sistema existente de múltiples vistas.

---

# 204. Futuro Tournament Runtime

El actual Tournament Graph es:

```text
DISEÑO
```

El futuro Runtime necesitará representar:

```text
EJECUCIÓN
```

No deben confundirse visualmente.

El diseñador muestra:

```text
Node
Connection
Start
Terminal
```

El runtime deberá mostrar:

```text
participantes
matches
scores
resultados
clasificados
estado
```

---

# 205. Posible UI de Runtime

Conceptualmente:

```text
Tournament Instance
│
├── Overview
├── Participants
├── Live Graph
├── Matches
├── Standings
├── Results
└── History
```

El “Live Graph” podría reutilizar el grafo de diseño, pero superponer:

```text
estado
cantidad de participantes
fase activa
fase completa
errores
```

---

# 206. Estados visuales del futuro runtime

Cada nodo podría mostrarse como:

```text
PENDING
→ slate

READY
→ cyan

RUNNING
→ amber

COMPLETED
→ emerald

ERROR
→ red
```

Esto crearía una representación visual inmediata de la competición.

---

# 207. Diseño para datos históricos

Una vez que OmniMerge produzca muchas competiciones, la prioridad UX cambiará parcialmente.

Actualmente la mayoría de pantallas responden:

> “¿Cómo configuro esto?”

En el futuro también tendrán que responder:

> “¿Qué pasó?”

Esto requerirá:

```text
filtros temporales
búsqueda
paginación
resúmenes
drill-down
```

---

# 208. Sistema de navegación futuro

La navegación global podría evolucionar hacia:

```text
Centro OmniMerge
│
├── Biblioteca
├── Comunidad
├── Torneos
├── Universos
└── Simulación
```

Mientras cada módulo mantiene su navegación secundaria.

Esto evita un sidebar global con cuarenta opciones.

---

# 209. Arquitectura visual futura recomendada

Una estructura escalable podría ser:

```text
GLOBAL
│
├── Design Tokens
├── Base Components
├── Modal System
├── Alert System
├── Image System
├── Preference System
└── User Controls

MODULES
│
├── Library
│   └── indigo
│
├── Community
│   └── violet
│
├── Tournaments
│   └── amber
│
├── Universes
│   └── identidad futura
│
└── Simulation
    └── identidad futura
```

---

# 210. Arquitectura JavaScript futura recomendada

Una estructura posible:

```text
resources/js/
│
├── app.js
├── bootstrap.js
│
├── core/
│   ├── confirm.js
│   ├── preferences.js
│   ├── http.js
│   └── utilities.js
│
├── library/
│   ├── dashboard.js
│   ├── entities.js
│   ├── entity-presentation.js
│   └── attribute-structure.js
│
├── community/
│   └── explorer.js
│
├── versions/
│   ├── workspace.js
│   └── compare.js
│
└── tournaments/
    ├── graph-builder.js
    ├── phase-preview.js
    └── runtime.js
```

Esto permitiría mantener Alpine sin que `app.js` se convierta en un archivo gigantesco.

---

# 211. Alpine.data como patrón futuro

Cada herramienta compleja podría registrarse así:

```javascript
document.addEventListener('alpine:init', () => {
    Alpine.data(
        'tournamentGraphBuilder',
        tournamentGraphBuilder
    );
});
```

Entonces Blade solamente necesita:

```html
<div x-data="tournamentGraphBuilder(...)">
```

La vista conoce la interfaz del componente, pero no toda su implementación.

---

# 212. Preferencias centralizadas

También podría crearse un helper conceptual:

```javascript
OmniPreferences.get(...)
OmniPreferences.set(...)
```

en lugar de utilizar:

```javascript
localStorage.getItem(...)
localStorage.setItem(...)
```

en cada pantalla.

Esto facilitaría:

```text
validación
versionado
migración
limpieza
sincronización futura
```

---

# 213. Fetch centralizado

El proyecto podría introducir una pequeña capa:

```javascript
omniFetch()
```

que gestione:

```text
CSRF
JSON
errores
loading
419 session expired
422 validation
500 server error
```

Esto será especialmente útil en Tournament Graph y futuros runtimes.

No necesita convertirse en un framework HTTP complejo.

---

# 214. Loading States

A medida que aumenten interacciones asincrónicas deben aparecer estados consistentes:

```text
Idle
Loading
Success
Error
```

Actualmente cada mini-aplicación puede resolverlos de forma ligeramente diferente.

Un componente global podría unificar:

```text
spinner
botón deshabilitado
texto “Guardando…”
```

---

# 215. Optimistic UI

No todas las acciones necesitan actualizarse optimistamente.

En elementos críticos como:

```text
Tournament Graph Connection
Match Result
Delete
```

es preferible esperar confirmación del servidor.

Para preferencias puramente visuales:

```text
view mode
density
tabs
```

la actualización puede ser inmediata.

---

# 216. Toasts futuros

`x-alert` funciona muy bien para redirects tradicionales.

Sin embargo, las interacciones asincrónicas futuras podrían beneficiarse de un sistema:

```text
toast
```

para mensajes como:

```text
Nodo movido
Conexión creada
Preferencia guardada
```

sin recargar la página.

Debería complementar a `x-alert`, no reemplazarlo necesariamente.

---

# 217. Data attributes como patrón declarativo

OmniConfirm demuestra que:

```text
data-*
```

puede ser una excelente herramienta para conectar Blade con comportamiento global.

Este patrón podría utilizarse prudentemente para:

```text
tooltips
copy-to-clipboard
dropdowns simples
```

pero no debería convertirse en una DSL gigantesca.

---

# 218. Código visual en Blade

Actualmente numerosas vistas contienen bloques:

```php
@php
    $colors = ...
    $icons = ...
@endphp
```

para elegir colores según estado o tipo.

Funciona, pero conforme los mismos mappings se repitan puede resultar mejor trasladarlos a:

```text
ViewModel
Presenter
Blade Component
Enum helper
```

Así se evita que distintas páginas muestren:

```text
ACTIVE
```

con colores diferentes por accidente.

---

# 219. Frontend y backend deben permanecer alineados

OmniMerge posee muchos estados de dominio.

Por ejemplo:

```text
ACTIVE
ARCHIVED
DRAFT
PUBLIC
PRIVATE
SWISS
ROUND_ROBIN
```

El frontend no debería mantener una lista completamente independiente que pueda quedar desactualizada.

Siempre que sea posible, las opciones deberían provenir del backend o compartir una definición clara.

---

# 220. Empty State contextual

Cada nuevo módulo debería diseñarse empezando por su estado vacío.

Ejemplo Universe:

```text
Todavía no has creado un Universo.

Un Universo permite colocar tus entidades
dentro de un contexto compartido.

[Crear mi primer Universo]
```

Es mejor que:

```text
No data.
```

---

# 221. Onboarding futuro

OmniMerge está alcanzando una complejidad donde un usuario nuevo puede no saber qué crear primero.

Una evolución futura podría ofrecer:

```text
1. Crea un tipo
2. Define atributos
3. Añade catálogos
4. Crea entidades
5. Organízalas
```

o permitir comenzar directamente por una entidad y crear dependencias durante el flujo.

---

# 222. Onboarding por ejemplo

También podría ofrecer plantillas demostrativas.

Ejemplo:

```text
Ejemplo Naruto
Ejemplo Países
Ejemplo Liga deportiva
```

Esto permitiría al usuario aprender la estructura observando un caso real.

---

# 223. Tooltips

Los conceptos técnicos de Torneos pueden beneficiarse de tooltips.

Ejemplos:

```text
BYE
Reseed
Floater
Acceleration
Entry Port
Phase Exit
```

Actualmente muchas explicaciones ya aparecen como texto permanente.

Los tooltips podrían complementar cuando el espacio sea reducido.

---

# 224. Ayuda contextual

El frontend debería evitar una gigantesca página de documentación como única ayuda.

Cada pantalla puede responder:

```text
¿Qué estoy configurando?
¿Por qué existe?
¿Qué efecto tendrá?
```

Los formularios de Torneos ya avanzan en esa dirección.

---

# 225. Destructive Zones

Las operaciones especialmente peligrosas deberían agruparse visualmente.

Ejemplo:

```text
Danger Zone
├── archivar
├── eliminar
└── resetear
```

PhaseTemplate ya utiliza este enfoque.

La separación evita que “Eliminar” aparezca junto al botón principal de edición sin contexto.

---

# 226. Confirmación proporcional al riesgo

No todas las acciones requieren modal.

Por ejemplo:

```text
cambiar vista
```

no necesita confirmación.

Pero:

```text
eliminar recurso
desvincular datos
archivar template
resetear estructura
```

sí puede necesitarla.

OmniConfirm debe reservarse para acciones con consecuencia real.

---

# 227. Visualización de códigos

OmniMerge utiliza códigos como:

```text
ENT000001
PHA...
TRN...
```

La interfaz suele presentarlos como metadato técnico.

Sería conveniente mantener una convención:

```text
font-mono
text-xs
slate
```

para que el usuario pueda distinguir:

```text
nombre humano
```

de:

```text
identificador estable
```

---

# 228. Navegación breadcrumbs

Con el crecimiento del sistema, determinados workspaces se beneficiarían de breadcrumbs explícitos.

Ejemplo:

```text
Torneos
/
Plantillas
/
Copa Mundial
/
Grafo
```

La navegación contextual ya resuelve parcialmente este problema, pero un breadcrumb puede mejorar orientación en estructuras profundas.

---

# 229. URLs y estado frontend

Los filtros importantes deberían seguir estando en URL cuando representan una búsqueda compartible.

Ejemplo:

```text
?search=naruto
&type=character
&status=ACTIVE
```

Mientras preferencias visuales como:

```text
grid vs gallery
```

pueden permanecer en localStorage.

Esta separación es importante.

---

# 230. Regla recomendada de persistencia

Puede utilizarse:

```text
URL
→ estado que cambia qué datos estoy consultando

localStorage
→ cómo prefiero ver esos datos

Database
→ configuración de negocio que debe persistir
```

Ejemplo:

```text
Filtro “ACTIVE”
→ URL

Vista “Gallery”
→ localStorage

Phase completion_mode
→ Database
```

Esta regla evita mezclar responsabilidades.

---

# 231. Filtros complejos futuros

Cuando las entidades posean muchas características, el usuario necesitará filtrado más poderoso.

Ejemplo:

```text
Anime = Naruto
AND
Aldea = Konoha
AND
Rango IN (Genin, Chunin)
```

El `Attribute Structure Builder` ya proporciona experiencia relevante para construir una futura interfaz de filtros compuestos.

---

# 232. Saved Views futuras

En bibliotecas grandes podría permitirse guardar:

```text
Mis personajes de Konoha
Mis entidades sin imagen
Atributos publicados
Colecciones incompletas
```

como vistas guardadas.

Eso sería una extensión natural del sistema de filtros actual.

---

# 233. Favoritos y recientes

El Hub y Dashboard podrían posteriormente priorizar:

```text
recursos recientes
favoritos
últimos editados
```

para reducir navegación en bibliotecas grandes.

---

# 234. Command Palette futura

Cuando OmniMerge alcance muchos módulos, una paleta:

```text
Ctrl + K
```

podría permitir:

```text
Buscar Naruto
Crear Entidad
Ir a Torneos
Abrir Universo X
```

El buscador actual del Dashboard constituye una primera base conceptual.

---

# 235. Evolución del Dashboard

El Dashboard ya permite personalización.

A largo plazo podría convertirse en un verdadero **Home Workspace** con widgets.

Pero conviene evitar transformarlo demasiado pronto en un constructor de dashboard genérico.

Primero debe resolver las tareas más frecuentes del usuario.

---

# 236. Evolución del Hub

El Hub debería continuar siendo más simple que los workspaces.

No debería duplicar:

```text
todos los filtros
todas las estadísticas
todas las acciones
```

de cada módulo.

Su responsabilidad principal será:

```text
orientación
estado general
entrada a módulos
```

---

# 237. Evolución de Comunidad

Cuando PhaseTemplates y TournamentTemplates sean públicos, Community podría evolucionar hacia categorías:

```text
Entidades
Colecciones
Atributos
Catálogos
Fases
Torneos
Universos
Creadores
```

El sistema actual de tabs y múltiples tipos de contenido ya proporciona una base adecuada.

---

# 238. Vista previa antes de clonar

Para recursos complejos como TournamentTemplate debería existir una vista pública suficientemente informativa antes de clonar.

No debería obligarse al usuario a copiar algo para descubrir su estructura.

Podría mostrar:

```text
número de fases
tipos de motores
complejidad
grafo read-only
entradas
terminales
```

---

# 239. Grafo público read-only

El Graph Builder actual puede evolucionar hacia:

```text
Editable Graph
```

y:

```text
Read-only Graph
```

El segundo sería útil para:

```text
Comunidad
historial
Tournament Instance
móvil
```

Esto permitiría reutilizar el renderer sin exponer herramientas de edición.

---

# 240. Separar renderer y editor del Graph

A nivel frontend, una arquitectura futura muy útil sería:

```text
Graph Renderer
      ↑
      │
Graph Editor
```

El Renderer sabe:

```text
dibujar nodos
dibujar conexiones
zoom
pan
seleccionar
```

El Editor añade:

```text
crear
mover
eliminar
conectar
editar
```

Esto facilitará reutilizar el grafo en otras superficies.

---

# 241. Posible evolución de SVG

SVG continúa siendo una excelente elección para conexiones.

No existe motivo inmediato para migrar a Canvas/WebGL.

SVG ofrece:

```text
DOM accesible
estilos CSS
eventos
flechas
texto
debug sencillo
```

Solo si los grafos llegasen a miles de elementos simultáneos podría evaluarse una alternativa más especializada.

---

# 242. No migrar a React únicamente por el Graph

El hecho de que `graph/show.blade.php` sea complejo no implica que toda OmniMerge deba convertirse en React.

Una estrategia más prudente es:

```text
1. extraer JS
2. separar renderer/editor
3. modularizar Alpine
4. medir complejidad
```

Solo si la interacción futura demuestra que Alpine resulta insuficiente tendría sentido introducir otra tecnología.

---

# 243. Cuándo Vue/React sí podría tener sentido

Podría justificarse si aparecen necesidades como:

```text
estado cliente enorme
undo/redo complejo
centenares de componentes interactivos
edición colaborativa en tiempo real
virtualización avanzada
offline-first
```

Actualmente esas necesidades no existen de manera general.

---

# 244. Undo/Redo futuro del Graph

Una mejora muy valiosa sería:

```text
Ctrl + Z
Ctrl + Shift + Z
```

para:

```text
mover nodo
crear conexión
eliminar conexión
crear nodo
```

Esto sí incrementaría significativamente la seguridad del editor.

Podría implementarse mediante historial de acciones cliente sin necesidad de React.

---

# 245. Autosave del Graph

La posición de nodos ya puede persistirse.

A futuro podría existir:

```text
Guardando...
Guardado
Error al guardar
```

en el header del editor.

Debe evitarse guardar cada `pointermove`; sería preferible persistir al terminar el drag o mediante debounce.

---

# 246. Keyboard Shortcuts futuros

Para usuarios avanzados:

```text
Delete
→ eliminar selección

Ctrl+D
→ duplicar nodo

Ctrl+Z
→ undo

Ctrl+Shift+Z
→ redo

+
→ zoom

-
→ zoom
```

podrían convertir Tournament Designer en una herramienta considerablemente más rápida.

---

# 247. Mini-map futura

Para grafos grandes podría añadirse una mini-map.

Ejemplo:

```text
┌─────────────────────────────┐
│                             │
│         CANVAS              │
│                             │
│                    ┌──────┐ │
│                    │MAPA  │ │
│                    └──────┘ │
└─────────────────────────────┘
```

No es necesaria todavía, pero será útil en torneos realmente complejos.

---

# 248. Snap to Grid

El fondo visual ya utiliza una cuadrícula.

Una mejora futura sería permitir:

```text
snap-to-grid
```

para mantener alineación automática.

También podría ofrecerse:

```text
activar/desactivar
```

para usuarios que prefieran posicionamiento libre.

---

# 249. Selección múltiple

Un futuro Graph Builder podría permitir:

```text
Shift + click
```

para seleccionar varios nodos y:

```text
mover
alinear
duplicar
eliminar
```

en conjunto.

Esto es más útil que añadir cientos de opciones individuales al inspector.

---

# 250. Acciones contextuales

Al hacer clic derecho o mediante menú `...`, podrían aparecer acciones como:

```text
Duplicar
Renombrar
Desactivar
Eliminar
Ver PhaseTemplate
```

Siempre deben existir también alternativas accesibles mediante botones normales.

---

# 251. Formularios complejos y progressive disclosure

Swiss demuestra que algunos formularios pueden tener muchas configuraciones.

La estrategia correcta es:

```text
mostrar primero lo esencial

y revelar
configuración avanzada
cuando sea necesaria
```

Esto evita intimidar a usuarios nuevos.

---

# 252. Básico vs Avanzado

Una futura mejora podría incorporar modos:

```text
Configuración básica

Configuración avanzada
```

Por ejemplo:

```text
Swiss básico
→ rondas
→ scoring
→ BYE

Swiss avanzado
→ floaters
→ acceleration
→ side balance
→ round rules
```

El backend puede seguir soportando todo.

La diferencia está únicamente en la complejidad expuesta.

---

# 253. Presets

Otra mejora UX importante sería ofrecer presets.

Ejemplos:

```text
Eliminación estándar
Mundial clásico
Liga ida y vuelta
Swiss 7 rondas
```

El usuario puede comenzar desde un preset y después modificar detalles.

Esto aprovecha la reutilización existente del backend.

---

# 254. Plantillas comunitarias

Posteriormente los presets pueden provenir de:

```text
OmniMerge defaults
```

o:

```text
Community
```

permitiendo una experiencia:

```text
Seleccionar template
↓
Clonar
↓
Editar
```

---

# 255. Estado actual del frontend por área

| Área                           | Estado                                 |
| ------------------------------ | -------------------------------------- |
| Landing                        | Muy desarrollada                       |
| Login/Register                 | Personalizado                          |
| Recuperación de cuenta         | Implementada                           |
| Hub                            | Implementado y visualmente consolidado |
| Perfil                         | Implementado                           |
| Dashboard Biblioteca           | Muy avanzado                           |
| Entity Types                   | CRUD visual                            |
| Entities                       | Avanzado                               |
| Bulk Entities                  | Implementado                           |
| Bulk Edit                      | Implementado                           |
| Entity Attributes              | Implementado                           |
| Entity Presentation            | Avanzado                               |
| Attributes                     | Avanzado                               |
| Attribute Structure            | Muy avanzado                           |
| Attribute Options/Catalogs     | Muy avanzado                           |
| Attribute Groups               | Implementado                           |
| Collections                    | Implementado                           |
| Community                      | Avanzado                               |
| Versions                       | Muy avanzado                           |
| Entity Versions                | Muy avanzado                           |
| Version Compare                | Implementado                           |
| Tournament Dashboard           | Implementado                           |
| Tournament Templates           | Implementado                           |
| Phase Templates                | Muy avanzado                           |
| Single Elimination UI          | Avanzado                               |
| Round Robin UI                 | Avanzado                               |
| Group Stage UI                 | Avanzado                               |
| Swiss UI                       | Muy avanzado                           |
| Competition Lab                | Foundation                             |
| Tournament Graph               | Foundation avanzada                    |
| Tournament Runtime             | Pendiente                              |
| Universes                      | Pendiente                              |
| Simulation UI                  | Pendiente                              |
| Rankings globales              | Pendiente                              |
| Historial competitivo completo | Pendiente                              |

---

# 256. Principales fortalezas del frontend

Las fortalezas más importantes que observo son:

1. **Identidad visual propia.**

OmniMerge ya no parece un Laravel Breeze básico.

2. **Separación visual por módulos.**

Biblioteca, Hub y Torneos tienen contextos distintos.

3. **Consistencia de paleta.**

Los colores tienen significado.

4. **Uso apropiado de Blade.**

La mayoría de páginas no necesitan SPA.

5. **Alpine utilizado donde aporta valor.**

6. **Componentes de imagen reutilizables.**

7. **Sistema global de confirmación.**

8. **Múltiples modos de visualización.**

9. **Preferencias persistentes en navegador.**

10. **Experiencia pensada para bibliotecas grandes.**

11. **Bulk creation/editing.**

12. **Workspaces especializados.**

13. **Version comparison.**

14. **Previews matemáticos de motores.**

15. **Graph Builder visual.**

16. **Microcopy educativa.**

17. **Responsive general consistente.**

18. **Diseño preparado para añadir módulos futuros.**

---

# 257. Principales deudas técnicas del frontend

También existen áreas que deberían vigilarse.

## Blade demasiado grandes

Principalmente:

```text
Dashboard
Welcome
Graph
Attribute Structure
Hub
Version Workspaces
```

## JavaScript inline

Los controladores Alpine complejos deberían comenzar a extraerse.

## CSS semántico implícito

El design system está principalmente dentro de utilities repetidas.

## Iconografía mixta

SVG + símbolos + emojis.

## localStorage disperso

Conviene centralizar preferencias.

## Testing frontend limitado

El comportamiento cliente está creciendo.

## Graph desktop-first

Necesitará estrategia específica para móvil/accesibilidad.

## Dependencia Tailwind inconsistente

Debe revisarse `@tailwindcss/vite`.

---

# 258. Qué NO considero un problema actualmente

No considero que sea necesario:

```text
reescribir todo en React
reescribir todo en Vue
introducir TypeScript obligatorio
crear microfrontends
separar backend/frontend
crear API REST para toda pantalla
```

Estas decisiones generarían mucho trabajo sin resolver las principales deudas actuales.

---

# 259. Qué sí haría primero

La evolución más lógica sería:

```text
1. Mantener Blade + Tailwind + Alpine.

2. Extraer JavaScript complejo
   a módulos.

3. Dividir Blade gigantes
   en partials semánticos.

4. Consolidar componentes visuales
   repetidos.

5. Crear una capa pequeña
   de preferencias.

6. Definir reglas de colores
   e iconografía.

7. Añadir testing
   para JS crítico.

8. Mejorar accesibilidad
   de herramientas complejas.
```

---

# 260. Principios que deben respetarse al generar frontend nuevo

A partir del estado actual del repositorio, cualquier nueva interfaz debería seguir estas reglas:

1. **Revisar primero el módulo al que pertenece.**

No debe asumirse automáticamente `app.blade.php`.

2. **Biblioteca utiliza principalmente indigo.**

3. **Torneos utiliza amber/orange como identidad global.**

4. **Cada motor competitivo mantiene su acento propio.**

5. **Hub y superficies públicas utilizan slate-950 con gradientes indigo/violet/fuchsia.**

6. **Código en inglés, interfaz principalmente en español.**

7. **Utilizar Figtree y el sistema tipográfico existente.**

8. **Utilizar `rounded-xl/2xl/3xl` de forma coherente.**

9. **Preferir bordes sutiles y superficies claras en workspaces.**

10. **Mantener sidebar de 72 y contenido desplazado desde `lg`.**

11. **Mantener responsividad móvil.**

12. **Usar componentes existentes antes de crear duplicados.**

13. **Usar `omni-image-upload` para imágenes cuando corresponda.**

14. **Usar OmniConfirm para acciones realmente destructivas.**

15. **No utilizar `window.confirm()` si existe OmniConfirm.**

16. **Usar Alpine para reactividad localizada.**

17. **No crear JavaScript gigante dentro de Blade si la lógica puede extraerse.**

18. **Usar `@js()` para datos servidor → navegador.**

19. **Persistir preferencias visuales en localStorage únicamente cuando sean realmente preferencias.**

20. **Mantener filtros de datos importantes en URL cuando sea posible.**

21. **No confiar en validación cliente como seguridad.**

22. **Mostrar estados vacíos útiles.**

23. **Explicar opciones técnicas complejas.**

24. **No ocultar consecuencias de acciones destructivas.**

25. **Mantener diseño de preview separado del runtime real.**

26. **No mezclar Tournament Designer con ejecución competitiva futura.**

27. **No convertir cada nuevo módulo en una copia de Biblioteca.**

28. **Conservar la identidad modular de OmniMerge.**

---

# 261. Dirección UX global del producto

El frontend actual revela una dirección bastante clara.

OmniMerge no intenta convertirse en:

> “un panel administrativo”.

Busca convertirse progresivamente en:

> **una colección de herramientas visuales conectadas entre sí.**

La evolución puede representarse así:

```text
CRUD
↓
Biblioteca visual
↓
Exploradores
↓
Workspaces
↓
Builders
↓
Designers
↓
Runtime visual
```

---

# 262. Evolución del tipo de interfaz

Los primeros módulos responden principalmente:

```text
Crear
Editar
Eliminar
```

Los módulos posteriores responden:

```text
Explorar
Comparar
Resolver
Previsualizar
Conectar
Simular
```

Esto es un cambio fundamental.

La arquitectura frontend futura debe diseñarse pensando más en:

```text
herramientas
```

que en:

```text
formularios
```

---

# 263. La Biblioteca como workspace creativo

La Biblioteca está evolucionando hacia un lugar donde el usuario no solamente almacena datos.

Hace:

```text
creación
clasificación
organización
versionado
exploración
publicación
reutilización
```

Por ello su interfaz se parece cada vez más a un gestor creativo.

---

# 264. Torneos como Designer

El módulo de Torneos ya comunica explícitamente otra identidad:

```text
Designer
```

El usuario no está llenando una tabla de torneo.

Está diseñando:

```text
mecánicas
fases
salidas
reglas
conexiones
```

El Tournament Graph lleva esta idea al máximo.

---

# 265. Universos debería convertirse en Manager

El futuro módulo de Universos probablemente tendrá otra personalidad.

Conceptualmente:

```text
Universe Manager
```

donde el usuario administra:

```text
temporadas
competidores
calendario
torneos
estado
historia
```

---

# 266. Simulation podría convertirse en Lab/Engine

La simulación probablemente necesitará una experiencia de:

```text
laboratorio
```

antes de integrarse completamente.

Por ejemplo:

```text
Competidor A
vs
Competidor B

Contexto
Reglas
Atributos

[Simular]

Resultado
Explicación
```

Después ese motor podrá ejecutarse automáticamente desde Tournament Runtime.

---

# 267. Lenguaje visual futuro por módulo

La plataforma podría terminar con una organización visual como:

```text
OMNIMERGE
│
├── Hub
│   slate / indigo / violet
│
├── Library
│   indigo
│
├── Community
│   violet
│
├── Tournaments
│   amber
│
├── Universes
│   color futuro
│
└── Simulation
    color futuro
```

La identidad debe permitir saber “dónde estoy” sin leer continuamente el breadcrumb.

---

# 268. Relación entre diseño y arquitectura backend

El frontend actual funciona bien precisamente porque refleja abstracciones del backend.

Ejemplos:

```text
Attribute
→ página de definición

EntityAttribute
→ características de entidad

Version
→ workspace

EntityVersion
→ timeline

PhaseTemplate
→ diseñador de fase

TournamentPhaseNode
→ tarjeta del grafo

PhaseExit
→ conector de salida

EntryPort
→ conector de entrada

TournamentTerminal
→ bloque terminal
```

Esta alineación entre modelo y representación debe conservarse.

---

# 269. No exponer el backend literalmente

Sin embargo, reflejar el dominio no significa mostrar al usuario nombres de tabla.

Por ejemplo, no debería decirse:

```text
Crear TournamentPhaseNode
```

si puede decirse:

```text
Agregar fase al torneo
```

El frontend traduce el modelo técnico a una acción humana.

---

# 270. Papel educativo del color

La codificación visual también ayuda a enseñar.

Después de utilizar Torneos varias veces, el usuario puede comenzar a asociar:

```text
verde
→ entra

violeta
→ sale/conecta

rosa
→ termina

amber
→ fase/nodo activo
```

El diseño, por tanto, sirve también como sistema de aprendizaje.

---

# 271. Copy y personalidad

La interfaz utiliza un tono directo y creativo.

Ejemplos conceptuales:

```text
Tu imaginación, estructurada.

Crea cualquier cosa. Conecta todo.

Descubre. Copia. Evoluciona.

Construye el lenguaje de tus competiciones.
```

Este tono es coherente con OmniMerge como herramienta creativa.

Debería mantenerse evitando transformarlo en un panel corporativo excesivamente frío.

---

# 272. Consistencia con “Create · Connect · Evolve”

El lema:

```text
Create · Connect · Evolve
```

resume también la arquitectura de interacción.

```text
Create
→ Biblioteca

Connect
→ relaciones, colecciones, torneos, universos

Evolve
→ versiones, temporadas, simulación, historial
```

La interfaz ya empieza a reflejar estas tres etapas.

---

# 273. Consideración importante: frontend como sistema de conocimiento

OmniMerge almacena conceptos complejos.

La interfaz necesita evitar que el usuario pierda contexto.

Por eso son valiosas:

```text
descripciones
help text
previews
badges
jerarquías
breadcrumbs
workspaces
comparaciones
```

La prioridad no debe ser únicamente reducir la cantidad de clics.

Debe ser preservar comprensión.

---

# 274. Estado general de madurez

El frontend puede dividirse aproximadamente en tres generaciones.

## Primera generación

```text
Auth
CRUD básicos
formularios
listas
```

## Segunda generación

```text
múltiples vistas
densidades
filtros
Community
uploads
bulk operations
```

## Tercera generación

```text
Version Workspaces
Attribute Structure
Presentation Builder
Phase Engines
Tournament Graph
```

La tercera generación está marcando claramente la dirección futura.

---

# 275. Arquitectura frontend objetivo

Sin cambiar radicalmente de tecnología, OmniMerge puede evolucionar hacia:

```text
Laravel Blade
    │
    ├── Layout System
    │
    ├── Design System
    │
    ├── Blade Components
    │
    ├── Domain Partials
    │
    └── Reactive Islands
             │
             ├── Alpine modules
             ├── local preferences
             ├── async requests
             └── interactive builders
```

Esto proporciona suficiente capacidad para una gran parte del roadmap.

---

# 276. Cuándo reconsiderar la arquitectura frontend

Solo tendría sentido replantear de forma más profunda la tecnología cuando exista evidencia de que aparecen problemas como:

```text
Graph extremadamente complejo
edición colaborativa
runtime en tiempo real
offline
miles de elementos interactivos
estado cliente masivo
```

Hasta ese momento, modularizar la solución actual ofrece mejor relación costo/beneficio.

---

# 277. Conclusión general

El frontend de OmniMerge ha evolucionado mucho más allá de una interfaz Laravel convencional.

Actualmente posee una arquitectura híbrida muy clara:

```text
Blade
→ estructura y renderizado

Tailwind
→ identidad visual

Alpine
→ interacción localizada

Laravel Forms
→ operaciones persistentes

fetch / Axios
→ interacciones dinámicas selectivas

localStorage
→ preferencias visuales
```

La plataforma también posee distintas identidades según el contexto:

```text
Landing / Auth / Hub
→ experiencia inmersiva oscura

Biblioteca
→ workspace claro productivo

Community
→ descubrimiento y reutilización

Versiones
→ exploración, timeline y comparación

Torneos
→ Competition Designer

Tournament Graph
→ editor visual de lógica competitiva
```

La principal fortaleza no está únicamente en que “se vea bonito”. Está en que el diseño empieza a corresponder directamente con la arquitectura conceptual de OmniMerge.

La Biblioteca utiliza interfaces adecuadas para crear y organizar.

Versiones utiliza árboles, timelines y comparación porque trabaja con evolución.

Comunidad utiliza exploradores porque trabaja con descubrimiento.

Los motores competitivos utilizan configuraciones y previews porque trabajan con reglas.

Tournament Graph utiliza nodos y conexiones porque trabaja con composición lógica.

Esta correspondencia entre **forma de interfaz** y **naturaleza del problema** debería mantenerse como una de las reglas más importantes de desarrollo.

La principal deuda técnica tampoco exige una reescritura. El problema más evidente es que varias herramientas han crecido dentro de Blade hasta superar cientos o miles de líneas. El siguiente nivel de madurez debería consistir en extraer comportamiento Alpine hacia módulos JavaScript, dividir markup en partials semánticos, consolidar componentes visuales, centralizar determinadas preferencias e introducir pruebas para las interacciones más críticas.

La arquitectura tecnológica actual continúa siendo válida:

```text
Laravel
+
Blade
+
Tailwind
+
Alpine
+
Vite
```

y puede soportar todavía una evolución considerable.

La dirección futura debería ser:

```text
NO
“convertir todo en una SPA”

SÍ
“convertir OmniMerge en un sistema
de workspaces modulares,
interactivos y consistentes”
```

La evolución visual completa puede resumirse así:

```text
OMNIMERGE
│
├── LANDING
│   └── descubre la plataforma
│
├── AUTH
│   └── entra a la plataforma
│
├── HUB
│   └── elige dónde trabajar
│
├── LIBRARY
│   ├── crea
│   ├── organiza
│   ├── versiona
│   └── publica
│
├── COMMUNITY
│   ├── descubre
│   └── reutiliza
│
├── TOURNAMENT DESIGNER
│   ├── diseña fases
│   ├── configura motores
│   ├── prueba formatos
│   └── conecta grafos
│
├── FUTURE UNIVERSE MANAGER
│   ├── contextualiza entidades
│   ├── administra temporadas
│   └── organiza competiciones
│
├── FUTURE SIMULATION ENGINE
│   ├── prueba interacciones
│   └── resuelve resultados
│
└── FUTURE HISTORY / RANKINGS
    ├── explica qué ocurrió
    └── muestra cómo evolucionó
```

Por tanto, el frontend de OmniMerge debe continuar siendo diseñado no como una colección de páginas independientes, sino como un **ecosistema coherente de herramientas especializadas**, donde cada módulo posea personalidad propia pero comparta una base visual, técnica y de interacción común.

Ese equilibrio entre:

```text
CONSISTENCIA GLOBAL
+
IDENTIDAD DE CADA DOMINIO
+
INTERACCIÓN ADECUADA AL PROBLEMA
```

es actualmente la característica más importante que debe protegerse durante toda la evolución futura de OmniMerge.
