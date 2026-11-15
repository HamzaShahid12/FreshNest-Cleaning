<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Service areas can be any city in any country: state becomes optional and a country is added. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->string('state', 100)->nullable()->default(null)->change();
        });
        Schema::table('service_areas', function (Blueprint $table) {
            $table->string('country', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
