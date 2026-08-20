DISEÑO INICIAL DEL MÓDULO DE ENTIDADES FLEXIBLES DE OMNIMERGE
1. Introducción
OmniMerge será una plataforma que permitirá a cada usuario crear entidades completamente personalizadas.
Una entidad podrá representar:
Un personaje.
Un animal.
Un objeto.
Un vehículo.
Un planeta.
Un color.
Un país.
Un concepto.
Una criatura.
Una organización.
Cualquier elemento definido por el usuario.
El sistema no tendrá una estructura rígida donde todas las entidades posean los mismos campos.
En lugar de crear columnas fijas como:
poder
velocidad
elemento
edad
color

OmniMerge permitirá que los usuarios creen sus propios atributos y los asignen libremente a sus entidades.
La estructura principal será:
Entidad
   ↓
Atributos asignados
   ↓
Uno o varios valores

Ejemplo:
Entidad: Pyron

Atributo: Elemento
Valores:
- Fuego
- Luz

Atributo: Poder
Valor:
- 95

Atributo: Habilidades
Valores:
- Volar
- Regeneración
- Aliento de fuego


2. Objetivo del módulo
Desarrollar un módulo flexible que permita crear, organizar, describir y clasificar entidades mediante atributos, catálogos, valores, colecciones, grupos visuales y relaciones dinámicas entre atributos.

3. Resumen de los componentes principales
El módulo estará compuesto por los siguientes bloques:
3.1. Usuarios
Cada usuario tendrá su propia biblioteca de:
Entidades.
Tipos de entidades.
Atributos.
Catálogos de opciones.
Colecciones.
Grupos de atributos.
Relaciones y condiciones.

3.2. Tipos de entidad
Permiten clasificar las entidades.
Ejemplos:
Personaje
Dragón
Vehículo
Animal
Planeta
Color
Objeto
Concepto

El tipo de entidad no limitará completamente los atributos de la entidad.
Servirá principalmente para:
Clasificar.
Organizar.
Sugerir atributos.
Aplicar plantillas iniciales.

3.3. Entidades
Son los elementos concretos creados por el usuario.
Ejemplos:
Pyron
Naruto Uzumaki
Marte
Rojo escarlata
Espada de Cristal
Libertad


3.4. Atributos
Son características creadas por el usuario.
Ejemplos:
Elemento
Poder
Edad
Tipo de chakra
Anime
Color
Habilidades
Puede volar
Altura
Historia

Un atributo será reutilizable en muchas entidades.

3.5. Opciones de atributo
Algunos atributos tendrán un catálogo de valores seleccionables.
Ejemplo:
Atributo: Elemento

Opciones:
- Fuego
- Agua
- Tierra
- Aire
- Luz
- Oscuridad

Otro ejemplo:
Atributo: Anime

Opciones:
- Naruto
- Dragon Ball
- One Piece
- Bleach

Estas opciones pueden llamarse valores catalogados, opciones o calificativos.
En la base de datos se recomienda usar:
attribute_options


3.6. Atributos asignados a entidades
Esta relación indica que una entidad utiliza determinado atributo.
Ejemplo:
Pyron utiliza el atributo Elemento.
Pyron utiliza el atributo Poder.
Pyron utiliza el atributo Habilidades.

Todavía no indica los valores.
Solo representa la asignación del atributo.

3.7. Valores de atributos
Almacenan los valores concretos de una entidad.
Ejemplo:
Pyron
Elemento:
- Fuego
- Luz

Otro:
Pyron
Poder:
- 95

Otro:
Pyron
Puede volar:
- Sí


3.8. Colecciones de entidades
Permiten agrupar entidades de muchas maneras.
Ejemplo:
Dragones
Criaturas de fuego
Personajes legendarios
Favoritos
Personajes del anime Naruto

Una entidad puede pertenecer a varias colecciones.

3.9. Grupos de atributos
Permiten ordenar los atributos visualmente.
Ejemplo:
Información general
- Nombre alternativo
- Edad
- Origen

Características físicas
- Altura
- Peso
- Color

Combate
- Poder
- Velocidad
- Resistencia

Un atributo puede aparecer en varios grupos.

