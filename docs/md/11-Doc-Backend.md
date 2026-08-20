DOCUMENTACIÓN TÉCNICA DEL BACKEND DE OMNIMERGE
1. Introducción
OmniMerge es una plataforma web concebida para permitir la creación, organización, reutilización, evolución y futura interacción de entidades genéricas dentro de estructuras configurables por el usuario. Aunque inicialmente el proyecto puede observarse como una aplicación orientada a la administración de entidades, atributos y colecciones, la evolución del backend demuestra que su arquitectura apunta a un sistema considerablemente más amplio: una plataforma capaz de representar información dinámica, versionarla, compartirla, reutilizarla, organizar competiciones complejas y, posteriormente, ejecutar universos y simulaciones.
La presente documentación describe exclusivamente el backend de OmniMerge. No se analiza en este capítulo la estructura visual, Blade, estilos, componentes gráficos ni decisiones de experiencia de usuario. El objetivo es documentar cómo está construido internamente el sistema, cómo ha evolucionado el código, cuáles son los dominios actuales, cómo se relacionan sus componentes, qué patrones arquitectónicos se están utilizando, qué decisiones deben conservarse y qué consideraciones deberían aplicarse durante las siguientes etapas de desarrollo.
La revisión corresponde al estado actual de la rama main del repositorio de OmniMerge al 13 de agosto de 2026. El historial de migraciones confirma una evolución incremental desde usuarios, tipos, entidades y atributos hasta versionado, contexto, motores competitivos y finalmente el grafo de torneos.

2. Visión general del backend
El backend de OmniMerge está desarrollado sobre Laravel 12 y PHP 8.2 o superior. Utiliza Eloquent como ORM, Form Requests para autorización y validación de entradas, Policies para control de acceso, Services para lógica de negocio y migraciones incrementales para representar la evolución del esquema de datos. El proyecto también dispone de PHPUnit para pruebas y mantiene la infraestructura estándar de Laravel para colas, caché y demás servicios del framework.
A nivel conceptual, el backend puede entenderse actualmente mediante los siguientes dominios:
USUARIO
│
├── Biblioteca
│   ├── Tipos de entidad
│   ├── Entidades
│   ├── Atributos
│   ├── Catálogos
│   ├── Grupos de atributos
│   └── Colecciones
│
├── Comunidad
│   ├── Publicación
│   ├── Procedencia
│   ├── Clonación
│   └── Interacciones
│
├── Versionado
│   ├── Versiones
│   ├── Versiones de entidades
│   ├── Atributos de versiones
│   ├── Multimedia
│   ├── Presentación
│   ├── Base activa
│   └── Reglas contextuales
│
└── Torneos
    ├── TournamentTemplate
    ├── PhaseTemplate
    ├── PhaseExit
    ├── Single Elimination
    ├── Round Robin
    ├── Group Stage
    ├── Swiss
    └── Tournament Graph

Esta separación es importante porque OmniMerge está dejando progresivamente de ser una aplicación CRUD tradicional para convertirse en un sistema compuesto por dominios con reglas de negocio propias.

3. Filosofía arquitectónica general
La arquitectura actual no sigue una implementación de Domain-Driven Design estricta ni una Clean Architecture formal, pero ha evolucionado hacia una organización inspirada en varios de sus principios.
La aplicación continúa utilizando las convenciones de Laravel:
HTTP Request
      ↓
Route
      ↓
Middleware
      ↓
FormRequest
      ↓
Controller
      ↓
Service
      ↓
Eloquent Model
      ↓
Database

Sin embargo, conforme aumentó la complejidad, se introdujeron Services especializados:
Controller
     │
     ├── autorización
     ├── recibe entrada validada
     │
     ▼
Domain Service
     │
     ├── reglas de negocio
     ├── transacciones
     ├── generación de códigos
     ├── clonación
     ├── cálculos
     └── coordinación de modelos
             │
             ▼
         Eloquent

Esto evita que toda la lógica termine dentro de los controladores.
El proyecto no necesita actualmente incorporar una capa Repository únicamente por razones ceremoniales. Eloquent ya constituye una abstracción suficientemente potente para la persistencia actual. Una capa adicional tendría sentido únicamente si en el futuro aparecen consultas muy complejas, múltiples mecanismos de persistencia o necesidad real de desacoplar determinadas operaciones.

4. Tecnologías principales del backend
La configuración de Composer actual establece PHP ^8.2 y Laravel Framework ^12.0. Entre las herramientas de desarrollo aparecen Laravel Breeze, Laravel Pint, PHPUnit, Mockery, Collision, Sail y otras dependencias estándar del ecosistema Laravel.
Las tecnologías principales de backend pueden resumirse como:
Tecnología
Responsabilidad
PHP 8.2+
Lenguaje principal
Laravel 12
Framework backend
Eloquent ORM
Persistencia y relaciones
Laravel Migrations
Evolución del esquema
Form Requests
Validación y autorización
Policies
Control de acceso por recurso
Services
Lógica de negocio
Storage
Administración de imágenes/archivos
PHPUnit 11
Pruebas
Laravel Pint
Formato y estilo PHP
Laravel Queue Infrastructure
Base disponible para procesamiento futuro

Una consideración importante es que la infraestructura de colas existe por el propio proyecto Laravel, pero actualmente los principales dominios de OmniMerge funcionan fundamentalmente de forma síncrona. Esto es razonable en la etapa actual. La necesidad real de procesamiento asíncrono aparecerá especialmente cuando se introduzcan simulaciones masivas, generación de torneos, estadísticas acumuladas o ejecuciones grandes.

5. Estructura principal del backend
La estructura relevante puede entenderse de la siguiente manera:
app/
├── Http/
│   ├── Controllers/
│   └── Requests/
│
├── Models/
│
├── Policies/
│
├── Providers/
│
├── Services/
│   ├── Attributes/
│   ├── Community/
│   ├── Entities/
│   ├── Tournaments/
│   └── Versions/
│
└── View/
    └── Components/

database/
├── migrations/
├── factories/
└── seeders/

routes/
├── auth.php
├── console.php
└── web.php

tests/
├── Feature/
└── Unit/

Lo significativo no es solamente la existencia de estas carpetas, sino que progresivamente los dominios importantes ya cuentan con clases especializadas en lugar de concentrar todas las responsabilidades dentro de app/Http/Controllers.
El archivo AppServiceProvider permanece prácticamente sin personalización, por lo que actualmente no existe una gran capa de bindings manuales, Gates globales o registros complejos de servicios. Laravel puede resolver la mayoría de los Services automáticamente mediante su contenedor de dependencias.

6. Ciclo de una solicitud en OmniMerge
Un flujo de escritura típico puede representarse así:
Usuario
   ↓
HTTP POST / PUT / DELETE
   ↓
Middleware de autenticación
   ↓
Route Model Binding
   ↓
FormRequest
   ├── prepareForValidation()
   ├── authorize()
   └── rules()
   ↓
Controller
   ↓
Policy / authorize()
   ↓
Service
   ↓
DB::transaction()
   ↓
Models Eloquent
   ↓
Base de datos
   ↓
Redirect / Response

Esta separación es una de las bases que deberían conservarse durante el crecimiento del proyecto.
El FormRequest no se limita a comprobar que un campo exista. En varios módulos se utiliza prepareForValidation() para normalizar datos antes de validarlos. Por ejemplo, pueden convertirse valores a mayúsculas, transformar booleanos, limpiar cadenas o normalizar estados. Los Requests de Torneos siguen este patrón de manera extensa.
Esto significa que el Controller puede asumir que recibe datos con una estructura mucho más predecible.

7. Evolución histórica del backend
El historial del repositorio permite dividir el backend en varias generaciones.
7.1. Primera etapa: autenticación y biblioteca básica
Las primeras migraciones del 4 de agosto introdujeron:
users
entity_types
entities
attributes
attribute_groups
attribute_options
entity_attributes
entity_attribute_values
attribute_group_attribute

La arquitectura inicial estaba centrada en responder a una necesidad fundamental:
permitir que cada usuario construya sus propias entidades sin obligar al sistema a conocer previamente todas las características posibles.
Esta decisión originó el sistema dinámico de atributos.

7.2. Segunda etapa: colecciones y comunidad
El 5 de agosto aparecieron:
collections
collection_entity

campos comunitarios en:
entities
attributes
collections

community_interactions

Esto convirtió la Biblioteca privada inicial en el principio de una plataforma colaborativa.
Ya no bastaba con crear recursos. Era necesario:
decidir qué recursos podían publicarse;
controlar su visibilidad;
permitir clonación;
conservar procedencia;
contabilizar clonaciones;
registrar interacciones.

7.3. Tercera etapa: identidad estable de los recursos
Entre el 8 y el 9 de agosto fueron incorporándose secuencias e identidad adicional para:
EntityType
Attribute
AttributeOption
Entity
Collection
AttributeGroup

También apareció source_entity_type_id.
Esta etapa introdujo una característica importante del backend: los recursos tienen códigos independientes de su nombre.
En vez de depender únicamente de nombres mutables como:
Naruto
Poder
Personaje

el backend puede manejar identificadores legibles y estables como:
ENT000001
ATR000001
...

Por ejemplo, Entity::formatCode() genera códigos mediante un número secuencial.
Esta distinción permite modificar nombres sin alterar la identidad lógica del recurso.

8. El usuario como raíz de propiedad
User se ha convertido en la raíz de propiedad de gran parte del backend.
Actualmente mantiene relaciones con:
EntityType
Entity
Attribute
AttributeOption
AttributeGroup
Collection
Version
EntityVersion
TournamentTemplate
PhaseTemplate

Esto responde a una regla importante:
Los recursos principales pertenecen a un usuario.
La consecuencia es que muchas operaciones no deben realizar consultas globales como:
Entity::all();

sino consultas contextualizadas:
$user->entities()

o mediante scopes equivalentes.
Esto proporciona aislamiento lógico entre las bibliotecas de diferentes usuarios.

9. Estado y rol del usuario
El modelo User incluye conceptos diferenciados de:
role
status

El método:
isActive()

comprueba que el estado sea ACTIVE.
Por otra parte:
isAdmin()

comprueba el rol ADMIN.
Es una decisión correcta porque estado y rol responden a problemas diferentes.
Un usuario puede ser:
role = USER
status = ACTIVE

mientras otro podría ser:
role = ADMIN
status = ACTIVE

o eventualmente:
role = USER
status = SUSPENDED

No deberían mezclarse ambos conceptos.

10. Soft Deletes y conservación de recursos
User utiliza SoftDeletes, al igual que numerosos recursos principales como Entity, Attribute, AttributeOption, PhaseTemplate y TournamentTemplate.
Esto permite que eliminar un recurso no implique necesariamente borrar inmediatamente su registro de la base de datos.
En términos conceptuales:
DELETE
   ↓
deleted_at = timestamp
   ↓
el recurso deja de aparecer normalmente
   ↓
los datos todavía existen

Esta característica es especialmente conveniente en OmniMerge porque los recursos pueden adquirir dependencias con:
versiones;
clones;
colecciones;
torneos;
historiales futuros.
A medida que se introduzcan competiciones reales, deberá extremarse todavía más esta política: los datos históricos de una competición finalizada no deberían depender de que un recurso actual continúe existiendo en su forma original.

11. Arquitectura de la Biblioteca
La Biblioteca constituye actualmente el núcleo de datos de OmniMerge.
Puede representarse de forma simplificada así:
User
│
├── EntityType
│
└── Entity
     │
     ├── EntityAttribute
     │      │
     │      ├── Attribute
     │      │      └── AttributeOption
     │      │
     │      └── EntityAttributeValue
     │
     ├── EntityVersion
     └── Collections

