/*
 * El editor de la definición de una plantilla de torneo.
 *
 * Solo se ocupa de QUÉ es la plantilla y CÓMO se reconoce. El recorrido
 * -entradas, fases, enlaces, finales- se monta en la Super Edición, y por
 * eso aquí no hay ni rastro de él más allá de la vista previa, que enseña
 * las cifras a cero mientras no exista.
 */

export default function tournamentTemplateDesigner(config = {}) {
    return {
        editing: Boolean(config.editing),

        preview: config.currentImage || null,

        objectUrl: null,

        removeImage: Boolean(config.removeImage),

        dirty: false,

        submitting: false,

        name: config.name || '',

        summary: config.summary || '',

        icon: config.icon || '',

        accent: config.accent || '',

        category: config.category || '',

        tags: Array.isArray(config.tags) ? [...config.tags] : [],

        tagDraft: '',

        capacityMode: config.capacityMode || 'OPEN',

        minParticipants: config.minParticipants ?? 8,

        maxParticipants: config.maxParticipants ?? '',

        allowByes: Boolean(config.allowByes),

        status: config.status || 'DRAFT',

        visibility: config.visibility || 'PRIVATE',

        allowCloning: config.allowCloning !== false,

        /*
         * Las clases de Tailwind y las etiquetas de categoría llegan ya
         * escritas desde Blade -donde Tailwind puede leerlas- y esto solo
         * elige cuál. Componerlas aquí produciría clases que no existen.
         */
        tones: config.tones || {},

        categories: config.categories || {},

        init() {
            const form = this.$root.closest('form');

            form?.addEventListener('submit', () => {
                /*
                 * Una etiqueta a medio escribir es una etiqueta que el
                 * usuario quiso poner: se recoge antes de enviar en vez de
                 * perderla por no haber pulsado Enter.
                 */
                this.addTag();

                this.submitting = true;
                this.dirty = false;
            });

            window.addEventListener('beforeunload', (event) => {
                if (!this.dirty || this.submitting) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });
        },

        markDirty() {
            if (!this.submitting) {
                this.dirty = true;
            }
        },

        loadImage(event) {
            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
            }

            this.objectUrl = URL.createObjectURL(file);
            this.preview = this.objectUrl;
            this.removeImage = false;

            this.markDirty();
        },

        clearImage() {
            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }

            this.preview = null;
            this.removeImage = true;

            this.markDirty();
        },

        /* ----------------------------------------------------- etiquetas */

        addTag() {
            const limpia = this.tagDraft
                .trim()
                .replace(/^#+/, '')
                .slice(0, 24);

            this.tagDraft = '';

            if (limpia === '' || this.tags.length >= 6) {
                return;
            }

            if (this.tags.includes(limpia)) {
                return;
            }

            this.tags.push(limpia);

            this.markDirty();
        },

        removeTag(etiqueta) {
            this.tags = this.tags.filter((actual) => actual !== etiqueta);

            this.markDirty();
        },

        /* ----------------------------------------------------- capacidad */

        chooseCapacityMode(modo) {
            this.capacityMode = modo;

            if (modo === 'RANGE') {
                this.maxParticipants =
                    this.maxParticipants || this.minParticipants || 8;
            } else {
                this.maxParticipants = '';
            }

            this.markDirty();
        },

        /* ----------------------------------------------- cómo se reconoce */

        /*
         * Icono y color EFECTIVOS: si el usuario no elige ninguno, hereda el
         * de su categoría. Las tablas repiten a propósito lo que
         * TournamentTemplate::display_icon y ::accent devuelven en PHP,
         * porque la vista previa tiene que enseñar exactamente lo que
         * después se verá en la biblioteca; si divergen, miente.
         */
        effectiveIcon() {
            if (this.icon) {
                return this.icon;
            }

            return this.categories[this.category]?.icon || '🏆';
        },

        effectiveAccent() {
            if (this.accent) {
                return this.accent;
            }

            return this.categories[this.category]?.accent || 'amber';
        },

        tone(parte) {
            const tono =
                this.tones[this.effectiveAccent()] || this.tones.slate || {};

            return tono[parte] || '';
        },

        categoryLabel() {
            return this.categories[this.category]?.label || 'Torneo';
        },

        /* ----------------------------------------------------- etiquetas */

        contractLabel() {
            if (this.capacityMode === 'RANGE') {
                return `${this.minParticipants || '—'}–${this.maxParticipants || '—'} participantes`;
            }

            return `${this.minParticipants || '—'}+ participantes`;
        },

        statusLabel() {
            return (
                {
                    DRAFT: 'Borrador',
                    ACTIVE: 'Activa',
                    ARCHIVED: 'Archivada',
                }[this.status] || this.status
            );
        },

        visibilityLabel() {
            return (
                {
                    PRIVATE: 'Privada',
                    PUBLIC: 'Pública',
                    UNLISTED: 'No listada',
                }[this.visibility] || this.visibility
            );
        },
    };
}
