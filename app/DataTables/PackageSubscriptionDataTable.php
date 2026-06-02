<?php

namespace App\DataTables;

use App\Models\Package;
use App\Models\PackageSubscription;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PackageSubscriptionDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
          
            ->addColumn('package_name', function ($packageSubscription) {
                return $packageSubscription->package ? $packageSubscription->package->name : '' ;
            })
            ->addColumn('user_name', function ($packageSubscription) {
                return $packageSubscription->user ? $packageSubscription->user->name : '' ;
            })
            ->addColumn('starts_at', function ($packageSubscription) {
                return $packageSubscription->starts_at ? $packageSubscription->starts_at->format('Y-m-d') : '' ;
            })
            ->addColumn('ends_at', function ($packageSubscription) {
                return $packageSubscription->ends_at ? $packageSubscription->ends_at->format('Y-m-d') : '' ;
            })
           
          
            ->addIndexColumn()
            ->rawColumns(['package_name', 'user_name', 'starts_at', 'ends_at',])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PackageSubscription $model): QueryBuilder
    {
        return $model->newQuery()->with('package','user')->paid()->latest();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('package-table')
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
            Column::computed('package_name')->title(__('packagename')),
            Column::computed('user_name')->title(__('username')),
            Column::make('total')->title(__('price')),
            Column::make('discount')->title(__('discount')),
            Column::computed('starts_at')->title(__('starts at')),
            Column::computed('ends_at')->title(__('ends at')),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Package_' . date('YmdHis');
    }
}
