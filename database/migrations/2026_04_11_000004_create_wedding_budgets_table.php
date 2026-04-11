<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_budgets', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('invitation_id')->nullable();
            $table->foreign('invitation_id')->references('id')->on('invitations')->nullOnDelete();
            $table->unsignedBigInteger('total_budget')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id']);
            $table->index(['invitation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_budgets');
    }
};
