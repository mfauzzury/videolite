<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Order;
use App\Services\BillPlzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected BillPlzService $billplz;

    public function __construct(BillPlzService $billplz)
    {
        $this->billplz = $billplz;
    }

    /**
     * Handle BillPlz webhook callback
     */
    public function billplz(Request $request)
    {
        Log::info('BillPlz webhook received', $request->all());

        // Get signature from header
        $signature = $request->header('X-Signature');

        if (!$signature) {
            Log::warning('BillPlz webhook missing signature');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        // Verify signature
        if (!$this->billplz->verifyWebhookSignature($request->all(), $signature)) {
            Log::error('BillPlz webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Parse webhook data
        $webhookData = $this->billplz->parseWebhookData($request->all());

        // Find order by BillPlz bill ID
        $order = Order::where('billplz_bill_id', $webhookData['bill_id'])->first();

        if (!$order) {
            Log::error('BillPlz webhook: Order not found', [
                'bill_id' => $webhookData['bill_id'],
            ]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Check if payment is successful
        if ($webhookData['paid'] && $webhookData['state'] === 'paid') {
            try {
                DB::beginTransaction();

                // Update order status
                $order->markAsPaid(
                    $webhookData['transaction_id'],
                    $webhookData['paid_at']
                );

                // Create enrollment if not exists
                $enrollment = Enrollment::where('user_id', $order->user_id)
                    ->where('course_id', $order->course_id)
                    ->first();

                if (!$enrollment) {
                    $enrollment = Enrollment::create([
                        'user_id' => $order->user_id,
                        'course_id' => $order->course_id,
                        'order_id' => $order->id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);

                    Log::info('Enrollment created from webhook', [
                        'enrollment_id' => $enrollment->id,
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'course_id' => $order->course_id,
                    ]);
                }

                DB::commit();

                Log::info('BillPlz webhook processed successfully', [
                    'order_id' => $order->id,
                    'enrollment_id' => $enrollment->id,
                ]);

                // TODO: Send confirmation email to user (Phase 8)

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('BillPlz webhook processing failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'error' => 'Failed to process payment',
                ], 500);
            }

        } elseif ($webhookData['state'] === 'failed') {
            // Mark order as failed
            $order->markAsFailed();

            Log::info('BillPlz payment failed', [
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as failed',
            ], 200);
        }

        // Return 200 for other states to acknowledge receipt
        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
        ], 200);
    }
}