3.10. Relaciones entre atributos
Permiten establecer conexiones conceptuales.
Ejemplo:
Anime
   ↓
Tipo de chakra

Otro:
País
   ↓
Ciudad

Otro:
Elemento base
   ↓
Técnica elemental


3.11. Condiciones entre atributos
Permiten mostrar, ocultar o exigir atributos según otros valores.
Ejemplo:
SI Anime = Naruto
ENTONCES mostrar Tipo de chakra.

Otro:
SI País = Perú
ENTONCES mostrar opciones de ciudades peruanas.


3.12. Reglas de opciones
Permiten filtrar las opciones disponibles según otros atributos.
Ejemplo:
Anime = Naruto
→ Técnicas disponibles:
  - Rasengan
  - Chidori
  - Byakugan

Anime = Dragon Ball
→ Técnicas disponibles:
  - Kamehameha
  - Genki Dama
  - Kaioken


4. Entidades o tablas principales
El sistema inicial deberá contener las siguientes tablas:
users
entity_types
entities

attributes
attribute_options

entity_attributes
entity_attribute_values

collections
collection_entity

attribute_groups
attribute_group_attribute

entity_type_attribute

attribute_relationships
attribute_conditions
attribute_option_rules

Como ampliación futura podrán agregarse:
tags
entity_tag

attribute_option_relationships
attribute_versions
entity_versions


5. Tabla users
Propósito
Almacena los usuarios registrados.
Cada usuario será propietario de sus elementos.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
name
VARCHAR(100)
Nombre completo
username
VARCHAR(50)
Nombre de usuario
email
VARCHAR(150)
Correo
password
VARCHAR(255)
Contraseña cifrada
role
VARCHAR(20)
Rol
status
VARCHAR(20)
Estado
last_login_at
TIMESTAMP
Último acceso
email_verified_at
TIMESTAMP
Verificación
remember_token
VARCHAR(100)
Token
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización
deleted_at
TIMESTAMP
Borrado lógico

Valores sugeridos
role
ADMIN
USER

status
ACTIVE
INACTIVE
SUSPENDED

Relaciones
User 1:N EntityType
User 1:N Entity
User 1:N Attribute
User 1:N Collection
User 1:N AttributeGroup


6. Tabla entity_types
Propósito
Clasifica las entidades.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
user_id
BIGINT
Propietario
code
VARCHAR(30)
Código
name
VARCHAR(100)
Nombre
description
TEXT
Descripción
icon
VARCHAR(100)
Icono
color
VARCHAR(20)
Color
status
VARCHAR(20)
Estado
sort_order
INT
Orden
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización
deleted_at
TIMESTAMP
Borrado lógico

Restricción
UNIQUE(user_id, code)

Ejemplo
id: 1
user_id: 1
code: DRAGON
name: Dragón
description: Criatura fantástica de naturaleza dracónica.
status: ACTIVE

Relaciones
EntityType belongsTo User
EntityType hasMany Entity
EntityType belongsToMany Attribute


7. Tabla entities
Propósito
Almacena las entidades creadas.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
user_id
BIGINT
Propietario
entity_type_id
BIGINT
Tipo
code
VARCHAR(30)
Código
name
VARCHAR(150)
Nombre
slug
VARCHAR(180)
Identificador URL
description
TEXT
Descripción
image
VARCHAR(255)
Imagen
status
VARCHAR(20)
Estado
visibility
VARCHAR(20)
Visibilidad
metadata
JSON
Datos adicionales
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización
deleted_at
TIMESTAMP
Borrado lógico

Restricciones
UNIQUE(user_id, code)
UNIQUE(user_id, slug)

Ejemplo
id: 10
user_id: 1
entity_type_id: 1
code: ENT-PYRON
name: Pyron
slug: pyron
description: Dragón legendario de fuego.
status: ACTIVE
visibility: PRIVATE

Relaciones
Entity belongsTo User
Entity belongsTo EntityType
Entity hasMany EntityAttribute
Entity belongsToMany Collection


