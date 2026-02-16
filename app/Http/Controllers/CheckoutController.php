<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function thankYou(Request $request)
    {
        $orderNumber = $request->query('order');
        $order = Order::where('order_number', $orderNumber)
            ->with('package')
            ->firstOrFail();

        // Verify ownership
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return view('checkout.thank-you', compact('order'));
    }
}
