<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\MyfatoorahService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(protected MyfatoorahService $myfatoorahService) {}


    public function success()
    {
         $this->myfatoorahService->validate(\request('paymentId'));
         return view('success');
      
    }

    public function error()
    {
        return view('error');
    }
}
