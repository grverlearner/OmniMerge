{{-- Lo que guarda una fase de grupos --}}
<input type="hidden" name="group_count_mode" :value="groupCountMode">
<input type="hidden" name="group_count" :value="groupCount">
<input type="hidden" name="target_group_size" :value="targetGroupSize">
<input type="hidden" name="min_group_size" :value="minGroupSize">
<input type="hidden" name="max_group_size" :value="maxGroupSize">
<input type="hidden" name="remainder_policy" :value="remainderPolicy">
<input type="hidden" name="distribution_mode" :value="distributionMode">
<input type="hidden" name="internal_cycles" :value="cycles">
<input type="hidden" name="internal_allow_draws" :value="allowDraws ? 1 : 0">
<input type="hidden" name="win_points" :value="points.win">
<input type="hidden" name="draw_points" :value="points.draw">
<input type="hidden" name="loss_points" :value="points.loss">

{{-- Cómo se construye la lista única de la fase --}}
<input type="hidden" name="overall_ranking_mode" :value="overallMode">

{{-- Hasta que jornada se juega, si se ha recortado --}}
<input type="hidden" name="round_limit" :value="isTrimmed ? roundLimit : ''">
