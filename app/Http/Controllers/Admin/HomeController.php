<?php

namespace App\Http\Controllers\Admin;

use App\Enum\ProductTypeEnum;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\RentalService;

class HomeController extends Controller
{
    public function __construct(
      ) {
    }
    public function index()
    {
     
        return view('dashboard.index');
    }
}