Su diseño busca permitir que el backend no necesite saber anticipadamente qué clase de objeto desea modelar cada usuario.

12. EntityType
EntityType clasifica una entidad sin convertir esa clasificación en una tabla rígida.
Conceptualmente:
EntityType
├── Personaje
├── País
├── Vehículo
├── Animal
├── Organización
└── Concepto

Posteriormente:
Entity
 └── belongsTo EntityType

Gracias a esta separación no son necesarias tablas completamente distintas como:
characters
countries
vehicles
animals
organizations

para cada categoría creada por el usuario.
El backend conserva así un núcleo genérico.

13. Entity como elemento central
Entity constituye el recurso principal sobre el cual se construye gran parte de OmniMerge.
El modelo actual incluye, entre otros:
user_id
source_entity_id
sequence_number
entity_type_id
code
name
slug
description
image
status
visibility
metadata
allow_cloning
views_count
clones_count
published_at

Además, utiliza SoftDeletes. Su relación de procedencia es autorreferencial:
Entity
   ├── sourceEntity
   └── clones

Esto permite conservar el origen de una copia comunitaria.
La entidad también mantiene relaciones con:
EntityAttribute
EntityVersion
EntityBaseVersion
EntityPresentation

De esta forma su función ha evolucionado progresivamente.
Al principio era simplemente el registro principal.
Actualmente representa la identidad canónica del objeto, mientras otros componentes pueden controlar su versión activa, presentación pública o atributos efectivos.

14. Por qué Entity no contiene atributos específicos del dominio
Una de las decisiones más acertadas del backend es evitar que entities termine con columnas como:
age
power
height
country
element
chakra
speed
team
species

Esto funcionaría solamente para una categoría específica.
OmniMerge necesita que la misma infraestructura pueda almacenar:
Naruto Uzumaki
Perú
Toyota Supra
Júpiter
Fuego
Real Madrid

Por ello, las características variables se extraen hacia el sistema de atributos.
El resultado es un modelo de información dinámico.

15. Attribute: definición de una característica
Attribute define qué característica puede describirse.
Algunos ejemplos:
Anime
Aldea
Clan
Poder
Velocidad
Color
Continente
Capital
Fecha de fundación

El modelo contiene considerablemente más información que solamente name y type.
Entre sus responsabilidades se encuentran:
propiedad y procedencia;
identidad;
descripción;
ayuda y placeholder;
representación visual;
tipo de dato;
fuente del valor;
comportamiento de selección;
posibilidad de múltiples valores;
obligatoriedad;
filtrado;
comparación;
búsqueda;
visibilidad;
validaciones;
restricciones numéricas;
unidades;
configuración;
orden;
jerarquía;
estado;
publicación;
clonación.
Esto demuestra que un Attribute en OmniMerge no es simplemente una columna dinámica: es una definición de metadatos y comportamiento.

16. Tipos y origen de valores
El modelo mantiene compatibilidad con decisiones anteriores mediante value_source, pero el código actual considera OPTION como mecanismo moderno para atributos basados en catálogos. También existen métodos para determinar si un atributo utiliza catálogo, es seleccionable o permite selección múltiple.
Esto evidencia una característica importante del desarrollo:
OmniMerge está evolucionando sin eliminar inmediatamente compatibilidad con estructuras anteriores.
Esa estrategia permite avanzar progresivamente, aunque debe vigilarse para evitar mantener indefinidamente dos formas distintas de resolver exactamente el mismo problema.

17. AttributeOption: catálogo de valores
AttributeOption representa una opción reutilizable perteneciente a un atributo.
Ejemplo:
Attribute: Aldea

AttributeOptions:
├── Konoha
├── Suna
├── Kiri
├── Kumo
└── Iwa

La opción contiene:
user_id
source_attribute_option_id
attribute_id
parent_option_id
sequence_number
code
name
description
image
icon
color
numeric_value
sort_order
metadata
status

Al igual que las entidades, las opciones pueden conservar procedencia mediante:
source_attribute_option_id

y mantienen una relación:
sourceOption
clones

Esto permite que los catálogos también formen parte del sistema comunitario.

18. Jerarquías de opciones
Las opciones soportan jerarquía mediante:
parent_option_id

El modelo define:
parent()
children()

Esto permite estructuras como:
País del Fuego
└── Konoha

País del Viento
└── Suna

o:
Elemento
├── Fuego
│   ├── Llama azul
│   └── Llama negra
└── Agua

No todos los catálogos necesitan jerarquía, pero la arquitectura ya está preparada para aquellos que sí la requieren.

19. Relaciones entre opciones
Además de la jerarquía padre-hijo, existen relaciones explícitas entre opciones mediante AttributeOptionRelationship.
El propio AttributeOption expone relaciones de salida y entrada:
outgoingOptionRelationships
incomingOptionRelationships

Esto permite modelar dependencias que no necesariamente corresponden a una jerarquía directa.
Ejemplo conceptual:
Naruto
   ↓ pertenece a
Anime: Naruto

Konoha
   ↓ pertenece a
País del Fuego

Este sistema será especialmente útil para formularios contextuales y filtros dependientes.

20. AttributeGroup
AttributeGroup permite organizar atributos relacionados sin modificar su definición fundamental.
Ejemplo:
Grupo: Información Ninja

├── Aldea
├── Rango
├── Clan
└── Naturaleza de Chakra

La relación con Attribute se implementa mediante una tabla pivote.
La existencia de campos contextuales en la asociación permite que un atributo pueda aparecer de manera diferente dependiendo del grupo sin duplicar el atributo original.
Esta es una buena aplicación de la separación entre:
definición

y:
uso contextual

Una idea que posteriormente vuelve a aparecer de manera todavía más importante en PhaseTemplate frente a TournamentPhaseNode.

21. EntityAttribute: asignación de atributos
EntityAttribute es una capa intermedia entre Entity y Attribute.
No se trata únicamente de una tabla pivot mínima.
Actualmente puede almacenar:
entity_id
attribute_id
custom_label
is_visible
is_featured
sort_order
notes

Esto es importante porque separa:
Attribute
¿Qué es esta característica?

de:
EntityAttribute
¿Cómo se utiliza esta característica en esta entidad?

Ejemplo:
Attribute:
"Poder"

Entity Naruto:
custom_label = "Nivel de poder"
featured = true

Entity Espada:
custom_label = "Potencia"
featured = false

Ambas entidades pueden reutilizar la misma definición sin estar obligadas a presentarla exactamente igual.

22. EntityAttributeValue: almacenamiento tipado
Uno de los problemas clásicos de los sistemas EAV es almacenar todo como texto:
value = "123"
value = "true"
value = "2026-08-13"

OmniMerge evita parcialmente ese problema mediante columnas especializadas.
EntityAttributeValue contiene:
attribute_option_id
text_value
integer_value
decimal_value
boolean_value
date_value
color_value
custom_value
json_value
sort_order

Esto significa que el valor real puede conservar su naturaleza.
Ejemplos:
Edad:
integer_value = 23

Activo:
boolean_value = true

Fecha:
date_value = 2026-08-13

Aldea:
attribute_option_id = Konoha

El modelo también contiene displayValue(), que prioriza el nombre de una opción de catálogo cuando existe y, en caso contrario, obtiene el valor almacenado en la columna correspondiente.

23. Valores múltiples
La separación:
EntityAttribute
      ↓
hasMany
      ↓
EntityAttributeValue

permite que una asignación pueda tener varios valores.
Por ejemplo:
Naruto
└── Naturaleza de Chakra
    ├── Viento
    ├── Tierra
    └── Fuego

No es necesario duplicar el atributo.
Se mantiene una asignación:
EntityAttribute

con múltiples:
EntityAttributeValue

Esta estructura es especialmente importante para el carácter genérico de OmniMerge.

24. Relaciones entre atributos
AttributeRelationship permite conectar dos atributos mediante:
source_attribute_id
target_attribute_id
relationship_type
sort_order
is_active

Esto proporciona una capa semántica adicional.
No todos los atributos son independientes.
Ejemplo conceptual:
Anime
   ↓ condiciona
Personaje

País
   ↓ condiciona
Región

El sistema diferencia relaciones entre definiciones de atributos de las relaciones entre opciones específicas.

25. Reglas contextuales de atributos
El backend evolucionó todavía más mediante AttributeContextRule.
Una regla contiene conceptos como:
target_attribute_id
name
action
match_mode
priority
is_active

y mantiene múltiples condiciones asociadas.
Las acciones contempladas actualmente incluyen:
SHOW
HIDE
REQUIRE

Esto significa que el backend está preparado para expresar reglas como:
SI:
Anime = Naruto

ENTONCES:
Mostrar "Aldea Ninja"

o:
SI:
Tipo = Ninja

ENTONCES:
Requerir "Rango Ninja"

Este sistema transforma los atributos de simples propiedades en un pequeño mecanismo declarativo de comportamiento.

26. Collections
Las colecciones constituyen agregaciones definidas por el usuario.
Conceptualmente:
Collection
└── many Entities

Ejemplos:
Equipo 7
Akatsuki
Mis personajes favoritos
Países de América
Participantes Temporada 1

La relación se mantiene mediante collection_entity, permitiendo que una entidad pueda formar parte de diferentes colecciones sin duplicarse.
Las colecciones también fueron incorporadas al mecanismo comunitario y de clonación durante la segunda etapa del proyecto.

27. Arquitectura de Comunidad
La Comunidad representa una ampliación fundamental del backend.
El principio no es:
Usuario B modifica recurso de Usuario A

sino:
Usuario A
   ↓
publica recurso

Usuario B
   ↓
descubre recurso
   ↓
clona
   ↓
obtiene copia independiente

Esto conserva correctamente la propiedad de los datos.
La copia puede continuar evolucionando sin alterar al recurso original.

28. Procedencia mediante source_*
El patrón de procedencia aparece en múltiples recursos:
source_entity_id
source_attribute_id
source_attribute_option_id
source_collection_id
source_entity_type_id
source_entity_version_id
source_phase_template_id
source_tournament_template_id

El propósito es responder a:
¿Este recurso fue creado originalmente por este usuario o proviene de otro recurso?
La relación de clonación no convierte la copia en una referencia viva.
Después de la clonación:
Original
    │
    └──── procedencia ────> Copia

pero:

Original ≠ Copia

Cada recurso mantiene su propia identidad.

29. CommunityCloneService
La lógica compleja de clonación está centralizada en CommunityCloneService.
El servicio actualmente supera ampliamente el tamaño de un CRUD simple y administra operaciones como:
cloneEntity()
cloneCollection()
cloneAttribute()
cloneOption()

El servicio comprueba primero si el recurso puede clonarse y utiliza transacciones para las operaciones compuestas. También conserva los campos source_*, duplica archivos públicos cuando corresponde, incrementa clones_count y registra la interacción.
Una colección, por ejemplo, puede necesitar clonar no solamente su fila principal, sino también las entidades relacionadas.
Esto demuestra por qué la clonación no debería implementarse dentro de un Controller.

30. Registro de interacciones comunitarias
Existe una tabla:
community_interactions

que registra información como:
user_id
content_type
content_id
interaction_type
metadata
timestamps

Actualmente CommunityCloneService escribe esta información directamente utilizando:
DB::table('community_interactions')->insert(...)

en lugar de emplear un modelo Eloquent específico.
Esto es completamente válido mientras la tabla funcione principalmente como log simple.
Sin embargo, si posteriormente se agregan:
estadísticas comunitarias;
historial de actividad;
recomendaciones;
feeds;
analítica;
reacciones diferentes;
sería conveniente introducir una abstracción específica, por ejemplo:
CommunityInteraction