8. Tabla attributes
Propósito
Almacena los atributos personalizados.
Campos generales
Campo
Tipo
Descripción
id
BIGINT
Identificador
user_id
BIGINT
Propietario
code
VARCHAR(50)
Código
name
VARCHAR(150)
Nombre
slug
VARCHAR(180)
Identificador
description
TEXT
Descripción
help_text
TEXT
Texto de ayuda
placeholder
VARCHAR(255)
Ejemplo visual
data_type
VARCHAR(30)
Tipo de dato
value_source
VARCHAR(30)
Fuente del valor
display_style
VARCHAR(30)
Forma de presentación
allows_multiple
BOOLEAN
Permite varios valores
allows_custom_values
BOOLEAN
Permite valores libres
is_required
BOOLEAN
Es obligatorio
is_filterable
BOOLEAN
Permite filtros
is_comparable
BOOLEAN
Permite comparación
is_searchable
BOOLEAN
Participa en búsquedas
is_visible
BOOLEAN
Visible por defecto
is_featured
BOOLEAN
Destacado
min_numeric_value
DECIMAL
Mínimo
max_numeric_value
DECIMAL
Máximo
min_length
INT
Longitud mínima
max_length
INT
Longitud máxima
unit
VARCHAR(30)
Unidad
sort_order
INT
Orden general
hierarchy_level
INT
Nivel orientativo
scope
VARCHAR(20)
Alcance
configuration
JSON
Configuración adicional
source_attribute_id
BIGINT
Atributo original clonado
status
VARCHAR(20)
Estado
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización
deleted_at
TIMESTAMP
Borrado lógico

Tipos de dato
TEXT
LONG_TEXT
INTEGER
DECIMAL
BOOLEAN
DATE
DATETIME
COLOR
OPTION
URL
IMAGE
ENTITY_REFERENCE
JSON

Tipos recomendados para el MVP
TEXT
LONG_TEXT
INTEGER
DECIMAL
BOOLEAN
DATE
COLOR
OPTION

Origen del valor
FREE
Valor escrito libremente.
CATALOG
Valor seleccionado del catálogo.
MIXED
Permite catálogo y valor libre.
REFERENCE
Referencia a otra entidad.
CALCULATED
Valor calculado.
Estilos de visualización
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

Ejemplo
id: 1
user_id: 1
code: ELEMENT
name: Elemento
data_type: OPTION
value_source: CATALOG
display_style: MULTISELECT
allows_multiple: true
allows_custom_values: false
is_filterable: true
is_comparable: true
sort_order: 1
status: ACTIVE

Relaciones
Attribute belongsTo User
Attribute hasMany AttributeOption
Attribute hasMany EntityAttribute
Attribute belongsToMany AttributeGroup
Attribute belongsToMany EntityType
Attribute hasMany AttributeRelationship
Attribute hasMany AttributeCondition


9. Tabla attribute_options
Propósito
Almacena el catálogo de valores de un atributo.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
attribute_id
BIGINT
Atributo
parent_option_id
BIGINT
Opción padre
code
VARCHAR(50)
Código
name
VARCHAR(150)
Nombre
description
TEXT
Descripción
image
VARCHAR(255)
Imagen
icon
VARCHAR(100)
Icono
color
VARCHAR(20)
Color
numeric_value
DECIMAL
Valor numérico opcional
sort_order
INT
Orden
metadata
JSON
Información adicional
status
VARCHAR(20)
Estado
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización
deleted_at
TIMESTAMP
Borrado lógico

Restricción
UNIQUE(attribute_id, code)

Ejemplo
Atributo: Elemento

Opciones:
1. Fuego
2. Agua
3. Tierra
4. Aire
5. Luz
6. Oscuridad

Jerarquía de opciones
Fuego
├── Llama
├── Magma
└── Calor

En este caso:
Magma.parent_option_id = Fuego.id

Relaciones
AttributeOption belongsTo Attribute
AttributeOption belongsTo ParentOption
AttributeOption hasMany ChildOptions
AttributeOption hasMany EntityAttributeValue


10. Tabla entity_attributes
Propósito
Indica qué atributos utiliza una entidad.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
entity_id
BIGINT
Entidad
attribute_id
BIGINT
Atributo
custom_label
VARCHAR(150)
Nombre personalizado
is_visible
BOOLEAN
Visible
is_featured
BOOLEAN
Destacado
sort_order
INT
Orden
notes
TEXT
Notas
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización

