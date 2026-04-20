<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_tasks', function (Blueprint $table) {
            $table->string('assignee_type')->nullable()->after('due_date');
            $table->string('assignee_label')->nullable()->after('assignee_type');
            $table->boolean('reminder_enabled')->default(false)->after('assignee_label');
            $table->integer('reminder_offset_days')->nullable()->after('reminder_enabled');
            $table->timestamp('last_reminded_at')->nullable()->after('reminder_offset_days');
            $table->timestamp('next_reminder_at')->nullable()->after('last_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'assignee_type',
                'assignee_label',
                'reminder_enabled',
                'reminder_offset_days',
                'last_reminded_at',
                'next_reminder_at',
            ]);
        });
    }
};
