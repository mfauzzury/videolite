@extends('layouts.frontend')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8">My Packages</h1>

    @if($enrollments->isEmpty())
        <div class="text-center py-12">
            <p class="text-gray-500 mb-4">You haven't purchased any packages yet.</p>
            <a href="{{ route('packages.index') }}" class="inline-block bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded">
                Browse Packages
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($enrollments as $enrollment)
                <div class="border rounded-lg overflow-hidden shadow">
                    @if($enrollment->package->thumbnail_path)
                        <img src="{{ asset('storage/' . $enrollment->package->thumbnail_path) }}" alt="{{ $enrollment->package->name }}" class="w-full h-48 object-cover">
                    @endif

                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">{{ $enrollment->package->name }}</h3>

                        <div class="text-sm text-gray-600 mb-4">
                            <div>📹 {{ $enrollment->package->getTotalVideosCount() }} videos</div>
                            <div>📅 Enrolled: {{ $enrollment->enrolled_at->format('M d, Y') }}</div>
                        </div>

                        <a href="{{ route('player.show', $enrollment->package->slug) }}" class="block w-full bg-primary-600 hover:bg-primary-700 text-white text-center py-2 rounded">
                            Watch Now
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
