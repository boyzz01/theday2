<?php

// database/migrations/2026_04_20_000002_make_plan_id_nullable_add_addon_quantity_to_transactions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->uuid('plan_id')->nullable()->change();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
            $table->tinyInteger('addon_quantity')->unsigned()->nullable()->after('subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('addon_quantity');
            $table->dropForeign(['plan_id']);
            $table->uuid('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
        });
    }
};
