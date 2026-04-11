<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['system', 'user'])->default('user');
            $table->foreignUuid('template_id')->nullable()->constrained('checklist_templates')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category');     // matches ChecklistTaskCategory enum values
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['todo', 'done', 'archived'])->default('todo');
            $table->date('due_date')->nullable();
            $table->integer('sort_order')->nullable();
            $table->boolean('is_user_modified')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['wedding_plan_id', 'status']);
            $table->index(['wedding_plan_id', 'category']);
            $table->index(['wedding_plan_id', 'source', 'is_user_modified']); // for recalculation
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_tasks');
    }
};
