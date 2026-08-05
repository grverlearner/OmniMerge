<div align="center">

# 🌌 OmniMerge

### Plataforma web para crear, organizar, compartir y reutilizar entidades, atributos, colecciones, universos y torneos

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge\&logo=laravel\&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge\&logo=php\&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8%2B-4479A1?style=for-the-badge\&logo=mysql\&logoColor=white)](https://www.mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=for-the-badge\&logo=tailwindcss\&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge\&logo=alpinedotjs\&logoColor=white)](https://alpinejs.dev)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

**OmniMerge permite modelar cualquier tipo de elemento mediante entidades, atributos dinámicos, valores seleccionables, imágenes, colecciones y contenido comunitario reutilizable.**

[Características](#-características-principales) ·
[Instalación](#-instalación) ·
[Arquitectura](#-arquitectura-del-sistema) ·
[Base de datos](#-modelo-de-datos) ·
[Rutas](#-rutas-principales) ·
[Roadmap](#-roadmap)

</div>

---

## 📖 Descripción

OmniMerge es una plataforma web multiusuario desarrollada con Laravel que permite crear, organizar y reutilizar entidades personalizadas.

Una entidad puede representar cualquier elemento, por ejemplo:

* Un personaje.
* Un animal.
* Un país.
* Un objeto.
* Una criatura.
* Un vehículo.
* Un planeta.
* Una organización.
* Una habilidad.
* Un color.
* Un concepto abstracto.
* Cualquier otro elemento definido por el usuario.

El sistema no obliga a trabajar con una estructura rígida. Cada usuario puede crear sus propios tipos de entidad, atributos, valores seleccionables, grupos de atributos y colecciones.

Por ejemplo, una entidad llamada **Naruto Uzumaki** puede configurarse así:

```text
Tipo de entidad: Personaje

Atributos:
├── Anime: Naruto
├── Elementos:
│   ├── Viento
│   └── Fuego
├── Poder: 92 puntos
├── Puede volar: No
└── Historia: Ninja de la Aldea Oculta de la Hoja
```

OmniMerge está diseñado para evolucionar posteriormente hacia:

* Universos independientes.
* Temporadas.
* Torneos.
* Simulaciones automáticas.
* Rankings.
* Historial de eventos.
* Narrativa emergente.
* Comparación de entidades.
* Motor de reglas.

---

## 🎯 Problema que busca resolver

Muchas herramientas de creación de personajes o simulación tienen formularios predefinidos y se encuentran limitadas a una temática concreta.

Entre sus principales limitaciones se encuentran:

* Solo permiten personajes humanoides.
* Poseen características fijas.
* No permiten crear atributos personalizados.
* No permiten valores múltiples.
* No permiten catálogos visuales.
* No permiten reutilizar entidades en diferentes contextos.
* No ofrecen organización mediante colecciones.
* No permiten compartir y clonar contenido.
* No están preparadas para simulaciones abiertas.

OmniMerge propone una solución flexible donde los usuarios pueden construir su propia estructura de información.

---

## 💡 Conceptos principales

### Tipo de entidad

Clasifica una entidad.

Ejemplos:

```text
Personaje
Animal
País
Planeta
Objeto
Criatura
Organización
Concepto
```

### Entidad

Es el elemento concreto creado por el usuario.

Ejemplos:

```text
Naruto Uzumaki
Perú
Dragón de fuego
Espada legendaria
Planeta Tierra
```

### Atributo

Representa una característica reutilizable.

Ejemplos:

```text
Anime
Elemento
Poder
Edad
País de origen
Puede volar
Color principal
Historia
```

### Opción de atributo

Es un valor seleccionable perteneciente a un atributo de catálogo.

```text
Atributo: Anime

Opciones:
├── Naruto
├── One Piece
├── Dragon Ball
└── Bleach
```

### Grupo de atributos

Organiza visualmente atributos relacionados.

```text
Información general
Apariencia
Personalidad
Combate
Poderes
Historia
```

### Colección

Agrupa varias entidades.

```text
Colección: Protagonistas de anime

Entidades:
├── Naruto Uzumaki
├── Monkey D. Luffy
└── Son Goku
```

### Comunidad

Permite explorar contenido público creado por otros usuarios y guardar una copia independiente en la biblioteca personal.

---

## ✨ Características principales

### 🔐 Autenticación y usuarios

* Registro de usuarios.
* Inicio y cierre de sesión.
* Recuperación de contraseña.
* Perfil de usuario.
* Nombre de usuario único.
* Roles.
* Estado de cuenta.
* Registro del último inicio de sesión.
* Borrado lógico de usuarios.

### 📊 Dashboard

* Resumen general del sistema.
* Cantidad de entidades.
* Cantidad de tipos.
* Entidades activas.
* Entidades públicas.
* Registros recientes.
* Accesos rápidos.

### 🧩 Tipos de entidad

* Crear tipos personalizados.
* Editar tipos.
* Icono.
* Color.
* Estado.
* Orden.
* Búsqueda.
* Protección mediante Policies.

### ✦ Entidades

* Crear cualquier tipo de entidad.
* Subir una imagen.
* Definir nombre, código y slug.
* Seleccionar tipo de entidad.
* Definir descripción.
* Estado.
* Visibilidad.
* Publicación comunitaria.
* Permitir o impedir clonación.
* Configurar atributos dinámicos.
* Añadir una entidad a varias colecciones.

### ☷ Atributos dinámicos

Tipos de datos soportados:

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

Orígenes de valor:

```text
FREE
CATALOG
MIXED
```

Presentaciones disponibles:

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

Configuraciones adicionales:

* Valor obligatorio.
* Valor visible.
* Valor buscable.
* Valor comparable.
* Valor filtrable.
* Valor destacado.
* Selección múltiple.
* Valores personalizados.
* Unidad de medida.
* Límites numéricos.
* Longitud mínima y máxima.
* Imagen.
* Icono.
* Color.

### ◆ Valores y opciones

Cada opción seleccionable puede tener:

* Nombre.
* Código.
* Descripción.
* Imagen.
* Icono.
* Color.
* Valor numérico.
* Opción superior.
* Orden.
* Estado.
* Metadatos.

Ejemplo:

```text
Atributo: Anime

Naruto
├── Imagen
├── Icono: 🍥
├── Color: naranja
└── Descripción

One Piece
├── Imagen
├── Icono: ☠
├── Color: rojo
└── Descripción
```

### ☑ Multiselección

Un atributo puede aceptar uno o varios valores.

Selección única:

```text
Anime:
└── Naruto
```

Selección múltiple:

```text
Elemento:
├── Fuego
├── Viento
└── Luz
```

### ▥ Grupos de atributos

Permiten organizar atributos mediante:

* Lista.
* Cuadrícula.
* Tarjetas.
* Tabla.
* Vista compacta.

### ▤ Colecciones

* Crear colecciones.
* Subir portada.
* Seleccionar color e icono.
* Añadir varias entidades.
* Quitar entidades.
* Ordenar entidades.
* Definir visibilidad.
* Publicar.
* Permitir clonación.

### 🌐 Explorar comunidad

* Buscar contenido público.
* Explorar entidades.
* Explorar colecciones.
* Explorar atributos.
* Ver catálogos.
* Filtrar por tipo.
* Filtrar por tipo de dato.
* Ordenar por popularidad.
* Ordenar por fecha.
* Ordenar por nombre.
* Mostrar cantidad de vistas.
* Mostrar cantidad de clonaciones.
* Abrir detalles públicos.
* Copiar contenido a la biblioteca personal.

### 📋 Clonación independiente

Cuando un usuario copia contenido comunitario, OmniMerge crea una copia independiente.

Esto significa que:

* El original no se modifica.
* La copia puede editarse libremente.
* Se conserva la referencia del contenido original.
* Se copian imágenes cuando corresponde.
* Se copian opciones y atributos.
* Las copias quedan privadas inicialmente.

---

## 🛠 Tecnologías utilizadas

### Backend

* PHP 8.2 o superior.
* Laravel 12.
* Eloquent ORM.
* Laravel Breeze.
* Laravel Policies.
* Form Requests.
* Services.
* Migrations.
* Blade.

### Frontend

* Blade Templates.
* Tailwind CSS.
* Alpine.js.
* JavaScript.
* Axios.
* Vite.

### Base de datos

* MySQL.
* Relaciones Eloquent.
* Migraciones.
* Borrado lógico.
* Columnas JSON.
* Tablas intermedias.

### Desarrollo

* Composer.
* Node.js.
* NPM.
* Git.
* GitHub.
* PHPUnit.
* Laravel Pint.
* Visual Studio Code.

---

## 🏗 Arquitectura del sistema

OmniMerge utiliza una arquitectura MVC complementada con Requests, Policies y Services.

```text
Solicitud del usuario
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
        ├── Policy
        ├── Form Request
        └── Service
        │
        ▼
      Model
        │
        ▼
   Base de datos
        │
        ▼
   Vista Blade
        │
        ▼
 Respuesta al usuario
```

### Models

Representan los datos y sus relaciones.

```text
app/Models/
```

### Controllers

Procesan las solicitudes y coordinan la lógica.

```text
app/Http/Controllers/
```

### Form Requests

Centralizan validación y autorización.

```text
app/Http/Requests/
```

### Policies

Determinan qué acciones puede realizar el usuario.

```text
app/Policies/
```

### Services

Contienen lógica de negocio compleja.

```text
app/Services/
```

### Views

Contienen la interfaz Blade.

```text
resources/views/
```

---

## 📁 Estructura principal

```text
OmniMerge/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Models/
│   ├── Policies/
│   ├── Providers/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
├── package.json
├── phpunit.xml
├── tailwind.config.js
└── vite.config.js
```

---

## 📂 Estructura de la aplicación

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Attributes/
│   │   │   ├── AttributeController.php
│   │   │   ├── AttributeGroupController.php
│   │   │   └── AttributeOptionController.php
│   │   ├── Collections/
│   │   │   └── CollectionController.php
│   │   ├── Community/
│   │   │   └── ExploreController.php
│   │   ├── Dashboard/
│   │   │   └── DashboardController.php
│   │   ├── Entities/
│   │   │   ├── EntityController.php
│   │   │   └── EntityAttributeController.php
│   │   ├── EntityTypes/
│   │   │   └── EntityTypeController.php
│   │   ├── Controller.php
│   │   └── ProfileController.php
│   │
│   └── Requests/
│       ├── Auth/
│       ├── Attributes/
│       ├── Collections/
│       ├── Entities/
│       ├── EntityTypes/
│       └── ProfileUpdateRequest.php
│
├── Models/
│   ├── Attribute.php
│   ├── AttributeGroup.php
│   ├── AttributeOption.php
│   ├── Collection.php
│   ├── Entity.php
│   ├── EntityAttribute.php
│   ├── EntityAttributeValue.php
│   ├── EntityType.php
│   └── User.php
│
├── Policies/
│   ├── AttributeGroupPolicy.php
│   ├── AttributeOptionPolicy.php
│   ├── AttributePolicy.php
│   ├── CollectionPolicy.php
│   ├── EntityPolicy.php
│   └── EntityTypePolicy.php
│
└── Services/
    ├── Community/
    │   └── CommunityCloneService.php
    └── Entities/
        └── EntityAttributeValueService.php
```

---

## 🎨 Estructura de vistas

```text
resources/views/
├── auth/
├── attribute-groups/
│   ├── partials/form.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── attribute-options/
│   ├── partials/form.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── attributes/
│   ├── partials/form.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── collections/
│   ├── partials/form.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── community/
│   ├── partials/
│   │   ├── attribute-card.blade.php
│   │   ├── collection-card.blade.php
│   │   ├── entity-card.blade.php
│   │   └── filters.blade.php
│   ├── attribute.blade.php
│   ├── collection.blade.php
│   ├── entity.blade.php
│   └── index.blade.php
├── dashboard/
│   └── index.blade.php
├── entities/
│   ├── partials/form.blade.php
│   ├── attributes.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── entity-types/
│   ├── partials/form.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── layouts/
│   ├── app.blade.php
│   └── guest.blade.php
├── partials/
│   ├── header.blade.php
│   └── sidebar.blade.php
└── profile/
```

---

## 🗄 Modelo de datos

### Usuarios

```text
users
```

Campos adicionales:

* `username`.
* `role`.
* `status`.
* `last_login_at`.
* `deleted_at`.

### Tipos de entidad

```text
entity_types
```

Cada tipo pertenece a un usuario.

### Entidades

```text
entities
```

Cada entidad pertenece a:

* Un usuario.
* Un tipo de entidad.

Una entidad puede:

* Poseer muchos atributos.
* Pertenecer a muchas colecciones.
* Ser clonada.
* Proceder de otra entidad.

### Atributos

```text
attributes
```

Cada atributo pertenece a un usuario.

Puede incluir:

* Tipo de dato.
* Origen de valor.
* Estilo de presentación.
* Imagen.
* Configuración.
* Reglas.
* Visibilidad.
* Información comunitaria.

### Opciones

```text
attribute_options
```

Cada opción pertenece a un atributo.

Puede tener una opción padre.

### Grupos

```text
attribute_groups
```

Se relacionan con atributos mediante:

```text
attribute_group_attribute
```

### Asignación de atributos

```text
entity_attributes
```

Indica que una entidad utiliza un atributo.

### Valores

```text
entity_attribute_values
```

Almacena valores tipados:

* Texto.
* Entero.
* Decimal.
* Booleano.
* Fecha.
* Color.
* Opción.
* JSON.

### Colecciones

```text
collections
```

Se relacionan con entidades mediante:

```text
collection_entity
```

### Interacciones comunitarias

```text
community_interactions
```

Registra:

* Vistas.
* Clonaciones.
* Futuramente favoritos.

---

## 🔗 Relaciones principales

```text
User
├── hasMany EntityType
├── hasMany Entity
├── hasMany Attribute
├── hasMany AttributeGroup
└── hasMany Collection
```

```text
EntityType
├── belongsTo User
└── hasMany Entity
```

```text
Entity
├── belongsTo User
├── belongsTo EntityType
├── hasMany EntityAttribute
├── belongsToMany Collection
├── belongsTo Entity como origen
└── hasMany Entity como copias
```

```text
Attribute
├── belongsTo User
├── hasMany AttributeOption
├── hasMany EntityAttribute
├── belongsToMany AttributeGroup
├── belongsTo Attribute como origen
└── hasMany Attribute como copias
```

```text
AttributeOption
├── belongsTo Attribute
├── belongsTo AttributeOption como padre
├── hasMany AttributeOption como hijos
└── hasMany EntityAttributeValue
```

```text
Collection
├── belongsTo User
├── belongsToMany Entity
├── belongsTo Collection como origen
└── hasMany Collection como copias
```

---

## 🔒 Seguridad

OmniMerge utiliza diferentes mecanismos de seguridad proporcionados por Laravel.

### Autenticación

Las rutas internas se protegen con:

```php
Route::middleware('auth')
```

### Policies

Las Policies impiden que un usuario modifique contenido ajeno.

Ejemplo conceptual:

```php
return $entity->user_id === $user->id;
```

### Form Requests

Los formularios se validan en clases independientes.

Ejemplo:

```text
app/Http/Requests/Entities/StoreEntityRequest.php
```

### Validación de pertenencia

Los IDs enviados desde formularios se restringen por usuario.

```php
Rule::exists('entities', 'id')
    ->where(
        fn ($query) => $query
            ->where('user_id', $this->user()->id)
            ->whereNull('deleted_at')
    )
```

### Protección CSRF

Todos los formularios incluyen:

```blade
@csrf
```

### Borrado lógico

Los principales modelos utilizan:

```php
use SoftDeletes;
```

### Contenido público

El explorador comunitario solo muestra contenido activo, publicado y público.

```php
->where('visibility', 'PUBLIC')
->where('status', 'ACTIVE')
->whereNotNull('published_at')
```

---

## 🖼 Gestión de imágenes

Las imágenes se almacenan en el disco público.

```text
storage/app/public/entities/
storage/app/public/attributes/
storage/app/public/attribute-options/
storage/app/public/collections/
```

Para crear el enlace público se debe ejecutar:

```bash
php artisan storage:link
```

Esto crea:

```text
public/storage
```

Los formularios que cargan archivos deben utilizar:

```html
enctype="multipart/form-data"
```

Formatos permitidos:

```text
JPG
JPEG
PNG
WEBP
```

---

## 📌 Requisitos

Antes de instalar el proyecto se necesita:

* PHP 8.2 o superior.
* Composer.
* Node.js.
* NPM.
* MySQL 8 o superior.
* Git.
* Extensiones PHP necesarias para Laravel.
* Un navegador moderno.

Comprobar versiones:

```bash
php -v
composer --version
node -v
npm -v
git --version
```

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/grverlearner/OmniMerge.git
cd OmniMerge
```

### 2. Instalar dependencias de PHP

```bash
composer install
```

### 3. Instalar dependencias frontend

En CMD, Bash o terminal compatible:

```bash
npm install
```

En PowerShell, si la política de ejecución bloquea `npm.ps1`:

```powershell
npm.cmd install
```

### 4. Crear el archivo de entorno

Windows:

```powershell
Copy-Item .env.example .env
```

CMD:

```cmd
copy .env.example .env
```

Linux o macOS:

```bash
cp .env.example .env
```

### 5. Generar la clave de Laravel

```bash
php artisan key:generate
```

### 6. Crear la base de datos

```sql
CREATE DATABASE omnimerge
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### 7. Configurar `.env`

```env
APP_NAME=OmniMerge
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=omnimerge
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@omnimerge.local"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

### 8. Ejecutar las migraciones

```bash
php artisan migrate
```

Para reconstruir toda la base de datos durante desarrollo:

```bash
php artisan migrate:fresh
```

> [!WARNING]
> `migrate:fresh` elimina todas las tablas y todos los datos existentes.

### 9. Crear el enlace de almacenamiento

```bash
php artisan storage:link
```

### 10. Compilar recursos

```bash
npm run build
```

En PowerShell:

```powershell
npm.cmd run build
```

### 11. Iniciar el servidor

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

En PowerShell:

```powershell
npm.cmd run dev
```

Abrir:

```text
http://127.0.0.1:8000
```

---

## ⚡ Inicio rápido con Composer

El proyecto incluye scripts de Composer para automatizar tareas comunes.

Instalación automatizada:

```bash
composer run setup
```

Entorno de desarrollo:

```bash
composer run dev
```

Este último puede iniciar:

* Servidor Laravel.
* Worker de cola.
* Registro de logs con Pail.
* Servidor Vite.

---

## 🧭 Rutas principales

### Inicio

```text
GET /
```

### Autenticación

```text
GET  /register
POST /register
GET  /login
POST /login
POST /logout
```

### Dashboard

```text
GET /dashboard
```

### Perfil

```text
GET    /profile
PATCH  /profile
DELETE /profile
```

### Tipos de entidad

```text
GET    /entity-types
GET    /entity-types/create
POST   /entity-types
GET    /entity-types/{entity_type}
GET    /entity-types/{entity_type}/edit
PUT    /entity-types/{entity_type}
DELETE /entity-types/{entity_type}
```

### Entidades

```text
GET    /entities
GET    /entities/create
POST   /entities
GET    /entities/{entity}
GET    /entities/{entity}/edit
PUT    /entities/{entity}
DELETE /entities/{entity}
```

### Atributos de una entidad

```text
GET /entities/{entity}/attributes
PUT /entities/{entity}/attributes
```

### Atributos

```text
GET    /attributes
GET    /attributes/create
POST   /attributes
GET    /attributes/{attribute}
GET    /attributes/{attribute}/edit
PUT    /attributes/{attribute}
DELETE /attributes/{attribute}
```

### Opciones de atributos

```text
GET    /attribute-options
GET    /attribute-options/create
GET    /attribute-options/{attributeOption}
GET    /attribute-options/{attributeOption}/edit
POST   /attributes/{attribute}/options
PUT    /attributes/{attribute}/options/{option}
DELETE /attribute-options/{attributeOption}
```

### Grupos de atributos

```text
GET    /attribute-groups
GET    /attribute-groups/create
POST   /attribute-groups
GET    /attribute-groups/{attribute_group}
GET    /attribute-groups/{attribute_group}/edit
PUT    /attribute-groups/{attribute_group}
DELETE /attribute-groups/{attribute_group}
```

### Colecciones

```text
GET    /collections
GET    /collections/create
POST   /collections
GET    /collections/{collection}
GET    /collections/{collection}/edit
PUT    /collections/{collection}
DELETE /collections/{collection}
```

### Comunidad

```text
GET /explore

GET /explore/entities/{entity}
GET /explore/collections/{collection}
GET /explore/attributes/{attribute}

POST /explore/entities/{entity}/clone
POST /explore/collections/{collection}/clone
POST /explore/attributes/{attribute}/clone
```

Para ver todas las rutas reales:

```bash
php artisan route:list
```

---

## 🧪 Pruebas

Ejecutar todas las pruebas:

```bash
php artisan test
```

También puede utilizarse:

```bash
composer test
```

Pruebas recomendadas:

* Un usuario puede registrarse.
* Un usuario puede iniciar sesión.
* Un usuario inactivo no puede ingresar.
* Un usuario puede crear tipos.
* Un usuario puede crear entidades.
* Un usuario no puede editar contenido ajeno.
* Una imagen se almacena correctamente.
* Un atributo puede guardar texto.
* Un atributo puede guardar números.
* Un atributo puede guardar varias opciones.
* Una colección puede contener entidades.
* El contenido privado no aparece en la comunidad.
* El contenido público aparece en la comunidad.
* Una clonación crea una copia independiente.

---

## 🧹 Calidad de código

Laravel Pint:

```bash
./vendor/bin/pint
```

En Windows:

```powershell
vendor\bin\pint
```

Comprobar rutas:

```bash
php artisan route:list
```

Comprobar migraciones:

```bash
php artisan migrate:status
```

Limpiar cachés:

```bash
php artisan optimize:clear
```

Limpiar vistas:

```bash
php artisan view:clear
```

---

## 🧰 Comandos útiles

### Crear un controlador

```bash
php artisan make:controller NombreController
```

### Crear modelo, migración y factory

```bash
php artisan make:model Nombre -mf
```

### Crear Request

```bash
php artisan make:request StoreNombreRequest
```

### Crear Policy

```bash
php artisan make:policy NombrePolicy --model=Nombre
```

### Abrir Tinker

```bash
php artisan tinker
```

### Verificar una vista

```php
view()->exists('collections.index');
```

### Limpiar todas las cachés

```bash
php artisan optimize:clear
```

---

## 🐛 Solución de problemas

### View not found

Error:

```text
View [collections.index] not found
```

Comprobar que exista:

```text
resources/views/collections/index.blade.php
```

Después ejecutar:

```bash
php artisan view:clear
php artisan optimize:clear
```

### Undefined method `authorize`

El controlador base debe usar:

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
```

### Undefined method `url`

Intelephense puede no reconocer:

```php
Storage::disk('public')->url($path);
```

Se puede tipar el disco:

```php
/** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
$disk = Storage::disk('public');

return $disk->url($path);
```

### Undefined method `user` o propiedad `id`

Asegurarse de importar:

```php
use Illuminate\Http\Request;
use App\Models\User;
```

Y tipar:

```php
/** @var User $user */
$user = $request->user();

$user->id;
```

### La imagen no aparece

Ejecutar:

```bash
php artisan storage:link
```

Verificar:

```text
storage/app/public/
public/storage/
```

Verificar que el formulario incluya:

```html
enctype="multipart/form-data"
```

### Error de NPM en PowerShell

Utilizar:

```powershell
npm.cmd install
npm.cmd run dev
npm.cmd run build
```

### Tabla ya existente

Durante desarrollo:

```bash
php artisan migrate:fresh
```

> [!CAUTION]
> Esto elimina los datos actuales.

### Cambios de rutas no aparecen

```bash
php artisan route:clear
php artisan optimize:clear
php artisan route:list
```

---

## 📝 Convenciones del proyecto

### Códigos

Los códigos se almacenan en mayúsculas y con guiones bajos.

```text
Naruto Uzumaki
→ NARUTO_UZUMAKI
```

### Slugs

Los slugs utilizan minúsculas y guiones.

```text
Naruto Uzumaki
→ naruto-uzumaki
```

### Estados

```text
ACTIVE
INACTIVE
ARCHIVED
```

### Visibilidad

```text
PRIVATE
PUBLIC
UNLISTED
```

### Roles

```text
USER
ADMIN
```

### Commits

Se recomienda utilizar Conventional Commits.

```text
feat(auth): configurar registro e inicio de sesión
feat(entities): implementar CRUD de entidades
feat(attributes): agregar atributos dinámicos
feat(collections): implementar colecciones
feat(community): agregar explorador comunitario
fix(storage): corregir visualización de imágenes
docs: actualizar documentación
```

---

## 🔄 Flujo de trabajo recomendado

```bash
git checkout main
git pull origin main
```

Crear rama:

```bash
git checkout -b feat/nombre-funcionalidad
```

Realizar cambios y revisar:

```bash
git status
git diff
```

Añadir y confirmar:

```bash
git add .
git commit -m "feat: descripción del cambio"
```

Publicar:

```bash
git push origin feat/nombre-funcionalidad
```

---

## 📈 Estado del desarrollo

### Sprint 0 — Base del proyecto

* [x] Laravel.
* [x] MySQL.
* [x] Git y GitHub.
* [x] Laravel Breeze.
* [x] Registro.
* [x] Inicio de sesión.
* [x] Perfil.

### Sprint 1 — Biblioteca básica

* [x] Dashboard.
* [x] Layout principal.
* [x] Sidebar.
* [x] Tipos de entidad.
* [x] Entidades.
* [x] Imágenes.
* [x] Policies.

### Sprint 2 — Atributos dinámicos

* [x] Atributos.
* [x] Tipos de dato.
* [x] Opciones.
* [x] Valores tipados.
* [x] Multiselección.
* [x] Grupos.
* [x] Asignación a entidades.

### Sprint 3 — Organización visual

* [x] Panel de opciones.
* [x] Imágenes en opciones.
* [x] Colecciones.
* [x] Portadas.
* [x] Relación colección-entidad.

### Sprint 4 — Comunidad

* [x] Explorador comunitario.
* [x] Búsqueda.
* [x] Filtros.
* [x] Publicación.
* [x] Estadísticas.
* [x] Vistas públicas.
* [x] Clonación.
* [x] Procedencia de copias.

---

## 🗺 Roadmap

### Próxima fase: relaciones y condiciones

* [ ] Relaciones entre atributos.
* [ ] Condiciones de visibilidad.
* [ ] Opciones dependientes.
* [ ] Reglas `SHOW`.
* [ ] Reglas `HIDE`.
* [ ] Reglas `REQUIRE`.
* [ ] Prioridades y grupos lógicos.

Tablas propuestas:

```text
attribute_relationships
attribute_conditions
attribute_option_relationships
```

### Universos

* [ ] Crear universos.
* [ ] Añadir entidades.
* [ ] Definir reglas.
* [ ] Crear temporadas.
* [ ] Estado temporal de entidades.

Tablas propuestas:

```text
universes
universe_entities
seasons
season_entities
```

### Torneos

* [ ] Crear torneos.
* [ ] Seleccionar participantes.
* [ ] Filtrar por atributos.
* [ ] Generar rondas.
* [ ] Generar encuentros.
* [ ] Registrar resultados.

Tablas propuestas:

```text
tournaments
tournament_participants
tournament_rounds
tournament_matches
match_results
```

### Motor de simulación

* [ ] Fórmulas personalizadas.
* [ ] Pesos.
* [ ] Probabilidad.
* [ ] Ventajas y desventajas.
* [ ] Aleatoriedad controlada.
* [ ] Eventos automáticos.

### Comunidad avanzada

* [ ] Favoritos.
* [ ] Seguimiento de creadores.
* [ ] Comentarios.
* [ ] Valoraciones.
* [ ] Reportes.
* [ ] Moderación.
* [ ] Notificaciones.
* [ ] Perfiles públicos.

### Analítica

* [ ] Rankings.
* [ ] Comparaciones.
* [ ] Tendencias.
* [ ] Atributos más utilizados.
* [ ] Entidades más exitosas.
* [ ] Gráficos.
* [ ] Exportación de reportes.

---

## 🤝 Contribuciones

Las contribuciones son bienvenidas.

Proceso recomendado:

1. Hacer fork del repositorio.
2. Crear una rama para la funcionalidad.
3. Realizar los cambios.
4. Añadir pruebas cuando corresponda.
5. Ejecutar Laravel Pint.
6. Confirmar que las pruebas pasan.
7. Crear un Pull Request.

Ejemplo:

```bash
git checkout -b feat/nueva-funcionalidad
git add .
git commit -m "feat: agregar nueva funcionalidad"
git push origin feat/nueva-funcionalidad
```

---

## 🔐 Reporte de seguridad

No publiques vulnerabilidades de seguridad como Issues públicos.

Envía el reporte de manera privada al responsable del repositorio e incluye:

* Descripción de la vulnerabilidad.
* Pasos para reproducirla.
* Impacto.
* Posible solución.
* Capturas o evidencias necesarias.

Nunca publiques:

* Contraseñas.
* API keys.
* Tokens.
* Credenciales de base de datos.
* Contenido del archivo `.env`.

---

## 📚 Documentación adicional

La documentación técnica ampliada puede incluirse posteriormente en:

```text
docs/
├── architecture.md
├── database.md
├── installation.md
├── community.md
├── attributes.md
├── collections.md
├── roadmap.md
└── troubleshooting.md
```

---

## 👨‍💻 Autor

**Grover Romeo Chambilla Alanoca**

Estudiante de Ingeniería en Informática y Sistemas.

Proyecto desarrollado como una plataforma flexible para la creación, organización, reutilización y futura simulación de entidades.

GitHub:

```text
@grverlearner
```

---

## 📄 Licencia

Este proyecto se distribuye bajo la licencia MIT.

Consulta el archivo:

```text
LICENSE
```

para conocer los términos completos.

---

<div align="center">

## 🌌 OmniMerge

**Crea cualquier entidad. Define sus características. Organiza tu universo. Comparte tus ideas.**

Hecho con Laravel, PHP, MySQL, Tailwind CSS y creatividad.

</div>
