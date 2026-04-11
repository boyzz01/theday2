<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('primary_invitation_id')->nullable()->constrained('invitations')->nullOnDelete();
            $table->date('event_date')->nullable();
            $table->timestamp('checklist_initialized_at')->nullable();
            $table->timestamps();

            $table->unique('user_id'); // 1 wedding plan per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_plans');
    }
};
