@extends('layouts.frontend')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-2xl mx-auto text-center">
        @if($order->isPaid())
            <div class="mb-6">
                <svg class="w-20 h-20 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>

            <h1 class="text-4xl font-bold mb-4">Payment Successful!</h1>
            <p class="text-gray-600 mb-8">Your order <span class="font-mono">{{ $order->order_number }}</span> has been confirmed.</p>

            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                <p class="font-semibold mb-2">You now have access to:</p>
                <p class="text-lg">{{ $order->package->name }}</p>
            </div>

            <a href="{{ route('player.show', $order->package->slug) }}" class="inline-block bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg text-lg font-semibold">
                Start Watching Now →
            </a>
        @else
            <div class="mb-6">
                <svg class="w-20 h-20 mx-auto text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
            </div>

            <h1 class="text-4xl font-bold mb-4">Payment Processing...</h1>
            <p class="text-gray-600 mb-8">Your order <span class="font-mono">{{ $order->order_number }}</span> is being processed.</p>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <p class="font-semibold">Payment is being verified</p>
                <p class="text-sm text-gray-600 mt-2">This usually takes a few moments. We'll notify you once confirmed.</p>
            </div>

            <a href="{{ route('dashboard') }}" class="inline-block bg-gray-600 hover:bg-gray-700 text-white px-8 py-3 rounded-lg font-semibold">
                Go to Dashboard
            </a>
        @endif
    </div>
</div>
@endsection
