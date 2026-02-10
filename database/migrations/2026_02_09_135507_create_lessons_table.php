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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete(); // Denormalized for quick access

            $table->string('title');
            $table->text('description')->nullable();

            // Lesson type
            $table->enum('type', ['video', 'article'])->default('video');

            // Video metadata
            $table->string('video_path', 500)->nullable();
            $table->string('video_filename')->nullable();
            $table->bigInteger('video_size_bytes')->unsigned()->nullable();
            $table->integer('duration_seconds')->unsigned()->nullable();

            // PDF reference material
            $table->string('pdf_path', 500)->nullable();
            $table->string('pdf_filename')->nullable();

            // Article content (if type=article)
            $table->longText('article_content')->nullable();

            // Ordering
            $table->integer('order_column')->unsigned()->default(0);

            $table->timestamps();

            $table->index(['section_id', 'order_column']);
            $table->index('course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
