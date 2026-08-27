{{-- Lo que guarda una eliminación directa --}}
<input type="hidden" name="completion_mode" :value="completionMode">
<input type="hidden" name="target_survivors" :value="targetSurvivors">
<input type="hidden" name="seeding_mode" :value="seedingMode">
<input type="hidden" name="pairing_mode" :value="pairingMode">
<input type="hidden" name="bye_assignment" :value="byeAssignment">
{{-- Un campo por grupo activo: el servidor los recibe como array --}}
<template x-for="key in placements" :key="'pl' + key">
    <input type="hidden" name="placements[]" :value="key">
</template>
<input type="hidden" name="ranking_source" :value="rankingSource">