o un servicio de eventos comunitarios.
No es una necesidad inmediata, pero constituye un punto natural de evolución.

31. Concurrencia en la generación de códigos
Una consideración técnica importante observada en varios Services es el uso de:
DB::transaction(...)

junto con:
lockForUpdate()

para bloquear temporalmente al usuario antes de determinar el siguiente número secuencial.
El problema que se intenta evitar es el siguiente.
Supóngase que dos solicitudes se ejecutan exactamente al mismo tiempo:
Solicitud A:
último sequence_number = 15

Solicitud B:
último sequence_number = 15

A calcula 16
B calcula 16

Sin bloqueo podrían intentar crear:
ENT000016
ENT000016

El patrón utilizado transforma conceptualmente la operación en:
BEGIN TRANSACTION

LOCK USER

leer última secuencia
    ↓
generar siguiente número
    ↓
crear recurso

COMMIT

UNLOCK

Esto es una muy buena consideración de integridad de datos.
Debería convertirse en una convención general para todos los nuevos recursos que utilicen secuencias locales por usuario.

32. EntityBuilderService
EntityBuilderService administra operaciones complejas relacionadas con la construcción de entidades.
Entre sus responsabilidades se encuentran:
creación individual;
creación masiva;
actualización;
generación de secuencias;
coordinación con valores de atributos;
transacciones;
estrategias ante duplicados.
La creación utiliza una transacción y un bloqueo lockForUpdate() sobre el usuario antes de calcular la siguiente secuencia. En creación masiva puede reservar secuencias consecutivas dentro de una misma operación.
Esto resulta especialmente importante en funcionalidades masivas, ya que crear cien entidades mediante cien flujos desconectados produciría mayor riesgo de inconsistencia.

33. Evolución hacia versionado
El 10 de agosto el modelo de backend experimentó una ampliación importante mediante la incorporación de:
versions
entity_versions
version_catalog_links
entity_version_attributes
entity_version_attribute_values
entity_version_images
entity_presentations
attribute_context_...
entity_base_versions

Esta etapa introdujo una distinción fundamental:
Entity

no equivale necesariamente a:
estado actual único de la entidad

Ahora una entidad puede poseer diferentes manifestaciones.

34. Entity frente a EntityVersion
Conceptualmente:
Entity
Naruto Uzumaki

constituye la identidad principal.
Mientras:
EntityVersion
├── Naruto niño
├── Naruto Shippuden
├── Naruto Modo Sabio
├── Naruto Kurama
└── Naruto Hokage

representa estados o versiones concretas.
Esta distinción será crítica en el futuro.
Un torneo podrá necesitar:
Entidad:
Naruto

pero un Universo o contexto podría determinar:
Versión:
Naruto Modo Sabio

Sin esta separación sería necesario duplicar completamente la entidad cada vez que cambie de estado.

35. Herencia de atributos entre versiones
VersionResolverService posee lógica destinada a resolver la versión apropiada y calcular atributos efectivos.
El esquema de resolución contempla conceptualmente:
Base
   ↓
Versiones padre
   ↓
Versión actual

Los valores más específicos pueden complementar o sobrescribir información heredada.
Esto crea una jerarquía útil.
Ejemplo:
Naruto Base
├── Aldea = Konoha
├── Clan = Uzumaki
└── Sexo = Masculino

Naruto Modo Sabio
├── hereda Aldea
├── hereda Clan
├── hereda Sexo
└── agrega Modo = Sabio

No es necesario volver a almacenar cada propiedad común.

36. EntityBaseVersion
EntityBaseVersion contiene una asociación explícita entre:
user_id
entity_id
entity_version_id

Su función es indicar qué versión debe actuar como base activa de una entidad.
Esto debe diferenciarse de la presentación pública.
La base activa responde conceptualmente a:
¿Qué versión constituye actualmente el estado base efectivo?

37. EntityPresentation
EntityPresentation responde a otro problema.
Contiene:
user_id
entity_id
mode
entity_version_id
entity_version_image_id
use_version_name
use_version_description

Su responsabilidad es:
¿Cómo debe presentarse públicamente la entidad?
Por tanto:
EntityBaseVersion

y:
EntityPresentation

no son equivalentes.
Una entidad podría tener como base activa una versión determinada y elegir otra configuración de presentación.
Esta separación es arquitectónicamente saludable porque evita mezclar:
estado lógico

con:
representación


38. Resolución pública de entidades
El modelo Entity ya incorpora accesores que resuelven nombre, descripción e imagen pública teniendo en cuenta la presentación configurada.
La imagen pública, por ejemplo, puede proceder de:
la entidad base;
una versión;
una imagen multimedia específica de una versión.
Esto permite que las capas superiores consulten:
publicName
publicDescription
publicImage

sin tener que reconstruir manualmente la lógica de presentación cada vez.

39. VersionCatalogLink
La existencia de version_catalog_links permite vincular versiones con opciones de catálogo.
Conceptualmente puede utilizarse para inferir una versión a partir del contexto.
Ejemplo:
Modo = Sabio
        ↓
Naruto Modo Sabio

o:
Etapa = Adulto
        ↓
Versión adulta

De esta forma las versiones no necesitan seleccionarse siempre de manera manual.
VersionResolverService puede participar en esa resolución contextual.

40. Importancia futura del sistema de versiones
El sistema de versiones será una de las piezas de conexión entre:
Biblioteca
Universo
Torneo
Simulación

Una futura simulación no debería preguntarse simplemente:
¿Quién es Naruto?

sino potencialmente:
¿Quién es Naruto
en este Universo,
en esta temporada,
en esta fecha,
bajo este contexto?

La respuesta podría ser una EntityVersion.
Por ello el versionado no debe considerarse únicamente una función visual.
Es infraestructura de dominio.

41. Arquitectura de Torneos
A partir del 12 de agosto aparece el dominio actualmente más complejo del backend.
La primera versión introdujo:
tournament_templates
tournament_phases

Posteriormente el diseño evolucionó y aparecieron:
phase_templates
phase_exits

seguido de configuraciones especializadas para:
Single Elimination
Round Robin
Group Stage
Swiss

y finalmente las tablas del:
Tournament Graph

Esta evolución resulta especialmente importante porque demuestra que el modelo inicial de “torneo con fases ordenadas” fue sustituido progresivamente por una arquitectura más flexible.

42. TournamentTemplate
TournamentTemplate representa un diseño reutilizable de torneo.
No debe confundirse con una futura ejecución real del torneo.
Conceptualmente:
TournamentTemplate
"Formato Copa Mundial"

describe:
cómo está estructurada la competición

Mientras una futura:
TournamentInstance
"Copa Mundial Temporada 2028"

describiría:
una ejecución específica con participantes y resultados reales

Actualmente TournamentTemplate mantiene relaciones tanto con la estructura inicial TournamentPhase como con el nuevo sistema de grafo:
TournamentPhaseNode
TournamentPhaseConnection
TournamentStart
TournamentTerminal

Esta coexistencia es consecuencia directa de la evolución reciente del backend.

43. TournamentPhase como estructura inicial
La primera foundation de Torneos utilizó TournamentPhase directamente asociada al TournamentTemplate.
Este enfoque permite pensar:
Tournament
├── Phase 1
├── Phase 2
└── Phase 3

Sin embargo, la documentación arquitectónica posterior identificó una limitación:
una fase no debería estar definida exclusivamente dentro de un torneo si se pretende reutilizarla.
La documentación actual ya señala que la antigua estructura tournament_phases no debería continuar creciendo como si fuera la definición reutilizable definitiva de fases.
Esto constituye una transición arquitectónica todavía visible en el código.

44. PhaseTemplate
PhaseTemplate resuelve el problema anterior.
Representa una fase independiente y reutilizable.
Ejemplos:
Eliminación directa estándar
Liga todos contra todos
Grupos de cuatro
Swiss siete rondas

Su modelo contiene información como:
user_id
source_phase_template_id
sequence_number
code
name
slug
description
image

phase_type
participant_mode

min_participants
max_participants
exact_participants
participant_multiple

allow_byes
best_of

status
visibility
allow_cloning

views_count
clones_count
published_at

settings
metadata

Además utiliza SoftDeletes.
Esta estructura sigue los mismos principios introducidos previamente en la Biblioteca:
propiedad
identidad
procedencia
publicación
clonación
configuración


45. Contrato de participantes de una fase
Una PhaseTemplate no solamente indica “qué tipo de fase es”.
También define su contrato de participantes.
Conceptos como:
min_participants
max_participants
exact_participants
participant_multiple
allow_byes

permiten saber qué entradas acepta una fase.
Esto será fundamental para validar posteriormente conexiones de grafos.
Por ejemplo:
Fase A produce 16 participantes
          ↓
Fase B acepta exactamente 8

debería detectarse como incompatibilidad si no existe una regla intermedia que seleccione ocho.

46. Tipos actuales de fase
Los Requests actuales contemplan, entre otros, los siguientes tipos:
SINGLE_ELIMINATION
ROUND_ROBIN
GROUP_STAGE
LEAGUE
SWISS
CUSTOM

y modos de participantes como:
INDIVIDUAL
TEAM
FLEXIBLE

La validación también contempla límites de participantes y opciones como best_of.
Esta decisión deja espacio para motores adicionales sin obligar a modificar completamente el concepto de PhaseTemplate.

47. PhaseTemplateService
PhaseTemplateService constituye actualmente uno de los Services de dominio más grandes.
Entre sus responsabilidades están:
previsualizar códigos;
crear;
actualizar;
normalizar contratos;
gestionar imágenes;
duplicar;
clonar configuraciones internas;
archivar;
eliminar;
copiar exits;
copiar configuraciones de cada motor competitivo.
El servicio utiliza transacciones y bloqueo de usuario para mantener la secuencia de códigos consistente.
El tamaño actual del servicio muestra que el dominio está creciendo rápidamente.
No significa que el diseño sea incorrecto, pero sí indica que en el futuro convendrá dividir determinadas responsabilidades.
Una posible evolución sería:
PhaseTemplateService
├── PhaseTemplateCreator
├── PhaseTemplateUpdater
├── PhaseTemplateDuplicator
├── PhaseTemplateContractNormalizer
└── PhaseTemplateMediaService

No resulta necesario realizar esa refactorización prematuramente, pero sí debe evitarse que el mismo archivo termine acumulando indefinidamente todos los comportamientos futuros.

48. PhaseExit
PhaseExit constituye uno de los conceptos arquitectónicos más importantes del backend de torneos.
Una fase necesita describir quién sale de ella.
Ejemplos:
WINNER
LOSER
QUALIFIED
ELIMINATED
TOP_N
BOTTOM_N
THIRD_PLACE

Pero no debe decidir hacia dónde continúa ese resultado.
La regla correcta es:
PHASE
  ↓
produce
  ↓
PhaseExit

y posteriormente:
TOURNAMENT GRAPH
  ↓
decide dónde conectar esa salida

La documentación interna expresa justamente ese principio: una salida identifica competidores, no destinos, y la fase no debería conocer cuál es su fase siguiente.

49. Razón de desacoplar PhaseExit del destino
Supóngase una fase de eliminación simple.
Podría producir:
WINNER
LOSER

En un torneo:
WINNER → Semifinal
LOSER → Eliminado

En otro:
WINNER → Main Bracket
LOSER → Lower Bracket

Y en otro:
WINNER → Final
LOSER → Third Place Match

Si PhaseTemplate almacenara directamente el destino, dejaría de ser reutilizable.
La separación actual permite utilizar exactamente la misma fase en contextos completamente distintos.

