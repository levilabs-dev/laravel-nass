<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nass_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('merchant')->default('default');
            $table->string('order_id')->index();
            $table->string('transaction_id')->unique();
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('checkout_url')->nullable();
            $table->string('wallet_token')->nullable();
            $table->nullableMorphs('payable');
            $table->timestamp('checked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nass_wallet_transactions');
    }
};
