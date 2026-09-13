<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\RoleTitleDataTable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleTitle\StoreRequest;
use App\Models\RoleTitle;
use App\Services\RoleTitleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RoleTitleController extends Controller
{

    public function __construct(protected RoleTitleService $roleTitleService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(RoleTitleDataTable $dataTable)
    {
        return $dataTable->render('dashboard.role-titles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $roles = $this->roleTitleService->getRoles();
        return view('dashboard.role-titles.create', ['roles' => $roles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        // The title row and its requirement rows are one unit: a failure after the
        // title is inserted would leave a rung with no requirements at all.
        try {
            DB::transaction(fn () => $this->roleTitleService->create($request->validated()));
        } catch (Throwable $exception) {
            return $this->saveFailed($exception);
        }

        added();
        return redirect()->route('admin.role-titles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(RoleTitle $roleTitle)
    {
        $roleTitle->load('requirements.action');

        return view('dashboard.role-titles.show', ['roleTitle' => $roleTitle]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RoleTitle $roleTitle)
    {
        $roles = $this->roleTitleService->getRoles();
        $roleTitle->load('requirements.action');

        return view('dashboard.role-titles.edit', ['roleTitle' => $roleTitle , 'roles'=>$roles]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, RoleTitle $roleTitle)
    {
        // RoleTitleService::update() re-syncs the rung by DELETING every existing
        // requirement and re-creating it. Un-wrapped, any failure between the delete
        // and the re-insert (the B-09 exception included) permanently strips the
        // title of its requirements, so the rung can never be earned again. The
        // transaction makes a failed edit a no-op instead of data loss.
        try {
            DB::transaction(fn () => $this->roleTitleService->update($roleTitle, $request->validated()));
        } catch (Throwable $exception) {
            return $this->saveFailed($exception);
        }

        updated();
        return redirect()->route('admin.role-titles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoleTitle $roleTitle)
    {
        try {
            $this->roleTitleService->delete($roleTitle);
        } catch (Throwable $exception) {
            return $this->saveFailed($exception);
        }

        deleted();
        return back();
    }

    /**
     * B-09: a save must never surface as a raw exception page.
     *
     * The leading cause of the reported edit exception is the pending migration
     * `2026_08_03_140000_add_tier_and_reward_points_to_role_titles_table` — without
     * it `role_titles.tier` / `reward_points` do not exist and the UPDATE dies with
     * `Unknown column`. That is a deploy step, not a code bug, so the code's job is
     * to fail loudly in the log and politely on screen (mirrors UserController).
     */
    private function saveFailed(Throwable $exception)
    {
        Log::error('Admin role-title save failed.', [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]);

        alert()->error(__('an error occurred, please try again'));

        return back()->withInput();
    }
}