50. Motores competitivos especializados
Una decisión particularmente importante del backend es no almacenar toda la configuración competitiva dentro de un gigantesco JSON.
En su lugar, cada familia competitiva dispone progresivamente de tablas, Models y Services especializados.
Esta dirección debería conservarse.

51. Single Elimination
El motor de eliminación simple dispone actualmente de componentes como:
PhaseSingleEliminationSetting
PhaseSingleEliminationRoundRule

SingleEliminationSettingsService
SingleEliminationRoundRuleService
SingleEliminationBracketCalculator
SingleEliminationValidator

La división permite separar:
configuración
reglas por ronda
cálculo de bracket
validación

en lugar de concentrarlos dentro del Controller.

52. Round Robin
Round Robin dispone de:
PhaseRoundRobinSetting
PhaseRoundRobinTiebreaker

RoundRobinSettingsService
RoundRobinTiebreakerService
RoundRobinScheduleCalculator
RoundRobinRankingDefinitionService
RoundRobinValidator

La incorporación de desempates como estructura propia es particularmente correcta.
En una liga real, “todos contra todos” describe solamente la generación de encuentros. No define necesariamente:
cómo puntúa una victoria
cómo se ordena la tabla
cómo se resuelve un empate

Estas preocupaciones necesitan configuración separada.

53. Group Stage
Group Stage dispone de componentes como:
PhaseGroupStageSetting
PhaseGroupStageGroup
PhaseGroupStageTiebreaker
PhaseGroupStageAdvancementRule

y Services como:
GroupStageAllocator
GroupStageAdvancementCalculator
GroupStageDefinitionService
GroupStageGroupService
GroupStagePreviewService
GroupStageSettingsService
GroupStageTiebreakerService
GroupStageValidator

Esta arquitectura reconoce que una fase de grupos contiene varios problemas diferentes:
¿Cómo se crean los grupos?
¿Cómo se distribuyen participantes?
¿Cómo se juega internamente?
¿Cómo se ordenan?
¿Quiénes clasifican?

No deberían resolverse todos mediante una única función.

54. Swiss
El motor Swiss es actualmente uno de los dominios algorítmicos más completos.
Dispone de elementos como:
PhaseSwissSetting
PhaseSwissTiebreaker
PhaseSwissAdvancementRule
PhaseSwissRoundRule

y Services:
SwissDefinitionService
SwissSettingsService
SwissTiebreakerService
SwissRoundRuleService
SwissAdvancementRuleService
SwissPairingCalculator
SwissRecordMapService
SwissAdvancementForecastService
SwissPreviewService
SwissValidator

La existencia de SwissPairingCalculator separado de la persistencia es especialmente importante.
El emparejamiento Swiss es un algoritmo de dominio, y por ello debe poder probarse independientemente de una petición HTTP.

55. Principio para nuevos motores competitivos
Los motores futuros deberían conservar el mismo patrón.
Por ejemplo, si posteriormente se implementa Double Elimination:
Tournaments/
└── DoubleElimination/
    ├── DoubleEliminationSettingsService
    ├── DoubleEliminationBracketCalculator
    ├── DoubleEliminationValidator
    └── ...

y, solamente si la configuración lo necesita:
phase_double_elimination_settings
phase_double_elimination_...

Lo que debería evitarse es transformar phase_templates.settings en:
{
  "single_elimination": {...},
  "round_robin": {...},
  "group_stage": {...},
  "swiss": {...},
  "double_elimination": {...}
}

hasta convertirlo en una base de datos escondida dentro de una columna JSON.

56. Uso correcto de JSON
OmniMerge utiliza campos como:
settings
metadata
configuration
validation_rules

Esto no es incorrecto.
Los JSON son apropiados para:
metadatos opcionales;
extensiones;
configuraciones poco consultadas;
parámetros experimentales;
información que no necesita relaciones.
Sin embargo, no deberían utilizarse para esconder información central que requiera:
claves foráneas;
índices;
búsquedas frecuentes;
restricciones;
relaciones;
integridad referencial.
Los motores de Torneos están evolucionando correctamente hacia tablas tipadas cuando una configuración adquiere suficiente importancia.

57. El Tournament Graph
El desarrollo más reciente del backend introduce un cambio conceptual fundamental.
El torneo deja de entenderse como:
Fase 1
↓
Fase 2
↓
Fase 3

y pasa a entenderse como:
Grafo dirigido

La documentación interna lo establece explícitamente:
un torneo es un grafo, no una lista.
Esto permite:
bifurcaciones
convergencias
repechajes
múltiples puntos de inicio
múltiples destinos finales
rutas de ganadores
rutas de perdedores
clasificatorios regionales
formatos híbridos


58. Tablas del Tournament Graph
La migración actual crea cinco piezas fundamentales:
tournament_phase_nodes
phase_entry_ports
tournament_starts
tournament_terminals
tournament_phase_connections

Conceptualmente:
TournamentTemplate
│
├── TournamentStart
│
├── TournamentPhaseNode
│    └── PhaseEntryPort
│
├── TournamentPhaseConnection
│
└── TournamentTerminal


59. TournamentPhaseNode
TournamentPhaseNode representa el uso contextual de una PhaseTemplate dentro de un torneo.
Esta distinción es esencial.
PhaseTemplate
"Eliminación simple"

puede utilizarse como:
TournamentPhaseNode #1
"Cuartos de final"

y nuevamente como:
TournamentPhaseNode #2
"Semifinal"

y nuevamente como:
TournamentPhaseNode #3
"Final"

sin duplicar la definición base.
La propia migración describe tournament_phase_nodes como uso contextual de un PhaseTemplate dentro de un TournamentTemplate.
El modelo contiene:
tournament_template_id
phase_template_id
sequence_number
code
name
description
x_position
y_position
status
settings

Los campos:
x_position
y_position

demuestran que la posición del grafo forma parte actualmente del estado persistente del backend.

60. Reutilización real de PhaseTemplate
Esta relación permite:
                   ┌── Node: Cuartos
                    │
PhaseTemplate ──────┼── Node: Semifinal
                    │
                    └── Node: Final

La fase es la definición.
El nodo es la instancia contextual dentro del diseño.
Esta separación sigue exactamente el mismo principio utilizado anteriormente en:
Attribute
vs
EntityAttribute

Es decir:
definición reutilizable
vs
uso contextual

Esta consistencia conceptual es una fortaleza del backend.

61. PhaseEntryPort
Una fase puede necesitar una o varias puertas de entrada.
PhaseEntryPort contiene información como:
tournament_phase_node_id
sequence_number
code
name
description

merge_policy

is_required
accepts_multiple_connections

min_participants
max_participants
exact_participants

sort_order
status
settings

Esto permite expresar casos donde diferentes flujos llegan al mismo nodo.
Ejemplo:
Ganadores de Región A ──┐
                        ├──> Clasificación Global
Ganadores de Región B ──┤
                        │
Invitados ───────────────┘

La puerta de entrada puede determinar si acepta múltiples conexiones y qué política de combinación utiliza.

62. TournamentStart
TournamentStart representa una fuente inicial de participantes.
La migración establece que un torneo puede tener una o muchas fuentes iniciales.
Entre los tipos previstos aparecen conceptos como:
MAIN_POOL
SEEDED_POOL
QUALIFIER_POOL
INVITED_POOL
CUSTOM

Esto significa que el grafo no asume:
todos los participantes entran por un único punto.
Puede existir:
Start A → Clasificatoria
Start B → Invitados directos
Start C → Campeón defensor

y todas estas ramas converger posteriormente.

63. TournamentTerminal
TournamentTerminal representa un destino final.
Conceptualmente:
CAMPEÓN
ELIMINADO
TERCER PUESTO
CLASIFICADO
FINALIZÓ TEMPORADA

No todos los participantes necesariamente terminan por el mismo terminal.
Ejemplo:
Final
├── WINNER → CHAMPION
└── LOSER  → RUNNER_UP

Mientras otras fases podrían enviar:
LOSER → ELIMINATED

La existencia de terminales explícitos hace posible verificar si todos los caminos del torneo terminan correctamente.

64. TournamentPhaseConnection
TournamentPhaseConnection representa la arista del grafo.
La migración establece actualmente dos clases de origen:
START
PHASE_EXIT

y dos clases de destino:
ENTRY_PORT
TERMINAL

Conceptualmente:
SOURCE
   ↓
TournamentPhaseConnection
   ↓
TARGET

Ejemplos:
START → ENTRY_PORT

PHASE_EXIT → ENTRY_PORT

PHASE_EXIT → TERMINAL

Esta solución evita que cada fase conozca directamente a otra fase.

65. Conexiones explícitas en lugar de relaciones polimórficas genéricas
El modelo de conexión utiliza campos explícitos como:
source_type
source_start_id
source_node_id
source_phase_exit_id

target_type
target_entry_port_id
target_terminal_id

junto con información de distribución.
Esta decisión produce más columnas, pero hace que el contrato del grafo sea extremadamente explícito.
Para un dominio crítico como el Tournament Engine, esa claridad puede ser preferible a esconder toda la semántica dentro de relaciones polimórficas genéricas.

66. Allocation Mode
Una conexión no solamente especifica:
A conecta con B

También puede necesitar determinar:
qué proporción de los participantes de A viaja hacia B.
Los Requests actuales contemplan conceptos de distribución como:
TAKE_N
PERCENTAGE

además de otras modalidades de asignación. La validación condiciona incluso cuándo allocation_value resulta obligatorio.
Esto permitirá estructuras como:
TOP 2 → Semifinal
RESTO → Repechaje

o:
50% → Ruta A
50% → Ruta B

sin obligar a que toda salida viaje completa por una única conexión.

67. Integridad referencial del grafo
La migración utiliza estrategias distintas de eliminación según la relación.
Por ejemplo, la relación desde un nodo hacia PhaseTemplate utiliza restrictOnDelete().
Conceptualmente esto protege contra:
eliminar físicamente una PhaseTemplate
que todavía está siendo utilizada
por un TournamentPhaseNode

Mientras otros componentes contextuales del propio torneo pueden utilizar eliminación en cascada al desaparecer su TournamentTemplate.
Esta elección demuestra que las reglas de FK se están empleando como parte de la integridad del dominio, no únicamente como decoración del esquema.

68. Servicios del Tournament Graph
Actualmente existen Services especializados como:
TournamentGraphNodeService
TournamentGraphConnectionService
TournamentGraphEndpointService
TournamentGraphLayoutService
TournamentGraphTopologyService
TournamentGraphValidationService

La separación es correcta porque el grafo contiene problemas muy diferentes.
NodeService
→ administrar nodos

ConnectionService
→ administrar conexiones

EndpointService
→ administrar puntos de entrada/salida

LayoutService
→ disposición

TopologyService
→ estructura matemática del grafo

ValidationService
→ reglas del diseño

Esta es una de las áreas donde la arquitectura del backend ya se parece más a un motor que a una aplicación CRUD.

69. TournamentGraphTopologyService
La topología necesita resolver problemas como:
¿Existe un ciclo?
¿Qué nodos son alcanzables?
¿Qué nodos dependen de otros?
¿Cuál es el orden topológico?

Esto no debería implementarse dentro de una vista ni repetirse en diferentes Controllers.
La existencia de un servicio especializado permite reutilizar el algoritmo tanto para:
validación
ejecución futura
auto-layout
diagnóstico

El propio ValidationService delega en TopologyService la detección de ciclos.

70. TournamentGraphValidationService
El sistema actual no se limita a comprobar que existan registros.
Ya valida reglas semánticas del grafo.
Entre los errores actuales aparecen casos como:
NO_START
NO_PHASE_NODE
NO_TERMINAL

También comprueba, entre otras cosas:
entradas obligatorias sin conexión
múltiples conexiones cuando no están permitidas
salidas sin utilizar
nodos sin salida
terminales sin entrada
ciclos
nodos inaccesibles
conflictos de distribución

