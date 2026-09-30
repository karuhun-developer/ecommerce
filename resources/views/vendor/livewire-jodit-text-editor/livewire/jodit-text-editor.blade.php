<div wire:ignore x-data="{
    editor: null,
    disposed: false,
    updateListener: null,
    async init() {
        const Jodit = await window.loadJodit();
        if (this.disposed || !this.$el.isConnected) return;
        this.editor = Jodit.make(this.$refs.input, {
            autofocus: false,
            toolbarSticky: true,
            uploader: { insertImageAsBase64URI: false },
            toolbarButtonSize: 'large',
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            defaultActionOnPaste: 'insert_clear_html',
            buttons: @js($buttons),
            theme: @js($theme),
        });
        this.editor.events.on('change', value => this.$wire.set('value', value));
        this.updateListener = event => {
            if (event.detail.editorId === @js($identifier)) {
                this.editor.value = event.detail.content ?? '';
            }
        };
        window.addEventListener('update-jodit-content', this.updateListener);
    },
    destroy() {
        this.disposed = true;
        window.removeEventListener('update-jodit-content', this.updateListener);
        this.editor?.destruct();
        this.editor = null;
    }
}">
    <textarea x-ref="input" id="{{ $joditId }}">{{ $value }}</textarea>
</div>
