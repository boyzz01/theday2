<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_details', function (Blueprint $table) {
            $table->string('groom_nickname', 10)->nullable()->after('groom_name');
            $table->string('bride_nickname', 10)->nullable()->after('bride_name');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_details', function (Blueprint $table) {
            $table->dropColumn(['groom_nickname', 'bride_nickname']);
        });
    }
};
