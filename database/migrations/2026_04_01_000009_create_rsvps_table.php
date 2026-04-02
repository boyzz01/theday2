<?php

// database/migrations/2026_04_01_000009_create_rsvps_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsvps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invitation_id')->constrained('invitations')->cascadeOnDelete();
            $table->string('guest_name');
            $table->string('phone', 20)->nullable();
            $table->enum('attendance', ['hadir', 'tidak_hadir', 'ragu'])->default('ragu');
            $table->unsignedSmallInteger('guest_count')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('invitation_id');
            $table->index('attendance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
