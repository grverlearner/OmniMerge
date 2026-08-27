{{-- Lo que guarda una liga --}}
<input type="hidden" name="cycles" :value="cycles">
<input type="hidden" name="initial_order_mode" :value="orderMode">
<input type="hidden" name="ranking_source" :value="rankingSource">
<input type="hidden" name="allow_draws" :value="allowDraws ? 1 : 0">
<input type="hidden" name="win_points" :value="points.win">
<input type="hidden" name="draw_points" :value="points.draw">
<input type="hidden" name="loss_points" :value="points.loss">

{{-- Hasta que jornada se juega, si se ha recortado --}}
<input type="hidden" name="round_limit" :value="isTrimmed ? roundLimit : ''">
