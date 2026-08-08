<div align="center">

# 🌌 OmniMerge

### Create · Connect · Evolve

**Plataforma web modular para crear, organizar, compartir y reutilizar entidades, atributos y colecciones, preparada para evolucionar hacia universos, torneos, simulaciones y rankings.**

**Laravel 12 · PHP 8.2+ · MySQL · Blade · Tailwind CSS · Alpine.js · Vite**

---

**Estado:** En desarrollo activo
**Arquitectura:** MVC + Form Requests + Policies + Services
**Repositorio:** `grverlearner/OmniMerge`

</div>

---

# 📖 Tabla de contenidos

1. [¿Qué es OmniMerge?](#-qué-es-omnimerge)
2. [Visión del proyecto](#-visión-del-proyecto)
3. [Problema que busca resolver](#-problema-que-busca-resolver)
4. [Arquitectura general de la plataforma](#-arquitectura-general-de-la-plataforma)
5. [Flujo del usuario](#-flujo-del-usuario)
6. [Módulos actuales](#-módulos-actuales)
7. [Conceptos principales](#-conceptos-principales)
8. [Características implementadas](#-características-implementadas)
9. [Stack tecnológico](#-stack-tecnológico)
10. [Arquitectura Laravel](#-arquitectura-laravel)
11. [Estructura del proyecto](#-estructura-del-proyecto)
12. [Modelo de datos](#-modelo-de-datos)
13. [Relaciones principales](#-relaciones-principales)
14. [Rutas principales](#-rutas-principales)
15. [Autenticación](#-autenticación)
16. [Centro OmniMerge — Hub](#-centro-omnimerge--hub)
17. [Biblioteca](#-biblioteca)
18. [Comunidad](#-comunidad)
19. [Gestión de imágenes](#-gestión-de-imágenes)
20. [Seguridad](#-seguridad)
21. [Instalación](#-instalación)
22. [Ejecución](#-ejecución)
23. [Comandos útiles](#-comandos-útiles)
24. [Pruebas](#-pruebas)
25. [Git y flujo de trabajo](#-git-y-flujo-de-trabajo)
26. [Documentación](#-documentación)
27. [Estado del desarrollo](#-estado-del-desarrollo)
28. [Roadmap](#-roadmap)
29. [Solución de problemas](#-solución-de-problemas)
30. [Principios del proyecto](#-principios-del-proyecto)

---

# 🌌 ¿Qué es OmniMerge?

OmniMerge es una plataforma web multiusuario orientada a la **creación, organización, reutilización y futura interacción de entidades completamente personalizables**.

La plataforma no obliga al usuario a trabajar únicamente con personajes humanos ni con estructuras predefinidas.

En OmniMerge una entidad puede representar:

* 👤 Un personaje.
* 🐉 Una criatura.
* 🐺 Un animal.
* 🌎 Un país.
* 🏙️ Una ciudad.
* 🪐 Un planeta.
* ⚔️ Un objeto.
* 🚗 Un vehículo.
* 🏰 Un lugar.
* 🛡️ Una organización.
* 🔥 Un elemento.
* 🎨 Un color.
* ✨ Una habilidad.
* 💡 Un concepto abstracto.
* Cualquier otra cosa definida por el usuario.

La idea central es:

> **OmniMerge no decide qué información debe tener una entidad. El usuario diseña su propia estructura.**

Por ejemplo:

```text
Entidad: Naruto Uzumaki

Tipo:
Personaje

Características:
├── Anime: Naruto
├── Elementos:
│   ├── Viento
│   └── Fuego
├── Poder: 92
├── Puede volar: No
└── Descripción: Ninja de la Aldea Oculta de la Hoja
```

La misma plataforma también puede utilizarse para:

```text
Entidad: Perú

Tipo:
País

Características:
├── Continente: América
├── Capital: Lima
├── Idioma: Español
├── Moneda: Sol
└── Color representativo: #D91023
```

Por esta razón OmniMerge no es simplemente un creador de personajes.

Es una **plataforma de modelado flexible de entidades**.

---

# 🎯 Visión del proyecto

OmniMerge se está diseñando como una plataforma modular.

La Biblioteca actualmente permite construir los elementos base.

En versiones futuras estas entidades podrán utilizarse dentro de:

```text
Biblioteca
    ↓
Universos
    ↓
Temporadas
    ↓
Torneos
    ↓
Simulaciones
    ↓
Resultados
    ↓
Rankings
    ↓
Historial
```

La visión general es permitir que un usuario pueda:

1. Crear entidades.
2. Diseñar sus características.
3. Agruparlas.
4. Compartirlas.
5. Reutilizarlas.
6. Introducirlas en universos.
7. Participar en torneos.
8. Ejecutar simulaciones.
9. Registrar resultados.
10. Analizar su evolución.

---

# ❓ Problema que busca resolver

Muchas herramientas existentes para crear personajes, fichas, historias o simulaciones presentan limitaciones como:

* Formularios rígidos.
* Características predefinidas.
* Dependencia de una temática específica.
* Restricción a personajes humanoides.
* Falta de atributos personalizados.
* Falta de selección múltiple.
* Ausencia de catálogos reutilizables.
* Falta de organización por colecciones.
* Falta de reutilización entre diferentes contextos.
* Ausencia de clonación comunitaria.
* Escasa preparación para simulaciones automáticas.

OmniMerge propone una arquitectura donde la estructura no está completamente determinada por el desarrollador.

El usuario puede construirla.

---

# 🏗 Arquitectura general de la plataforma

OmniMerge está dividido conceptualmente en diferentes niveles.

```text
                           OMNIMERGE
                               │
              ┌────────────────┴────────────────┐
              │                                 │
              ▼                                 ▼
       Página pública                     Autenticación
             /                          /login /register
              │                                 │
              └────────────────┬────────────────┘
                               │
                               ▼
                      🏠 CENTRO OMNIMERGE
                             /hub
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
        ▼                      ▼                      ▼
 📚 Biblioteca           🌐 Comunidad          👤 Perfil
   /dashboard              /explore              /profile
        │
        ├── Tipos de entidad
        ├── Entidades
        ├── Atributos
        ├── Opciones
        ├── Grupos
        └── Colecciones


              MÓDULOS FUTUROS DESDE EL HUB

        ├── 🌌 Universos
        ├── 🏆 Torneos
        ├── ⚡ Simulaciones
        └── 📊 Rankings
```

---

# 🔄 Flujo del usuario

## Visitante

```text
/
↓
Landing pública
↓
Login o Registro
```

## Usuario que inicia sesión

```text
/login
↓
Validación
↓
Autenticación correcta
↓
/hub
```

## Usuario que se registra

```text
/register
↓
Creación de cuenta
↓
Inicio automático de sesión
↓
/hub
```

## Desde el Hub

```text
/hub
│
├── Biblioteca
│      ↓
│   /dashboard
│
├── Comunidad
│      ↓
│   /explore
│
├── Perfil
│      ↓
│   /profile
│
├── Universos
│      ↓
│   Próximamente
│
├── Torneos
│      ↓
│   Próximamente
│
└── Rankings
       ↓
    Próximamente
```

---

# 🧩 Módulos actuales

## 🌐 Landing pública

Ruta:

```text
/
```

Presenta:

* Qué es OmniMerge.
* Características principales.
* Ejemplos de entidades.
* Atributos dinámicos.
* Comunidad.
* Roadmap.
* Inicio de sesión.
* Registro.

---

## 🔐 Autenticación

Incluye:

* Registro.
* Inicio de sesión.
* Cierre de sesión.
* Recuperación de contraseña.
* Confirmación de contraseña.
* Gestión básica de perfil.

---

## 🏠 Centro OmniMerge — Hub

Ruta:

```text
/hub
```

Es el punto central de la aplicación después de autenticarse.

Permite acceder a:

* 📚 Biblioteca.
* 🌐 Comunidad.
* 👤 Perfil y cuenta.
* 🌌 Universos — futuro.
* 🏆 Torneos — futuro.
* 📊 Rankings — futuro.

También muestra:

* Estadísticas generales.
* Creaciones recientes.
* Accesos rápidos.
* Información del usuario.

---

## 📚 Biblioteca

Actualmente es el módulo más desarrollado.

Contiene:

```text
Biblioteca
│
├── Dashboard
├── Tipos de entidad
├── Entidades
├── Atributos
├── Valores y opciones
├── Grupos de atributos
└── Colecciones
```

La Biblioteca representa el lugar donde se construyen las piezas reutilizables de OmniMerge.

---

## 🌐 Comunidad

Permite explorar contenido publicado por otros usuarios.

Actualmente contempla:

* Entidades.
* Colecciones.
* Atributos.
* Búsqueda.
* Filtros.
* Ordenamiento.
* Contadores.
* Detalles.
* Clonación de contenido.

---

## 👤 Perfil y cuenta

Actualmente permite administrar aspectos básicos de la cuenta.

Está preparado para evolucionar posteriormente hacia:

* Perfil público.
* Avatar.
* Biografía.
* Privacidad.
* Seguridad.
* Apariencia.
* Notificaciones.
* Preferencias.

---

# 💡 Conceptos principales

## Tipo de entidad

Define la clasificación general de una entidad.

Ejemplos:

```text
Personaje
País
Animal
Objeto
Planeta
Criatura
Lugar
Concepto
```

---

## Entidad

Es un elemento concreto creado por el usuario.

Ejemplos:

```text
Naruto Uzumaki
Perú
Dragón Arcano
Espada Legendaria
Planeta Tierra
```

---

## Atributo

Representa una característica que puede asignarse a una entidad.

Ejemplos:

```text
Anime
Elemento
Poder
Edad
Fecha de nacimiento
Color
Puede volar
Descripción
```

---

## Opción de atributo

Es un valor perteneciente a un catálogo.

Ejemplo:

```text
Atributo:
Anime

Opciones:
├── Naruto
├── One Piece
├── Dragon Ball
└── Bleach
```

Cada opción puede almacenar:

* Nombre.
* Código.
* Descripción.
* Imagen.
* Icono.
* Color.
* Valor numérico.
* Opción padre.
* Metadatos.
* Estado.

---

## Grupo de atributos

Permite organizar atributos visualmente.

Ejemplos:

```text
Información general
Apariencia
Personalidad
Combate
Historia
Poderes
Información geográfica
```

---

## Colección

Permite agrupar entidades.

Ejemplo:

```text
Colección:
Protagonistas de anime

Entidades:
├── Naruto Uzumaki
├── Monkey D. Luffy
└── Son Goku
```

---

## Biblioteca

Es el repositorio personal del usuario.

Contiene las piezas que posteriormente podrán utilizarse dentro de otros módulos.

---

## Hub

Es el centro general de OmniMerge.

No administra directamente las estructuras complejas de las entidades.

Su trabajo es conectar los diferentes módulos.

---

# ✨ Características implementadas

## Usuarios

* Registro.
* Username único.
* Correo único.
* Inicio de sesión.
* Cierre de sesión.
* Estado del usuario.
* Roles.
* Registro de último acceso.
* Recuperación de contraseña.

---

## Tipos de entidad

* Crear.
* Listar.
* Mostrar.
* Editar.
* Eliminar.
* Código personalizado.
* Nombre.
* Descripción.
* Icono.
* Color.
* Estado.
* Orden.

---

## Entidades

* CRUD completo.
* Tipo de entidad.
* Código.
* Nombre.
* Slug.
* Descripción.
* Imagen.
* Estado.
* Visibilidad.
* Publicación.
* Clonación.
* Metadatos.
* Atributos dinámicos.
* Colecciones.

---

## Atributos dinámicos

Tipos de datos contemplados:

```text
TEXT
LONG_TEXT
INTEGER
DECIMAL
BOOLEAN
DATE
COLOR
OPTION
```

Orígenes de valores:

```text
FREE
CATALOG
MIXED
```

Presentaciones posibles:

```text
TEXTBOX
TEXTAREA
NUMBER
SELECT
MULTISELECT
RADIO
CHECKBOX
TAGS
SLIDER
COLOR_PICKER
DATE_PICKER
```

Configuraciones:

* Obligatorio.
* Visible.
* Destacado.
* Filtrable.
* Comparable.
* Buscable.
* Multivalor.
* Valores personalizados.
* Valores mínimos y máximos.
* Unidad.
* Longitud.
* Configuración JSON.

---

## Opciones

* Crear opciones.
* Editar.
* Eliminar.
* Buscar.
* Filtrar.
* Imagen.
* Icono.
* Color.
* Descripción.
* Valor numérico.
* Orden.
* Jerarquía padre/hijo.
* Metadatos.

---

## Multiselección

Un atributo puede permitir múltiples opciones.

Ejemplo:

```text
Elemento:
├── Fuego
├── Viento
└── Luz
```

---

## Grupos de atributos

Permiten organizar características.

Diseños contemplados:

```text
LIST
GRID
CARDS
TABLE
COMPACT
```

---

## Colecciones

* CRUD.
* Portada.
* Icono.
* Color.
* Visibilidad.
* Estado.
* Publicación.
* Clonación.
* Asociación de múltiples entidades.
* Orden de entidades.

---

## Comunidad

* Contenido público.
* Búsqueda.
* Filtros.
* Ordenamiento.
* Estadísticas.
* Detalles públicos.
* Vistas.
* Clonaciones.
* Procedencia.
* Copias independientes.

---

# 🛠 Stack tecnológico

## Backend

```text
PHP 8.2+
Laravel 12
Eloquent ORM
Laravel Breeze
Laravel Policies
Form Requests
Services
Migrations
```

## Frontend

```text
Blade
Tailwind CSS
Alpine.js
JavaScript
Axios
Vite
```

## Base de datos

```text
MySQL
```

## Desarrollo

```text
Composer
Node.js
NPM
Git
GitHub
PHPUnit
Laravel Pint
Visual Studio Code
```

---

# 🧱 Arquitectura Laravel

OmniMerge utiliza MVC complementado con otras capas.

```text
Solicitud HTTP
      │
      ▼
    Route
      │
      ▼
  Middleware
      │
      ▼
 Controller
      │
 ┌────┼──────────────┐
 │    │              │
 ▼    ▼              ▼
Request Policy      Service
 │    │              │
 └────┴──────┬───────┘
             ▼
           Model
             │
             ▼
          MySQL
             │
             ▼
        Blade View
             │
             ▼
        Navegador
```

---

# 📦 Responsabilidad de cada capa

## Migration

Define la estructura de la base de datos.

```text
database/migrations/
```

---

## Model

Representa los datos y relaciones.

```text
app/Models/
```

---

## Form Request

Valida y normaliza información.

```text
app/Http/Requests/
```

---

## Policy

Controla permisos.

```text
app/Policies/
```

---

## Controller

Coordina solicitudes.

```text
app/Http/Controllers/
```

---

## Service

Contiene lógica compleja.

```text
app/Services/
```

---

## Route

Define URLs.

```text
routes/
```

---

## Blade

Genera la interfaz.

```text
resources/views/
```

---

## Test

Comprueba el comportamiento esperado.

```text
tests/
```

---

# 📁 Estructura del proyecto

```text
OmniMerge/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Attributes/
│   │   │   ├── Collections/
│   │   │   ├── Community/
│   │   │   ├── Dashboard/
│   │   │   ├── Entities/
│   │   │   ├── EntityTypes/
│   │   │   ├── Hub/
│   │   │   ├── Controller.php
│   │   │   └── ProfileController.php
│   │   │
│   │   └── Requests/
│   │       ├── Auth/
│   │       ├── Attributes/
│   │       ├── Collections/
│   │       ├── Entities/
│   │       └── EntityTypes/
│   │
│   ├── Models/
│   │   ├── Attribute.php
│   │   ├── AttributeGroup.php
│   │   ├── AttributeOption.php
│   │   ├── Collection.php
│   │   ├── Entity.php
│   │   ├── EntityAttribute.php
│   │   ├── EntityAttributeValue.php
│   │   ├── EntityType.php
│   │   └── User.php
│   │
│   ├── Policies/
│   │   ├── AttributeGroupPolicy.php
│   │   ├── AttributeOptionPolicy.php
│   │   ├── AttributePolicy.php
│   │   ├── CollectionPolicy.php
│   │   ├── EntityPolicy.php
│   │   └── EntityTypePolicy.php
│   │
│   ├── Providers/
│   │
│   └── Services/
│       ├── Community/
│       │   └── CommunityCloneService.php
│       │
│       └── Entities/
│           └── EntityAttributeValueService.php
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── docs/
│   ├── arquitectura/
│   ├── desarrollo/
│   ├── modulos/
│   ├── manuales/
│   └── pdf/
│
├── public/
│
├── resources/
│   ├── css/
│   ├── js/
│   │
│   └── views/
│       ├── auth/
│       ├── attribute-groups/
│       ├── attribute-options/
│       ├── attributes/
│       ├── collections/
│       ├── community/
│       ├── components/
│       ├── dashboard/
│       ├── entities/
│       ├── entity-types/
│       ├── hub/
│       ├── layouts/
│       ├── partials/
│       ├── profile/
│       └── welcome.blade.php
│
├── routes/
│   ├── auth.php
│   ├── console.php
│   └── web.php
│
├── storage/
│
├── tests/
│
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── phpunit.xml
├── tailwind.config.js
├── vite.config.js
└── README.md
```

> `docs/` es la carpeta recomendada para conservar la documentación técnica del proyecto.

---

# 🖼 Estructura de vistas

```text
resources/views/
│
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   └── ...
│
├── attributes/
│   ├── partials/
│   │   └── form.blade.php
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
│
├── attribute-options/
│   ├── partials/
│   │   └── form.blade.php
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
│
├── attribute-groups/
│
├── collections/
│
├── community/
│   ├── partials/
│   │   ├── filters.blade.php
│   │   ├── entity-card.blade.php
│   │   ├── attribute-card.blade.php
│   │   └── collection-card.blade.php
│   ├── index.blade.php
│   ├── entity.blade.php
│   ├── attribute.blade.php
│   └── collection.blade.php
│
├── dashboard/
│   └── index.blade.php
│
├── entities/
│
├── entity-types/
│
├── hub/
│   └── index.blade.php
│
├── layouts/
│   ├── app.blade.php
│   ├── guest.blade.php
│   └── hub.blade.php
│
├── partials/
│   ├── sidebar.blade.php
│   └── header.blade.php
│
└── welcome.blade.php
```

---

# 🗄 Modelo de datos

## `users`

Usuarios de OmniMerge.

Principales datos:

```text
id
name
username
email
password
role
status
last_login_at
timestamps
deleted_at
```

---

## `entity_types`

Tipos definidos por los usuarios.

Ejemplos:

```text
Personaje
País
Objeto
Criatura
```

---

## `entities`

Entidades principales.

Contempla:

```text
user_id
entity_type_id
source_entity_id
code
name
slug
description
image
visibility
status
allow_cloning
views_count
clones_count
published_at
metadata
```

---

## `attributes`

Características dinámicas.

Incluye información como:

```text
user_id
source_attribute_id
code
name
slug
description
image
icon
color
data_type
value_source
display_style
allows_multiple
allows_custom_values
is_required
is_filterable
is_comparable
is_searchable
is_visible
is_featured
unit
scope
status
allow_cloning
views_count
clones_count
published_at
```

---

## `attribute_options`

Opciones seleccionables.

Ejemplo:

```text
Attribute:
Anime

Options:
Naruto
One Piece
Dragon Ball
```

---

## `attribute_groups`

Agrupación visual de atributos.

---

## `attribute_group_attribute`

Relación muchos a muchos entre:

```text
AttributeGroup
↕
Attribute
```

---

## `entity_attributes`

Relaciona:

```text
Entity
↓
Attribute
```

Permite almacenar configuración específica de ese atributo dentro de una entidad.

---

## `entity_attribute_values`

Almacena valores concretos.

Puede manejar:

```text
text_value
integer_value
decimal_value
boolean_value
date_value
color_value
attribute_option_id
custom_value
json_value
```

---

## `collections`

Agrupa entidades.

---

## `collection_entity`

Tabla intermedia entre:

```text
Collection
↕
Entity
```

---

## `community_interactions`

Registra actividad comunitaria.

Tipos contemplados:

```text
VIEW
CLONE
FAVORITE
```

Contenido:

```text
ENTITY
COLLECTION
ATTRIBUTE
```

---

# 🔗 Relaciones principales

```text
User
│
├── hasMany EntityType
├── hasMany Entity
├── hasMany Attribute
├── hasMany AttributeGroup
└── hasMany Collection
```

```text
EntityType
│
├── belongsTo User
└── hasMany Entity
```

```text
Entity
│
├── belongsTo User
├── belongsTo EntityType
├── hasMany EntityAttribute
├── belongsToMany Collection
├── belongsTo source Entity
└── hasMany cloned Entities
```

```text
Attribute
│
├── belongsTo User
├── hasMany AttributeOption
├── hasMany EntityAttribute
├── belongsToMany AttributeGroup
├── belongsTo source Attribute
└── hasMany cloned Attributes
```

```text
AttributeOption
│
├── belongsTo Attribute
├── belongsTo parent AttributeOption
├── hasMany child AttributeOption
└── hasMany EntityAttributeValue
```

```text
Collection
│
├── belongsTo User
├── belongsToMany Entity
├── belongsTo source Collection
└── hasMany cloned Collections
```

---

# 🧭 Rutas principales

## Página pública

```text
GET /
```

---

## Autenticación

```text
GET  /login
POST /login

GET  /register
POST /register

POST /logout
```

También existen las rutas de recuperación y cambio de contraseña proporcionadas por Breeze.

---

## Centro OmniMerge

```text
GET /hub
```

Nombre:

```text
hub
```

---

## Biblioteca

```text
GET /dashboard
```

Nombre:

```text
dashboard
```

---

## Tipos de entidad

```text
GET    /entity-types
GET    /entity-types/create
POST   /entity-types
GET    /entity-types/{entity_type}
GET    /entity-types/{entity_type}/edit
PUT    /entity-types/{entity_type}
DELETE /entity-types/{entity_type}
```

---

## Entidades

```text
GET    /entities
GET    /entities/create
POST   /entities
GET    /entities/{entity}
GET    /entities/{entity}/edit
PUT    /entities/{entity}
DELETE /entities/{entity}
```

---

## Atributos de entidad

```text
GET /entities/{entity}/attributes
PUT /entities/{entity}/attributes
```

---

## Atributos

```text
GET    /attributes
GET    /attributes/create
POST   /attributes
GET    /attributes/{attribute}
GET    /attributes/{attribute}/edit
PUT    /attributes/{attribute}
DELETE /attributes/{attribute}
```

---

## Opciones

```text
GET    /attribute-options
GET    /attribute-options/create
GET    /attribute-options/{attributeOption}
GET    /attribute-options/{attributeOption}/edit

POST   /attributes/{attribute}/options
PUT    /attributes/{attribute}/options/{option}

DELETE /attribute-options/{attributeOption}
```

---

## Grupos

```text
GET    /attribute-groups
GET    /attribute-groups/create
POST   /attribute-groups
GET    /attribute-groups/{attribute_group}
GET    /attribute-groups/{attribute_group}/edit
PUT    /attribute-groups/{attribute_group}
DELETE /attribute-groups/{attribute_group}
```

---

## Colecciones

```text
GET    /collections
GET    /collections/create
POST   /collections
GET    /collections/{collection}
GET    /collections/{collection}/edit
PUT    /collections/{collection}
DELETE /collections/{collection}
```

---

## Comunidad

```text
GET /explore

GET /explore/entities/{entity}
GET /explore/collections/{collection}
GET /explore/attributes/{attribute}

POST /explore/entities/{entity}/clone
POST /explore/collections/{collection}/clone
POST /explore/attributes/{attribute}/clone
```

---

## Perfil

```text
GET    /profile
PATCH  /profile
DELETE /profile
```

---

# 🔐 Autenticación

La autenticación está basada en Laravel Breeze.

## Registro

Solicita:

```text
Nombre
Username
Email
Contraseña
Confirmar contraseña
```

El flujo es:

```text
POST /register
↓
Validación
↓
Creación del usuario
↓
Asignación de rol USER
↓
Estado ACTIVE
↓
Inicio automático de sesión
↓
/hub
```

---

## Login

Solicita:

```text
Email
Contraseña
Recordarme
```

Incluye:

* Validación.
* Rate limiting.
* Comprobación de credenciales.
* Validación del estado del usuario.
* Actualización del último login.
* Regeneración de sesión.

Flujo:

```text
POST /login
↓
LoginRequest
↓
authenticate()
↓
session()->regenerate()
↓
/hub
```

---

# 🏠 Centro OmniMerge — Hub

El Hub es la principal modificación arquitectónica reciente.

Antes:

```text
Login
↓
Dashboard
↓
Todo el sistema
```

Ahora:

```text
Login
↓
Hub
↓
Seleccionar área de trabajo
```

El Hub puede mostrar:

```text
📚 Biblioteca
🌐 Comunidad
👤 Perfil
🌌 Universos
🏆 Torneos
📊 Rankings
```

Los módulos que todavía no existen aparecen como:

```text
Próximamente
```

y no deben tener rutas falsas.

---

# 📊 Estadísticas del Hub

Actualmente puede mostrar:

```text
Número de entidades
Número de atributos
Número de colecciones
Contenido público
```

También combina actividad reciente de:

```text
Entidades
Atributos
Colecciones
```

para producir una vista general de la cuenta.

---

# 📚 Biblioteca

La Biblioteca contiene todos los componentes destinados a construir los recursos de OmniMerge.

Su sidebar se limita a:

```text
← Centro OmniMerge

BIBLIOTECA

Dashboard

ENTIDADES
├── Tipos de entidad
└── Entidades

CARACTERÍSTICAS
├── Atributos
├── Valores y opciones
└── Grupos de atributos

ORGANIZACIÓN
└── Colecciones
```

La Comunidad, los Universos y los Torneos no forman parte de este sidebar.

---

# 🌐 Comunidad

La Comunidad es un módulo global.

El principio es:

> Los usuarios no modifican directamente el contenido de otras personas.

Cuando se permite reutilización, se genera una copia independiente.

---

# 📋 Clonación

## Entidad

Puede copiar:

```text
Datos
Tipo
Imagen
Atributos
Opciones
Valores
Metadatos
```

---

## Atributo

Puede copiar:

```text
Definición
Configuración
Imagen
Opciones
Jerarquías de opciones
```

---

## Colección

Puede copiar:

```text
Datos
Portada
Entidades públicas y clonables
Orden
```

---

## Procedencia

Se conserva mediante campos como:

```text
source_entity_id
source_attribute_id
source_collection_id
```

Esto permite saber de qué recurso provino una copia.

---

# 🔒 Visibilidad

Entidades y colecciones pueden manejar:

```text
PRIVATE
PUBLIC
UNLISTED
```

Los atributos utilizan un concepto equivalente mediante `scope`.

---

# 🌐 Publicación comunitaria

El contenido que aparece en Comunidad debe ser:

```text
PUBLIC
ACTIVE
publicado
```

y, para poder copiarlo:

```text
allow_cloning = true
```

---

# 🖼 Gestión de imágenes

Los archivos cargados se almacenan utilizando el disco público.

Estructura típica:

```text
storage/app/public/
│
├── entities/
├── attributes/
├── attribute-options/
└── collections/
```

Para exponerlos públicamente:

```bash
php artisan storage:link
```

Esto crea:

```text
public/storage
```

Los formularios con archivos deben utilizar:

```html
enctype="multipart/form-data"
```

---

# 🔐 Seguridad

OmniMerge sigue varios niveles de seguridad.

## Autenticación

Las rutas privadas utilizan:

```php
Route::middleware('auth')
```

---

## Policies

Controlan quién puede modificar recursos.

Regla típica:

```text
user_id del recurso
=
id del usuario autenticado
```

---

## Form Requests

Validan toda información antes de enviarla a la lógica de negocio.

---

## Propiedad de relaciones

Cuando un formulario permite elegir entidades, atributos u otros registros, los IDs deben pertenecer al mismo usuario.

---

## CSRF

Los formularios utilizan:

```blade
@csrf
```

---

## Mass Assignment

Los modelos utilizan `$fillable`.

No se recomienda:

```php
Model::create($request->all());
```

Se recomienda utilizar:

```php
Model::create($request->validated());
```

---

## Salida HTML

Blade debe utilizar normalmente:

```blade
{{ $value }}
```

para escapar contenido.

Evitar utilizar HTML sin escapar salvo que sea realmente necesario.

---

# 📌 Requisitos

Para ejecutar OmniMerge se necesita:

```text
PHP 8.2+
Composer
Node.js
NPM
MySQL
Git
```

Comprobar:

```bash
php -v
composer --version
node -v
npm -v
git --version
```

---

# 🚀 Instalación

## 1. Clonar el proyecto

```bash
git clone <URL_DEL_REPOSITORIO>
cd OmniMerge
```

---

## 2. Instalar dependencias PHP

```bash
composer install
```

---

## 3. Instalar dependencias frontend

```bash
npm install
```

En PowerShell, si existe un problema con `npm.ps1`:

```powershell
npm.cmd install
```

---

## 4. Crear `.env`

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

---

## 5. Generar clave

```bash
php artisan key:generate
```

---

## 6. Crear base de datos

Ejemplo:

```sql
CREATE DATABASE omnimerge
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

---

## 7. Configurar `.env`

Ejemplo:

```env
APP_NAME=OmniMerge
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=omnimerge
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

> Nunca publiques tu `.env` real en GitHub.

---

## 8. Ejecutar migraciones

```bash
php artisan migrate
```

Para reconstrucción completa únicamente durante desarrollo:

```bash
php artisan migrate:fresh
```

⚠️ `migrate:fresh` elimina los datos existentes.

---

## 9. Crear enlace de Storage

```bash
php artisan storage:link
```

---

## 10. Compilar frontend

```bash
npm run build
```

PowerShell:

```powershell
npm.cmd run build
```

---

# ▶️ Ejecución

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

PowerShell:

```powershell
npm.cmd run dev
```

La aplicación estará disponible normalmente en:

```text
127.0.0.1:8000
```

---

# ⚡ Comandos útiles

## Servidor

```bash
php artisan serve
```

## Frontend

```bash
npm run dev
npm run build
```

PowerShell:

```powershell
npm.cmd run dev
npm.cmd run build
```

---

## Migraciones

```bash
php artisan migrate
php artisan migrate:status
php artisan migrate:rollback
```

Desarrollo:

```bash
php artisan migrate:fresh
php artisan migrate:fresh --seed
```

---

## Rutas

```bash
php artisan route:list
```

Buscar Hub:

```bash
php artisan route:list --name=hub
```

Buscar Biblioteca:

```bash
php artisan route:list --name=dashboard
```

Buscar Comunidad:

```bash
php artisan route:list --name=community
```

---

## Caché

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

---

## Storage

```bash
php artisan storage:link
```

---

## Tests

```bash
php artisan test
```

Detener en el primer error:

```bash
php artisan test --stop-on-failure
```

---

## Tinker

```bash
php artisan tinker
```

---

## Crear modelo

```bash
php artisan make:model Nombre
```

Modelo + migración + factory:

```bash
php artisan make:model Nombre -mf
```

---

## Crear controlador

```bash
php artisan make:controller NombreController
```

Resource:

```bash
php artisan make:controller Carpeta/NombreController --resource --model=Nombre
```

Invokable:

```bash
php artisan make:controller Carpeta/NombreController --invokable
```

---

## Crear Request

```bash
php artisan make:request Carpeta/StoreNombreRequest
```

---

## Crear Policy

```bash
php artisan make:policy NombrePolicy --model=Nombre
```

---

## Crear migración

```bash
php artisan make:migration nombre_de_la_migracion
```

---

# 🧪 Pruebas

Las pruebas se encuentran en:

```text
tests/
├── Feature/
└── Unit/
```

Deben comprobar comportamientos como:

* Registro.
* Login.
* Redirección al Hub.
* Seguridad de recursos.
* Creación de entidades.
* Creación de atributos.
* Multiselección.
* Subida de imágenes.
* Colecciones.
* Comunidad.
* Clonación.
* Privacidad.

---

# 🔄 Cambio importante de tests por el Hub

Los tests de autenticación que anteriormente esperaban:

```text
/dashboard
```

deben esperar:

```text
/hub
```

después de:

* Login.
* Registro.

---

# 🌳 Git y flujo de trabajo

## Ver estado

```bash
git status
```

---

## Crear una rama

Ejemplo:

```bash
git checkout -b feat/nombre-funcionalidad
```

---

## Revisar cambios

```bash
git diff
```

---

## Preparar

```bash
git add .
```

---

## Revisar staged

```bash
git diff --cached
```

---

## Commit

```bash
git commit -m "feat: descripcion del cambio"
```

---

## Push

```bash
git push origin main
```

Para una rama nueva:

```bash
git push -u origin feat/nombre-funcionalidad
```

---

# 📝 Convención recomendada de commits

```text
feat: nueva funcionalidad
fix: corrección de error
docs: documentación
refactor: reorganización interna
test: pruebas
style: cambios visuales
chore: mantenimiento
security: seguridad
```

Ejemplos:

```text
feat(auth): personalizar login y registro

feat(hub): agregar centro general de OmniMerge

feat(community): implementar explorador comunitario

feat(attributes): implementar atributos dinamicos

fix(storage): corregir acceso a imagenes

docs: actualizar README del proyecto
```

---

# 📚 Documentación

Además de este README, se recomienda utilizar:

```text
docs/
│
├── arquitectura/
├── desarrollo/
├── modulos/
├── manuales/
└── pdf/
```

Ejemplo:

```text
docs/
│
├── arquitectura/
│   ├── ARQUITECTURA_GENERAL.md
│   ├── HUB_Y_NAVEGACION.md
│   └── BASE_DE_DATOS.md
│
├── desarrollo/
│   ├── MANUAL_DESARROLLO.md
│   ├── GUIA_MANTENIMIENTO.md
│   └── INSTALACION.md
│
├── modulos/
│   ├── BIBLIOTECA.md
│   ├── ENTIDADES.md
│   ├── ATRIBUTOS.md
│   ├── COLECCIONES.md
│   └── COMUNIDAD.md
│
├── manuales/
│   ├── GUIA_LARAVEL.md
│   └── GUIA_GIT_GITHUB.md
│
└── pdf/
    └── versiones finales de documentos
```

Los archivos Markdown son recomendables porque Git puede mostrar exactamente qué partes cambiaron.

Los PDF pueden conservarse como versiones finales o documentos de presentación.

---

# 📈 Estado del desarrollo

## ✅ Fase 0 — Configuración

* [x] Laravel 12.
* [x] PHP 8.2+.
* [x] MySQL.
* [x] Composer.
* [x] NPM.
* [x] Vite.
* [x] Git.
* [x] GitHub.

---

## ✅ Fase 1 — Autenticación

* [x] Laravel Breeze.
* [x] Registro.
* [x] Username.
* [x] Login.
* [x] Logout.
* [x] Estado del usuario.
* [x] Perfil básico.
* [x] Rediseño visual de login.
* [x] Rediseño visual de registro.
* [x] Redirección al Hub.

---

## ✅ Fase 2 — Interfaz general

* [x] Landing pública.
* [x] Identidad visual OmniMerge.
* [x] Logo personalizado.
* [x] Layout interno.
* [x] Sidebar.
* [x] Header.
* [x] Diseño responsive.

---

## ✅ Fase 3 — Centro OmniMerge

* [x] Hub general.
* [x] Layout exclusivo.
* [x] Estadísticas generales.
* [x] Actividad reciente.
* [x] Accesos rápidos.
* [x] Biblioteca desde Hub.
* [x] Comunidad desde Hub.
* [x] Perfil desde Hub.
* [x] Módulos futuros como placeholders.
* [x] Navegación de regreso al Hub.

---

## ✅ Fase 4 — Biblioteca básica

* [x] Dashboard.
* [x] Tipos de entidad.
* [x] Entidades.
* [x] Policies.
* [x] Imágenes.
* [x] Visibilidad.
* [x] Estados.

---

## ✅ Fase 5 — Atributos dinámicos

* [x] Atributos.
* [x] Diferentes tipos de datos.
* [x] Opciones seleccionables.
* [x] Multiselección.
* [x] Valores tipados.
* [x] Grupos de atributos.
* [x] Configuración de atributos en entidades.

---

## ✅ Fase 6 — Organización

* [x] Colecciones.
* [x] Imágenes de colección.
* [x] Relación colección-entidad.
* [x] Estados.
* [x] Visibilidad.

---

## 🚧 Fase 7 — Comunidad

* [x] Explorador.
* [x] Entidades públicas.
* [x] Colecciones públicas.
* [x] Atributos públicos.
* [x] Búsqueda.
* [x] Filtros.
* [x] Vistas.
* [x] Clonación.
* [x] Procedencia.
* [ ] Favoritos.
* [ ] Comentarios.
* [ ] Valoraciones.
* [ ] Seguimiento de usuarios.
* [ ] Moderación.

---

# 🗺 Roadmap

## 🔗 Relaciones y condiciones

Planeado:

```text
attribute_relationships
attribute_conditions
attribute_option_relationships
```

Objetivos:

* Dependencias entre atributos.
* Opciones dependientes.
* Mostrar campos condicionalmente.
* Ocultar campos.
* Hacer obligatorios ciertos atributos.
* AND / OR.
* Reglas por opciones.

---

# 🌌 Universos

El módulo estará separado de Biblioteca.

Conceptualmente:

```text
Universos
│
├── Dashboard
├── Mis universos
├── Crear universo
├── Entidades
├── Reglas
├── Temporadas
├── Eventos
└── Historial
```

Posibles tablas:

```text
universes
universe_entities
seasons
season_entities
```

---

# 🏆 Torneos

Conceptualmente:

```text
Torneos
│
├── Dashboard
├── Mis torneos
├── Crear torneo
├── Participantes
├── Rondas
├── Enfrentamientos
├── Resultados
└── Rankings
```

Posibles tablas:

```text
tournaments
tournament_participants
tournament_rounds
tournament_matches
match_results
```

---

# ⚡ Simulación

Planeado:

* Motor de reglas.
* Fórmulas.
* Pesos.
* Probabilidades.
* Ventajas.
* Desventajas.
* Aleatoriedad controlada.
* Eventos.
* Resultados persistentes.

---

# 📊 Rankings y analítica

Planeado:

* Victorias.
* Derrotas.
* Rendimiento.
* Historial.
* Evolución.
* Comparaciones.
* Estadísticas.
* Tendencias.
* Gráficos.

---

# 🌐 Comunidad avanzada

Planeado:

* Favoritos.
* Comentarios.
* Valoraciones.
* Perfiles públicos.
* Seguidores.
* Creadores.
* Reportes.
* Moderación.
* Notificaciones.

---

# 👤 Cuenta y configuración

Actualmente:

```text
/profile
```

Futuro:

```text
/profile

/settings
/settings/security
/settings/privacy
/settings/appearance
/settings/notifications
```

---

# 🐛 Solución de problemas

## `View [...] not found`

Verificar que exista la vista.

Ejemplo:

```text
resources/views/collections/index.blade.php
```

Después:

```bash
php artisan view:clear
php artisan optimize:clear
```

---

## `Route [...] not defined`

Ejecutar:

```bash
php artisan route:list
```

Revisar:

```text
routes/web.php
routes/auth.php
```

---

## `Undefined method authorize`

El controlador base debe utilizar:

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
```

---

## `Undefined method url`

Al utilizar Storage puede tiparse el adapter:

```php
/** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
$disk = Storage::disk('public');

return $disk->url($path);
```

---

## `Undefined method user`

Comprobar que se importe:

```php
use Illuminate\Http\Request;
```

y no el Request de Symfony.

Cuando sea necesario:

```php
/** @var User $user */
$user = $request->user();
```

---

## Imagen no visible

Ejecutar:

```bash
php artisan storage:link
```

Comprobar:

```text
storage/app/public/
public/storage/
```

y que el formulario use:

```html
enctype="multipart/form-data"
```

---

## `MassAssignmentException`

Revisar:

```php
protected $fillable = [
    ...
];
```

---

## `Unknown column`

Revisar:

```bash
php artisan migrate:status
php artisan migrate
```

---

## Los cambios Blade no aparecen

```bash
php artisan view:clear
php artisan optimize:clear
```

y mantener Vite ejecutándose:

```bash
npm run dev
```

---

## Cambios CSS no aparecen

```bash
npm run build
```

o:

```bash
npm run dev
```

---

## Error 403

Revisar:

```text
Policy
authorize()
user_id
estado del usuario
```

---

## Después del login sigo llegando a `/dashboard`

Revisar:

```text
AuthenticatedSessionController.php
```

La redirección debe apuntar a:

```text
hub
```

---

# ⚠️ Carpetas que no deben modificarse manualmente

Evitar modificaciones directas en:

```text
vendor/
node_modules/
bootstrap/cache/
storage/framework/
public/build/
```

Estas carpetas contienen dependencias o archivos generados.

---

# 🔒 Archivos privados

Nunca publicar:

```text
.env
```

Puede contener:

* Contraseñas.
* Credenciales.
* Tokens.
* Claves.
* Configuración privada.

---

# 📌 Principios del proyecto

## 1. OmniMerge es la plataforma

```text
OmniMerge
≠
Gestor de entidades
```

---

## 2. Biblioteca es un módulo

```text
OmniMerge
└── Biblioteca
```

La Biblioteca construye los recursos base.

---

## 3. Hub es la entrada general

```text
Login
↓
Hub
↓
Módulos
```

---

## 4. Los módulos deben mantenerse separados

```text
Hub
├── Biblioteca
├── Comunidad
├── Universos
├── Torneos
└── Cuenta
```

Evitar un único sidebar con absolutamente todas las funcionalidades.

---

## 5. Las entidades deben ser reutilizables

Una entidad creada una vez podrá utilizarse posteriormente en distintos contextos.

---

## 6. Las copias comunitarias deben ser independientes

Editar una copia nunca debe modificar el original.

---

## 7. La seguridad se aplica en backend

Ocultar un botón no sustituye:

```text
Policy
Request
Consulta segura
Middleware
```

---

## 8. La base de datos se modifica mediante migraciones

No realizar cambios estructurales manualmente en MySQL sin registrar una migración equivalente.

---

## 9. La lógica compleja debe salir de los controladores

Utilizar Services cuando una operación involucre muchas reglas o tablas.

---

## 10. Todo módulo importante debe poder probarse

Cada nueva funcionalidad debe considerar:

```text
Migración
Modelo
Request
Policy
Controller
Service
Route
View
Test
```

---

# 🧠 Idea central de OmniMerge

La arquitectura actual puede resumirse de esta manera:

```text
OMNIMERGE
=
Plataforma completa


HUB
=
Centro de navegación


BIBLIOTECA
=
Construcción de recursos


COMUNIDAD
=
Descubrimiento y reutilización


UNIVERSOS
=
Contextos donde existirán las entidades


TORNEOS
=
Contextos competitivos


SIMULACIÓN
=
Motor que determina acontecimientos


RANKINGS
=
Análisis de resultados
```

---

# 🚀 Estado actual resumido

OmniMerge ya dispone de una base funcional que permite:

```text
Usuario
↓
Registrarse
↓
Entrar al Hub
↓
Acceder a Biblioteca
↓
Crear tipos
↓
Crear entidades
↓
Crear atributos
↓
Crear opciones
↓
Asignar valores
↓
Crear colecciones
↓
Publicar contenido
↓
Explorar Comunidad
↓
Clonar contenido
```

La siguiente evolución del proyecto se concentrará en convertir esas piezas en elementos capaces de formar parte de **universos, torneos y simulaciones**.

---

# 📄 Licencia

Actualmente el proyecto se encuentra en desarrollo.

Antes de distribuirlo formalmente como proyecto de código abierto debe definirse y añadirse un archivo de licencia explícito al repositorio.

---

<div align="center">

# 🌌 OmniMerge

### Crea cualquier entidad. Define sus características. Organiza tus ideas. Conecta tus universos.

**Create · Connect · Evolve**

</div>
