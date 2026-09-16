<?php

namespace App\DataTables;

use App\Enums\GroupRole;
use App\Models\RoleTitle;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class RoleTitleDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'dashboard.role-titles.action')
            ->filterColumn('title', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.en'))) like LOWER(?)", ["%$keyword%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar'))) like LOWER(?)", ["%$keyword%"]);
                });
            })
            ->addColumn('title', function ($roleTitle) {
                return $roleTitle->title;
             })
             ->addColumn('role', function ($roleTitle) {
                return __($roleTitle->role);
             })
            ->filterColumn('role', function ($query, $keyword) {
                // The column is rendered with __(), so accept the translated
                // label ("قاضي") as well as the raw stored value ("judge").
                $needle = mb_strtolower(trim((string) $keyword));

                $values = collect(GroupRole::cases())
                    ->filter(fn (GroupRole $role) => str_contains(mb_strtolower(__($role->value)), $needle)
                        || str_contains($role->value, $needle))
                    ->map(fn (GroupRole $role) => $role->value)
                    ->values()
                    ->all();

                $query->where(function ($q) use ($needle, $values) {
                    $q->where('role_titles.role', 'like', "%{$needle}%");

                    if ($values) {
                        $q->orWhereIn('role_titles.role', $values);
                    }
                });
            })
             
           
             ->addIndexColumn()

         ->rawColumns(['title','role','action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(RoleTitle $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (datatable_has_no_explicit_order()) {
            $query->latest();
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('role-title-table')
                    ->columns($this->getColumns())
                    // NOT minifiedAjax(): its data callback strips `searchable`
                    // and the per-column `search` payload, which is exactly what
                    // the column filters need to reach filterColumn() on the server.
                    ->ajax(route('admin.role-titles.index', [], false))
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
            Column::computed('title')->title(__('title'))->searchable(),
            Column::computed('role')->title(__('role'))->searchable(),
            Column::computed('action')->title(__('actions')),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'RoleTitle_' . date('YmdHis');
    }
}
