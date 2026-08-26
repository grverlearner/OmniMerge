/*
|--------------------------------------------------------------------------
| exitCriterionFields
|--------------------------------------------------------------------------
|
| El estado de los campos que describen quien sale por una puerta.
|
| Vive en un modulo y no escrito dentro del x-data porque hacen falta en
| tres sitios —crear la salida, anadirle otro criterio y editar uno— y tres
| copias del mismo objeto literal se separan a la primera correccion.
|
| Lee `groups`, `structure` y `castSize` del componente padre: Alpine evalua
| el x-data dentro del ambito del componente, asi que estan a mano.
|
*/

export default function exitCriterionFields(type, values = {}) {

    return {

        type: type ?? 'EACH_GROUP_TOP_N',

        take: values.take ?? 2,
        from: values.from ?? 1,
        to: values.to ?? 2,
        groupId: values.groupId ?? null,

        get perGroup() {
            return ['EACH_GROUP_TOP_N', 'EACH_GROUP_BOTTOM_N'].includes(this.type);
        },

        get totalTake() {
            return ['CROSS_GROUP_POSITION_TOP_N', 'CROSS_GROUP_POSITION_BOTTOM_N',
                    'BEST_REMAINING', 'WORST_REMAINING'].includes(this.type);
        },

        get usesTake() {
            return this.perGroup || this.totalTake;
        },

        get usesFrom() {
            return ['EACH_GROUP_POSITION', 'EACH_GROUP_RANGE',
                    'CROSS_GROUP_POSITION_TOP_N', 'CROSS_GROUP_POSITION_BOTTOM_N',
                    'SPECIFIC_GROUP_POSITION', 'SPECIFIC_GROUP_RANGE'].includes(this.type);
        },

        get usesTo() {
            return ['EACH_GROUP_RANGE', 'SPECIFIC_GROUP_RANGE'].includes(this.type);
        },

        get usesGroup() {
            return ['SPECIFIC_GROUP_POSITION', 'SPECIFIC_GROUP_RANGE'].includes(this.type);
        },

        /*
         * La cuenta, hecha en voz alta.
         *
         * Un criterio «de cada grupo» multiplica, y esa multiplicacion no se
         * ve por ninguna parte: pedir 8 de cada grupo en una fase de 4
         * grupos de 4 clasifica a los 16.
         */
        get reach() {
            const size = this.structure?.min_size ?? 0;
            const count = this.groups?.length ?? 0;

            if (!count || !size) {
                return null;
            }

            const n = Math.max(0, parseInt(this.take) || 0);

            if (this.perGroup) {
                return {
                    perGroup: true,
                    total: Math.min(n, size) * count,
                    everyone: n >= size,
                };
            }

            if (this.totalTake) {
                return { perGroup: false, total: n, everyone: false };
            }

            return null;
        },
    };
}
