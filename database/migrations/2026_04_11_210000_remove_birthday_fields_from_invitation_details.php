<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invitation_details', 'birthday_person_name')) {
            Schema::table('invitation_details', function (Blueprint $table) {
                $table->dropColumn(['birthday_person_name', 'birthday_photo_url', 'birthday_age']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('invitation_details', function (Blueprint $table) {
            $table->string('birthday_person_name')->nullable();
            $table->string('birthday_photo_url')->nullable();
            $table->unsignedTinyInteger('birthday_age')->nullable();
        });
    }
};
