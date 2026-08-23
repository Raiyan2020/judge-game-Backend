<?php

namespace App\DataTables;

use App\Enums\BannerType;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class BannerDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
        ->addColumn('action', 'dashboard.banners.action')

         ->addColumn('image', function ($banner) {
            return " <img src='{$banner->image}'  width='75' height='75'>";
        })
        ->filterColumn('title', function ($query, $keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.en'))) like LOWER(?)", ["%$keyword%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar'))) like LOWER(?)", ["%$keyword%"]);
            });
        })
       
         ->addColumn('title', function ($banner) {
            return $banner->title;
         })
        ->editColumn('type', function ($banner) {
            return $banner->type?->label() ?? '-';
        })
        ->filterColumn('type', function ($query, $keyword) {
            // Let admins search by the translated label ("news banner") as well
            // as by the raw stored value ("news").
            $needle = mb_strtolower(trim($keyword));

            $values = collect(BannerType::cases())
                ->filter(fn (BannerType $type) => str_contains(mb_strtolower($type->label()), $needle)
                    || str_contains($type->value, $needle))
                ->map(fn (BannerType $type) => $type->value)
                ->values()
                ->all();

            $query->whereIn('banners.type', $values ?: ['']);
        })
        ->addColumn('status', function ($banner) {
            return view('dashboard.banners.status', ['banner' => $banner]);
        })
        ->addIndexColumn()
       ->rawColumns(['image','action','status','title'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Banner $model): QueryBuilder
    {
        $query = $model->newQuery()->latest();

        // Placement tabs on the index page (?type=home / ?type=news). No param
        // means "all", which is what the page showed before types existed.
        $type = BannerType::tryFrom((string) request('type'));

        if ($type) {
            $query->ofType($type);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('banner-table')
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
            Column::computed('title')->title(__('title'))->searchable(),
            Column::make('type')->title(__('banner type')),
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
        return 'Banner_' . date('YmdHis');
    }
}
