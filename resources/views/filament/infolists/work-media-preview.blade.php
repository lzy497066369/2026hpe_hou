@php
    use App\Support\AdminDisplay;

    $file = $getRecord()->contentFile;
    $url = AdminDisplay::fileUrl($file);
    $type = AdminDisplay::mediaType($file);
@endphp

@if (! $url)
    <span class="text-sm text-gray-500">未上传</span>
@elseif ($type === 'image')
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
        <img src="{{ $url }}" alt="作品内容" style="max-width: 360px; max-height: 240px; border-radius: 12px; object-fit: contain;" />
    </a>
@elseif ($type === 'audio')
    <audio controls preload="none" style="width: min(520px, 100%);">
        <source src="{{ $url }}" type="{{ $file?->mime_type }}">
        当前浏览器不支持音频播放。
    </audio>
@elseif ($type === 'video')
    <video controls preload="metadata" style="width: min(520px, 100%); max-height: 320px; border-radius: 12px;">
        <source src="{{ $url }}" type="{{ $file?->mime_type }}">
        当前浏览器不支持视频播放。
    </video>
@else
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 underline">
        {{ AdminDisplay::fileName($file) }}
    </a>
@endif
