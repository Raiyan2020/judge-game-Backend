<?php

namespace App\DataTables;

use App\Models\Tip;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TipDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
        ->addColumn('action', 'dashboard.tips.action')
       
         ->addColumn('description', function ($tip) {
            return $tip->description;
         })
        ->addColumn('status', function ($tip) {
            return view('dashboard.tips.status', ['tip' => $tip]);
        })
        ->addColumn('image', function ($tip) {
            return " <img src='{$tip->image}'  width='75' height='75'>";
        })
        ->addIndexColumn()
       ->rawColumns(['image','action','status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Tip $model): QueryBuilder
    {
        return $model->newQuery()->latest();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('tip-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(0)
            ->responsive(true)
            ->selectStyleSingle()
            ->buttons([])
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
            Column::computed('description')->title(__('description')),
            Column::computed('image')->title(__('image')),
            Column::computed('status')->title(__('status')),
            Column::computed('action')->title(__('actions')),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Tip_' . date('YmdHis');
    }
}
