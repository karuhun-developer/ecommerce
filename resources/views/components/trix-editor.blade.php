@props([
    'id' => 'id_trix_description',
    'model' => '',
    'model_name' => 'trix_description',
])
<style>
    .trix-button-group--file-tools { display: none !important; }
    .trix-button--icon-link { display: none !important; }
</style>
<input id="{{ $id }}" type="hidden" name="{{ $model_name }}" value="{{ $model }}">
<trix-editor wire:ignore input="{{ $id }}" class="trix-content"
    x-data
    x-on:trix-blur="
        @this.set('{{ $model_name }}', document.getElementById('{{ $id }}').getAttribute('value'))
    "
    x-on:trix-file-accept="if (!['image/png', 'image/jpeg', 'image/jpg'].includes($event.file.type)) {
        // file type not allowed, prevent default upload
        return $event.preventDefault()
    }"
    x-on:trix-attachment-add="console.log('Gabisa bg')"
></trix-editor>

@assets
<link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.0/dist/trix.css">
<script type="text/javascript" src="https://unpkg.com/trix@2.0.0/dist/trix.umd.min.js"></script>
@endassets
