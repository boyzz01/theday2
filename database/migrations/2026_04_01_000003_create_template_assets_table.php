<?php

// database/migrations/2026_04_01_000003_create_template_assets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('file_url');
            $table->enum('type', ['bg', 'image', 'font', 'audio']);
            $table->string('label')->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('bytes');
            $table->timestamps();

            $table->index('template_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_assets');
    }
};