Restricción
UNIQUE(entity_id, attribute_id)

Ejemplo
entity_id: Pyron
attribute_id: Elemento
custom_label: Afinidades elementales
is_visible: true
sort_order: 1

Relaciones
EntityAttribute belongsTo Entity
EntityAttribute belongsTo Attribute
EntityAttribute hasMany EntityAttributeValue


11. Tabla entity_attribute_values
Propósito
Almacena los valores reales de una entidad.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
entity_attribute_id
BIGINT
Atributo asignado
attribute_option_id
BIGINT
Opción seleccionada
text_value
TEXT
Texto
integer_value
BIGINT
Entero
decimal_value
DECIMAL
Decimal
boolean_value
BOOLEAN
Booleano
date_value
DATE
Fecha
datetime_value
DATETIME
Fecha y hora
color_value
VARCHAR(20)
Color
url_value
VARCHAR(500)
URL
image_value
VARCHAR(255)
Imagen
referenced_entity_id
BIGINT
Entidad referenciada
custom_value
VARCHAR(255)
Valor personalizado
json_value
JSON
Valor complejo
sort_order
INT
Orden
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización

Regla
Cada fila utilizará solamente el campo correspondiente al tipo del atributo.
Ejemplo de opción
Entidad: Pyron
Atributo: Elemento
Opción: Fuego

Ejemplo numérico
Entidad: Pyron
Atributo: Poder
integer_value: 95

Ejemplo booleano
Entidad: Pyron
Atributo: Puede volar
boolean_value: true

Relaciones
EntityAttributeValue belongsTo EntityAttribute
EntityAttributeValue belongsTo AttributeOption
EntityAttributeValue belongsTo ReferencedEntity


12. Tabla collections
Propósito
Agrupa entidades.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
user_id
BIGINT
Propietario
code
VARCHAR(50)
Código
name
VARCHAR(150)
Nombre
description
TEXT
Descripción
image
VARCHAR(255)
Imagen
icon
VARCHAR(100)
Icono
color
VARCHAR(20)
Color
visibility
VARCHAR(20)
Visibilidad
status
VARCHAR(20)
Estado
sort_order
INT
Orden
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización
deleted_at
TIMESTAMP
Borrado lógico

Ejemplo
Dragones
Criaturas de fuego
Personajes legendarios
Favoritos

Relaciones
Collection belongsTo User
Collection belongsToMany Entity


13. Tabla collection_entity
Propósito
Relaciona entidades y colecciones.
Campos
Campo
Tipo
Descripción
collection_id
BIGINT
Colección
entity_id
BIGINT
Entidad
sort_order
INT
Orden
added_at
TIMESTAMP
Fecha de adición
notes
TEXT
Notas

Clave primaria
PRIMARY KEY(collection_id, entity_id)

Relación
Collection N:M Entity


14. Tabla attribute_groups
Propósito
Organiza atributos visualmente.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
user_id
BIGINT
Propietario
code
VARCHAR(50)
Código
name
VARCHAR(150)
Nombre
description
TEXT
Descripción
icon
VARCHAR(100)
Icono
color
VARCHAR(20)
Color
layout_type
VARCHAR(30)
Diseño
collapsible
BOOLEAN
Puede contraerse
default_expanded
BOOLEAN
Abierto inicialmente
sort_order
INT
Orden
status
VARCHAR(20)
Estado
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización
deleted_at
TIMESTAMP
Borrado lógico

Diseños
LIST
GRID
CARDS
TABLE
COMPACT

Ejemplo
Grupo: Combate

Atributos:
- Poder
- Velocidad
- Resistencia
- Habilidades

Relaciones
AttributeGroup belongsTo User
AttributeGroup belongsToMany Attribute


15. Tabla attribute_group_attribute
Propósito
Relaciona atributos con grupos.
Campos
Campo
Tipo
Descripción
attribute_group_id
BIGINT
Grupo
attribute_id
BIGINT
Atributo
custom_label
VARCHAR(150)
Etiqueta
sort_order
INT
Orden
is_featured
BOOLEAN
Destacado

Clave primaria
PRIMARY KEY(attribute_group_id, attribute_id)

