<?php

namespace App\Console\Commands;

use App\Models\PackageSubscription;
use App\Services\Payment\MyfatoorahService;
use Illuminate\Console\Command;

class ReconcileSubscriptions extends Command
{
    protected $signature = 'subscriptions:reconcile {--days=7 : Only rows created within the last N days}';

    protected $description = 'Re-verify not-yet-paid subscriptions against MyFatoorah and flip any that actually paid (recovers charged-but-unpaid rows).';

    public function handle(MyfatoorahService $myfatoorah): int
    {
        $days = (int) $this->option('days');

        // Rows that carry an invoice id (so we can re-query) but never flipped
        // to paid — e.g. a checkout whose `/success` callback never reached us.
        // `refunded` is excluded: refund is terminal, never resurrected here.
        $query = PackageSubscription::query()
            ->whereNotNull('payment_invoice_id')
            ->where(function ($q) {
                $q->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['paid', 'refunded']);
            });

        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $checked = 0;
        $flipped = 0;

        $query->chunkById(100, function ($subscriptions) use ($myfatoorah, &$checked, &$flipped) {
            foreach ($subscriptions as $subscription) {
                $checked++;
                $result = $myfatoorah->validate($subscription->payment_invoice_id, 'InvoiceId');
                if ($result && $result->fresh()->payment_status === 'paid') {
                    $flipped++;
                    $this->info("Subscription {$subscription->id} confirmed paid.");
                }
            }
        });

        $this->info("Reconcile done: checked {$checked}, newly paid {$flipped}.");

        return self::SUCCESS;
    }
}
