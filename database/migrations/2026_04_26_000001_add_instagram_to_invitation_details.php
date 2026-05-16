<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_details', function (Blueprint $table) {
            $table->string('groom_instagram', 100)->nullable()->after('groom_nickname');
            $table->string('bride_instagram', 100)->nullable()->after('bride_nickname');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_details', function (Blueprint $table) {
            $table->dropColumn(['groom_instagram', 'bride_instagram']);
        });
    }
};