Relación
AttributeGroup N:M Attribute


16. Tabla entity_type_attribute
Propósito
Asigna atributos recomendados a tipos de entidad.
No obliga necesariamente a usarlos.
Campos
Campo
Tipo
Descripción
entity_type_id
BIGINT
Tipo
attribute_id
BIGINT
Atributo
default_group_id
BIGINT
Grupo recomendado
is_required
BOOLEAN
Obligatorio
is_suggested
BOOLEAN
Recomendado
sort_order
INT
Orden
configuration
JSON
Configuración

Clave primaria
PRIMARY KEY(entity_type_id, attribute_id)

Ejemplo
Tipo: Dragón

Atributos sugeridos:
- Elemento
- Poder
- Tamaño
- Capacidad de vuelo


17. Tabla attribute_relationships
Propósito
Almacena relaciones entre atributos.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
parent_attribute_id
BIGINT
Atributo origen
child_attribute_id
BIGINT
Atributo dependiente
relationship_type
VARCHAR(30)
Tipo
is_required
BOOLEAN
Obligatoria
sort_order
INT
Orden
depth_level
INT
Nivel
configuration
JSON
Configuración
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización

Restricción
UNIQUE(
    parent_attribute_id,
    child_attribute_id,
    relationship_type
)

Tipos
PARENT_CHILD
DEPENDS_ON
REQUIRES
EXCLUDES
RELATED_TO
INHERITS_FROM
GROUPS_WITH

Ejemplo
Anime DEPENDS_ON Obra ficticia
Tipo de chakra DEPENDS_ON Anime
Técnica elemental REQUIRES Elemento

Relación
Un atributo puede tener múltiples padres y múltiples hijos.

18. Tabla attribute_conditions
Propósito
Establece condiciones para mostrar, ocultar o exigir atributos.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
target_attribute_id
BIGINT
Atributo afectado
source_attribute_id
BIGINT
Atributo evaluado
source_option_id
BIGINT
Opción evaluada
operator
VARCHAR(30)
Operador
action
VARCHAR(30)
Acción
comparison_value
JSON
Valor comparado
condition_group
INT
Grupo de condición
logical_operator
VARCHAR(10)
AND u OR
priority
INT
Prioridad
error_message
VARCHAR(255)
Mensaje
status
VARCHAR(20)
Estado
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización

Operadores
EQUALS
NOT_EQUALS
CONTAINS
NOT_CONTAINS
GREATER_THAN
LESS_THAN
HAS_VALUE
HAS_NO_VALUE
IN
NOT_IN

Acciones
SHOW
HIDE
ENABLE
DISABLE
REQUIRE
MAKE_OPTIONAL
FILTER_OPTIONS

Ejemplo
SI Anime = Naruto
ENTONCES mostrar Tipo de chakra.

Representación:
source_attribute_id = Anime
source_option_id = Naruto
target_attribute_id = Tipo de chakra
operator = EQUALS
action = SHOW


19. Tabla attribute_option_rules
Propósito
Filtra opciones según el contexto.
Campos
Campo
Tipo
Descripción
id
BIGINT
Identificador
target_option_id
BIGINT
Opción afectada
source_attribute_id
BIGINT
Atributo evaluado
source_option_id
BIGINT
Opción evaluada
operator
VARCHAR(30)
Operador
action
VARCHAR(30)
Acción
priority
INT
Prioridad
status
VARCHAR(20)
Estado
created_at
TIMESTAMP
Creación
updated_at
TIMESTAMP
Actualización

Acciones
ALLOW
BLOCK
SHOW
HIDE

Ejemplo
SI Anime = Naruto
PERMITIR Técnica = Rasengan.

SI Anime = Dragon Ball
PERMITIR Técnica = Kamehameha.


20. Tablas opcionales futuras
20.1. tags
Etiquetas rápidas.
Legendario
Favorito
Pendiente
Jefe final

20.2. entity_tag
Relaciona entidades y etiquetas.
20.3. attribute_option_relationships
Permite relaciones entre opciones.
Ejemplos:
Fuego INCOMPATIBLE_WITH Agua
Magma REQUIRES Fuego
Super Saiyajin UPGRADES_TO Super Saiyajin 2

