<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Worldwide addresses: free-form state/postal code, country, and map coordinates. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['bookings', 'addresses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('state', 100)->nullable()->change();
                $table->string('zip', 20)->nullable()->change();
            });
            Schema::table($name, function (Blueprint $table) {
                $table->string('country', 100)->nullable();
                $table->string('country_code', 2)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['bookings', 'addresses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['country', 'country_code', 'latitude', 'longitude']);
            });
        }
    }
};
