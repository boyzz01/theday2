<?php

// database/migrations/2026_04_02_182452_create_guest_drafts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('guest_session_id')->index();
            $table->foreignUuid('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->json('data')->nullable();
            $table->unsignedTinyInteger('step')->default(1);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_drafts');
    }
};
