<?php

namespace App\DataTables;

use App\Enum\CouponTypeEnum;
use App\Models\Coupon;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class CouponDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {

        return (new EloquentDataTable($query))
        ->addColumn('action', function ($q)  {
            
            return view('dashboard.coupons.action', ['id' => $q->id]);
        })
        ->addColumn('status', function ($coupon) {
            return view('dashboard.coupons.status', ['coupon' => $coupon]);
        })
       
        ->addIndexColumn()
        ->rawColumns(['status','action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Coupon $model): QueryBuilder
    {
        
        return $model->newQuery()->latest();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
        ->setTableId('coupon-table')
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
            Column::make('code')->title(__('code')),
            Column::make('discount')->title(__('percentage')),
            Column::make('start_at')->title(__('start at')),
            Column::make('end_at')->title(__('end at')),
            Column::computed('status')->title(__('status')),
            Column::computed('action')->title(__('actions'))
        ];
      
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Coupon_' . date('YmdHis');
    }
}
