<?php

declare(strict_types=1);

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Back-fill slugs for existing plans
        Plan::where('name', 'Free')->update(['slug' => 'free']);
        Plan::where('name', 'Silver')->update(['slug' => 'silver']);
        Plan::where('name', 'Gold')->update(['slug' => 'gold']);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
