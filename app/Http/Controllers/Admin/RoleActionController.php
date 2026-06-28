<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CaseRole;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleAtion\StoreRequest;
use App\Services\RoleActionService;

use function Symfony\Component\String\b;

class RoleActionController extends Controller
{

    public function __construct(protected RoleActionService $roleActionService) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = $this->roleActionService->getRoles();

        return view('dashboard.role-actions.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->roleActionService->updatePoints($request->validated());
        added();
        return redirect()->route('admin.role-actions.index');
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit($role)
    {
        $actions = $this->roleActionService->getActionsByRole($role);
        return view('dashboard.role-actions.form', ['actions' => $actions]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getActions($role)
    {
        if ($role == 'lawyer') {
            $role = CaseRole::DEFENDANT_LAWYER->value;
        }
        $actions = $this->roleActionService->getActionsByRole($role);

        return response()->json($actions);
    }
}
