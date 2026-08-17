<?php

namespace App\DataTables;

use App\Models\Group;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class GroupDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'dashboard.groups.action')
            ->addColumn('name', function ($group) {
                return $group->name;
            })
            ->addColumn('image', function ($group) {
                if ($group->getRawOriginal('image')) {
                    return '<img src="' . e($group->image) . '" width="75" height="75" alt="' . e($group->name) . '">';
                }

                return '<span class="text-muted">' . e(__('no image')) . '</span>';
            })
            ->addColumn('owner', function ($group) {
                return $group->owner ? $group->owner->name : '';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('groups.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('owner', function ($query, $keyword) {
                $query->whereHas('owner', function ($ownerQuery) use ($keyword) {
                    $ownerQuery->where('name', 'like', "%{$keyword}%");
                });
            })

            ->addIndexColumn()
            ->rawColumns(['name', 'image', 'owner', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Group $model): QueryBuilder
    {
        return $model->newQuery()->with('owner')
        ->withCount([
            'users as accepted_users_count' => function ($query) {
                $query->where('group_user.status', 'accepted');
            }
        ])
        ->withCount('legalCases as legal_cases_count')
        ->latest();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('group-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(0)
            ->responsive(true)
            ->selectStyleSingle()
            ->responsive()
            ->buttons([])
            ->parameters([
                'searching' => true,
            ])
            ->language([
                'lengthMenu' => '_MENU_',
                'sProcessing' => __('Loading...'),
                'sLengthMenu' => '',
                'sZeroRecords' => __('There is no data'),
                'sEmptyTable' => __('There is no data'),
                'infoFiltered' => '',
                'sInfo' => '',
                'sInfoEmpty' => '',
                'sInfoPostFix' => '',
                'sSearch' => '',
                'sSearchPlaceholder' => __('Search'),
                'sUrl' => '',
                'sInfoThousands' => ',',
                'sLoadingRecords' => __('Loading...'),
                'oPaginate' => [
                    'sNext' => "<i class='next'></i>",
                    'sPrevious' => "<i class='previous'></i>",
                ]
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#'),
            Column::computed('name')->title(__('name'))->searchable(),
            Column::computed('image')->title(__('image')),
            Column::computed('owner')->title(__('owner'))->searchable(),
            Column::computed('accepted_users_count')->title(__('members count')),
            Column::computed('legal_cases_count')->title(__('legal cases count')),
            Column::computed('action')->title(__('actions')),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Group_' . date('YmdHis');
    }
}
