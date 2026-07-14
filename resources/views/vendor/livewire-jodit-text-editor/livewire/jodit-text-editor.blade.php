<div wire:ignore>
    <textarea id="{{ $joditId }}">{!! $value !!}</textarea>
</div>

@script
    <script>
        window.loadJodit().then(Jodit => {
            const buttons = @json($buttons);

            const editor = Jodit.make('#' + @js($joditId), {
                "autofocus": true,
                "toolbarSticky": true,
                "uploader": {
                    "insertImageAsBase64URI": true
                },
                "toolbarButtonSize": "large",
                "showCharsCounter": false,
                "showWordsCounter": false,
                "showXPathInStatusbar": false,
                "defaultActionOnPaste": "insert_clear_html",
                "buttons": buttons,
                "theme": "{{ $theme }}"
            });

            document.getElementById(@js($joditId)).addEventListener('change', function() {
                @this.set('value', this.value);
            });

            window.addEventListener('update-jodit-content', (event) => {
                if (Array.isArray(event.detail) && event.detail.length > 0) {
                    // Check if this is an array with [editorId, content]
                    if (Array.isArray(event.detail[0]) && event.detail[0].length === 2) {
                        const [targetId, newContent] = event.detail[0];

                        // Only update if the editor ID matches this instance
                        if (targetId === @js($identifier)) {
                            editor.value = newContent;
                        }
                    } else {
                        // Original behavior: update all editors (backward compatibility)
                        editor.value = event.detail[0];
                    }
                } else {
                    console.warn('Invalid event detail format:', event.detail);
                }
            });
        });
    </script>
@endscript