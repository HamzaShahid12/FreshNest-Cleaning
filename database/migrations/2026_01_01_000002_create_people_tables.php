<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('street');
            $table->string('unit')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip', 10);
            $table->text('instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('job_title')->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();       // service ids; empty = all services
            $table->json('working_days')->nullable(); // ISO weekday numbers 1 (Mon) - 7 (Sun)
            $table->string('work_start', 5)->default('08:00');
            $table->string('work_end', 5)->default('17:00');
            $table->string('emergency_contact')->nullable();
            $table->boolean('is_available')->default(true);
            $table->date('hired_on')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->decimal('value', 10, 2);
            $table->decimal('min_subtotal', 10, 2)->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->date('starts_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('employee_profiles');
        Schema::dropIfExists('addresses');
    }
};
