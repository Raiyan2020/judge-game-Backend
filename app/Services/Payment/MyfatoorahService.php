<?php

namespace App\Services\Payment;

use Exception;
use App\Models\Order;
use App\Enum\OrderPaymentStatusEnum;
use App\Models\PackageSubscription;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\PackageService;
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

           // return $http->json()['Data']['PaymentURL'];
              return $http->json()['Data']['InvoiceURL'];
        } catch (\Exception $e) {

            throw ValidationException::withMessages([

                'payment' => __('error in payment gateway'),
                'message' => $e->getMessage(),
                'object' => $e->getTrace(),
            ]);
        }
    }



    public function validate($payment_id)
    {
        try {
            $http = Http::withToken(config('payment.mayfatoorah.token'))
                ->post(config('payment.mayfatoorah.url') . '/GetPaymentStatus', [
                    'Key' => $payment_id,
                    'KeyType' => 'PaymentId',
                ]);
            $data = $http->json();

            if (!isset($data['Data']['CustomerReference'])) {
                return null;
            }
            $package_subscription_id = $data['Data']['CustomerReference'];
            $packageSubscription = PackageSubscription::find($package_subscription_id);
            if ($packageSubscription) {
                if ($data['IsSuccess'] and $data['Data']['InvoiceStatus'] == 'Paid') {
                    if ($packageSubscription->payment_status !== 'paid') {
                        $packageSubscription->update([
                            'payment_status' => 'paid',
                            'payment_object' => $data['Data'],

                        ]);
                    }


                    return $packageSubscription;
                }
            }

            return null;
        } catch (\Exception $e) {

            return $e;
        }
    }

}
