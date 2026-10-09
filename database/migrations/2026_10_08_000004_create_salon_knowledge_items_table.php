<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The approved salon knowledge base the AI retrieves from. No record here,
     * no answer from the AI: static and semi-static facts only, never service
     * prices or stylist availability (see services and stylist_slots).
     */
    public function up(): void
    {
        Schema::create('salon_knowledge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('title');
            $table->text('body');
            $table->json('translations')->nullable();
            $table->json('tags')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['salon_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_knowledge_items');
    }
};
