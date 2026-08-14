export default function competitionLab(config) {
    return {
        state: config.initialState ?? null,
        stateToken: config.initialToken ?? null,
        actionUrl: config.actionUrl,
        storageKey: config.storageKey,

        loading: false,
        error: '',
        selectedParticipantId: null,

        init() {
            if (this.state && this.stateToken) {
                this.persist();
                return;
            }

            const stored =
                sessionStorage.getItem(
                    this.storageKey
                );

            if (!stored) {
                return;
            }

            try {
                const payload =
                    JSON.parse(stored);

                this.state =
                    payload.state;

                this.stateToken =
                    payload.stateToken;
            } catch {
                sessionStorage.removeItem(
                    this.storageKey
                );
            }
        },

        persist() {
            if (!this.state || !this.stateToken) {
                return;
            }

            sessionStorage.setItem(
                this.storageKey,
                JSON.stringify({
                    state: this.state,
                    stateToken: this.stateToken,
                })
            );
        },

        async execute(action) {
            if (!this.stateToken || this.loading) {
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const response =
                    await fetch(
                        this.actionUrl,
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        ?.content
                                    ?? '',
                            },

                            body: JSON.stringify({
                                action,
                                state_token:
                                    this.stateToken,
                            }),
                        }
                    );

                const payload =
                    await response.json();

                if (!response.ok) {
                    const errors =
                        payload.errors
                        ??
                        {};

                    this.error =
                        Object.values(errors)
                            .flat()
                            .join(' ')
                        ||
                        payload.message
                        ||
                        'No fue posible ejecutar la acción.';

                    return;
                }

                this.state =
                    payload.state;

                this.stateToken =
                    payload.state_token;

                this.persist();
            } catch {
                this.error =
                    'No fue posible comunicarse con el Competition Lab.';
            } finally {
                this.loading = false;
            }
        },

        removeLocalState() {
            sessionStorage.removeItem(
                this.storageKey
            );

            this.state = null;
            this.stateToken = null;
            this.selectedParticipantId = null;
        },

        selectParticipant(id) {
            this.selectedParticipantId = id;
        },

        selectedParticipant() {
            if (
                !this.state
                ||
                !this.selectedParticipantId
            ) {
                return null;
            }

            return this.state
                .participants[
                this.selectedParticipantId
            ]
                ??
                null;
        },

        participants() {
            return Object.values(
                this.state?.participants
                ??
                {}
            );
        },

        starts() {
            return Object.values(
                this.state?.starts
                ??
                {}
            );
        },

        nodes() {
            return Object.values(
                this.state?.nodes
                ??
                {}
            );
        },

        terminals() {
            return Object.values(
                this.state?.terminals
                ??
                {}
            );
        },
    };
}