La validación de una entrada obligatoria sin conexiones utiliza, por ejemplo:
REQUIRED_ENTRY_UNCONNECTED

Y la detección de ciclos genera:
GRAPH_CYCLE

Esta es una base fundamental para el futuro Competition Engine.

71. Por qué los ciclos están prohibidos actualmente
El grafo actual está conceptualmente orientado a un DAG:
Directed Acyclic Graph

Un ciclo como:
Fase A
   ↓
Fase B
   ↓
Fase C
   ↓
Fase A

implicaría que una ejecución contextual puede regresar a una fase anterior.
El ValidationService lo considera inválido.
Esta decisión simplifica considerablemente la ejecución futura porque permite obtener un orden topológico.
Si algún día OmniMerge necesita formatos verdaderamente cíclicos, deberían modelarse explícitamente como repetición interna de una fase o como nuevos nodos generados durante la ejecución, en lugar de permitir ciclos arbitrarios en el template.

72. Form Requests como frontera de entrada
La carpeta app/Http/Requests ha crecido considerablemente.
Actualmente existen Requests especializados para:
entidades;
tipos;
atributos;
opciones;
colecciones;
versiones;
TournamentTemplate;
PhaseTemplate;
PhaseExit;
Single Elimination;
Round Robin;
Group Stage;
Swiss;
Tournament Graph;
nodes;
entry ports;
starts;
terminals;
connections;
posiciones del grafo.
Esta proliferación es positiva cuando cada Request responde a una operación bien definida.
Evita Controllers llenos de:
$request->validate([...]);

repetidos.

73. Responsabilidades correctas de un FormRequest
En OmniMerge el patrón debería mantenerse así:
FormRequest
│
├── authorize()
├── prepareForValidation()
├── rules()
└── messages()

authorize() responde:
¿Puede este usuario intentar esta operación?
prepareForValidation() responde:
¿Cómo normalizo la entrada?
rules() responde:
¿Qué estructura es válida?
messages() responde:
¿Cómo explico un error de entrada?
La lógica de negocio compleja no debería trasladarse al Request.

74. Policies
Las Policies actuales cubren recursos importantes como:
Entity
EntityType
Attribute
AttributeOption
AttributeGroup
Collection
Version
EntityVersion
PhaseTemplate
TournamentTemplate

Las reglas siguen mayoritariamente un patrón de propiedad.
Por ejemplo, en recursos publicables:
VIEW
propietario O recurso publicado

UPDATE
solamente propietario

DELETE
solamente propietario

Las Policies también consultan si el usuario está activo. En el caso de Entity, determinadas operaciones administrativas pueden comprobar isAdmin().

75. Autenticación no equivale a autorización
Una ruta bajo:
auth

únicamente garantiza:
hay un usuario autenticado

No garantiza:
ese usuario es propietario del recurso

Por eso las Policies siguen siendo necesarias.
Ejemplo:
/authenticated

no debería permitir que:
Usuario B
PUT /entities/5

modifique una entidad cuyo:
user_id = Usuario A

La Policy es la capa que resuelve esa diferencia.

76. Controllers
Los Controllers actuales cumplen principalmente una función de coordinación.
En un flujo ideal:
public function store(StoreSomethingRequest $request)
{
    $resource = $service->create(
        $request->user(),
        $request->validated()
    );

    return ...;
}

Este patrón es preferible a:
public function store(Request $request)
{
    // 300 líneas de reglas,
    // consultas,
    // transacciones,
    // archivos,
    // algoritmos,
    // clonaciones.
}

La mayor parte de los dominios complejos ya está desplazando lógica a Services.

77. Caso especial: TournamentGraphController
TournamentGraphController ya coordina una cantidad considerable de información.
Para mostrar un grafo necesita cargar:
nodes
phase templates
phase exits
entry ports
starts
terminals
connections
validation result
graph payload

El problema no es todavía crítico, pero constituye una señal.
Si el controlador continúa creciendo, una evolución adecuada sería:
TournamentGraphController
       ↓
TournamentGraphPayloadService
       ↓
Graph DTO

De esta manera el Controller no necesitaría conocer los detalles exactos de serialización del grafo.

78. Services como capa de dominio
Actualmente app/Services está organizado por áreas como:
Attributes
Community
Entities
Tournaments
Versions

Esta estructura es más adecuada que una carpeta con decenas de Services sin clasificación.
Debería continuar evolucionando según dominio.
Por ejemplo:
Services/
└── Tournaments/
    ├── Graph/
    ├── SingleElimination/
    ├── RoundRobin/
    ├── GroupStage/
    ├── Swiss/
    └── Competition/

cuando aparezca el Competition Engine.

79. Servicios grandes y cohesión
Durante la revisión se observan algunos Services que ya concentran una cantidad considerable de código:
CommunityCloneService
PhaseTemplateService
EntityBuilderService
VersionResolverService
TournamentGraphValidationService
SwissPairingCalculator

Esto no significa automáticamente que deban dividirse.
El tamaño por sí solo no constituye un error.
La pregunta adecuada es:
¿El archivo sigue resolviendo un único problema coherente?
Si un Service empieza a realizar:
creación
clonación
archivos
validación
transformación
analytics
eventos
serialización

entonces sí existe motivo para subdividirlo.
La siguiente etapa del proyecto debería vigilar especialmente este aspecto.

80. Transacciones
OmniMerge utiliza DB::transaction() en operaciones donde se escriben múltiples registros relacionados.
Esto es esencial.
Ejemplo:
crear PhaseTemplate
+ generar código
+ crear configuración
+ crear exits

No debería terminar en:
PhaseTemplate creado
pero settings fallaron

La transacción busca garantizar:
TODO
o
NADA

El mismo principio se aplica a clonaciones y creación de estructuras complejas.

81. Base de datos y archivos no comparten transacción
Existe una consideración técnica importante.
Una transacción SQL puede revertir:
INSERT
UPDATE
DELETE

pero no puede revertir automáticamente:
Storage::put()

Por ello algunos Services administran la eliminación del archivo almacenado si posteriormente falla la operación de base de datos.
Esta es una forma de compensación manual.
Conforme aumente el número de módulos con imágenes podría resultar conveniente crear una capa común como:
MediaStorageService

que centralice:
store
replace
delete
copy
rollback compensation

No es obligatorio inmediatamente, pero evitaría replicar lógica de manejo de archivos.

82. Códigos secuenciales
Diversos recursos utilizan:
sequence_number
code

El patrón tiene varias ventajas.
id responde:
identidad técnica global

Mientras:
code

puede responder:
identidad pública legible

Ejemplo:
id = 483
code = ENT000027

El código no debería generarse basándose únicamente en:
count() + 1

porque las eliminaciones y concurrencia podrían producir duplicados.
La estrategia actual de:
MAX(sequence_number) + 1

junto con bloqueo dentro de una transacción es considerablemente más segura.

83. Slugs
Los recursos publicables también utilizan slugs.
Un slug responde principalmente a navegación:
naruto-uzumaki

No debería utilizarse como única identidad lógica.
Puede cambiar si cambia el nombre.
Por tanto:
id
code
slug

cumplen funciones diferentes.
id
→ persistencia interna

code
→ identidad legible estable

slug
→ navegación


84. Estados y visibilidad
Muchos modelos utilizan conceptos como:
status
visibility
published_at
allow_cloning

Estos campos no son redundantes.
Por ejemplo:
status = ACTIVE
visibility = PRIVATE

significa algo diferente de:
status = ACTIVE
visibility = PUBLIC
published_at = fecha

Una entidad pública puede necesitar además estar activa y efectivamente publicada antes de ser considerada clonable. La lógica de Entity distingue esas condiciones mediante métodos como isPublished() y canBeCloned().

85. Evitar proliferación futura de strings mágicos
Actualmente muchos estados se representan mediante strings:
ACTIVE
ARCHIVED
DRAFT
PUBLIC
PRIVATE
SINGLE_ELIMINATION
ROUND_ROBIN
START
PHASE_EXIT
ENTRY_PORT
TERMINAL

El sistema funciona correctamente porque los Requests centralizan buena parte de la validación.
Sin embargo, conforme aumente el backend, puede aparecer el problema:
if ($status === 'ACTIVE')

repetido por decenas de archivos.
Una evolución recomendable sería introducir progresivamente PHP Backed Enums.
Ejemplo conceptual:
enum TournamentStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Archived = 'ARCHIVED';
}

No es necesario convertir toda la aplicación de una sola vez.
Podría introducirse primero en los nuevos dominios, especialmente Competition Engine.

86. Rutas
El backend utiliza actualmente:
routes/web.php
routes/auth.php
routes/console.php

y bootstrap/app.php registra principalmente routes/web.php para las rutas web.
El problema es que routes/web.php ha crecido considerablemente debido principalmente al dominio de Torneos.
Actualmente contiene rutas para:
dashboard de torneos;
laboratorio;
TournamentTemplates;
PhaseTemplates;
PhaseExits;
Single Elimination;
Round Robin;
Groups;
Swiss;
Graph;
Nodes;
Entry Ports;
Starts;
Terminals;
Connections.
La sección de Torneos ya está organizada mediante:
Route::prefix('tournaments')
    ->name('tournaments.')

lo cual es correcto.

87. Evolución recomendada de las rutas
Conforme aparezca Competition Engine, convendría dividir la definición de rutas.
Por ejemplo:
routes/
├── web.php
├── auth.php
├── tournaments.php
├── library.php
├── community.php
└── versions.php

No es necesario que todas estas divisiones se hagan inmediatamente.
La prioridad evidente sería:
tournaments.php

debido al tamaño actual del módulo.
Los nombres públicos de las rutas deberían mantenerse.
Es decir, reorganizar internamente no debería romper:
tournaments.templates.show
tournaments.phases.show
...


88. Orden de rutas
La estructura actual también demuestra conciencia sobre un problema frecuente de Laravel:
ruta estática

frente a:
parámetro dinámico

Una ruta:
/tournaments/lab

debe definirse antes de algo como:
/tournaments/{tournamentTemplate}

si existe posibilidad de conflicto.
Este mismo principio deberá mantenerse con nuevas rutas como:
/history
/results
/rankings
/execution


89. Manejo de almacenamiento
Los modelos y Services utilizan el disco:
public

para recursos como:
avatar
entity image
attribute image
attribute option image
phase image
version images

El modelo User, por ejemplo, comprueba que el archivo exista en Storage::disk('public') antes de generar su URL.
Esta comprobación evita devolver URLs hacia archivos que ya no existen físicamente.

90. Regla futura para multimedia
A medida que aumenten:
EntityVersionImage
Tournament media
Universe media
Rewards
Logos
Banners

será recomendable separar dos conceptos:
archivo físico

y:
uso del archivo

En una etapa avanzada podría existir un verdadero Media domain.
No es todavía necesario transformar el proyecto en un DAM completo, pero sí sería conveniente evitar que cada nuevo módulo invente independientemente su propia estrategia de almacenamiento.

91. Pruebas existentes
El proyecto dispone actualmente de pruebas Feature para funcionalidades iniciales como:
Authentication
Profile
EntityType
Entity
Attribute
AttributeOption

Además se han incorporado pruebas unitarias específicas del dominio de Torneos.
Entre ellas existen pruebas para:
SingleEliminationBracketCalculator
RoundRobinScheduleCalculator
GroupStageAllocator
GroupStageAdvancementCalculator
SwissPairingCalculator
SwissRecordMapService
SwissAdvancementForecastService
TournamentGraphTopologyService

