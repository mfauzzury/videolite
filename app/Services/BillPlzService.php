<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillPlzService
{
    protected string $apiKey;
    protected string $collectionId;
    protected string $signatureKey;
    protected string $baseUrl;

    public function __construct()
    {
        // Read from settings first, fallback to config
        $this->apiKey = Setting::get('billplz_api_key', config('services.billplz.api_key'));
        $this->collectionId = Setting::get('billplz_collection_id', config('services.billplz.collection_id'));
        $this->signatureKey = Setting::get('billplz_signature_key', config('services.billplz.signature_key'));

        $mode = Setting::get('billplz_mode', config('services.billplz.mode', 'sandbox'));
        $this->baseUrl = $mode === 'production'
            ? 'https://www.billplz.com/api/v3'
            : 'https://www.billplz-sandbox.com/api/v3';
    }

    /**
     * Create a bill on BillPlz
     */
    public function createBill(Order $order, string $redirectUrl, string $callbackUrl): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->asForm()
                ->post("{$this->baseUrl}/bills", [
                    'collection_id' => $this->collectionId,
                    'description' => "Order #{$order->order_number} - {$order->course->title}",
                    'email' => $order->customer_email,
                    'name' => $order->customer_name,
                    'amount' => $order->total_amount * 100, // Convert to cents
                    'reference_1_label' => 'Order Number',
                    'reference_1' => $order->order_number,
                    'reference_2_label' => 'Course',
                    'reference_2' => $order->course->title,
                    'callback_url' => $callbackUrl,
                    'redirect_url' => $redirectUrl,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Update order with BillPlz details
                $order->update([
                    'billplz_bill_id' => $data['id'],
                    'billplz_url' => $data['url'],
                ]);

                Log::info('BillPlz bill created', [
                    'order_id' => $order->id,
                    'bill_id' => $data['id'],
                ]);

                return [
                    'success' => true,
                    'bill_id' => $data['id'],
                    'url' => $data['url'],
                ];
            }

            Log::error('BillPlz bill creation failed', [
                'order_id' => $order->id,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create payment bill',
            ];

        } catch (\Exception $e) {
            Log::error('BillPlz API exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get bill details from BillPlz
     */
    public function getBill(string $billId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->get("{$this->baseUrl}/bills/{$billId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to retrieve BillPlz bill', [
                'bill_id' => $billId,
                'response' => $response->json(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('BillPlz getBill exception', [
                'bill_id' => $billId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify webhook signature from BillPlz
     */
    public function verifyWebhookSignature(array $data, string $signature): bool
    {
        // BillPlz uses X-Signature header with specific fields
        $signatureFields = [
            'amount',
            'collection_id',
            'id',
            'paid',
            'paid_at',
            'state',
            'url',
        ];

        $signatureString = '';
        foreach ($signatureFields as $field) {
            $signatureString .= $field . $data[$field] ?? '';
        }

        $calculatedSignature = hash_hmac('sha256', $signatureString, $this->signatureKey);

        $isValid = hash_equals($calculatedSignature, $signature);

        if (!$isValid) {
            Log::warning('BillPlz webhook signature mismatch', [
                'expected' => $calculatedSignature,
                'received' => $signature,
            ]);
        }

        return $isValid;
    }

    /**
     * Parse webhook data from BillPlz callback
     */
    public function parseWebhookData(array $data): array
    {
        return [
            'bill_id' => $data['id'] ?? null,
            'collection_id' => $data['collection_id'] ?? null,
            'paid' => ($data['paid'] ?? 'false') === 'true',
            'state' => $data['state'] ?? null,
            'amount' => isset($data['amount']) ? ((int) $data['amount']) / 100 : 0,
            'paid_at' => $data['paid_at'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'transaction_status' => $data['transaction_status'] ?? null,
        ];
    }
}
