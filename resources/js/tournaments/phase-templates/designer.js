export default function phaseTemplateDesigner(
    config = {}
) {
    return {
        editing:
            Boolean(config.editing),

        preview:
            config.currentImage || null,

        objectUrl:
            null,

        removeImage:
            Boolean(config.removeImage),

        dirty:
            false,

        submitting:
            false,

        name:
            config.name || '',

        phaseType:
            config.phaseType
            || 'SINGLE_ELIMINATION',

        participantMode:
            config.participantMode
            || 'INDIVIDUAL',

        capacityMode:
            config.capacityMode
            || 'OPEN',

        minParticipants:
            config.minParticipants ?? 2,

        maxParticipants:
            config.maxParticipants ?? '',

        exactParticipants:
            config.exactParticipants ?? '',

        participantMultiple:
            config.participantMultiple ?? '',

        allowByes:
            Boolean(config.allowByes),

        bestOf:
            Number(config.bestOf || 1),

        status:
            config.status || 'DRAFT',

        visibility:
            config.visibility || 'PRIVATE',

        allowCloning:
            config.allowCloning !== false,

        engine:
            config.engine || null,


        init() {
            const form =
                this.$root.closest('form');

            form?.addEventListener(
                'submit',
                () => {
                    this.submitting = true;
                    this.dirty = false;
                }
            );

            window.addEventListener(
                'beforeunload',
                (event) => {
                    if (
                        !this.dirty
                        || this.submitting
                    ) {
                        return;
                    }

                    event.preventDefault();
                    event.returnValue = '';
                }
            );
        },


        markDirty() {
            if (!this.submitting) {
                this.dirty = true;
            }
        },


        loadImage(event) {
            const file =
                event.target.files?.[0];

            if (!file) {
                return;
            }

            if (this.objectUrl) {
                URL.revokeObjectURL(
                    this.objectUrl
                );
            }

            this.objectUrl =
                URL.createObjectURL(file);

            this.preview =
                this.objectUrl;

            this.removeImage =
                false;

            this.markDirty();
        },


        clearImage() {
            if (this.objectUrl) {
                URL.revokeObjectURL(
                    this.objectUrl
                );

                this.objectUrl = null;
            }

            this.preview = null;
            this.removeImage = true;
            this.markDirty();
        },


        chooseCapacityMode(mode) {
            this.capacityMode = mode;

            if (mode === 'EXACT') {
                this.exactParticipants =
                    this.exactParticipants
                    || this.minParticipants
                    || 2;
            }

            if (mode === 'RANGE') {
                this.maxParticipants =
                    this.maxParticipants
                    || this.minParticipants
                    || 2;
            }

            this.markDirty();
        },


        typeLabel() {
            return {
                SINGLE_ELIMINATION:
                    'Eliminación directa',

                ROUND_ROBIN:
                    'Todos contra todos',

                GROUP_STAGE:
                    'Fase de grupos',

                LEAGUE:
                    'Liga / División',

                SWISS:
                    'Sistema suizo',

                CUSTOM:
                    'Personalizada',
            }[this.phaseType]
                || this.phaseType;
        },


        participantModeLabel() {
            return {
                INDIVIDUAL:
                    'Individual',

                TEAM:
                    'Equipos',

                FLEXIBLE:
                    'Flexible',
            }[this.participantMode]
                || this.participantMode;
        },


        contractLabel() {
            let label;

            if (this.capacityMode === 'EXACT') {
                label = `${this.exactParticipants || '—'
                    } exactos`;
            } else if (
                this.capacityMode === 'RANGE'
            ) {
                label = `${this.minParticipants || '—'
                    }–${this.maxParticipants || '—'
                    } participantes`;
            } else {
                label = `${this.minParticipants || '—'
                    }+ participantes`;
            }

            if (
                Number(this.participantMultiple)
                > 1
            ) {
                label += ` · múltiplo de ${this.participantMultiple
                    }`;
            }

            return label;
        },


        statusLabel() {
            return {
                DRAFT:
                    'Borrador',

                ACTIVE:
                    'Activa',

                ARCHIVED:
                    'Archivada',
            }[this.status]
                || this.status;
        },


        visibilityLabel() {
            return {
                PRIVATE:
                    'Privada',

                PUBLIC:
                    'Pública',

                UNLISTED:
                    'No listada',
            }[this.visibility]
                || this.visibility;
        },
    };
}