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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 280)->unique();
            $table->text('synopsis')->nullable();
            $table->char('language', 2)->default('en');
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->foreignId('license_type_id')->constrained('license_types');
            $table->string('rights_source', 500)->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('view_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('language');
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
