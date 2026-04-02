<?php

// database/migrations/2026_04_01_000004_create_invitations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('template_id')->constrained('templates')->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->enum('event_type', ['pernikahan', 'ulang_tahun']);
            $table->json('custom_config')->nullable();
            $table->enum('status', ['draft', 'published', 'expired'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_password_protected')->default(false);
            $table->string('password')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('status');
            $table->index('user_id');
            $table->index('event_type');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
