<?php

namespace App\DataTables;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CouponDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($q) {
                return view('dashboard.coupons.action', ['id' => $q->id]);
            })
            ->addColumn('status', function ($coupon) {
                return view('dashboard.coupons.status', ['coupon' => $coupon]);
            })
            ->editColumn('discount', fn (Coupon $coupon) => format_discount_percent($coupon->discount))
            ->editColumn('start_at', fn (Coupon $coupon) => optional($coupon->start_at)->format('Y-m-d') ?? $coupon->start_at)
            ->editColumn('end_at', fn (Coupon $coupon) => optional($coupon->end_at)->format('Y-m-d') ?? $coupon->end_at)
            ->filterColumn('code', function ($query, $keyword) {
                $query->where('coupons.code', 'like', "%{$keyword}%");
            })
            ->filterColumn('discount', function ($query, $keyword) {
                $query->where('coupons.discount', 'like', "%{$keyword}%");
            })
            ->filterColumn('start_at', function ($query, $keyword) {
                $query->where('coupons.start_at', 'like', "%{$keyword}%");
            })
            ->filterColumn('end_at', function ($query, $keyword) {
                $query->where('coupons.end_at', 'like', "%{$keyword}%");
            })
            ->addIndexColumn()
            ->rawColumns(['status', 'action'])
            ->setRowId('id');
    }

    public function query(Coupon $model): QueryBuilder
    {
        return $model->newQuery()->latest();
    }

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
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#'),
            Column::make('code')->title(__('code'))->searchable(),
            Column::make('discount')->title(__('percentage'))->searchable(),
            Column::make('start_at')->title(__('start at'))->searchable(),
            Column::make('end_at')->title(__('end at'))->searchable(),
            Column::computed('status')->title(__('status')),
            Column::computed('action')->title(__('actions')),
        ];
    }

    protected function filename(): string
    {
        return 'Coupon_' . date('YmdHis');
    }
}
