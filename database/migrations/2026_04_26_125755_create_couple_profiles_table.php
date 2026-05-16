<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couple_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('groom_name')->nullable();
            $table->string('groom_nickname', 50)->nullable();
            $table->string('groom_instagram', 100)->nullable();
            $table->string('groom_parent_names')->nullable();
            $table->string('bride_name')->nullable();
            $table->string('bride_nickname', 50)->nullable();
            $table->string('bride_instagram', 100)->nullable();
            $table->string('bride_parent_names')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couple_profiles');
    }
};