20.4. entity_versions
Almacena versiones históricas de entidades.
20.5. attribute_versions
Almacena cambios de configuración en atributos.

21. Diagrama general de relaciones
USERS
├── ENTITY_TYPES
├── ENTITIES
├── ATTRIBUTES
├── COLLECTIONS
└── ATTRIBUTE_GROUPS

ENTITY_TYPES
├── ENTITIES
└── N:M ATTRIBUTES

ENTITIES
├── ENTITY_ATTRIBUTES
└── N:M COLLECTIONS

ATTRIBUTES
├── ATTRIBUTE_OPTIONS
├── ENTITY_ATTRIBUTES
├── N:M ATTRIBUTE_GROUPS
├── N:M ENTITY_TYPES
├── ATTRIBUTE_RELATIONSHIPS
├── ATTRIBUTE_CONDITIONS
└── ATTRIBUTE_OPTION_RULES

ENTITY_ATTRIBUTES
└── ENTITY_ATTRIBUTE_VALUES

ATTRIBUTE_OPTIONS
├── ENTITY_ATTRIBUTE_VALUES
├── CHILD ATTRIBUTE_OPTIONS
├── ATTRIBUTE_CONDITIONS
└── ATTRIBUTE_OPTION_RULES


22. Relaciones completas
Usuario y tipo de entidad
users 1:N entity_types

Usuario y entidad
users 1:N entities

Usuario y atributo
users 1:N attributes

Usuario y colección
users 1:N collections

Usuario y grupo de atributos
users 1:N attribute_groups

Tipo de entidad y entidad
entity_types 1:N entities

Tipo de entidad y atributo
entity_types N:M attributes

Tabla intermedia:
entity_type_attribute

Entidad y atributo
entities N:M attributes

Tabla intermedia con identidad propia:
entity_attributes

Atributo asignado y valores
entity_attributes 1:N entity_attribute_values

Atributo y opciones
attributes 1:N attribute_options

Opción superior y opción inferior
attribute_options 1:N attribute_options

Relación recursiva mediante:
parent_option_id

Entidad y colección
entities N:M collections

Tabla intermedia:
collection_entity

Atributo y grupo
attributes N:M attribute_groups

Tabla intermedia:
attribute_group_attribute

Atributo y atributo
attributes N:M attributes

Tabla:
attribute_relationships


23. Ejemplo completo
Usuario
Grover

Tipo
Personaje de anime

Entidad
Naruto Uzumaki

Atributos
Anime
Tipo de personaje
Tipo de chakra
Aldea
Rango ninja
Técnicas

Catálogo Anime
Naruto
Dragon Ball
One Piece
Bleach

Catálogo Tipo de chakra
Fuego
Agua
Tierra
Viento
Rayo

Catálogo Técnica
Rasengan
Chidori
Kamehameha
Genki Dama
Gomu Gomu no Pistol

Valores de Naruto
Anime:
- Naruto

Tipo de personaje:
- Ninja

Tipo de chakra:
- Viento

Aldea:
- Aldea Oculta de la Hoja

Técnicas:
- Rasengan
- Clones de sombra

Condición
SI Anime = Naruto
MOSTRAR Tipo de chakra.

Regla de opciones
SI Anime = Naruto
PERMITIR Rasengan.

SI Anime = Naruto
OCULTAR Kamehameha.


24. Reglas de negocio principales
24.1. Propiedad
El usuario solamente podrá modificar sus propios registros.
24.2. Unicidad
No debe repetirse un código dentro de la cuenta del mismo usuario.
24.3. Atributos múltiples
Si:
allows_multiple = false

solo se permitirá un valor.
Si:
allows_multiple = true

se permitirán varios.
24.4. Validación del tipo
El campo utilizado debe coincidir con data_type.
24.5. Catálogo
Si:
value_source = CATALOG

debe seleccionarse una opción registrada.
24.6. Valor mixto
Si:
value_source = MIXED

podrá seleccionarse una opción o escribirse un valor.
24.7. Límites numéricos
Los valores deberán respetar mínimo y máximo.
24.8. Borrado lógico
No se eliminarán físicamente atributos, opciones o entidades en uso.
24.9. Cambio del tipo de dato
No se permitirá cambiar data_type si existen valores asociados, salvo mediante un proceso de conversión.
24.10. Relaciones circulares
Debe impedirse crear relaciones como:
A depende de B
B depende de A

