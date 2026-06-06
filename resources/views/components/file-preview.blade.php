@props([
    'type' => 'image',
    'file' => null,
    'form_file' => null,
])

<div class="mb-3 max-w-[95%]">
    @if($type === 'image')
        @if($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
            <img src="{{ $file->temporaryUrl() }}" alt="logo" class="w-full h-auto object-contain" loading="lazy">
        @elseif($form_file != null)
            <img src="{{ $form_file }}" alt="logo" class="w-full h-auto object-contain" loading="lazy">
        @endif

    @elseif($type === 'video')
        @if($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
            <video controls class="w-full h-auto object-contain">
                <source src="{{ $file->temporaryUrl() }}" type="{{ $file->getMimeType() }}">
                Your browser does not support the video tag.
            </video>
        @elseif($form_file != null)
            <video controls class="w-full h-auto object-contain">
                <source src="{{ $form_file }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        @endif

    @elseif($type === 'audio')
        @if($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
            <audio controls class="w-full">
                <source src="{{ $file->temporaryUrl() }}" type="{{ $file->getMimeType() }}">
                Your browser does not support the audio element.
            </audio>
        @elseif($form_file != null)
            <audio controls class="w-full">
                <source src="{{ $form_file }}" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        @endif

    @elseif($type === 'pdf')
        @if($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
            <iframe src="{{ $file->temporaryUrl() }}" class="w-full h-96" frameborder="0"></iframe>
        @elseif($form_file != null)
            <iframe src="{{ $form_file }}" class="w-full h-96" frameborder="0"></iframe>
        @endif

    @else

        @if($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
            <div class="p-4 border border-gray-300 rounded bg-gray-50">
                <p class="text-gray-700">File Name: {{ $file->getClientOriginalName() }}</p>
                <p class="text-gray-700">File Size: {{ number_format($file->getSize() / 1024, 2) }} KB</p>
            </div>
        @elseif($form_file != null)
            <div class="p-4 border border-gray-300 rounded bg-gray-50">
                <p class="text-gray-700">File: <a href="{{ $form_file }}" target="_blank" class="text-blue-600 underline">Download</a></p>
            </div>
        @endif

    @endif
</div>
