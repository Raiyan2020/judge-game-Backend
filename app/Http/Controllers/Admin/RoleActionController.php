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
        $data = $request->validated();

        $this->roleActionService->updatePoints($data);
        updated();

        // Always come back to the points page of the role that was edited.
        // The role is read from the saved actions when the hidden input is
        // missing, so the save never falls back to the roles list.
        $role = $this->roleActionService->resolveRole($data);

        return $role
            ? redirect()->route('admin.role-actions.show', ['role' => $role])
            : redirect()->route('admin.role-actions.index');
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
