<?php

namespace App\DataTables;

use App\Models\Country;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CountryDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'dashboard.countries.action')
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) like LOWER(?)", ["%$keyword%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))) like LOWER(?)", ["%$keyword%"]);
                });
            })
            ->addColumn('name', function ($country) {
                return $country->name;
             })
             
             ->addColumn('image', function ($country) {
                return " <img src='{$country->image}'  width='75' height='75'>";
            })
            ->addColumn('status', function ($country) {
                return view('dashboard.countries.status', ['country' => $country]);
            })
             ->addIndexColumn()

         ->rawColumns(['name','image','status','action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Country $model): QueryBuilder
    {
        return $model->newQuery()->latest();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('country-table')
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
            Column::make('country_code')->title(__('phone code')),
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
        return 'Country_' . date('YmdHis');
    }
}
