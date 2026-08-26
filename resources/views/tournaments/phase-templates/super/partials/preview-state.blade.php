{{--
    Lo que estás previsualizando, en campos ocultos.

    Crear, editar o borrar una puerta recarga la pantalla, y sin esto el
    servidor contestaba con la configuración GUARDADA: cambiabas a «grupos
    personalizados», creabas uno, y volvías con «cantidad fija» seleccionada.

    Cada motor declara sus controles en previewParams(), así que esto vale
    para todos sin listar ninguno.
--}}

<template x-for="entry in Object.entries({ ...previewParams(), participants })"
    :key="'st' + entry[0]">
    <input type="hidden" :name="entry[0]" :value="entry[1]">
</template>
