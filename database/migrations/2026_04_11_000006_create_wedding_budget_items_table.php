<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('wedding_budgets')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('wedding_budget_categories')->restrictOnDelete();
            $table->uuid('invitation_id')->nullable();
            $table->foreign('invitation_id')->references('id')->on('invitations')->nullOnDelete();
            $table->string('title');
            $table->string('vendor_name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('planned_amount')->default(0);
            $table->unsignedBigInteger('actual_amount')->nullable();
            $table->enum('payment_status', ['unpaid', 'dp', 'paid'])->default('unpaid');
            $table->date('payment_date')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['budget_id', 'category_id']);
            $table->index(['budget_id', 'payment_status']);
            $table->index(['budget_id', 'is_archived']);
            $table->index(['payment_date']);
            $table->index(['title']);
            $table->index(['vendor_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_budget_items');
    }
};
