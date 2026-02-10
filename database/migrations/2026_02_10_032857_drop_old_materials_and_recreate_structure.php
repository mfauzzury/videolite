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
        // Drop tables with FK to packages first
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('orders');

        // Drop old tables
        Schema::dropIfExists('material_progress');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('topics');

        // Recreate topics table (simplified - no school_year)
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('subject_id');
            $table->index('status');
            $table->index('slug');
        });

        // Create videos table (WP-style media library)
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('video_path', 500);
            $table->string('video_filename');
            $table->unsignedBigInteger('video_size_bytes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('subject_id');
            $table->index('status');
            $table->index('slug');
        });

        // Create topic_video pivot table (many-to-many)
        Schema::create('topic_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['topic_id', 'video_id']);
            $table->index('topic_id');
            $table->index('video_id');
        });

        // Recreate packages table (simplified)
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('order_column')->default(0);
            $table->string('meta_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('is_featured');
            $table->index('slug');
        });

        // Create package_video pivot table (many-to-many)
        Schema::create('package_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->unique(['package_id', 'video_id']);
            $table->index('package_id');
            $table->index('video_id');
        });

        // Create video_progress table
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
            $table->index('user_id');
            $table->index('video_id');
        });

        // Recreate orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['billplz'])->default('billplz');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('billplz_bill_id', 100)->nullable();
            $table->text('billplz_url')->nullable();
            $table->timestamp('billplz_paid_at')->nullable();
            $table->string('billplz_transaction_id', 100)->nullable();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('user_id');
            $table->index('package_id');
            $table->index('payment_status');
            $table->index('created_at');
        });

        // Recreate enrollments table
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'expired', 'suspended'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('enrolled_at');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'package_id']);
            $table->index('user_id');
            $table->index('package_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('video_progress');
        Schema::dropIfExists('package_video');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('topic_video');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('topics');
    }
};
