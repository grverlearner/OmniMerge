<?php

/*
|--------------------------------------------------------------------------
| Mensajes de validación
|--------------------------------------------------------------------------
|
| La aplicación entera está escrita en español, pero no tenía carpeta de
| idioma y arrancaba en inglés: cualquier regla sin mensaje propio salía
| como «The game mode field is required.». Estos son los mensajes de
| siempre de Laravel, en español y con el tono del resto de pantallas.
|
| Los formularios que ya traen su mensaje propio lo siguen usando: esto solo
| cubre lo que no lo tenía.
|
*/

return [

    'accepted' => 'Hay que aceptar :attribute.',
    'accepted_if' => 'Hay que aceptar :attribute cuando :other es :value.',
    'active_url' => ':Attribute no es una dirección válida.',
    'after' => ':Attribute tiene que ser una fecha posterior a :date.',
    'after_or_equal' => ':Attribute tiene que ser una fecha igual o posterior a :date.',
    'alpha' => ':Attribute solo puede llevar letras.',
    'alpha_dash' => ':Attribute solo puede llevar letras, números, guiones y guiones bajos.',
    'alpha_num' => ':Attribute solo puede llevar letras y números.',
    'array' => ':Attribute tiene que ser una lista.',
    'ascii' => ':Attribute solo puede llevar caracteres simples.',
    'before' => ':Attribute tiene que ser una fecha anterior a :date.',
    'before_or_equal' => ':Attribute tiene que ser una fecha igual o anterior a :date.',
    'between' => [
        'array' => ':Attribute tiene que tener entre :min y :max elementos.',
        'file' => ':Attribute tiene que pesar entre :min y :max kilobytes.',
        'numeric' => ':Attribute tiene que estar entre :min y :max.',
        'string' => ':Attribute tiene que tener entre :min y :max caracteres.',
    ],
    'boolean' => ':Attribute tiene que ser sí o no.',
    'can' => ':Attribute tiene un valor que no está permitido.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'contains' => ':Attribute no incluye un valor obligatorio.',
    'current_password' => 'La contraseña no es correcta.',
    'date' => ':Attribute no es una fecha válida.',
    'date_equals' => ':Attribute tiene que ser la fecha :date.',
    'date_format' => ':Attribute no tiene el formato :format.',
    'decimal' => ':Attribute tiene que tener :decimal decimales.',
    'declined' => ':Attribute tiene que rechazarse.',
    'declined_if' => ':Attribute tiene que rechazarse cuando :other es :value.',
    'different' => ':Attribute y :other tienen que ser distintos.',
    'digits' => ':Attribute tiene que tener :digits dígitos.',
    'digits_between' => ':Attribute tiene que tener entre :min y :max dígitos.',
    'dimensions' => 'La imagen de :attribute no tiene un tamaño válido.',
    'distinct' => ':Attribute tiene un valor repetido.',
    'doesnt_end_with' => ':Attribute no puede terminar en: :values.',
    'doesnt_start_with' => ':Attribute no puede empezar por: :values.',
    'email' => ':Attribute tiene que ser un correo válido.',
    'ends_with' => ':Attribute tiene que terminar en: :values.',
    'enum' => 'El valor elegido en :attribute no es válido.',
    'exists' => 'El valor elegido en :attribute no existe.',
    'extensions' => ':Attribute tiene que ser de uno de estos tipos: :values.',
    'file' => ':Attribute tiene que ser un archivo.',
    'filled' => ':Attribute no puede ir vacío.',
    'gt' => [
        'array' => ':Attribute tiene que tener más de :value elementos.',
        'file' => ':Attribute tiene que pesar más de :value kilobytes.',
        'numeric' => ':Attribute tiene que ser mayor que :value.',
        'string' => ':Attribute tiene que tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => ':Attribute tiene que tener :value elementos o más.',
        'file' => ':Attribute tiene que pesar :value kilobytes o más.',
        'numeric' => ':Attribute tiene que ser :value o más.',
        'string' => ':Attribute tiene que tener :value caracteres o más.',
    ],
    'hex_color' => ':Attribute tiene que ser un color hexadecimal válido.',
    'image' => ':Attribute tiene que ser una imagen.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'in_array' => ':Attribute tiene que estar en :other.',
    'integer' => ':Attribute tiene que ser un número entero.',
    'ip' => ':Attribute tiene que ser una dirección IP válida.',
    'ipv4' => ':Attribute tiene que ser una dirección IPv4 válida.',
    'ipv6' => ':Attribute tiene que ser una dirección IPv6 válida.',
    'json' => ':Attribute tiene que ser un JSON válido.',
    'list' => ':Attribute tiene que ser una lista.',
    'lowercase' => ':Attribute tiene que ir en minúsculas.',
    'lt' => [
        'array' => ':Attribute tiene que tener menos de :value elementos.',
        'file' => ':Attribute tiene que pesar menos de :value kilobytes.',
        'numeric' => ':Attribute tiene que ser menor que :value.',
        'string' => ':Attribute tiene que tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => ':Attribute no puede tener más de :value elementos.',
        'file' => ':Attribute no puede pesar más de :value kilobytes.',
        'numeric' => ':Attribute tiene que ser :value o menos.',
        'string' => ':Attribute no puede tener más de :value caracteres.',
    ],
    'mac_address' => ':Attribute tiene que ser una dirección MAC válida.',
    'max' => [
        'array' => ':Attribute no puede tener más de :max elementos.',
        'file' => ':Attribute no puede pesar más de :max kilobytes.',
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'max_digits' => ':Attribute no puede tener más de :max dígitos.',
    'mimes' => ':Attribute tiene que ser un archivo de tipo: :values.',
    'mimetypes' => ':Attribute tiene que ser un archivo de tipo: :values.',
    'min' => [
        'array' => ':Attribute tiene que tener al menos :min elementos.',
        'file' => ':Attribute tiene que pesar al menos :min kilobytes.',
        'numeric' => ':Attribute tiene que ser al menos :min.',
        'string' => ':Attribute tiene que tener al menos :min caracteres.',
    ],
    'min_digits' => ':Attribute tiene que tener al menos :min dígitos.',
    'missing' => ':Attribute no debería venir.',
    'missing_if' => ':Attribute no debería venir cuando :other es :value.',
    'missing_unless' => ':Attribute no debería venir salvo que :other sea :value.',
    'missing_with' => ':Attribute no debería venir cuando hay :values.',
    'missing_with_all' => ':Attribute no debería venir cuando hay :values.',
    'multiple_of' => ':Attribute tiene que ser múltiplo de :value.',
    'not_in' => 'El valor elegido en :attribute no es válido.',
    'not_regex' => 'El formato de :attribute no es válido.',
    'numeric' => ':Attribute tiene que ser un número.',
    'password' => [
        'letters' => ':Attribute tiene que llevar al menos una letra.',
        'mixed' => ':Attribute tiene que llevar al menos una mayúscula y una minúscula.',
        'numbers' => ':Attribute tiene que llevar al menos un número.',
        'symbols' => ':Attribute tiene que llevar al menos un símbolo.',
        'uncompromised' => 'Ese :attribute ha aparecido en una filtración de datos. Elige otro.',
    ],
    'present' => ':Attribute tiene que venir.',
    'present_if' => ':Attribute tiene que venir cuando :other es :value.',
    'present_unless' => ':Attribute tiene que venir salvo que :other sea :value.',
    'present_with' => ':Attribute tiene que venir cuando hay :values.',
    'present_with_all' => ':Attribute tiene que venir cuando hay :values.',
    'prohibited' => ':Attribute no está permitido.',
    'prohibited_if' => ':Attribute no está permitido cuando :other es :value.',
    'prohibited_unless' => ':Attribute no está permitido salvo que :other esté en :values.',
    'prohibits' => ':Attribute impide que venga :other.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'Falta :attribute.',
    'required_array_keys' => ':Attribute tiene que incluir: :values.',
    'required_if' => 'Falta :attribute cuando :other es :value.',
    'required_if_accepted' => 'Falta :attribute cuando se acepta :other.',
    'required_if_declined' => 'Falta :attribute cuando se rechaza :other.',
    'required_unless' => 'Falta :attribute salvo que :other esté en :values.',
    'required_with' => 'Falta :attribute cuando hay :values.',
    'required_with_all' => 'Falta :attribute cuando hay :values.',
    'required_without' => 'Falta :attribute cuando no hay :values.',
    'required_without_all' => 'Falta :attribute cuando no hay ninguno de :values.',
    'same' => ':Attribute y :other tienen que coincidir.',
    'size' => [
        'array' => ':Attribute tiene que tener :size elementos.',
        'file' => ':Attribute tiene que pesar :size kilobytes.',
        'numeric' => ':Attribute tiene que ser :size.',
        'string' => ':Attribute tiene que tener :size caracteres.',
    ],
    'starts_with' => ':Attribute tiene que empezar por: :values.',
    'string' => ':Attribute tiene que ser texto.',
    'timezone' => ':Attribute tiene que ser una zona horaria válida.',
    'unique' => 'Ya existe un registro con ese :attribute.',
    'uploaded' => 'No se pudo subir :attribute.',
    'uppercase' => ':Attribute tiene que ir en mayúsculas.',
    'url' => ':Attribute tiene que ser una dirección válida.',
    'ulid' => ':Attribute tiene que ser un ULID válido.',
    'uuid' => ':Attribute tiene que ser un UUID válido.',

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos
    |--------------------------------------------------------------------------
    |
    | Sin esto el mensaje diría «Falta game mode». Los nombres que salen en
    | más de un formulario de la aplicación, dichos como los dice la pantalla.
    |
    */

    'attributes' => [
        'name' => 'el nombre',
        'description' => 'la descripción',
        'email' => 'el correo',
        'password' => 'la contraseña',
        'username' => 'el nombre de usuario',
        'image' => 'la imagen',
        'status' => 'el estado',
        'context' => 'el contexto',
        'code' => 'el código',
        'type' => 'el tipo',

        'game_mode' => 'el modo de juego',
        'game_key' => 'el juego',
        'series_format' => 'el formato de la batalla',
        'best_of' => 'al mejor de',
        'fixed_games' => 'los enfrentamientos fijos',
        'decision_mode' => 'cómo se decide la batalla',
        'battle_participants' => 'cuántos compiten en una batalla',
        'allow_draws' => 'si se permiten empates',

        'tournament_template_id' => 'la plantilla',
        'universe_tournament_id' => 'el torneo',
        'universe_season_id' => 'la temporada',
        'recurrence_mode' => 'cada cuánto se juega',
        'recurrence_interval' => 'cada cuántas temporadas',
        'first_season_number' => 'la primera temporada',
        'eligibility_mode' => 'cómo se combinan las condiciones',
        'assignments' => 'los participantes',

        'min_participants' => 'el mínimo de participantes',
        'max_participants' => 'el máximo de participantes',
        'phase_type' => 'el tipo de fase',
        'entity_type_id' => 'el tipo de entidad',
        'attribute_id' => 'el atributo',
        'criterion' => 'el criterio',
        'direction' => 'la dirección',
    ],

];