cuando provoquen un ciclo inválido.
24.11. Orden
Deben manejarse por separado:
sort_order
hierarchy_level
relationship
group
condition

El orden no representa dependencia.

25. Orden recomendado de implementación
Fase 1: núcleo básico
Crear:
users
entity_types
entities
attributes
attribute_options
entity_attributes
entity_attribute_values

Funciones:
Crear tipos.
Crear entidades.
Crear atributos.
Crear opciones.
Asignar atributos.
Registrar valores.
Fase 2: organización
Crear:
collections
collection_entity
attribute_groups
attribute_group_attribute
entity_type_attribute

Funciones:
Organizar entidades.
Agrupar atributos.
Sugerir atributos por tipo.
Configurar orden visual.
Fase 3: relaciones
Crear:
attribute_relationships
attribute_conditions

Funciones:
Jerarquías.
Dependencias.
Requisitos.
Mostrar u ocultar atributos.
Fase 4: reglas de opciones
Crear:
attribute_option_rules

Funciones:
Filtrar catálogos.
Mostrar opciones según contexto.
Bloquear opciones incompatibles.

26. Orden de migraciones Laravel
1. create_users_table
2. create_entity_types_table
3. create_entities_table
4. create_attributes_table
5. create_attribute_options_table
6. create_entity_attributes_table
7. create_entity_attribute_values_table
8. create_collections_table
9. create_collection_entity_table
10. create_attribute_groups_table
11. create_attribute_group_attribute_table
12. create_entity_type_attribute_table
13. create_attribute_relationships_table
14. create_attribute_conditions_table
15. create_attribute_option_rules_table


27. Modelos Laravel requeridos
User
EntityType
Entity
Attribute
AttributeOption
EntityAttribute
EntityAttributeValue
Collection
AttributeGroup
AttributeRelationship
AttributeCondition
AttributeOptionRule

Las tablas intermedias podrán manejarse mediante relaciones Eloquent:
collection_entity
attribute_group_attribute
entity_type_attribute


28. Lista final de entidades que deben crearse
Entidades principales obligatorias
1. User
Representa al usuario.
2. EntityType
Representa el tipo de entidad.
3. Entity
Representa cualquier entidad creada.
4. Attribute
Representa una característica configurable.
5. AttributeOption
Representa una opción seleccionable de un atributo.
6. EntityAttribute
Representa un atributo asignado a una entidad.
7. EntityAttributeValue
Representa un valor concreto.
8. Collection
Representa una colección de entidades.
9. AttributeGroup
Representa un grupo visual de atributos.
10. AttributeRelationship
Representa una relación entre atributos.
11. AttributeCondition
Representa una condición de visibilidad o validación.
12. AttributeOptionRule
Representa una regla de disponibilidad de opciones.

Tablas intermedias obligatorias
13. collection_entity
Relaciona entidades y colecciones.
14. attribute_group_attribute
Relaciona atributos y grupos.
15. entity_type_attribute
Relaciona tipos de entidad y atributos sugeridos.

29. Resumen final del modelo
La estructura principal de OmniMerge será:
Usuario
   ↓
Crea tipos, entidades, atributos, colecciones y grupos

Entidad
   ↓
Tiene atributos asignados
   ↓
Cada atributo tiene uno o varios valores

Atributo
   ↓
Puede tener un catálogo de opciones

Atributo
   ↓
Puede relacionarse o depender de otros atributos

Condición
   ↓
Determina cuándo mostrar, exigir u ocultar atributos

Regla de opción
   ↓
Determina qué opciones se permiten según el contexto

Con este diseño, OmniMerge podrá soportar:
Entidades completamente personalizadas.
Atributos dinámicos.
Catálogos seleccionables.
Valores múltiples.
Jerarquías.
Dependencias.
Condiciones.
Agrupación visual.
Colecciones.
Comparaciones.
Filtros.
Futuras simulaciones.
Este será el núcleo sobre el que posteriormente podrán construirse universos, temporadas, torneos, batallas, rankings e historial.

