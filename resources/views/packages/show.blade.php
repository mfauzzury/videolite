@extends('layouts.frontend')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <h1 class="text-4xl font-bold mb-4">{{ $package->name }}</h1>
            <p class="text-gray-600 mb-6">{{ $package->description }}</p>

            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">What's Included</h2>
                <div class="space-y-2">
                    @foreach($package->videos as $video)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-primary-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                </svg>
                                <span>{{ $video->title }}</span>
                            </div>
                            <span class="text-sm text-gray-500">{{ $video->getFormattedDuration() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="border rounded-lg p-6 sticky top-4">
                @if($package->thumbnail_path)
                    <img src="{{ asset('storage/' . $package->thumbnail_path) }}" alt="{{ $package->name }}" class="w-full rounded mb-4">
                @endif

                <div class="mb-4">
                    @if($package->hasDiscount())
                        <div class="text-gray-400 line-through">RM{{ number_format($package->compare_at_price, 2) }}</div>
                        <div class="text-3xl font-bold text-primary-600">RM{{ number_format($package->price, 2) }}</div>
                        <div class="bg-red-500 text-white text-sm px-2 py-1 rounded inline-block mt-2">{{ $package->getDiscountPercentage() }}% OFF</div>
                    @else
                        <div class="text-3xl font-bold">RM{{ number_format($package->price, 2) }}</div>
                    @endif
                </div>

                <div class="text-sm text-gray-600 mb-4">
                    <div>📹 {{ $package->getTotalVideosCount() }} videos</div>
                    <div>⏱️ {{ gmdate('H:i:s', $package->getTotalDuration()) }} total</div>
                </div>

                @if($isEnrolled)
                    <a href="{{ route('player.show', $package->slug) }}" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded font-semibold">
                        Continue Watching
                    </a>
                @else
                    <button onclick="purchasePackage({{ $package->id }})" class="w-full bg-primary-600 hover:bg-primary-700 text-white py-3 rounded font-semibold">
                        Buy Now
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
async function purchasePackage(packageId) {
    @guest
        window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
        return;
    @endguest

    try {
        const response = await fetch('/api/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Authorization': 'Bearer {{ auth()->user()?->createToken("web")->plainTextToken ?? "" }}'
            },
            body: JSON.stringify({ package_id: packageId })
        });

        const data = await response.json();

        if (data.success && data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            alert(data.message || 'Failed to create order');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}
</script>
@endsection
