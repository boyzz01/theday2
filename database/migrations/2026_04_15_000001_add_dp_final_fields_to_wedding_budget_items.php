<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_budget_items', function (Blueprint $table) {
            $table->unsignedBigInteger('dp_amount')->nullable()->after('actual_amount');
            $table->boolean('dp_paid')->default(false)->after('dp_amount');
            $table->timestamp('dp_paid_at')->nullable()->after('dp_paid');

            $table->unsignedBigInteger('final_amount')->nullable()->after('dp_paid_at');
            $table->boolean('final_paid')->default(false)->after('final_amount');
            $table->timestamp('final_paid_at')->nullable()->after('final_paid');

            $table->date('due_date')->nullable()->after('final_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_budget_items', function (Blueprint $table) {
            $table->dropColumn([
                'dp_amount', 'dp_paid', 'dp_paid_at',
                'final_amount', 'final_paid', 'final_paid_at',
                'due_date',
            ]);
        });
    }
};
