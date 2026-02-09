<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Order identification
            $table->string('order_number', 50)->unique(); // ORD-YYYYMMDD-XXXXX

            // Customer
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();

            // Pricing (simplified - no coupon support in v1)
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total_amount', 10, 2);

            // Payment
            $table->enum('payment_method', ['billplz'])->default('billplz');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            // BillPlz specific
            $table->string('billplz_bill_id', 100)->nullable();
            $table->text('billplz_url')->nullable();
            $table->timestamp('billplz_paid_at')->nullable();
            $table->string('billplz_transaction_id', 100)->nullable();

            // Customer info (cached for records)
            $table->string('customer_name');
            $table->string('customer_email');

            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->timestamp('paid_at')->nullable();

            $table->index('user_id');
            $table->index('package_id');
            $table->index('payment_status');
            $table->index('billplz_bill_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
