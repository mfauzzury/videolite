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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle', 500)->nullable();
            $table->text('description')->nullable();

            // Mathematics specific fields
            $table->string('school_year')->nullable(); // Form 1, Form 2, etc.
            $table->string('topic')->nullable(); // Algebra, Geometry, etc.

            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_at_price', 10, 2)->nullable();

            // Course metadata
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'all_levels'])->default('all_levels');
            $table->string('language', 10)->default('en');
            $table->integer('duration_minutes')->unsigned()->nullable();

            // Status & visibility
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);

            // Learning outcomes (JSON arrays)
            $table->json('what_you_will_learn')->nullable();
            $table->json('requirements')->nullable();
            $table->json('target_audience')->nullable();

            // SEO
            $table->string('meta_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();

            // Relationships
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();

            // Stats (denormalized for performance)
            $table->integer('students_count')->unsigned()->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'published_at']);
            $table->index('category_id');
            $table->index('instructor_id');
            $table->index(['is_featured', 'status']);
            $table->index('school_year');
            $table->index('topic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
