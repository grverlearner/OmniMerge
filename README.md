# 🌌 OmniMerge

> Plataforma web flexible para crear, organizar, versionar, relacionar y reutilizar entidades dinámicas mediante atributos, catálogos, colecciones y contextos configurables.

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square\&logo=laravel\&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square\&logo=php\&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat-square\&logo=mysql\&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-UI-06B6D4?style=flat-square\&logo=tailwindcss\&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-Interaction-8BC0D0?style=flat-square\&logo=alpinedotjs\&logoColor=white)
![Status](https://img.shields.io/badge/Estado-En%20desarrollo-yellow?style=flat-square)

---

## 📖 ¿Qué es OmniMerge?

**OmniMerge** es una plataforma web diseñada para permitir que cada usuario construya y organice información completamente personalizada sin quedar limitado a un tipo específico de contenido.

En OmniMerge una entidad puede ser prácticamente cualquier cosa:

* un personaje;
* una persona;
* un animal;
* un país;
* una ciudad;
* un objeto;
* un vehículo;
* una organización;
* una criatura;
* un concepto;
* una obra;
* un elemento ficticio;
* o cualquier otro tipo de elemento definido por el usuario.

La plataforma no impone atributos rígidos.

Cada usuario puede crear sus propios:

* tipos de entidad;
* atributos;
* catálogos;
* opciones;
* jerarquías;
* dependencias;
* grupos;
* colecciones;
* versiones;
* imágenes;
* reglas contextuales.

El objetivo final de OmniMerge es evolucionar hacia una plataforma capaz de utilizar toda esta información para construir **universos, interacciones, torneos, simulaciones, escenarios e historiales reutilizables**.

---

# 🎯 Visión del proyecto

OmniMerge busca resolver una limitación común en muchas plataformas de creación de personajes, fichas o universos:

> Los sistemas tradicionales suelen decidir de antemano qué puede crear el usuario y qué atributos debe tener.

OmniMerge adopta el enfoque contrario.

```text
Usuario
   ↓
Define su estructura
   ↓
Crea sus atributos
   ↓
Crea sus catálogos
   ↓
Crea sus entidades
   ↓
Crea sus versiones
   ↓
Organiza información
   ↓
Relaciona contenido
   ↓
Construye universos
   ↓
Simula escenarios
```

La información es configurable y reutilizable.

---

# 🧠 Filosofía principal

La arquitectura de OmniMerge gira alrededor de algunas ideas fundamentales.

## 1. Las entidades son genéricas

No existe una tabla exclusiva para:

```text
Personajes
Animales
Países
Objetos
```

Todos pueden representarse mediante:

```text
Entity
```

y clasificarse con:

```text
EntityType
```

---

## 2. Los atributos no están codificados de forma rígida

Una entidad no tiene columnas como:

```text
edad
aldea
clan
nivel
elemento
raza
sexo
```

En su lugar, OmniMerge permite crear atributos dinámicamente.

Ejemplo:

```text
Entidad:
Naruto Uzumaki

Atributos:
├── Anime
├── Aldea
├── Clan
├── Naturaleza de Chakra
├── Rango Ninja
├── Dōjutsu
├── Afiliación
└── Bijū
```

Otro usuario podría crear:

```text
Entidad:
Toyota Supra

Atributos:
├── Marca
├── Modelo
├── Motor
├── Potencia
├── Tracción
└── Año
```

La arquitectura es la misma.

---

# ✨ Funcionalidades implementadas

Actualmente OmniMerge dispone de una base funcional considerable.

## 🔐 Autenticación

Sistema de autenticación basado en Laravel.

Incluye:

* registro;
* inicio de sesión;
* cierre de sesión;
* recuperación de contraseña;
* verificación de correo;
* perfil de usuario;
* protección de rutas;
* autorización mediante Policies.

---

# 🧩 Tipos de Entidad

Los usuarios pueden definir las categorías generales de sus entidades.

Ejemplos:

```text
Personaje
Animal
País
Objeto
Vehículo
Organización
Criatura
Concepto
```

Cada tipo permite organizar la biblioteca sin imponer una estructura fija de atributos.

---

# 👤 Entidades

Las entidades constituyen el núcleo principal de OmniMerge.

Una Entidad puede almacenar información como:

* propietario;
* tipo;
* código interno;
* nombre;
* slug;
* descripción;
* imagen;
* estado;
* visibilidad;
* configuración de clonación;
* métricas comunitarias;
* metadata;
* atributos;
* colecciones;
* versiones.

Ejemplo:

```text
Naruto Uzumaki
├── Tipo: Personaje
├── Código: ENT000001
├── Estado: ACTIVE
├── Visibilidad: PUBLIC
├── Imagen
├── Descripción
├── Características
├── Colecciones
└── Versiones
```

---

# 🗂️ Biblioteca de Entidades

La biblioteca permite administrar las entidades pertenecientes al usuario.

Dispone de diferentes formas de visualización:

* galería;
* cuadrícula;
* lista;
* tabla.

También incluye herramientas para:

* búsqueda;
* ordenamiento;
* filtros;
* estado;
* visibilidad;
* tipo;
* presencia de imagen;
* selección;
* navegación hacia edición o detalle.

La imagen utilizada puede resolverse mediante la **Base activa de la Entidad**, manteniendo el nombre y demás datos originales cuando corresponde.

---

# 🧰 Edición masiva

OmniMerge dispone de funcionalidades para trabajar con múltiples entidades.

La edición masiva permite seleccionar varias Entidades y ejecutar determinadas operaciones sobre el conjunto.

Entre las operaciones contempladas se encuentran:

* modificación de propiedades compartidas;
* cambios de estado;
* archivado;
* eliminación lógica;
* administración grupal.

Las acciones sensibles utilizan confirmaciones antes de ejecutarse.

---

# 🏷️ Sistema de Atributos dinámicos

Uno de los componentes más importantes de OmniMerge es su sistema de atributos.

El usuario puede crear atributos sin modificar la estructura de la base de datos.

Ejemplos:

```text
Anime
Clan
Aldea
Naturaleza
Sexo
Rango Ninja
Dōjutsu
Elemento
País
Región
Ciudad
Marca
Categoría
```

Los atributos pueden disponer de:

* nombre;
* código;
* descripción;
* tipo de dato;
* imagen;
* icono;
* ayuda;
* placeholder;
* orden;
* visibilidad;
* obligatoriedad;
* configuración;
* jerarquía;
* catálogo de opciones.

---

# 🔢 Tipos de datos

El sistema está preparado para manejar diferentes tipos de valores.

Entre ellos:

```text
TEXT
NUMBER
BOOLEAN
DATE
OPTION
```

Los atributos de tipo `OPTION` pueden trabajar mediante catálogos definidos por el usuario.

---

# 📚 Catálogos y opciones

Los catálogos permiten evitar texto libre cuando los valores pertenecen a un conjunto conocido.

Ejemplo:

```text
Atributo:
Anime

Catálogo:
├── Naruto
├── One Piece
├── Bleach
├── Dragon Ball
└── Attack on Titan
```

Otro ejemplo:

```text
Atributo:
Naturaleza de Chakra

Opciones:
├── Fuego
├── Agua
├── Viento
├── Tierra
└── Rayo
```

Cada opción puede disponer de:

* nombre;
* descripción;
* imagen;
* código;
* estado;
* orden;
* relaciones;
* jerarquía.

---

# 🌳 Jerarquía de opciones

Las opciones pertenecientes al mismo catálogo pueden relacionarse mediante una estructura padre-hijo.

Ejemplo:

```text
Perú
├── Tacna
│   ├── Tacna
│   ├── Pocollay
│   └── Gregorio Albarracín
│
├── Lima
│   ├── Miraflores
│   └── San Isidro
│
└── Arequipa
```

La relación se construye mediante:

```text
parent_option_id
```

Esto permite representar estructuras jerárquicas dentro de un mismo atributo.

---

# 🧠 Contextos y dependencias de Atributos

OmniMerge dispone de un sistema destinado a determinar cuándo determinados atributos son relevantes.

Ejemplo:

```text
Anime = Naruto
        ↓
Mostrar:
├── Aldea Ninja
├── Clan
├── Rango Ninja
└── Naturaleza de Chakra
```

Mientras que:

```text
Anime = One Piece
        ↓
Mostrar:
├── Haki
├── Tripulación
└── Fruta del Diablo
```

De esta manera un usuario puede mantener una biblioteca genérica sin llenar formularios con atributos irrelevantes.

---

## Acciones contextuales

Las reglas pueden utilizar conceptos como:

```text
SHOW
HIDE
REQUIRE
```

y modos lógicos:

```text
ALL
ANY
```

Ejemplo:

```text
Mostrar Rango Ninja si:

Anime = Naruto
AND
Tipo de Personaje = Ninja
```

---

## Operadores

Las condiciones contemplan operadores como:

```text
EQUALS
NOT_EQUALS
EXISTS
NOT_EXISTS
```

---

# 🔗 Relaciones entre opciones de diferentes catálogos

Además de las jerarquías internas, OmniMerge permite relacionar opciones pertenecientes a diferentes atributos.

Ejemplo:

```text
País = Perú
        ↓
Región permitida:
├── Tacna
├── Lima
└── Arequipa
```

Otro ejemplo:

```text
Anime = Naruto
        ↓
Aldea:
├── Konoha
├── Suna
├── Kiri
├── Kumo
└── Iwa
```

Estas dependencias permiten crear formularios mucho más inteligentes.

---

# 🗃️ Grupos de Atributos

Los atributos pueden organizarse visualmente mediante grupos.

Ejemplo:

```text
IDENTIDAD
├── Nombre
├── Sexo
└── Edad

ORIGEN
├── País
├── Aldea
└── Clan

HABILIDADES
├── Naturaleza
├── Dōjutsu
└── Especialidad
```

Los grupos son principalmente una herramienta de organización visual y no deben confundirse con las dependencias contextuales.

---

# 🗂️ Colecciones

Las Entidades pueden organizarse mediante Colecciones.

Ejemplos:

```text
Personajes de Naruto
Hokages
Akatsuki
Equipo 7
Personajes favoritos
Vehículos japoneses
Países de Sudamérica
```

Una Entidad puede formar parte de distintas Colecciones.

Las Colecciones pueden almacenar información como:

* propietario;
* nombre;
* descripción;
* imagen;
* visibilidad;
* orden;
* elementos asociados.

---

# 🔄 Sistema de Versiones

OmniMerge permite que una misma Entidad posea múltiples representaciones.

Esto evita crear una Entidad independiente cada vez que un elemento cambia de época, transformación, edad, vestimenta o estado.

Ejemplo:

```text
Naruto Uzumaki
│
├── Naruto Niño
├── Naruto Shippuden
├── Naruto Modo Sabio
├── Naruto Modo Kurama
├── Naruto Adulto
└── Naruto Hokage
```

Todas continúan perteneciendo a:

```text
Naruto Uzumaki
```

---

# 🧬 Version

`Version` representa la definición reutilizable del contexto.

Ejemplos:

```text
Shippuden
Boruto
Niño
Adulto
Modo Sabio
Modo Kurama
Hokage
```

---

# 🧍 EntityVersion

`EntityVersion` representa la aplicación de una Version concreta a una Entidad.

Ejemplo:

```text
Version:
Shippuden

EntityVersion:
Naruto Uzumaki — Shippuden
```

---

# 🧭 Tipos de Version

Las Versiones pueden clasificarse según su naturaleza.

Entre los tipos contemplados se encuentran:

```text
ERA
AGE
FORM
TRANSFORMATION
OUTFIT
TIMELINE
OTHER
```

Esto permite modelar diferentes clases de cambios.

---

# 🌲 Jerarquía de EntityVersions

Una EntityVersion puede depender de otra.

Ejemplo:

```text
Naruto
│
└── Shippuden
    │
    ├── Modo Sabio
    │
    └── Modo Kurama
```

Esto permite construir cadenas de herencia.

---

# 🧠 Atributos efectivos

Una Version puede heredar información y posteriormente sobrescribir atributos concretos.

Conceptualmente:

```text
Entidad original
       ↓
Version padre
       ↓
Version hija
       ↓
Resultado efectivo
```

Ejemplo:

```text
Naruto Original
├── Anime = Naruto
├── Aldea = Konoha
└── Clan = Uzumaki

Naruto Shippuden
├── Edad = 16
└── Rango = Genin

Modo Sabio
└── Modo = Sennin
```

El resultado efectivo puede combinar los valores según la cadena de Versiones.

---

# ⚡ Version Resolver

OmniMerge dispone de un servicio encargado de resolver qué Version utilizar y cuáles son sus atributos efectivos.

El Resolver contempla:

* Version explícita;
* vínculos con catálogos;
* resolución automática;
* fallback;
* `EntityVersion` predeterminada;
* cadena de padres;
* herencia de atributos;
* sobrescrituras.

---

# ⚡ Default del Resolver

Una `EntityVersion` puede marcarse mediante:

```text
is_default = true
```

Este concepto significa:

> Version utilizada como fallback del sistema de resolución cuando no existe otro contexto más específico.

No debe confundirse con la Base activa.

Visualmente puede identificarse como:

```text
⚡ RESOLVER
```

---

# ⭐ Base activa

OmniMerge permite seleccionar una `EntityVersion` como **Base activa** de una Entidad.

Ejemplo:

```text
Entidad original:
Naruto Uzumaki

Versiones:
├── Naruto Niño
├── Naruto Shippuden      ★ BASE ACTIVA
├── Naruto Boruto
└── Naruto Baryon
```

La Entidad original continúa existiendo y nunca se reemplaza físicamente.

La Base activa funciona como la representación principal utilizada en determinadas vistas de trabajo.

---

## Base original

Si no existe ninguna Base activa personalizada:

```text
Entidad original
        ↓
Base utilizada
```

Si existe:

```text
Entidad original
        ↓
se conserva

EntityVersion seleccionada
        ↓
★ Base activa
```

El usuario puede restaurar la Base original cuando lo necesite.

---

# 🖼️ Imagen según Base activa

En determinadas vistas de OmniMerge, como la Biblioteca y Comunidad, puede utilizarse la imagen procedente de la Base activa.

Prioridad:

```text
¿Existe Base activa?
        │
        ├── Sí
        │    ↓
        │  ¿Tiene imagen?
        │    ├── Sí → usar imagen de la Version
        │    └── No → usar imagen original
        │
        └── No
             ↓
          imagen original
```

Este comportamiento puede cambiar únicamente la imagen sin reemplazar obligatoriamente:

* nombre;
* descripción;
* código;
* metadata;
* demás información de la Entidad.

---

# ◎ Presentación pública

OmniMerge también dispone de un concepto separado para decidir cómo debe presentarse una Entidad públicamente.

Esto permite diferenciar:

```text
Entidad original
Base activa
Default del Resolver
Presentación pública
```

Son conceptos independientes.

---

## Modos de Presentación

La presentación puede trabajar conceptualmente con modos como:

```text
BASE
VERSION_PRIMARY
VERSION_MEDIA
```

Permitiendo escoger:

* Entidad original;
* imagen principal de una Version;
* elemento multimedia específico.

---

# 🧩 Diferencia entre los cuatro conceptos

Esta separación es importante dentro de OmniMerge.

```text
ENTITY ORIGINAL
│
│ Datos canónicos.
│ Nunca desaparece al seleccionar otra representación.
│
├── ★ BASE ACTIVA
│      Representación principal para determinadas
│      vistas de trabajo.
│
├── ⚡ DEFAULT RESOLVER
│      Fallback técnico utilizado durante
│      resolución automática.
│
└── ◎ PRESENTACIÓN PÚBLICA
       Representación destinada a Comunidad.
```

Una misma EntityVersion puede cumplir varios roles.

Ejemplo:

```text
Naruto Shippuden
★ BASE
◎ PÚBLICA

Naruto Boruto
⚡ RESOLVER
```

o:

```text
Naruto Shippuden
★ BASE
⚡ RESOLVER
◎ PÚBLICA
```

---

# 🖼️ Multimedia de Versiones

Las `EntityVersion` disponen de soporte para contenido multimedia.

Esto permite tener:

```text
Naruto Modo Sabio
│
├── Imagen principal
│
└── Galería
    ├── Imagen 1
    ├── Imagen 2
    └── Imagen 3
```

La galería puede utilizarse posteriormente para elegir imágenes específicas en la presentación pública.

---

# 🌐 Comunidad

OmniMerge posee un módulo de Comunidad donde los usuarios pueden descubrir contenido público creado por otros usuarios.

Permite explorar distintos tipos de recursos, incluyendo:

* Entidades;
* Colecciones;
* Atributos;
* Catálogos;
* creadores.

---

# 🔎 Explorador

El módulo de Comunidad dispone de herramientas para:

* búsqueda;
* filtrado;
* tarjetas;
* detalle de contenido;
* navegación por creadores;
* consulta de Colecciones;
* descubrimiento de Entidades.

---

# 📥 Clonación

Una de las ideas centrales de OmniMerge es la reutilización.

Un usuario puede encontrar contenido público creado por otra persona y crear una copia independiente dentro de su propia Biblioteca.

Conceptualmente:

```text
Usuario A
   ↓
crea una Entidad pública
   ↓
Comunidad
   ↓
Usuario B
   ↓
Clonar
   ↓
Copia privada e independiente
```

La creación original no debe modificarse.

---

# 👁️ Visibilidad

Los recursos pueden disponer de diferentes niveles de visibilidad.

Ejemplo:

```text
PRIVATE
PUBLIC
```

Esto permite separar:

```text
Biblioteca personal
        ↓
Contenido privado

Comunidad
        ↓
Contenido público
```

---

# 🗑️ Eliminación lógica

Diversos recursos del sistema utilizan `SoftDeletes`.

Esto significa que una eliminación puede marcar un registro mediante:

```text
deleted_at
```

sin eliminarlo inmediatamente de forma física.

Este enfoque facilita:

* recuperación futura;
* auditoría;
* conservación de relaciones;
* mayor seguridad sobre los datos.

---

# 🎨 Interfaz

OmniMerge utiliza una interfaz personalizada construida principalmente con:

* Blade;
* Tailwind CSS;
* Alpine.js.

La interfaz incorpora:

* tarjetas;
* modales;
* buscadores;
* selectores;
* vistas múltiples;
* previews de imágenes;
* galerías;
* formularios dinámicos;
* paneles;
* badges;
* navegación contextual;
* drag/drop en determinadas cargas;
* componentes reutilizables.

---

# ✅ Sistema de confirmaciones OmniMerge

Las operaciones delicadas están evolucionando desde los diálogos nativos del navegador:

```javascript
confirm(...)
```

hacia un componente visual global propio.

Ejemplo:

```text
┌──────────────────────────────────┐
│ ★  Cambiar Base activa           │
│                                  │
│ Naruto Shippuden                 │
│                                  │
│ Esta Version pasará a utilizarse │
│ como Base principal.             │
│                                  │
│ [Cancelar]      [Usar como Base] │
└──────────────────────────────────┘
```

El sistema permite distintas variantes:

```text
danger
warning
primary
violet
success
```

y puede mostrar:

* icono;
* título;
* mensaje;
* detalle;
* imagen;
* recurso afectado;
* botón de acción;
* estado `Procesando...`.

---

# 🖼️ Componentes de carga de imágenes

OmniMerge dispone de componentes reutilizables para mejorar la experiencia de carga de imágenes.

Entre sus características se encuentran:

* selección mediante clic;
* drag & drop;
* preview antes de guardar;
* validación visual;
* eliminación;
* restauración;
* visualización de nombre y tamaño;
* múltiples imágenes para galerías.

---

# 🏗️ Arquitectura general

OmniMerge está desarrollado utilizando una arquitectura Laravel tradicional organizada en distintas capas.

```text
Request
   ↓
Route
   ↓
Controller
   ↓
Form Request / Policy
   ↓
Service
   ↓
Model
   ↓
Database
   ↓
Blade View
```

En funcionalidades más complejas se utilizan Services para evitar colocar toda la lógica dentro de los Controllers.

Ejemplos:

```text
EntityBuilderService
VersionResolverService
EntityVersionService
EntityPresentationService
EntityBaseVersionService
AttributeContextService
```

---

# 🛠️ Tecnologías

## Backend

* PHP 8.2+
* Laravel 12
* Eloquent ORM
* Laravel Policies
* Form Requests
* Services
* Blade

## Base de datos

* MySQL

## Frontend

* Blade
* Tailwind CSS
* Alpine.js
* JavaScript
* Vite

## Desarrollo

* Composer
* Node.js
* npm
* Git
* GitHub

---

# 🗄️ Modelo conceptual de datos

La plataforma contiene diferentes grupos de tablas.

## Usuarios

```text
users
```

---

## Entidades

```text
entity_types
entities
```

---

## Atributos

```text
attributes
attribute_options
entity_attributes
entity_attribute_values
attribute_groups
attribute_group_attribute
```

---

## Contextos

```text
attribute_relationships
attribute_context_rules
attribute_context_rule_conditions
attribute_option_relationships
```

---

## Colecciones

```text
collections
collection_entity
```

---

## Versiones

```text
versions
entity_versions
version_catalog_links
entity_version_attributes
entity_version_attribute_values
entity_version_images
```

---

## Representación de Entidades

```text
entity_presentations
entity_base_versions
```

---

# 🔗 Relaciones principales

De forma simplificada:

```text
User
│
├── EntityType
│
├── Entity
│   │
│   ├── EntityAttribute
│   │   └── EntityAttributeValue
│   │
│   ├── Collection
│   │
│   ├── EntityVersion
│   │   ├── EntityVersionAttribute
│   │   ├── EntityVersionAttributeValue
│   │   └── EntityVersionImage
│   │
│   ├── EntityBaseVersion
│   │
│   └── EntityPresentation
│
├── Attribute
│   ├── AttributeOption
│   ├── AttributeGroup
│   └── Context Rules
│
└── Version
```

---

# 🧭 Flujo recomendado de creación

Una manera natural de trabajar con OmniMerge es la siguiente.

## Paso 1 — Crear Tipo de Entidad

```text
Personaje
```

## Paso 2 — Crear Atributos

```text
Anime
Clan
Aldea
Naturaleza
Rango Ninja
```

## Paso 3 — Crear Catálogos

```text
Anime
├── Naruto
├── One Piece
└── Bleach
```

## Paso 4 — Crear dependencias

```text
Anime = Naruto
        ↓
Mostrar Clan
Mostrar Aldea
Mostrar Rango Ninja
```

## Paso 5 — Crear Entidad

```text
Naruto Uzumaki
```

## Paso 6 — Asignar características

```text
Anime = Naruto
Clan = Uzumaki
Aldea = Konoha
Naturaleza = Viento
```

## Paso 7 — Crear Versiones

```text
Naruto Niño
Naruto Shippuden
Naruto Modo Sabio
Naruto Hokage
```

## Paso 8 — Configurar Base

```text
Naruto Shippuden
★ BASE ACTIVA
```

## Paso 9 — Crear Colecciones

```text
Equipo 7
Hokages
Personajes de Naruto
```

## Paso 10 — Publicar

```text
PUBLIC
```

permitiendo que aparezca en Comunidad.

---

# 🌀 Ejemplo completo: Universo Naruto

OmniMerge puede utilizarse para representar información de una franquicia completa.

```text
Naruto
│
├── Atributos
│   ├── Anime
│   ├── Clan
│   ├── Aldea
│   ├── Naturaleza
│   ├── Rango Ninja
│   ├── Dōjutsu
│   ├── Afiliación
│   ├── Jinchūriki
│   └── Bijū
│
├── Entidades
│   ├── Naruto Uzumaki
│   ├── Sasuke Uchiha
│   ├── Sakura Haruno
│   └── Kakashi Hatake
│
├── Versiones
│   └── Naruto Uzumaki
│       ├── Niño
│       ├── Shippuden
│       ├── Modo Sabio
│       ├── Modo Kurama
│       ├── Adulto
│       └── Hokage
│
└── Colecciones
    ├── Equipo 7
    ├── Hokages
    ├── Akatsuki
    └── Jinchūrikis
```

El mismo motor puede utilizarse para cualquier otro dominio.

---

# 📂 Estructura principal del proyecto

```text
OmniMerge/
│
├── app/
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   │
│   ├── Models/
│   ├── Policies/
│   ├── Providers/
│   │
│   └── Services/
│       ├── Attributes/
│       ├── Entities/
│       └── Versions/
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
├── public/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── attributes/
│       ├── attribute-groups/
│       ├── attribute-options/
│       ├── collections/
│       ├── community/
│       ├── components/
│       ├── entities/
│       ├── entity-types/
│       ├── entity-versions/
│       ├── layouts/
│       └── versions/
│
├── routes/
│   └── web.php
│
├── storage/
│
├── tests/
│
├── artisan
├── composer.json
├── package.json
├── vite.config.js
└── README.md
```

---

# ⚙️ Requisitos

Antes de instalar OmniMerge se recomienda disponer de:

```text
PHP 8.2+
Composer
MySQL
Node.js
npm
Git
```

---

# 🚀 Instalación

## 1. Clonar repositorio

```bash
git clone https://github.com/grverlearner/OmniMerge.git
```

Entrar al proyecto:

```bash
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

En PowerShell, si existe una restricción de ejecución sobre `npm.ps1`, puede utilizarse:

```powershell
npm.cmd install
```

---

## 4. Crear `.env`

En Windows:

```powershell
Copy-Item .env.example .env
```

o manualmente copiar:

```text
.env.example
```

como:

```text
.env
```

---

## 5. Generar APP_KEY

```bash
php artisan key:generate
```

---

# 🗄️ Configurar MySQL

Ejemplo de configuración en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=omnimerge
DB_USERNAME=root
DB_PASSWORD=
```

Crear previamente la base de datos:

```sql
CREATE DATABASE omnimerge
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

---

# 📦 Ejecutar migraciones

```bash
php artisan migrate
```

Para consultar el estado:

```bash
php artisan migrate:status
```

> ⚠️ Durante el desarrollo normal se recomienda utilizar `php artisan migrate`.
>
> `php artisan migrate:fresh` elimina todas las tablas y todos los datos existentes, por lo que no debe utilizarse sobre una base con información importante.

---

# 🖼️ Storage público

Para permitir acceso a imágenes almacenadas mediante Laravel:

```bash
php artisan storage:link
```

---

# 🎨 Compilar frontend

Producción:

```bash
npm run build
```

En PowerShell también puede utilizarse:

```powershell
npm.cmd run build
```

Desarrollo:

```bash
npm run dev
```

o:

```powershell
npm.cmd run dev
```

---

# ▶️ Ejecutar aplicación

```bash
php artisan serve
```

La aplicación estará disponible normalmente en:

```text
http://127.0.0.1:8000
```

---

# 🧹 Limpiar cachés

Durante el desarrollo pueden utilizarse:

```bash
php artisan optimize:clear
```

o individualmente:

```bash
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

---

# 🧪 Pruebas

Ejecutar:

```bash
php artisan test
```

El conjunto de pruebas todavía requiere ampliación y mantenimiento conforme crecen los módulos del proyecto.

Entre las áreas que deberían disponer progresivamente de mayor cobertura se encuentran:

* atributos dinámicos;
* contextos;
* clonación;
* EntityVersions;
* herencia;
* Base activa;
* Resolver;
* Presentación pública;
* permisos;
* operaciones masivas.

---

# 🔒 Seguridad y propiedad

Los recursos pertenecen a usuarios concretos.

Las operaciones sensibles deben validar:

```text
Usuario autenticado
        ↓
¿Es propietario?
        ↓
Sí → continuar
No → rechazar
```

Se utilizan mecanismos como:

* middleware `auth`;
* Policies;
* Form Requests;
* scopes por usuario;
* validación dentro de Services;
* restricciones mediante claves foráneas.

---

# 📐 Principios de arquitectura

## La Entity siempre es la identidad canónica

Una Version no debe convertirse físicamente en una nueva Entity cuando únicamente representa otro estado del mismo elemento.

```text
Naruto
├── Niño
├── Shippuden
├── Hokage
└── Baryon
```

continúa siendo:

```text
1 Entity
+
N EntityVersions
```

---

## Las configuraciones deben ser independientes

No mezclar:

```text
Base activa
Default Resolver
Presentación pública
Herencia
```

Cada concepto cumple una responsabilidad distinta.

---

## Los contextos no deben codificarse por dominio

OmniMerge no debe contener reglas específicas como:

```php
if ($anime === 'Naruto') {
    mostrarAldeaNinja();
}
```

Las relaciones deben almacenarse como datos configurables.

Así el mismo sistema puede funcionar para:

```text
Anime
Geografía
Vehículos
Animales
Juegos
Empresas
Historia
Universos ficticios
```

---

## Evitar consultas N+1

Cuando una vista muestra muchas Entidades con información relacionada, se utiliza `eager loading`.

Ejemplo conceptual:

```php
Entity::with([
    'entityType',
    'baseVersionSetting.entityVersion',
]);
```

en vez de consultar la Base de cada Entidad individualmente.

---

# 📊 Estado general del proyecto

| Módulo                         | Estado                            |
| ------------------------------ | --------------------------------- |
| Autenticación                  | ✅ Implementado                    |
| Perfil                         | ✅ Implementado                    |
| Tipos de Entidad               | ✅ Implementado                    |
| Entidades                      | ✅ Implementado                    |
| Biblioteca de Entidades        | ✅ Implementado                    |
| Edición masiva                 | ✅ Implementado                    |
| Atributos dinámicos            | ✅ Implementado                    |
| Catálogos                      | ✅ Implementado                    |
| Jerarquía de opciones          | ✅ Implementado                    |
| Grupos de Atributos            | ✅ Implementado                    |
| Colecciones                    | ✅ Implementado                    |
| Comunidad                      | ✅ Implementado                    |
| Clonación                      | ✅ Implementado                    |
| Versiones                      | ✅ Implementado                    |
| EntityVersions                 | ✅ Implementado                    |
| Herencia de Versiones          | ✅ Implementado                    |
| Atributos por Version          | ✅ Implementado                    |
| Resolver de Versiones          | ✅ Implementado                    |
| Multimedia de Versiones        | ✅ Implementado                    |
| Presentación pública           | 🟡 Implementado / en refinamiento |
| Base activa                    | 🟡 Implementación reciente        |
| Contextos de Atributos         | 🟡 Implementado / en refinamiento |
| Relaciones entre Catálogos     | 🟡 Implementado / en refinamiento |
| Componentes globales de imagen | 🟡 En integración                 |
| OmniConfirm                    | 🟡 En integración                 |
| Tests automatizados            | 🟡 Cobertura parcial              |
| Universos                      | ⏳ Pendiente                       |
| Torneos                        | ⏳ Pendiente                       |
| Simulaciones                   | ⏳ Pendiente                       |
| Motor de interacciones         | ⏳ Pendiente                       |
| Estadísticas avanzadas         | ⏳ Pendiente                       |
| Historial de simulaciones      | ⏳ Pendiente                       |

---

# 🚧 Trabajo pendiente

Aunque OmniMerge ya posee buena parte de su infraestructura de datos, todavía existen módulos importantes por desarrollar.

## 🌌 Universos

Los Universos serán espacios independientes donde podrán agruparse:

* Entidades;
* Colecciones;
* reglas;
* configuraciones;
* relaciones;
* escenarios.

Ejemplo:

```text
Universo Naruto
├── Personajes
├── Aldeas
├── Equipos
├── Akatsuki
└── Reglas
```

---

# 🏆 Torneos

El sistema de Torneos permitirá seleccionar Entidades utilizando filtros y características.

Ejemplo:

```text
Participantes:

Anime = Naruto
AND
Aldea = Konoha
AND
Rango = Jōnin
```

Posteriormente podrían organizarse en estructuras como:

```text
Octavos
↓
Cuartos
↓
Semifinal
↓
Final
```

---

# 🎲 Simulaciones

Las simulaciones constituyen uno de los objetivos principales a largo plazo.

La idea es que OmniMerge pueda utilizar:

* Entidades;
* Versiones;
* atributos;
* reglas;
* relaciones;
* contexto;
* historial;

para generar resultados derivados de interacciones.

---

# 📜 Historial

Las simulaciones futuras deberían poder conservar:

```text
Universo
Participantes
Version utilizada
Contexto
Resultado
Fecha
Eventos
Cambios
```

para permitir revisar escenarios anteriores.

---

# 📈 Estadísticas

Posteriormente podrán generarse estadísticas como:

* participaciones;
* victorias;
* derrotas;
* resultados;
* popularidad;
* frecuencia de uso;
* versiones más utilizadas;
* Entidades más clonadas;
* Entidades más vistas;
* rendimiento dentro de Torneos.

---

# 🗺️ Roadmap propuesto

## Fase actual — Consolidación del núcleo

```text
✓ Entidades
✓ Atributos
✓ Catálogos
✓ Colecciones
✓ Comunidad
✓ Versiones
✓ Multimedia
✓ Resolver

→ Refinar Base activa
→ Refinar Presentación pública
→ Refinar Contextos
→ Uniformizar interfaz
→ Ampliar tests
```

---

## Próxima fase — Relaciones y Universos

```text
Universos
↓
Entidades dentro de Universos
↓
Reglas por Universo
↓
Relaciones entre Entidades
↓
Configuraciones reutilizables
```

---

## Fase posterior — Torneos

```text
Filtros
↓
Participantes
↓
Llaves
↓
Rondas
↓
Resultados
↓
Historial
```

---

## Fase avanzada — Simulaciones

```text
Universo
+
Entidades
+
Versiones
+
Atributos
+
Contexto
+
Reglas

        ↓

Motor de simulación

        ↓

Eventos
Resultados
Historial
Estadísticas
```

---

# 🔧 Deuda técnica y mejoras recomendadas

Entre las áreas que deben seguir mejorándose se encuentran:

### Testing

Aumentar cobertura en:

```text
Feature Tests
Unit Tests
Services
Policies
Context Resolver
Version Resolver
Cloning
```

### Controllers grandes

Algunos Controllers han crecido considerablemente.

Se recomienda continuar trasladando lógica hacia:

```text
Services
Queries
Actions
DTOs
```

cuando sea necesario.

### JavaScript

Continuar centralizando comportamientos globales como:

* confirmaciones;
* previews;
* formularios interactivos;
* selección masiva;
* notificaciones.

### Toasts

Implementar un sistema global para mensajes:

```text
success
error
warning
info
```

en lugar de depender únicamente de alertas estáticas.

### Documentación técnica

Mantener sincronizados:

```text
README.md
docs/
migraciones
modelo de datos
roadmap
```

cada vez que se realicen cambios arquitectónicos importantes.

---

# 📝 Convenciones conceptuales

Dentro del proyecto se utilizan los siguientes términos:

### Entity

Identidad principal y canónica de un elemento.

### EntityType

Clasificación general de una Entity.

### Attribute

Propiedad dinámica definida por un usuario.

### AttributeOption

Valor perteneciente al catálogo de un Attribute.

### AttributeGroup

Agrupación visual de Attributes.

### EntityAttribute

Asignación de un Attribute a una Entity.

### EntityAttributeValue

Valor concreto del Attribute asignado.

### Collection

Agrupación reutilizable de Entities.

### Version

Definición reutilizable de una etapa, forma, época o transformación.

### EntityVersion

Aplicación concreta de una Version sobre una Entity.

### Base original

Datos almacenados directamente en `Entity`.

### Base activa

`EntityVersion` seleccionada como representación principal de trabajo.

### Default Resolver

`EntityVersion` utilizada como fallback técnico durante resolución automática.

### Presentación pública

Configuración utilizada para controlar cómo se muestra la Entity hacia Comunidad.

### Context Rule

Regla que determina la aplicabilidad de Attributes dependiendo de otros valores.

---

# 🔍 Ejemplo conceptual completo

```text
USER
│
└── Biblioteca
    │
    ├── Tipo
    │   └── Personaje
    │
    ├── Atributos
    │   ├── Anime
    │   ├── Aldea
    │   └── Clan
    │
    ├── Catálogos
    │   ├── Anime
    │   │   ├── Naruto
    │   │   └── One Piece
    │   │
    │   └── Aldea
    │       ├── Konoha
    │       └── Suna
    │
    ├── Contextos
    │   └── Anime = Naruto
    │       └── Mostrar Aldea
    │
    └── Entidad
        └── Naruto Uzumaki
            │
            ├── Anime = Naruto
            ├── Aldea = Konoha
            │
            ├── Versiones
            │   ├── Niño
            │   ├── Shippuden ★
            │   └── Hokage
            │
            ├── Colecciones
            │   ├── Equipo 7
            │   └── Konoha
            │
            └── Comunidad
                └── PUBLIC
```

---

# 💡 Objetivo a largo plazo

El objetivo de OmniMerge no es solamente almacenar fichas.

La plataforma pretende evolucionar desde:

```text
Crear información
```

hacia:

```text
Crear
↓
Organizar
↓
Relacionar
↓
Versionar
↓
Compartir
↓
Reutilizar
↓
Construir Universos
↓
Simular
↓
Registrar resultados
```

La misma infraestructura debe ser suficientemente flexible para trabajar con distintos dominios sin rediseñar la aplicación para cada uno.

---

# 🔗 Repositorio

```text
https://github.com/grverlearner/OmniMerge
```

---

# 📌 Estado del desarrollo

**OmniMerge se encuentra actualmente en desarrollo activo.**

El núcleo relacionado con:

```text
Entidades
Atributos
Catálogos
Colecciones
Versiones
Comunidad
```

ya dispone de una implementación funcional considerable.

La prioridad inmediata consiste en consolidar y refinar estas bases antes de comenzar los grandes módulos futuros:

```text
Universos
Torneos
Simulaciones
```

---

<div align="center">

## 🌌 OmniMerge

**Create anything. Connect everything. Build your universe.**

</div>
