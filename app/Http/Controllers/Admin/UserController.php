<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\UserService;
use App\DataTables\UserDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreRequest;


class UserController extends Controller
{


    public function __construct(protected UserService $userService )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(UserDataTable $dataTable)
    {
        return $dataTable->render('dashboard.users.index');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('dashboard.users.show', ['user' => $user]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.users.create', $this->userService->getFormData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->userService->create($request->validated());
        added();
        return redirect()->route('admin.users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('dashboard.users.edit', array_merge(
            ['user' => $user],
            $this->userService->getFormData()
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());
        updated();
        return redirect()->route('admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->userService->delete($user);
        deleted();
        return back();
    }

   
}
