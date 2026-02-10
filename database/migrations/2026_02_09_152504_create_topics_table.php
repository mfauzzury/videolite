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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('school_year', ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6']);
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->string('level', 50)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('material_count')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subject_id', 'school_year', 'slug'], 'unique_subject_year_slug');
            $table->index('subject_id');
            $table->index('school_year');
            $table->index('status');
            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
