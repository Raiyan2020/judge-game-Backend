<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The MyFatoorah invoice id returned by SendPayment. Persisted so the
     * webhook (and the reconcile command) can map a payment event back to its
     * subscription and re-verify via GetPaymentStatus without relying solely on
     * the gateway echoing CustomerReference. Also the natural idempotency key.
     */
    public function up(): void
    {
        Schema::table('package_subscriptions', function (Blueprint $table) {
            $table->string('payment_invoice_id')->nullable()->index()->after('payment_method_id');
        });
    }

    public function down(): void
    {
        Schema::table('package_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['payment_invoice_id']);
            $table->dropColumn('payment_invoice_id');
        });
    }
};
