<?php

// database/migrations/2026_04_01_000005_create_invitation_details_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invitation_id')->unique()->constrained('invitations')->cascadeOnDelete();

            // Pernikahan fields
            $table->string('groom_name')->nullable();
            $table->string('bride_name')->nullable();
            $table->string('groom_photo_url')->nullable();
            $table->string('bride_photo_url')->nullable();
            $table->string('groom_parent_names')->nullable();
            $table->string('bride_parent_names')->nullable();

            // Common fields
            $table->text('opening_text')->nullable();
            $table->text('closing_text')->nullable();
            $table->string('cover_photo_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_details');
    }
};
