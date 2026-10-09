<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The salon's service list. Prices and durations live here and nowhere
     * else: the AI Beautician may only quote a price it has just read through
     * a tool call.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // hair | nails | skin | makeup | spa
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('translations')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('duration_minutes'); // chair time, in 30-minute steps
            $table->decimal('deposit', 12, 2)->default(0); // paid to hold the booking, 0 = free
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable(); // signature, bestseller, halal, vegan, ammonia_free, sensitive_skin, for_men, bridal, ...
            $table->json('cautions')->nullable(); // ingredients to flag: ammonia, fragrance, latex, nut_oil, acrylic, peroxide, ...
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_unavailable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['salon_id', 'slug']);
            $table->index(['salon_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
