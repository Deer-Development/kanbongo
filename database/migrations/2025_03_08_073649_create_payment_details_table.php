<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Wise Profile Details
            $table->string('wise_profile_id')->nullable();
            $table->string('wise_profile_type')->nullable(); // personal/business
            
            // Personal/Business Details
            $table->string('full_name')->nullable();
            $table->string('registration_number')->nullable(); // For business
            $table->date('date_of_birth')->nullable();
            
            // Address Details
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            
            // Bank Account Details for Receiving Money
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('sort_code')->nullable(); // UK
            $table->string('iban')->nullable(); // EU
            $table->string('bic')->nullable(); // SWIFT/BIC
            $table->string('routing_number')->nullable(); // US ACH
            $table->string('bank_code')->nullable();
            
            // Business Details
            $table->string('business_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('business_subcategory')->nullable();
            
            // Verification Status
            $table->boolean('is_verified')->default(false);
            $table->json('verification_documents')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Currency Preferences
            $table->string('default_currency')->nullable();
            $table->json('supported_currencies')->nullable();
            
            // Balance Account Details
            $table->string('wise_balance_account_id')->nullable();
            $table->json('balance_accounts')->nullable(); // Store multiple currency balances
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
