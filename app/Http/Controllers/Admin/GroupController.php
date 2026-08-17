<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\GroupDataTable;
use App\Http\Controllers\Controller;
use App\Models\Group;

class GroupController extends Controller
{
    public function index(GroupDataTable $dataTable)
    {
        return $dataTable->render('dashboard.groups.index');
    }

    public function show(Group $group)
    {
        $group->load('owner')->loadCount([
            'users as accepted_users_count' => function ($query) {
                $query->where('group_user.status', 'accepted');
            },
            'legalCases as legal_cases_count',
        ]);

        return view('dashboard.groups.show', ['group' => $group]);
    }
}
