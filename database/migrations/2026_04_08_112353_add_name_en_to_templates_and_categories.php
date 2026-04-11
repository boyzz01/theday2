<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_categories', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->text('description_en')->nullable()->after('description');
        });

        // Seed English translations for categories
        DB::table('template_categories')->where('slug', 'pernikahan')->update([
            'name_en'        => 'Wedding',
            'description_en' => 'Wedding invitation templates',
        ]);
        // Seed English translations for templates
        $translations = [
            'Bunga Abadi'  => ['name_en' => 'Eternal Flower',  'description_en' => 'Elegant wedding template with floral touches and warm golden hues.'],
            'Langit Senja' => ['name_en' => 'Dusk Sky',        'description_en' => 'Romantic wedding template with a stunning sunset color palette.'],
            'Hijau Daun'   => ['name_en' => 'Leaf Green',      'description_en' => 'Fresh wedding template with a natural, lush greenery vibe.'],
            'Nusantara'    => ['name_en' => 'Nusantara',       'description_en' => 'Premium Javanese royal-style wedding template — majestic, sacred, and grand. Features animated gates, batik kawung ornaments, sulur lung-lungan scrollwork, and lotus mandala.'],
        ];

        foreach ($translations as $name => $trans) {
            DB::table('templates')->where('name', $name)->update($trans);
        }
    }

    public function down(): void
    {
        Schema::table('template_categories', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'description_en']);
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'description_en']);
        });
    }
};
