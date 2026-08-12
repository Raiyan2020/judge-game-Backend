<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coupon;
use App\DataTables\CouponDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupon\StoreRequest;
use App\Services\CouponService;

class CouponController extends Controller
{

    public function __construct(
        protected CouponService $couponService,
    ) 
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(CouponDataTable $dataTable)
    {
        return $dataTable->render('dashboard.coupons.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.coupons.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $coupon = $this->couponService->create($request->validated());
        added();
        return redirect()->route('admin.coupons.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon)
    {
        return view('dashboard.coupons.show', ['coupon' => $coupon]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Coupon $coupon)
    {
        return view('dashboard.coupons.edit', ['coupon' => $coupon]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Coupon $coupon)
    {
        $this->couponService->update($coupon, $request->validated());
        updated();
        return redirect()->route('admin.coupons.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon)
    {
        $this->couponService->delete($coupon);
        deleted();
        return back();
    }

    public function changeStatus(Coupon $coupon)
    {
        $this->couponService->activation($coupon);
        statusChange();
        return back();
    }


}
