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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['subject', 'subject_year', 'topic']);

            // Flexible references based on type
            $table->foreignId('subject_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('school_year', ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'])->nullable();
            $table->foreignId('topic_id')->nullable()->constrained()->cascadeOnDelete();

            // Pricing
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('compare_at_price', 10, 2)->nullable();

            // Display
            $table->string('thumbnail_path', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('order_column')->default(0);

            // SEO
            $table->string('meta_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('subject_id');
            $table->index('topic_id');
            $table->index('status');
            $table->index(['is_featured', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
