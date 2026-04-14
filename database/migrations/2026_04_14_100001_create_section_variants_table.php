<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('section_type');
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('status', ['active', 'deprecated', 'archived'])->default('active');
            $table->json('schema_json')->nullable();
            $table->json('ui_meta_json')->nullable();
            $table->string('render_component')->nullable();
            $table->string('editor_component')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('section_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_variants');
    }
};
