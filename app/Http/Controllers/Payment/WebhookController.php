<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\MyfatoorahService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(protected MyfatoorahService $myfatoorahService) {}

    /**
     * Browser return after checkout. Still runs `validate()` (belt-and-suspenders
     * alongside the webhook) and renders the confirmation page. The paid-flip is
     * idempotent, so redirect + webhook arriving together is safe.
     */
    public function success()
    {
        $this->myfatoorahService->validate(\request('paymentId'), 'PaymentId');
        return view('success');
    }

    public function error()
    {
        return view('error');
    }

    /**
     * MyFatoorah server-to-server webhook — the reliable paid/refund signal,
     * independent of the browser redirect. Unauthenticated (MyFatoorah sends no
     * bearer), so the ONLY trust is: (a) an optional HMAC signature when a secret
     * is configured, and (b) — always — re-verifying the event against the
     * MyFatoorah API with our own token before changing anything. The webhook
     * body is never trusted on its own.
     *
     * Always returns 200 once handled (MyFatoorah retries up to 5×); a non-2xx
     * is reserved for a rejected signature.
     */
    public function handle(Request $request)
    {
        // Signature is defense-in-depth only — the API re-check below is the real
        // gate (a forged body can't cause a false flip: `validate()` re-queries
        // MyFatoorah with our own token and matches the amount). So a mismatch
        // REJECTS only when enforcement is explicitly on; otherwise we log and
        // fall through, because the canonical-string format is unverified here
        // and must not silently drop legitimate webhooks.
        $secret = config('payment.mayfatoorah.webhook_secret');
        if (! empty($secret) && ! $this->signatureValid($request, $secret)) {
            if (config('payment.mayfatoorah.webhook_enforce_signature')) {
                Log::warning('MyFatoorah webhook rejected: invalid signature.');
                return response()->json(['status' => false], 401);
            }
            Log::warning('MyFatoorah webhook signature mismatch (enforcement off) — continuing to API verify.');
        }

        $payload = $request->all();
        $eventCode = $this->eventCode($payload);
        $invoiceId = $this->extractInvoiceId($payload);

        if (! $invoiceId) {
            // Nothing actionable, but acknowledge so it isn't retried forever.
            Log::info('MyFatoorah webhook with no invoice id.', ['event' => $eventCode]);
            return response()->json(['status' => true]);
        }

        // 1 = PAYMENT_STATUS_CHANGED, 2 = REFUND_STATUS_CHANGED (V2 codes).
        if ($eventCode === 2 || $this->looksLikeRefund($payload)) {
            $this->myfatoorahService->markRefunded($invoiceId, 'InvoiceId');
        } else {
            $this->myfatoorahService->validate($invoiceId, 'InvoiceId');
        }

        return response()->json(['status' => true]);
    }

    /**
     * HMAC-SHA256 of the canonical `key=value,...` string, base64-encoded,
     * compared against the `myfatoorah-signature` header. Best-effort defense in
     * depth — the API re-verification is the real gate — so a header-format
     * mismatch never silently passes: it only rejects when a secret is set.
     */
    private function signatureValid(Request $request, string $secret): bool
    {
        $provided = $request->header('myfatoorah-signature');
        if (empty($provided)) {
            return false;
        }

        $data = $request->input('Data', []);
        // Canonical field order for the payment-status event (V2). Null → ''.
        $ordered = [
            'Invoice.Id' => data_get($data, 'Invoice.Id', data_get($data, 'InvoiceId')),
            'Invoice.Status' => data_get($data, 'Invoice.Status'),
            'Transaction.Status' => data_get($data, 'Transaction.Status'),
            'Transaction.PaymentId' => data_get($data, 'Transaction.PaymentId'),
            'Invoice.ExternalIdentifier' => data_get($data, 'Invoice.ExternalIdentifier'),
        ];
        $canonical = collect($ordered)
            ->map(fn ($v, $k) => $k . '=' . ($v ?? ''))
            ->implode(',');

        $computed = base64_encode(hash_hmac('sha256', $canonical, $secret, true));

        return hash_equals($computed, $provided);
    }

    private function eventCode(array $payload): ?int
    {
        $code = data_get($payload, 'Event.Code', data_get($payload, 'EventType'));
        return is_numeric($code) ? (int) $code : null;
    }

    /**
     * The invoice id, tolerant of V2 (`Data.Invoice.Id`) and legacy
     * (`Data.InvoiceId` / top-level `InvoiceId`) shapes.
     */
    private function extractInvoiceId(array $payload): ?string
    {
        $id = data_get($payload, 'Data.Invoice.Id')
            ?? data_get($payload, 'Data.InvoiceId')
            ?? data_get($payload, 'InvoiceId');
        return $id ? (string) $id : null;
    }

    private function looksLikeRefund(array $payload): bool
    {
        $name = (string) data_get($payload, 'Event.Name', '');
        return str_contains(strtoupper($name), 'REFUND');
    }
}
