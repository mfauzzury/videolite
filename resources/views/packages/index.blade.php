@extends('layouts.frontend')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8">Browse Packages</h1>

    @foreach($subjects as $subject)
        <div class="mb-12">
            <h2 class="text-2xl font-bold mb-4 flex items-center">
                <span class="w-2 h-8 bg-primary-500 mr-3"></span>
                {{ $subject->name }}
            </h2>

            @if($subject->packages->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($subject->packages as $package)
                        <div class="border rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                            @if($package->thumbnail_path)
                                <img src="{{ asset('storage/' . $package->thumbnail_path) }}" alt="{{ $package->name }}" class="w-full h-48 object-cover">
                            @else
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-400">No thumbnail</span>
                                </div>
                            @endif

                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-2">{{ $package->name }}</h3>
                                <p class="text-gray-600 text-sm mb-4">{{ Str::limit($package->description, 100) }}</p>

                                <div class="flex justify-between items-center">
                                    <div>
                                        @if($package->hasDiscount())
                                            <span class="text-gray-400 line-through text-sm">RM{{ number_format($package->compare_at_price, 2) }}</span>
                                            <span class="text-xl font-bold text-primary-600">RM{{ number_format($package->price, 2) }}</span>
                                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded ml-2">{{ $package->getDiscountPercentage() }}% OFF</span>
                                        @else
                                            <span class="text-xl font-bold">RM{{ number_format($package->price, 2) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <a href="{{ route('packages.show', $package->slug) }}" class="block w-full mt-4 bg-primary-600 hover:bg-primary-700 text-white text-center py-2 rounded">
                                    View Details
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">No packages available for this subject.</p>
            @endif
        </div>
    @endforeach
</div>
@endsection
