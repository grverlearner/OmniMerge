import { superEditorBase, mergeParts } from './super/base';
import { roundRobinEditor } from './super/round-robin';
import { groupStageEditor } from './super/group-stage';
import { singleEliminationEditor } from './super/single-elimination';

/*
|--------------------------------------------------------------------------
| phaseSuperEditor
|--------------------------------------------------------------------------
|
| El punto de entrada del editor. Junta la base compartida con el modulo del
| motor que toque, y ese es todo el trabajo que hace.
|
| Anadir Eliminacion Directa o Swiss es escribir su modulo y una linea aqui:
| ni el armazon, ni la cabecera, ni el guardado, ni el controlador se tocan.
|
| Se une con mergeParts y no con spread a proposito. El spread copia el
| VALOR que devuelve un getter en ese instante, no el getter: `structure`,
| `classified` o `groups` quedarian congelados en su primer valor y la
| pantalla no reaccionaria a nada. Es exactamente el fallo que ya obligo a
| separar competitionArena en su propio componente.
|
*/

const ENGINES = {
    roundRobin: roundRobinEditor,
    groupStage: groupStageEditor,
    singleElimination: singleEliminationEditor,
};

export default function phaseSuperEditor(config) {

    const engine = ENGINES[config.engine];

    if (!engine) {
        throw new Error('Super Edición sin motor para «' + config.engine + '».');
    }

    return mergeParts(
        superEditorBase(config),
        engine(config)
    );
}
