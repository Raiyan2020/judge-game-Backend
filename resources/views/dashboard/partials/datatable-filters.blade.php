{{--
    Per-column filter bar for a Yajra server-side DataTable.

    The shared dashboard toolbar (dashboard/layout/datatables.blade.php) only exposes a
    GLOBAL search box, so `columns[i][search][value]` was never sent and every
    `filterColumn()` handler defined in the DataTable classes stayed unreachable.
    This partial renders real per-column inputs and pushes their values into
    `column(i).search()` so the server-side handlers actually run.

    Usage: include this view with a `tableId` (the setTableId() value of the
    DataTable) and a `filters` array, e.g.

        tableId => 'country-table'
        filters => [
            ['key' => 'name', 'label' => __('name')],
            ['key' => 'country_code', 'label' => __('phone code')],
        ]

    Each filter item:
        key   => the DataTable column `data`/`name` (must be ->searchable() in getColumns())
        label => already-translated label (use __())
--}}
@php
    $filters = $filters ?? [];
    $filtersId = 'dt-filters-' . $tableId;
@endphp

@if (!empty($filters))
    <div class="admin-table-filters card mb-2" id="{{ $filtersId }}" data-table-id="{{ $tableId }}">
        <div class="card-body py-1">
            <div class="row align-items-end">
                @foreach ($filters as $filter)
                    <div class="col-12 col-md-6 col-lg-3 mb-1">
                        <label class="mb-25" for="{{ $filtersId }}-{{ $filter['key'] }}">
                            {{ $filter['label'] }}
                        </label>
                        <input type="text"
                               class="form-control dt-column-filter"
                               id="{{ $filtersId }}-{{ $filter['key'] }}"
                               data-column="{{ $filter['key'] }}"
                               placeholder="{{ $filter['placeholder'] ?? $filter['label'] }}"
                               autocomplete="off">
                    </div>
                @endforeach

                <div class="col-12 col-md-6 col-lg-2 mb-1">
                    <button type="button" class="btn btn-secondary btn-block dt-column-filter-reset">
                        <i class="feather icon-x"></i> {{ __('Reset') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var tableId = @json($tableId);
                var filtersId = @json($filtersId);

                function root() {
                    return $('#' + filtersId);
                }

                // Resolve a column index from its key so the bar keeps working if the
                // column order ever changes. DataTables stores the column `data` option
                // as `mData` and the `name` option as `sName`.
                function columnIndex(api, key) {
                    var columns = api.settings()[0].aoColumns;

                    for (var i = 0; i < columns.length; i++) {
                        var column = columns[i];

                        if (column.mData === key || column.sName === key
                            || column.data === key || column.name === key) {
                            return i;
                        }
                    }

                    return -1;
                }

                function applyFilters(api) {
                    var changed = false;

                    root().find('.dt-column-filter').each(function () {
                        var index = columnIndex(api, $(this).attr('data-column'));

                        if (index < 0) {
                            return;
                        }

                        var value = String($(this).val() || '').trim();

                        if (api.column(index).search() !== value) {
                            api.column(index).search(value);
                            changed = true;
                        }
                    });

                    if (changed) {
                        api.draw();
                    }
                }

                function bind(api) {
                    var $root = root();

                    if (!$root.length || $root.data('dtFiltersBound')) {
                        return;
                    }
                    $root.data('dtFiltersBound', true);

                    var timer = null;

                    $root.on('input change', '.dt-column-filter', function () {
                        clearTimeout(timer);
                        timer = setTimeout(function () {
                            applyFilters(api);
                        }, 400);
                    });

                    $root.on('keydown', '.dt-column-filter', function (event) {
                        if (event.key === 'Enter' || event.keyCode === 13) {
                            event.preventDefault();
                            clearTimeout(timer);
                            applyFilters(api);
                        }
                    });

                    $root.on('click', '.dt-column-filter-reset', function () {
                        $root.find('.dt-column-filter').val('');
                        applyFilters(api);
                    });

                    // The shared toolbar "Refresh" clears the global search only; keep the
                    // per-column inputs and the column search state in sync with it.
                    $(document).on(
                        'click',
                        '.admin-table-toolbar-block[data-table-id="' + tableId + '"] .reloadTable',
                        function () {
                            $root.find('.dt-column-filter').val('');
                            api.columns().search('');
                            api.draw();
                        }
                    );
                }

                // The DataTable is initialised by a script pushed after this one, so bind
                // through a delegated init.dt listener instead of calling .DataTable() now.
                $(document).on('init.dt', function (event, settings) {
                    if (!settings || !settings.nTable || settings.nTable.id !== tableId) {
                        return;
                    }

                    bind($(settings.nTable).DataTable());
                });

                // Fallback: the table was already initialised before this handler ran.
                $(function () {
                    var $table = $('#' + tableId);

                    if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                        bind($table.DataTable());
                    }
                });
            })();
        </script>
    @endpush
@endif