Esta es una dirección correcta.
Los algoritmos competitivos son precisamente el tipo de código que debe probarse independientemente de la interfaz.

92. Qué falta en pruebas
La principal brecha actual está entre:
tests unitarios de algoritmos

y:
tests de integración completa del dominio de Torneos

Deberían aparecer progresivamente Feature Tests como:
un usuario no puede modificar la PhaseTemplate de otro

una PhaseTemplate puede duplicarse correctamente

un TournamentTemplate conserva propiedad

un nodo no puede utilizar una PhaseTemplate inválida

una conexión incorrecta es rechazada

un grafo válido pasa validación

un grafo con ciclo falla

un grafo con entrada requerida desconectada falla

el auto-layout no destruye conexiones

archivar un template conserva integridad

Esto será especialmente importante antes de construir el Competition Engine.

93. Pirámide de pruebas recomendada
La estructura futura debería ser aproximadamente:
                pocos
            Feature / E2E
              /       \
       Integration Tests
           /           \
        Unit Tests algorítmicos
              muchos

Los cálculos como:
pairing
ranking
allocation
topology
advancement

deben seguir siendo unitarios siempre que sea posible.
Las reglas que involucran:
DB
Policy
Request
Relationships
Transactions

necesitan pruebas Feature o de integración.

94. Validación estructural frente a validación de dominio
El backend ya comienza a mostrar dos categorías diferentes.
Primera:
Validación de entrada

Ejemplo:
name required
max 120
status in ...

Responsabilidad:
FormRequest

Segunda:
Validación de dominio

Ejemplo:
grafo contiene un ciclo
entrada obligatoria desconectada
allocation conflict

Responsabilidad:
Domain Validator Service

Esta separación debe mantenerse.
No debería intentarse meter toda la validación del Tournament Graph dentro de rules().

95. Mensajes y localización
Muchos mensajes de validación y dominio están actualmente escritos directamente en español dentro de Requests y Services.
Esto es adecuado mientras OmniMerge funcione únicamente en español.
Si posteriormente se desea internacionalización, sería conveniente migrar gradualmente mensajes hacia:
lang/es/
lang/en/

La lógica no debería cambiar; únicamente la fuente del texto.
No es una prioridad de backend inmediata.

96. Fortalezas actuales del backend
Después de revisar la evolución completa del código, las principales fortalezas identificadas son:
Modelo de Entity genérico.
La aplicación no está acoplada a personajes, deportes, países ni ningún dominio específico.
Sistema dinámico de atributos.
Los usuarios pueden definir características sin modificar el esquema central de entities.
Valores tipados.
No todo se almacena como texto.
Catálogos reutilizables y jerárquicos.
AttributeOption posee suficiente estructura para casos complejos.
Separación definición/contexto.
Aparece en Attribute/EntityAttribute y vuelve a aparecer en PhaseTemplate/TournamentPhaseNode.
Procedencia comunitaria.
Los clones mantienen relación con su origen sin convertirse en referencias vivas.
Versionado separado de Entity.
Una entidad puede evolucionar sin perder identidad.
Presentación separada de base activa.
Policies para propiedad.
Form Requests especializados.
Transacciones para operaciones compuestas.
Bloqueo de concurrencia para secuencias.
Services para lógica compleja.
Motores competitivos por dominio.
Tournament Graph desacoplado de PhaseTemplate.
PhaseExit independiente del destino.
Validación topológica del grafo.
Pruebas unitarias de algoritmos competitivos.
Todas estas decisiones preparan el proyecto para una etapa considerablemente más compleja.

97. Deuda técnica actual
El backend también posee puntos que deberían vigilarse.
No todos requieren una refactorización inmediata, pero deben quedar documentados.
97.1. Coexistencia TournamentPhase / TournamentPhaseNode
El sistema antiguo:
TournamentPhase

continúa presente junto al sistema nuevo:
PhaseTemplate
+
TournamentPhaseNode

Esta transición debe resolverse antes de que el Competition Engine dependa de ambas.
El nuevo desarrollo debería orientarse al segundo modelo.

97.2. Services de gran tamaño
Algunos Services están creciendo mucho.
El peligro futuro no es solamente la cantidad de líneas.
Es comenzar a mezclar responsabilidades.
Debe preferirse:
Service coordinador
+
Services especializados

antes que un único MegaService.

97.3. TournamentGraphValidationService creciente
La validación del grafo seguirá aumentando.
Actualmente ya contempla estructura, entradas, salidas, ciclos y distribución.
En el futuro aparecerán reglas como:
capacidades incompatibles
número imposible de participantes
convergencias inválidas
selector incompatible con exit
terminal incorrecto
reglas de tipo de participantes

Una evolución natural sería:
TournamentGraphValidationService
       │
       ├── StructureValidator
       ├── ConnectivityValidator
       ├── CapacityValidator
       ├── AllocationValidator
       ├── ReachabilityValidator
       └── TopologyValidator

y un coordinador que acumule los resultados.

97.4. routes/web.php
Debe evitarse que continúe creciendo indefinidamente.
La separación de rutas por dominio debería realizarse antes o durante la introducción del Competition Engine.

97.5. Strings de estados
Conforme aumenten los estados y tipos, debería considerarse PHP Enums para evitar strings duplicados.

97.6. JSON excesivo
settings y metadata deben seguir siendo extensiones, no sustitutos de tablas de dominio.

97.7. Cobertura de integración
Torneos necesita más Feature Tests antes de ejecutar competiciones reales.

98. Arquitectura futura: Competition Engine
La siguiente gran frontera del backend no debería ser simplemente añadir más diseñadores.
Debe aparecer una separación clara entre:
DISEÑO

y:
EJECUCIÓN

Actualmente existen:
TournamentTemplate
PhaseTemplate
TournamentPhaseNode
TournamentPhaseConnection

Todos ellos describen cómo debería funcionar una competición.
Todavía será necesario representar:
una competición que está ocurriendo realmente.

99. TournamentTemplate frente a TournamentInstance
La separación futura debería ser:
TournamentTemplate
        │
        │ se instancia
        ▼
TournamentInstance

Ejemplo:
TournamentTemplate:
"Copa de 32 participantes"

Puede producir:
TournamentInstance:
"Copa Ninja 2027"

y:
TournamentInstance:
"Copa Pokémon Temporada 15"

El template es reutilizable.
La instancia es histórica.

100. Inmutabilidad histórica
Una consideración crítica será evitar que una competición pasada cambie si posteriormente se modifica el template.
Supóngase:
2027
Torneo ejecutado con Template V1

Después:
2028
Usuario modifica Template

Los resultados de 2027 no deberían reinterpretarse con las nuevas reglas.
Por ello la propia planificación futura del proyecto contempla snapshots o revisiones de TournamentTemplate para preservar ejecuciones históricas.
Una estrategia podría ser:
TournamentTemplate
       ↓
TournamentTemplateRevision
       ↓
TournamentInstance

o:
TournamentInstance
└── template_snapshot JSON

La primera opción ofrece mayor estructura.
La segunda es más sencilla.
Debe decidirse antes de construir el runtime definitivo.

101. Participante abstracto
Otro principio futuro importante es que un torneo no debería depender directamente de Entity.
Un participante podría provenir de:
ENTITY
MOCK
UNIVERSE_COMPETITOR
TEAM
EXTERNAL

La documentación futura del proyecto ya contempla esta abstracción.
Conceptualmente:
TournamentParticipant
├── source_type
├── source_id
├── display_name_snapshot
├── image_snapshot
└── metadata_snapshot

Esto permitiría ejecutar el Competition Lab sin necesidad de universos reales.

102. Posibles entidades del Competition Engine
Una arquitectura futura razonable podría incorporar:
TournamentInstance
TournamentParticipant

PhaseInstance
RoundInstance

Match
MatchParticipant
MatchResult

Standing
Advancement

No todos necesitan existir desde el primer Sprint.
La implementación debería hacerse incrementalmente.

103. Flujo futuro de ejecución
Un flujo conceptual podría ser:
TournamentInstance
        ↓
cargar snapshot del grafo
        ↓
registrar participantes
        ↓
resolver Start
        ↓
alimentar EntryPorts
        ↓
crear PhaseInstance
        ↓
generar Rounds/Matches
        ↓
resolver resultados
        ↓
calcular PhaseExit
        ↓
resolver Connections
        ↓
alimentar próximos Nodes
        ↓
alcanzar Terminals
        ↓
Tournament Completed

Esta arquitectura reutilizaría todo el trabajo actual del Tournament Graph.

104. El Competition Engine no debe calcular quién gana
El torneo debería controlar:
estructura competitiva
emparejamientos
rondas
clasificación
avance

pero no debería decidir arbitrariamente:
Naruto vence a Sasuke porque tiene más Chakra

Ese problema pertenece a otro dominio.
Por ello la arquitectura futura del proyecto separa Tournament Engine y Simulation Engine.

105. MatchResultProvider
Una abstracción futura especialmente conveniente sería:
interface MatchResultProvider
{
    public function resolve(Match $match): MatchResult;
}

Podrían existir implementaciones:
ManualResultProvider
RandomResultProvider
SimulationResultProvider
ExternalResultProvider

Así:
Competition Engine
        ↓
solicita resultado
        ↓
MatchResultProvider

No necesita saber cómo se produjo el resultado.

106. Competition Lab antes que Simulation Engine
Esta separación permite desarrollar completamente el sistema competitivo utilizando:
participantes mock
+
resultados random

antes de construir el motor de simulación.
Eso es estratégicamente conveniente.
Primero se demuestra:
el torneo funciona

Después se conecta:
cómo se decide el ganador

La planificación actual del proyecto también sugiere estabilizar el motor competitivo mediante un laboratorio antes de conectarlo con Universos y Simulación.

107. Eventos de dominio futuros
Cuando el Competition Engine aparezca, será conveniente introducir eventos.
Por ejemplo:
TournamentStarted
PhaseStarted
RoundStarted
MatchCreated
MatchResolved
ParticipantAdvanced
PhaseCompleted
TournamentCompleted

Estos eventos permitirían desacoplar funcionalidades como:
estadísticas
historial
logros
recompensas
notificaciones
rankings

del núcleo que ejecuta el torneo.
Ejemplo:
MatchResolved
      │
      ├── actualizar avance
      ├── estadísticas
      ├── historial
      └── recompensas

sin que MatchService tenga que realizar directamente todas esas tareas.

108. Idempotencia futura
Una operación crítica como:
resolveMatch()

debería ser idempotente o estar correctamente protegida.
El problema sería:
Petición 1 resuelve Match #8
Petición 2 simultánea resuelve Match #8

y ambas generan avances.
El backend deberá utilizar estrategias como:
DB transaction
row lock
estado previo
unique constraints

para garantizar que un resultado no se procese dos veces.

109. Estados del runtime
Las instancias futuras deberían utilizar una máquina de estados explícita.
Ejemplo:
DRAFT
READY
RUNNING
PAUSED
COMPLETED
CANCELLED

Y una fase:
PENDING
READY
RUNNING
COMPLETED

Un Match:
PENDING
READY
IN_PROGRESS
COMPLETED
VOID

No debería permitirse cualquier transición arbitraria.
Por ejemplo:
COMPLETED → RUNNING

probablemente debería ser inválida salvo operación administrativa especial.

110. Arquitectura futura de Universos
Una vez estabilizado Competition Engine, el siguiente gran dominio podrá ser Universo.
El Universo no debería reemplazar la Biblioteca.
La relación conceptual debería ser:
LIBRARY
Entity
    ↓
UNIVERSE
UniverseCompetitor
    ↓
SEASON
    ↓
TournamentInstance

La Biblioteca contiene recursos canónicos.
El Universo los contextualiza.

