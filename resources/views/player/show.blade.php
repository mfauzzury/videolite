<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $package->name }} - Watch</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900">
    <div class="flex h-screen">
        <!-- Sidebar - Video List -->
        <aside class="w-80 bg-white overflow-y-auto">
            <div class="p-4 border-b">
                <a href="{{ route('dashboard') }}" class="text-sm text-primary-600 hover:underline">
                    ← Back to My Packages
                </a>
                <h2 class="font-bold mt-2 text-lg">{{ $package->name }}</h2>
                <p class="text-sm text-gray-500">{{ $videos->count() }} videos</p>
            </div>

            <div class="p-2">
                @foreach($videos as $video)
                    <a href="{{ route('player.show', ['package' => $package->slug, 'video' => $video->id]) }}"
                       class="block p-3 hover:bg-gray-100 rounded {{ $video->id === $currentVideo?->id ? 'bg-blue-100 border-l-4 border-blue-600' : '' }}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="font-medium text-sm">{{ $video->title }}</div>
                                @if($video->description)
                                    <div class="text-xs text-gray-500 mt-1">{{ Str::limit($video->description, 50) }}</div>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500 ml-2">{{ $video->getFormattedDuration() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </aside>

        <!-- Main Content - Video Player -->
        <main class="flex-1 flex flex-col">
            @if($currentVideo)
                <div class="flex-1 bg-black flex items-center justify-center">
                    <video id="videoPlayer" class="w-full h-full" controls>
                        <source src="{{ route('api.lessons.stream', $currentVideo->id) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>

                <div class="bg-white p-6">
                    <h1 class="text-2xl font-bold mb-2">{{ $currentVideo->title }}</h1>
                    @if($currentVideo->description)
                        <p class="text-gray-600">{{ $currentVideo->description }}</p>
                    @endif
                </div>
            @else
                <div class="flex-1 flex items-center justify-center">
                    <p class="text-white">No video selected</p>
                </div>
            @endif
        </main>
    </div>
</body>
</html>
