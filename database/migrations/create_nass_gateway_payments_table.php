<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nass_gateway_payments', function (Blueprint $table) {
            $table->id();
            $table->string('merchant')->default('default');
            $table->string('order_id')->unique();
            $table->string('status')->default('pending')->index();
            $table->string('response_code')->nullable();
            $table->string('action_code')->nullable();
            $table->string('status_message')->nullable();
            $table->string('rrn')->nullable()->index();
            $table->string('int_ref')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('card')->nullable();
            $table->string('terminal')->nullable();
            $table->string('payment_url')->nullable();
            $table->text('p_sign')->nullable();
            $table->json('transaction_params')->nullable();
            $table->nullableMorphs('payable');
            $table->timestamp('checked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nass_gateway_payments');
    }
};
