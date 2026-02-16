<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Package;
use App\Services\BillPlzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected BillPlzService $billplz;

    public function __construct(BillPlzService $billplz)
    {
        $this->billplz = $billplz;
    }

    /**
     * Get user's order history
     */
    public function index(Request $request)
    {
        $orders = Auth::user()->orders()
            ->with(['package:id,name,slug', 'enrollment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Get single order details
     */
    public function show(Order $order)
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this order',
            ], 403);
        }

        $order->load(['package', 'enrollment']);

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * Create a new order and generate payment URL
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $package = Package::findOrFail($request->package_id);

        // Check if package is active
        if ($package->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This package is not available for purchase',
            ], 400);
        }

        // Check if user already enrolled
        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->where('status', 'active')
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this package',
            ], 400);
        }

        // Check if there's a pending order for this package
        $pendingOrder = Order::where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->where('payment_status', 'pending')
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($pendingOrder) {
            // Return existing pending order
            return response()->json([
                'success' => true,
                'message' => 'Pending order already exists',
                'order' => $pendingOrder,
                'payment_url' => $pendingOrder->billplz_url,
            ]);
        }

        try {
            DB::beginTransaction();

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'package_id' => $package->id,
                'subtotal' => $package->price,
                'total_amount' => $package->price,
                'payment_method' => 'billplz',
                'payment_status' => 'pending',
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Create BillPlz payment
            $redirectUrl = url('/checkout/thank-you?order=' . $order->order_number);
            $callbackUrl = url('/api/webhooks/billplz');

            $billResult = $this->billplz->createBill($order, $redirectUrl, $callbackUrl);

            if (!$billResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment',
                    'error' => $billResult['error'] ?? 'Unknown error',
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order->fresh(),
                'payment_url' => $billResult['url'],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
