<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleAtion\StoreRequest;
use App\Services\RoleActionService;

class RoleActionController extends Controller
{
    public function __construct(protected RoleActionService $roleActionService)
    {
    }

    public function index()
    {
        $roles = $this->roleActionService->getRoles();

        return view('dashboard.role-actions.index', compact('roles'));
    }

    public function show(string $role)
    {
        $actions = $this->roleActionService->getActionsByRole($role);

        return view('dashboard.role-actions.show', [
            'role' => $role,
            'actions' => $actions,
        ]);
    }

    public function store(StoreRequest $request)
    {
        $this->roleActionService->updatePoints($request->validated());
        updated();

        $role = $request->input('role');

        return redirect()->route(
            $role ? 'admin.role-actions.show' : 'admin.role-actions.index',
            $role ? ['role' => $role] : []
        );
    }

    public function edit(string $role)
    {
        $actions = $this->roleActionService->getActionsByRole($role);

        return view('dashboard.role-actions.form', [
            'role' => $role,
            'actions' => $actions,
        ]);
    }

    public function getActions(string $role)
    {
        $actions = app(\App\Repositories\RoleAchievementRepository::class)->getRoleActions($role);

        return $actions->map(fn ($action) => [
            'id' => $action->id,
            'key' => $action->key,
            'title' => [
                'ar' => $action->getTranslation('title', 'ar'),
                'en' => $action->getTranslation('title', 'en'),
            ],
        ])->values();
    }
}
