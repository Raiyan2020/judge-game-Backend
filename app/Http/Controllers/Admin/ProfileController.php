<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Profile\UpdatePasswordRequest;
use App\Http\Requests\Admin\Profile\UpdateProfileRequest;
use App\Services\AdminService;

class ProfileController extends Controller
{
    public function __construct(protected AdminService $adminService)
    {
    }

    public function show()
    {
        return view('dashboard.profile.show', [
            'admin' => auth('admin')->user(),
        ]);
    }

    public function edit()
    {
        return view('dashboard.profile.edit', [
            'admin' => auth('admin')->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $this->adminService->update(auth('admin')->user(), $request->validated());
        updated();

        return redirect()->route('admin.profile.show');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $this->adminService->update(auth('admin')->user(), [
            'password' => $request->validated('password'),
        ]);
        updated();

        return redirect()->route('admin.profile.show');
    }
}
