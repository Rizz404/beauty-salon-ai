<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stylists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('title')->nullable(); // e.g. "Senior colorist"
            $table->text('description')->nullable();
            $table->json('translations')->nullable();
            $table->string('specialty'); // the service category this stylist performs
            $table->unsignedTinyInteger('years_experience')->default(1);
            $table->json('skills')->nullable(); // balayage, keratin, gel_nails, bridal_makeup, ...
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['salon_id', 'slug']);
        });

        // The stylist's portfolio.
        Schema::create('stylist_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stylist_id')->constrained()->cascadeOnDelete();
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        /**
         * Mock booking data: the one table the AI is NEVER allowed to answer
         * from memory. Each row is one 30-minute block of a stylist's day; a
         * service takes as many consecutive blocks as its duration needs. A
         * salon-software adapter can replace it later without touching the AI
         * or knowledge-base layers.
         */
        Schema::create('stylist_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stylist_id')->constrained()->cascadeOnDelete();
            $table->date('slot_date');
            $table->string('time_slot', 5); // HH:MM, the start of the 30-minute block
            $table->boolean('is_booked')->default(false);
            $table->timestamps();
            $table->unique(['stylist_id', 'slot_date', 'time_slot'], 'stylist_slots_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stylist_slots');
        Schema::dropIfExists('stylist_images');
        Schema::dropIfExists('stylists');
    }
};
