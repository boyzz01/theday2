<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('section_key');
            $table->string('section_type');
            $table->string('label');
            $table->foreignUuid('default_variant_id')->nullable()->constrained('section_variants')->nullOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_enabled_by_default')->default(true);
            $table->boolean('is_removable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('supports_multiple_items')->default(false);
            $table->boolean('supports_reordering')->default(false);
            $table->json('default_data_json')->nullable();
            $table->json('default_style_json')->nullable();
            $table->json('rules_json')->nullable();
            $table->json('visibility_conditions_json')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'section_key']);
            $table->index('section_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_sections');
    }
};
