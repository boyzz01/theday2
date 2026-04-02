<?php

// database/migrations/2026_04_01_000012_create_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->unsignedSmallInteger('max_invitations')->default(1);
            $table->unsignedSmallInteger('max_gallery_photos')->default(5);
            $table->boolean('custom_music')->default(false);
            $table->boolean('remove_watermark')->default(false);
            $table->boolean('custom_domain')->default(false);
            $table->boolean('analytics_access')->default(false);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
