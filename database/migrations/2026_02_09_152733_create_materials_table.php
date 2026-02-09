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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('type', ['video', 'pdf']);

            // Video fields (nullable for PDFs)
            $table->string('video_path', 500)->nullable();
            $table->string('video_filename')->nullable();
            $table->unsignedBigInteger('video_size_bytes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_path', 500)->nullable();

            // PDF fields (nullable for videos)
            $table->string('pdf_path', 500)->nullable();
            $table->string('pdf_filename')->nullable();
            $table->unsignedBigInteger('pdf_size_bytes')->nullable();
            $table->unsignedInteger('pdf_pages')->nullable();

            // Common fields
            $table->unsignedInteger('order_column')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index('topic_id');
            $table->index('type');
            $table->index('status');
            $table->index('order_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
