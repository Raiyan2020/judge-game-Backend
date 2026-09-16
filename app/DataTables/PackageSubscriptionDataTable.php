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
            ->editColumn('total', fn (PackageSubscription $subscription) => format_money($subscription->total))
            ->editColumn('discount', fn (PackageSubscription $subscription) => format_money($subscription->discount))
            ->filterColumn('total', function ($query, $keyword) {
                $this->filterMoneyColumn($query, 'package_subscriptions.total', $keyword);
            })
            ->filterColumn('discount', function ($query, $keyword) {
                $this->filterMoneyColumn($query, 'package_subscriptions.discount', $keyword);
            })
            ->filterColumn('package_name', function ($query, $keyword) {
                $query->whereHas('package', function ($packageQuery) use ($keyword) {
                    $packageQuery->where(function ($q) use ($keyword) {
                        $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) like LOWER(?)", ["%$keyword%"])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))) like LOWER(?)", ["%$keyword%"]);
                    });
                });
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->whereHas('user', function ($userQuery) use ($keyword) {
                    $userQuery->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('starts_at', function ($query, $keyword) {
                // The cell shows the Y-m-d part only, so match on that.
                $query->whereDate('package_subscriptions.starts_at', 'like', "%{$keyword}%");
            })
            ->filterColumn('ends_at', function ($query, $keyword) {
                $query->whereDate('package_subscriptions.ends_at', 'like', "%{$keyword}%");
            })
           
          
            ->addIndexColumn()
            ->rawColumns(['package_name', 'user_name', 'starts_at', 'ends_at', 'total', 'discount'])
            ->setRowId('id');
    }

    /**
     * Filter a money column by the keyword the admin actually sees.
     *
     * The cell is rendered with format_money(), which rounds to a whole number
     * and appends the currency ("10.500" is displayed as "11 د.ك"), so match the
     * stored decimal AND its rounded form, after dropping the currency label.
     */
    protected function filterMoneyColumn($query, string $column, $keyword): void
    {
        $needle = trim(str_replace([__(app_currency()), app_currency()], '', (string) $keyword));

        $query->where(function ($q) use ($column, $needle) {
            $q->where($column, 'like', "%{$needle}%")
                ->orWhereRaw("ROUND({$column}) like ?", ["%{$needle}%"]);
        });
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PackageSubscription $model): QueryBuilder
    {
        $query = $model->newQuery()->with('package', 'user')->paid();

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
            ->setTableId('package-subscription-table')
            ->columns($this->getColumns())
            // NOT minifiedAjax(): its data callback strips `searchable` and the
            // per-column `search` payload, which is exactly what the column
            // filters need to reach filterColumn() on the server.
            ->ajax(route('admin.subscriptions.index', [], false))
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
            Column::computed('package_name')->title(__('packagename'))->searchable(),
            Column::computed('user_name')->title(__('username'))->searchable(),
            Column::make('total')->title(__('total'))->searchable(),
            Column::make('discount')->title(__('discount'))->searchable(),
            Column::computed('starts_at')->title(__('starts at'))->searchable(),
            Column::computed('ends_at')->title(__('ends at'))->searchable(),
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