111. UniverseCompetitor
Un futuro UniverseCompetitor podría responder:
¿Quién participa dentro de este Universo?

y almacenar información contextual como:
universe_id
entity_id
active_entity_version_id
status
joined_at
retired_at
metadata

Así, eliminar un competidor del Universo no implica eliminar la Entity de la Biblioteca.

112. Season
Una futura Season proporcionaría dimensión temporal.
Ejemplo:
Universo Naruto
├── Temporada 1
├── Temporada 2
└── Temporada 3

Cada temporada podría:
activar diferentes competidores;
seleccionar versiones;
organizar torneos;
acumular rankings;
producir historial.
Esto no debería mezclarse directamente dentro de TournamentTemplate.

113. UniverseTournament
Una configuración intermedia podría conectar:
Universe
+
TournamentTemplate

y especificar:
reglas de elegibilidad
frecuencia
temporada
selección de participantes
ranking requerido

Posteriormente esa configuración produciría un:
TournamentInstance


114. Simulation Engine
El Simulation Engine constituiría otro dominio independiente.
Conceptualmente:
SimulationEngine
     ↓
recibe participantes contextualizados
     ↓
obtiene atributos efectivos
     ↓
obtiene versiones
     ↓
aplica reglas
     ↓
utiliza aleatoriedad controlada
     ↓
produce resultado

Su salida debería ser entendible por el Competition Engine sin que este necesite conocer la fórmula interna.

115. Aleatoriedad reproducible
Cuando aparezcan simulaciones, una consideración importante será utilizar seeds.
En lugar de:
resultado completamente imposible de reproducir

podría almacenarse:
random_seed = 736281

De esta manera:
mismos participantes
+
mismas reglas
+
misma seed
=
mismo resultado

Esto facilitaría:
debugging;
reproducción de errores;
auditoría;
tests;
simulaciones comparables.

116. Procesamiento mediante Queue
Las simulaciones grandes podrían requerir ejecutar:
100
1 000
10 000

matches.
Ese tipo de procesamiento no debería realizarse dentro de una petición HTTP que espere hasta finalizar.
Laravel ya dispone de infraestructura de Jobs/Queues en el proyecto base.
Una etapa futura podría introducir:
RunTournamentJob
RunPhaseJob
ResolveMatchJob
GenerateStatisticsJob

No debería hacerse antes de que el motor síncrono esté estable.

117. Estadísticas e historial
Cuando existan competiciones reales, el sistema tendrá que distinguir:
estado actual

de:
historial

No debería calcularse toda la historia consultando solamente el estado final.
Será conveniente conservar eventos o resultados históricos.
Ejemplo:
Match #1
Naruto 1 - Sasuke 0

Match #2
Naruto 0 - Kakashi 1

Entonces:
ranking
estadísticas
racha
victorias
derrotas

pueden derivarse de datos históricos verificables.

118. Auditoría futura
Para operaciones críticas podría incorporarse un sistema de auditoría.
Ejemplo:
Quién:
Usuario 5

Qué:
modificó resultado del Match 87

Antes:
Naruto winner

Después:
Sasuke winner

Cuándo:
2027-04-05 19:31

Esto adquirirá importancia si OmniMerge permite torneos compartidos o competitivos entre varios usuarios.

119. Rendimiento
El modelo dinámico de OmniMerge es flexible, pero puede producir una gran cantidad de relaciones.
Una entidad podría tener:
30 atributos

cada uno con:
varios valores

y además:
versiones
imágenes
contextos
colecciones

Por ello el backend deberá continuar utilizando eager loading selectivo.
Debe evitarse:
foreach ($entities as $entity) {
    $entity->entityAttributes;
    ...
}

si produce consultas N+1.
El Tournament Graph ya carga relaciones complejas explícitamente desde su Controller antes de construir el payload.

120. Índices de base de datos
Conforme el volumen crezca, será necesario revisar los índices en función de consultas reales.
Campos candidatos típicos:
user_id
status
visibility
published_at
source_*_id
entity_id
attribute_id
tournament_template_id
phase_template_id
sequence_number
code

No deberían agregarse índices indiscriminadamente.
Cada índice mejora determinadas lecturas pero incrementa costo de escritura y almacenamiento.
La optimización debería basarse en:
EXPLAIN
query logs
profiling

cuando exista volumen suficiente.

121. Constraints
La integridad no debería depender únicamente de PHP.
Siempre que sea posible deben conservarse:
foreign keys
unique constraints
not null
cascade/restrict

directamente en la base de datos.
El Tournament Graph ya utiliza esta filosofía en varias relaciones.
La base de datos constituye una última línea de defensa frente a estados imposibles.

122. Migraciones incrementales
El historial del repositorio demuestra que OmniMerge ha utilizado migraciones incrementales.
No existe una única migración gigante que reemplace continuamente el esquema.
Esto debe conservarse.
En desarrollo futuro:
NO:
editar una migración antigua ya utilizada para fingir que siempre fue así

Preferible:
crear nueva migración

Ejemplo:
2026_08_20_add_revision_id_to_tournament_instances.php

Esto permite reconstruir el proceso histórico de evolución de la base de datos.

123. Evitar migrate:fresh como estrategia de evolución
migrate:fresh puede ser útil durante pruebas o reinicios deliberados de entornos locales.
Pero no debería convertirse en la estrategia normal para modificar OmniMerge.
Conforme existan:
bibliotecas reales
entidades
catálogos
torneos
universos
historial

los datos adquirirán muchísimo valor.
La evolución debe preservar información.

124. Datos derivados frente a datos fuente
Una regla importante para los siguientes dominios será distinguir:
dato fuente

de:
dato calculado

Ejemplo:
MatchResult

es un dato fuente histórico.
Mientras:
victorias totales

puede ser derivado.
Si una estadística es barata de calcular, podría obtenerse dinámicamente.
Si es costosa y consultada constantemente, podría materializarse y reconstruirse cuando sea necesario.

125. DTOs y payloads
Conforme aumente el Competition Engine, no será conveniente pasar enormes arrays sin contrato entre capas.
Podrían introducirse progresivamente objetos como:
TournamentGraphData
MatchResolutionData
ParticipantSnapshotData
PhaseExecutionResult

Esto aportaría:
tipos claros;
autocompletado;
contratos explícitos;
pruebas más fáciles.
No es necesario introducir DTO para cada formulario CRUD.
Deberían utilizarse donde exista verdadera complejidad de dominio.

126. API futura
Actualmente OmniMerge funciona principalmente mediante rutas web tradicionales.
Eso es apropiado.
No es necesario crear una API REST para cada modelo simplemente porque pueda hacerse.
Una API adquiriría sentido cuando aparezca alguno de estos consumidores:
aplicación móvil
frontend separado
integraciones externas
bots
servicios de simulación
API pública

Entonces debería crearse una frontera específica:
routes/api.php
API Controllers
API Resources
versionado API
tokens
rate limits

sin obligar a los Controllers web actuales a servir simultáneamente todos los formatos.

127. Separación futura por dominios
Con el crecimiento previsto, una estructura eventual podría ser:
app/
├── Domains/
│   ├── Library/
│   ├── Community/
│   ├── Versions/
│   ├── Tournaments/
│   ├── Competition/
│   ├── Universes/
│   └── Simulation/
│
└── Http/

Sin embargo, no recomiendo realizar ahora una migración masiva hacia esta estructura.
La estructura actual:
Models
Services
Policies
Requests
Controllers

todavía es manejable.
Lo importante es mantener organización interna por dominio donde la complejidad realmente lo exige.
Cambiar toda la arquitectura solamente por estética generaría mucho riesgo sin producir funcionalidad.

128. Principio de evolución arquitectónica
La arquitectura debería evolucionar según dolor real.
Ejemplo:
PhaseTemplateService demasiado grande
       ↓
identificar responsabilidades
       ↓
extraer una de ellas
       ↓
mantener API estable

y no:
"Microservicios son modernos"
       ↓
reescribir todo OmniMerge

El proyecto todavía se beneficia enormemente de continuar como un monolito modular Laravel.

129. Por qué no usar microservicios todavía
OmniMerge tiene múltiples dominios, pero todos comparten actualmente:
usuarios
identidades
entidades
atributos
versiones
torneos

Separarlos físicamente demasiado pronto implicaría:
comunicación de red
autenticación distribuida
eventual consistency
deploys separados
observabilidad distribuida
duplicación de contratos

sin una necesidad real.
Un monolito bien modularizado permite desarrollar más rápido y mantener transacciones locales.

130. Seguridad futura
Además de Policies, conforme se incorporen funciones públicas deberían considerarse:
rate limiting
validación de uploads
límites de tamaño
MIME real
protección de endpoints masivos
throttling para clonación
protección de simulaciones costosas

También será importante evitar utilizar campos enviados por el usuario para asignar directamente:
user_id
owner_id
source_id sensibles
status administrativos

cuando esos valores deban determinarse desde el contexto autenticado.

131. Mass Assignment
Los modelos actuales utilizan $fillable.
Esta práctica debería mantenerse.
Los Services deberían controlar explícitamente qué datos terminan en:
Model::create(...)

No debería utilizarse:
$model->fill($request->all());

en dominios complejos.
Preferible:
validated()
+
transformación del Service


132. Validación de propiedad de relaciones
Un riesgo típico de sistemas multiusuario ocurre cuando un usuario posee Entity A pero envía:
attribute_id

perteneciente a Usuario B.
Validar solamente:
'exists:attributes,id'

no siempre es suficiente.
En operaciones sensibles deberá comprobarse:
el atributo existe
Y
pertenece al usuario
o está permitido por la regla comunitaria correspondiente

El mismo principio se aplicará a:
PhaseTemplate
TournamentTemplate
Universe
Version


133. Modelo de propiedad futuro
Actualmente:
User
  ↓ owns
Resource

es suficiente.
Si OmniMerge incorpora equipos o colaboración, posiblemente será necesario evolucionar hacia:
Owner
├── User
└── Team

Pero introducirlo hoy produciría complejidad innecesaria.
La arquitectura debería permanecer orientada al propietario individual hasta que exista un requerimiento concreto de colaboración.

134. Código backend y convenciones de idioma
La convención actual predominante es apropiada:
Código:
inglés

Modelos:
inglés

Tablas:
inglés

Métodos:
inglés

Interfaz y mensajes:
español

Ejemplos:
TournamentTemplate
PhaseTemplate
EntityVersion
AttributeOption

y no:
PlantillaTorneo
PlantillaFase
VersionEntidad

Esta regla debería mantenerse para conservar compatibilidad con las convenciones del ecosistema Laravel.

135. Comentarios internos
El código actual posee muchos bloques de comentarios descriptivos.
Son útiles especialmente en Services complejos.
Sin embargo, conforme el proyecto madure, los comentarios deberían explicar principalmente:
POR QUÉ

y no únicamente:
QUÉ

Ejemplo útil:
Se bloquea el usuario para evitar que dos solicitudes
generen simultáneamente la misma secuencia.

Es mejor que:
Bloquear usuario.

porque explica la razón arquitectónica.

136. Determinismo de algoritmos
Los calculadores de Torneos deberían mantenerse lo más puros posible.
Idealmente:
Input
  ↓
Calculator
  ↓
Output

sin efectos laterales innecesarios.
Ejemplo:
participants + settings
        ↓
SwissPairingCalculator
        ↓
pairings

Esto facilita:
unit testing;
reproducción;
comparación;
optimización.
La persistencia debe situarse alrededor del algoritmo, no necesariamente dentro de él.

137. Separar Definition, Calculation y Persistence
Los motores de Torneos ya apuntan a una separación útil:
Definition Service
→ interpreta configuración

Calculator
→ calcula

