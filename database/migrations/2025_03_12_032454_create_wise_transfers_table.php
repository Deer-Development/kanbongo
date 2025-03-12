<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wise_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paycheck_id')->constrained('paychecks')->cascadeOnDelete();
            $table->string('wise_transfer_id');
            $table->string('wise_recipient_id');
            $table->string('target_account_id');
            $table->string('quote_id');
            $table->string('status');
            $table->decimal('source_amount', 10, 2);
            $table->string('source_currency', 3);
            $table->decimal('target_amount', 10, 2);
            $table->string('target_currency', 3);
            $table->decimal('rate', 10, 6);
            $table->string('reference')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wise_transfers');
    }
}; 