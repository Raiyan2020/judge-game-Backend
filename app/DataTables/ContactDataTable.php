<?php

namespace App\DataTables;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ContactDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'dashboard.contacts.action')
            ->addColumn('formatted_phone', fn (Contact $contact) => format_phone_with_code($contact->country_code, $contact->phone))
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('contacts.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('email', function ($query, $keyword) {
                $query->where('contacts.email', 'like', "%{$keyword}%");
            })
            ->filterColumn('message', function ($query, $keyword) {
                $query->where('contacts.message', 'like', "%{$keyword}%");
            })
            ->filterColumn('formatted_phone', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('contacts.country_code', 'like', "%{$keyword}%")
                        ->orWhere('contacts.phone', 'like', "%{$keyword}%")
                        ->orWhereRaw("CONCAT(IFNULL(contacts.country_code, ''), IFNULL(contacts.phone, '')) like ?", ["%{$keyword}%"]);
                });
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(Contact $model): QueryBuilder
    {
        return $model->newQuery()->select('contacts.*')->latest();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('contact-table')
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
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#'),
            Column::make('name')->title(__('name'))->searchable(),
            Column::make('email')->title(__('email'))->searchable(),
            Column::computed('formatted_phone')->title(__('phone'))->searchable(),
            Column::make('message')->title(__('message'))->searchable(),
            Column::computed('action')->title(__('actions')),
        ];
    }

    protected function filename(): string
    {
        return 'Contact_' . date('YmdHis');
    }
}
