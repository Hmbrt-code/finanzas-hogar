import { reactive } from 'vue';

const state = reactive({
    show:          false,
    title:         '',
    message:       '',
    confirmLabel:  'Confirmar',
    cancelLabel:   'Cancelar',
    danger:        false,
    resolve:       null,
});

export function useConfirm() {
    function confirm({ title = '', message = '', confirmLabel = 'Confirmar', cancelLabel = 'Cancelar', danger = false } = {}) {
        state.show         = true;
        state.title        = title;
        state.message      = message;
        state.confirmLabel = confirmLabel;
        state.cancelLabel  = cancelLabel;
        state.danger       = danger;

        return new Promise(resolve => {
            state.resolve = resolve;
        });
    }

    function onConfirm() {
        state.show = false;
        state.resolve?.(true);
    }

    function onCancel() {
        state.show = false;
        state.resolve?.(false);
    }

    return { confirm, state, onConfirm, onCancel };
}