Persistence Service
→ guarda cambios

Validator
→ comprueba reglas

Este patrón debería convertirse en referencia para Simulation Engine.
Por ejemplo:
SimulationDefinition
SimulationCalculator
SimulationResult
SimulationValidator


138. Competition Engine como consumidor del Tournament Graph
El Graph actual debe verse como el programa declarativo del torneo.
TournamentTemplate
+
Graph

dice:
esto es lo que debería ocurrir.
Competition Engine será el intérprete.
Conceptualmente:
Tournament Graph
      ↓
Competition Engine
      ↓
Runtime State

Esta distinción es muy similar a:
código fuente
      ↓
runtime

El diseñador no debería contener estados de ejecución como:
match actual
score
winner
round completed

Eso pertenece a TournamentInstance.

139. El grafo como contrato
Antes de ejecutar un torneo debería cumplirse:
GraphValidationResult = valid

Una instancia no debería comenzar a partir de un diseño estructuralmente inconsistente.
El flujo ideal sería:
DRAFT TEMPLATE
      ↓
EDIT
      ↓
VALIDATE
      ↓
PUBLISH / READY
      ↓
SNAPSHOT
      ↓
EXECUTE

Esto evita descubrir errores arquitectónicos en medio de una competición.

140. Versionado de templates
En el futuro sería conveniente distinguir:
editar borrador

de:
alterar template utilizado históricamente

Un posible modelo:
TournamentTemplate
├── revision 1
├── revision 2
└── revision 3

Una TournamentInstance apuntaría a:
revision 2

aunque el usuario ya esté editando revision 3.
La misma idea podría aplicarse eventualmente a PhaseTemplate si las ejecuciones requieren reproducibilidad absoluta.

141. Observabilidad
Cuando Tournament/Simulation Engine comience a ejecutar procesos complejos, será importante registrar eventos técnicos.
Ejemplo:
TournamentInstance 55 started
Node 18 activated
16 participants entered
8 matches generated
Node 18 completed
8 participants routed through Exit QUALIFIED

Esto permitirá diagnosticar problemas sin reconstruir mentalmente toda la ejecución.
Laravel logging es suficiente inicialmente.
No es necesario instalar una plataforma distribuida compleja.

142. Errores de dominio
Para nuevos motores puede resultar conveniente introducir excepciones específicas.
Ejemplo:
InvalidTournamentGraphException
InvalidParticipantCountException
MatchAlreadyResolvedException
PhaseExecutionException

en lugar de utilizar:
Exception

para todos los casos.
Esto permitirá manejar de manera diferente:
error esperado del dominio

y:
error técnico inesperado


143. Estructura futura recomendada para Competition
Una posible organización:
app/Services/Tournaments/Competition/
├── TournamentExecutionService.php
├── TournamentParticipantService.php
├── PhaseExecutionService.php
├── MatchService.php
├── AdvancementService.php
├── StandingService.php
├── RuntimeGraphService.php
└── ResultProviders/
    ├── MatchResultProvider.php
    ├── ManualResultProvider.php
    ├── RandomResultProvider.php
    └── SimulationResultProvider.php

Esta es únicamente una guía arquitectónica.
Los nombres exactos deberían decidirse cuando se diseñen las tablas reales.

144. Reglas que deberían conservarse al desarrollar backend nuevo
A partir del estado actual de OmniMerge, deberían tratarse como reglas del proyecto:
Los recursos principales deben conservar propiedad explícita.
Los Controllers deben coordinar y no convertirse en motores de lógica.
La validación HTTP pertenece a Form Requests.
La autorización pertenece a Policies o a reglas explícitas equivalentes.
Los algoritmos complejos deben vivir en Services o Calculators.
Las operaciones multi-registro deben utilizar transacciones cuando corresponda.
Las secuencias concurrentes deben protegerse.
Los recursos reutilizables no deben conocer su contexto de uso.
PhaseTemplate no debe conocer el siguiente nodo.
PhaseExit identifica participantes, no destinos.
TournamentTemplate describe estructura, no ejecución.
TournamentPhaseNode representa el uso contextual de una fase.
El torneo debe seguir modelándose como grafo.
Competition Engine debe ejecutar el grafo, no rediseñarlo.
Simulation Engine debe resolver enfrentamientos, no administrar brackets.
Universe debe proporcionar contexto, no implementar internamente el Tournament Engine.
La Biblioteca debe continuar siendo la fuente canónica de entidades.
Las versiones deben preservar la identidad de Entity.
Los clones deben ser copias independientes con procedencia.
No debe utilizarse JSON para sustituir relaciones fundamentales.
Los cambios del esquema deben realizarse mediante nuevas migraciones.
La historia de competiciones futuras debe ser inmutable o reproducible.
Los algoritmos críticos deben poseer tests.
La nueva lógica debe respetar el inglés para código y español para interacción de usuario.

145. Prioridades técnicas antes del Competition Engine
Antes de entrar profundamente en ejecución de torneos, recomendaría consolidar los siguientes puntos:
Primero, estabilizar Tournament Graph.
Debe poder representar sin problemas:
Single Elimination
Group → Knockout
Swiss → Playoffs
Double paths
Repechaje
Third place
Múltiples starts
Convergencias
Múltiples terminals

Segundo, ampliar validación de capacidades.
No basta con saber que:
A está conectado a B

También interesa saber:
¿cuántos competidores puede producir A?
¿cuántos puede recibir B?

Tercero, añadir Feature Tests del grafo.
Cuarto, definir claramente la transición TournamentPhase → TournamentPhaseNode.
Quinto, definir el modelo mínimo de TournamentInstance.

146. Elementos que no recomiendo construir todavía
No recomendaría adelantarse a:
microservicios
Kubernetes
event sourcing completo
CQRS completo
GraphQL
repositorios para todos los modelos
Redis obligatorio
Kafka
arquitecturas distribuidas

sin un problema real que lo justifique.
El backend actual todavía puede crecer considerablemente como monolito modular Laravel.
La prioridad debe ser la claridad del dominio.

147. Estado actual del backend por dominio
Dominio
Estado aproximado
Autenticación
Funcional
Usuarios / Perfil
Funcional
Entity Types
Funcional
Entities
Funcional y avanzado
Attributes
Funcional y avanzado
Attribute Options
Funcional y avanzado
Attribute Groups
Funcional
Collections
Funcional
Community
Funcional, con clonación
Entity Versions
Implementado
Presentation
Implementado
Base Version
Implementado
Attribute Context
Implementado
TournamentTemplate
Implementado
PhaseTemplate
Implementado
PhaseExit
Implementado
Single Elimination Definition Engine
Implementado
Round Robin Definition Engine
Implementado
Group Stage Definition Engine
Implementado
Swiss Definition Engine
Implementado
Tournament Graph
Foundation avanzada implementada
Graph Validation
Implementada parcialmente y extensible
Competition Runtime
Pendiente
TournamentInstance
Pendiente
Matches reales
Pendiente
Universes
Pendiente
Seasons
Pendiente
Simulation Engine
Pendiente
Estadísticas globales
Pendiente
Historial competitivo completo
Pendiente


148. Evolución conceptual completa del backend
El backend puede resumirse mediante esta progresión:
1. USER
   │
   ▼
2. LIBRARY
   │
   ├── EntityType
   ├── Entity
   ├── Attribute
   ├── AttributeOption
   ├── AttributeGroup
   └── Collection
   │
   ▼
3. COMMUNITY
   │
   ├── Publicación
   ├── Clonación
   └── Procedencia
   │
   ▼
4. VERSIONING
   │
   ├── EntityVersion
   ├── Effective Attributes
   ├── Presentation
   ├── Active Base
   └── Context Rules
   │
   ▼
5. PHASE ENGINE
   │
   ├── Single Elimination
   ├── Round Robin
   ├── Group Stage
   └── Swiss
   │
   ▼
6. TOURNAMENT GRAPH
   │
   ├── Starts
   ├── Nodes
   ├── Entry Ports
   ├── Connections
   └── Terminals
   │
   ▼
7. COMPETITION ENGINE
   │
   ├── Instances
   ├── Participants
   ├── Matches
   ├── Results
   └── Advancement
   │
   ▼
8. UNIVERSES
   │
   ├── Competitors
   ├── Seasons
   ├── Eligibility
   └── Tournament Instances
   │
   ▼
9. SIMULATION ENGINE
   │
   ├── Context
   ├── Versions
   ├── Attributes
   ├── Rules
   └── Resolution
   │
   ▼
10. HISTORY / STATS / RANKINGS

Esta progresión refleja con claridad hacia dónde está evolucionando OmniMerge.

149. Evaluación general de la arquitectura
El backend actual de OmniMerge tiene una característica especialmente positiva: su arquitectura ha ido evolucionando junto con la comprensión del problema.
No se intentó diseñar desde el primer día una base de datos gigantesca para Universos, Torneos y Simulaciones sin haber comprobado primero las necesidades.
La evolución observada ha sido:
primero almacenar
↓
después estructurar
↓
después reutilizar
↓
después versionar
↓
después conectar
↓
ahora competir
↓
posteriormente ejecutar

Eso genera algunas estructuras transitorias —como TournamentPhase frente a TournamentPhaseNode—, pero es preferible a quedar atrapado en un modelo inicial demasiado rígido.
La clave de las siguientes etapas será consolidar las nuevas abstracciones y retirar gradualmente las antiguas cuando dejen de tener una responsabilidad real.

150. Conclusión
El backend de OmniMerge ha dejado de ser un conjunto de CRUDs independientes.
Actualmente puede entenderse como un monolito modular basado en dominios, construido sobre Laravel, donde diferentes capas comienzan a cooperar alrededor de una idea común:
definir recursos genéricos una sola vez y permitir que sean reutilizados en diferentes contextos sin destruir su identidad original.
La Biblioteca resuelve la definición de información.
Entity
Attribute
AttributeOption
Collection

La Comunidad resuelve la reutilización.
Publicación
Clonación
Procedencia

El sistema de versiones resuelve la evolución contextual.
EntityVersion
BaseVersion
Presentation
Context Rules

El sistema de fases resuelve reglas competitivas reutilizables.
PhaseTemplate
PhaseExit
Single Elimination
Round Robin
Group Stage
Swiss

El Tournament Graph resuelve la composición.
Start
PhaseNode
EntryPort
Connection
Terminal

Y la arquitectura futura deberá incorporar la ejecución:
TournamentInstance
CompetitionParticipant
PhaseInstance
Match
Result
Advancement

Posteriormente:
Universe
Season
UniverseCompetitor

proporcionará contexto, mientras:
SimulationEngine

resolverá enfrentamientos.
La separación que deberá protegerse durante todo el crecimiento del proyecto puede resumirse finalmente así:
LIBRARY
define quiénes y qué existen.

VERSIONING
define en qué estado pueden existir.

PHASE
define cómo funciona una etapa competitiva.

TOURNAMENT
define cómo se conectan las etapas.

COMPETITION ENGINE
ejecuta esa estructura.

UNIVERSE
define dónde, cuándo y quién participa.

SIMULATION ENGINE
decide qué ocurre durante una interacción.

HISTORY
conserva lo que ocurrió.

Esa separación es actualmente la base arquitectónica más importante de OmniMerge y debería actuar como criterio para cualquier código backend nuevo que se incorpore al proyecto.
El repositorio ya contiene suficientes elementos para afirmar que OmniMerge está evolucionando desde una plataforma de administración de contenido dinámico hacia un motor general de entidades, contextos y sistemas competitivos, con una arquitectura que, si continúa manteniendo esta separación de responsabilidades, puede crecer hacia Universos y Simulación sin necesidad de rehacer su núcleo completo.

