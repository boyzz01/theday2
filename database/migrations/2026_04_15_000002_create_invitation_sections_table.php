<?php

// database/migrations/2026_04_15_000002_create_invitation_sections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invitation_sections')) {
            return; // Table already exists (created in a prior session)
        }

        Schema::create('invitation_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invitation_id')->constrained('invitations')->cascadeOnDelete();

            // 'cover' | 'konten_utama' | 'couple' | 'quote' | 'events' | ...
            $table->string('section_key', 50);
            // 'informasi' | 'acara' | 'media' | 'interaksi' | 'tampilan' | 'publikasi'
            $table->string('step_key', 30);

            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_required')->default(false);

            // empty | incomplete | complete | warning | disabled | error
            $table->string('completion_status', 20)->default('empty');

            // For sections that don't have a dedicated table (quote, countdown, etc.)
            $table->json('data_json')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['invitation_id', 'section_key']);
            $table->index('invitation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_sections');
    }
};
