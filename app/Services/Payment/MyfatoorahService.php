<?php

namespace App\Services\Payment;

use App\Models\PackageSubscription;
use App\Services\PackageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MyfatoorahService
{
    public function __construct(
        protected PackageService $orderInvoiceNotificationService,
    ) {}

    public function getPaymentUrl(PackageSubscription $packageSubscription)
    {
        try {
            $http = Http::withToken(config('payment.mayfatoorah.token'))
                ->post(config('payment.mayfatoorah.url') . '/SendPayment', [
                    'CustomerName' => $packageSubscription->user?->name,
                    'CustomerMobile' => $packageSubscription->user?->phone,
                    'InvoiceValue' =>  $packageSubscription->total,
                    'CallBackUrl' => route('payment.success'),
                    'Errorurl' => route('payment.error'),
                    'CustomerReference' => $packageSubscription->id,
                    'PaymentMethodId' => $packageSubscription->payment_method_id ?? 1,
                    'NotificationOption' => 'LNK',
                ]);

            $data = $http->json();

            // Persist the invoice id so the `subscriptions:reconcile` command can
            // map an unpaid row back to its invoice. NON-FATAL: if the migration
            // that adds the column hasn't run yet, payment must still work — the
            // webhook + redirect paid-flip don't depend on this column (they use
            // the CustomerReference the API returns), only reconcile does.
            $invoiceId = $data['Data']['InvoiceId'] ?? null;
            if ($invoiceId) {
                try {
                    $packageSubscription->update(['payment_invoice_id' => $invoiceId]);
                } catch (\Throwable $e) {
                    Log::warning('Could not store payment_invoice_id (run migrations to enable reconcile): ' . $e->getMessage());
                }
            }

            return $data['Data']['InvoiceURL'];
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'payment' => __('error in payment gateway'),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Authoritatively confirm a payment with MyFatoorah and flip the matching
     * subscription to paid — the single source of truth for `payment_status`,
     * called from BOTH the browser `/success` redirect AND the server-to-server
     * webhook, so it must be safe to run twice and from either id type.
     *
     * @param  string  $key      the lookup key value (a PaymentId or InvoiceId)
     * @param  string  $keyType  'PaymentId' (redirect) or 'InvoiceId' (webhook)
     * @return PackageSubscription|null  the paid subscription, or null
     */
    public function validate($key, string $keyType = 'PaymentId')
    {
        try {
            $http = Http::withToken(config('payment.mayfatoorah.token'))
                ->post(config('payment.mayfatoorah.url') . '/GetPaymentStatus', [
                    'Key' => $key,
                    'KeyType' => $keyType,
                ]);
            $data = $http->json();

            $ref = $data['Data']['CustomerReference'] ?? null;
            if (! $ref) {
                return null;
            }

            $subscription = PackageSubscription::find($ref);
            if (! $subscription) {
                return null;
            }

            // Refund is TERMINAL — never resurrect access for a refunded row,
            // whatever the gateway now reports for the invoice.
            if ($subscription->payment_status === 'refunded') {
                return $subscription;
            }

            // Must be a genuinely successful, PAID invoice.
            if (empty($data['IsSuccess']) || ($data['Data']['InvoiceStatus'] ?? null) !== 'Paid') {
                return $subscription;
            }

            // Amount guard: the amount actually paid must match what we charged.
            // Refuse the flip on any mismatch rather than granting access cheaply.
            $paidValue = (float) ($data['Data']['InvoiceValue'] ?? 0);
            $expected = (float) $subscription->total;
            if (round($paidValue, 3) < round($expected, 3)) {
                Log::warning('MyFatoorah amount mismatch on subscription ' . $subscription->id, [
                    'expected' => $expected,
                    'paid' => $paidValue,
                ]);
                return $subscription;
            }

            // Idempotent flip: a conditional UPDATE, so concurrent redirect +
            // webhook can't both run side effects. Only the caller that actually
            // changed the row (affected == 1) owns the "just became paid" moment.
            $flipped = PackageSubscription::where('id', $subscription->id)
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                        ->orWhereNotIn('payment_status', ['paid', 'refunded']);
                })
                ->update([
                    'payment_status' => 'paid',
                    'payment_object' => $data['Data'],
                ]);

            if ($flipped) {
                // Side effects that must fire exactly once belong here.
                Log::info('Subscription ' . $subscription->id . ' marked paid.');
            }

            return $subscription->refresh();
        } catch (\Exception $e) {
            // Never swallow into a value: a webhook needs a real signal, and a
            // silent failure would leave a charged user unpaid with no trace.
            Log::error('MyFatoorah validate failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Confirm a refund with MyFatoorah, then revoke access. Called by the
     * webhook on a REFUND event — verified via the API, never trusted from the
     * webhook body alone.
     */
    public function markRefunded($key, string $keyType = 'InvoiceId'): ?PackageSubscription
    {
        try {
            $http = Http::withToken(config('payment.mayfatoorah.token'))
                ->post(config('payment.mayfatoorah.url') . '/GetPaymentStatus', [
                    'Key' => $key,
                    'KeyType' => $keyType,
                ]);
            $data = $http->json();

            $ref = $data['Data']['CustomerReference'] ?? null;
            if (! $ref) {
                return null;
            }

            $subscription = PackageSubscription::find($ref);
            if (! $subscription) {
                return null;
            }

            // A refunded invoice reports its status as Refunded (or partially).
            // The exact string is unverified against the live account, so log
            // what we actually receive to confirm on the first real refund.
            $status = $data['Data']['InvoiceStatus'] ?? null;
            Log::info('MyFatoorah refund status for subscription ' . $subscription->id . ': ' . var_export($status, true));
            if (! in_array($status, ['Refunded', 'PartiallyRefunded'], true)) {
                return $subscription;
            }

            PackageSubscription::where('id', $subscription->id)
                ->where('payment_status', '!=', 'refunded')
                ->update([
                    'payment_status' => 'refunded',
                    'payment_object' => $data['Data'],
                ]);

            Log::info('Subscription ' . $subscription->id . ' marked refunded — access revoked.');

            return $subscription->refresh();
        } catch (\Exception $e) {
            Log::error('MyFatoorah refund handling failed: ' . $e->getMessage());
            return null;
        }
    }
}
