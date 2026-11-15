<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 40)->nullable();

            $table->string('property_type', 20)->default('house');
            $table->unsignedSmallInteger('bedrooms')->default(1);
            $table->unsignedSmallInteger('bathrooms')->default(1);
            $table->unsignedInteger('sqft')->nullable();
            $table->string('frequency', 20)->default('one_time');

            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('duration_minutes');

            $table->string('street');
            $table->string('unit')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip', 10);
            $table->text('instructions')->nullable();

            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone', 30)->nullable();
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->boolean('requires_quote')->default(false);

            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_method', 20)->default('pay_later');
            $table->string('payment_status', 20)->default('unpaid'); // unpaid | paid | refunded | partially_refunded

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('work_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['scheduled_at', 'status']);
            $table->index(['employee_id', 'scheduled_at']);
            $table->index(['user_id', 'scheduled_at']);
        });

        Schema::create('booking_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_extra_id')->nullable()->constrained('service_extras')->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 20)->default('offline'); // stripe | offline
            $table->string('method', 20)->default('pay_later');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending'); // pending | paid | failed | refunded | partially_refunded
            $table->string('transaction_reference')->nullable()->index();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('payment_intent_id')->nullable()->index();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->string('refund_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status', 20)->default('unpaid'); // unpaid | paid | void | refunded
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Stripe webhook idempotency: each event id is processed once.
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('event_id')->unique();
            $table->string('type');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->index(['is_approved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_status_histories');
        Schema::dropIfExists('booking_extras');
        Schema::dropIfExists('bookings');
    }
};
