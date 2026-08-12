@push('scripts')

   <script src="{{asset('_dashboard/app-assets/vendors/js/ui/jquery.sticky.js')  }}"></script>
   <script src="{{asset('datatables/datatables.min.js')  }}"></script>
    <script>
        (function () {
            function getContainer($table) {
                return $table.closest('.card-body, .card-content, .table-responsive').first();
            }

            function getDeleteToken() {
                return $('#delete-form input[name="_token"]').val();
            }

            function ensureTableWrap($table) {
                let $mount = $table.closest('.dataTables_wrapper');
                if (!$mount.length) {
                    $mount = $table;
                }

                if (!$mount.parent().hasClass('admin-table-wrap__content')) {
                    $mount.wrap('<div class="admin-table-wrap__content"></div>');
                }

                let $content = $mount.parent('.admin-table-wrap__content');
                if (!$content.parent().hasClass('admin-table-wrap')) {
                    $content.wrap('<div class="admin-table-wrap"></div>');
                }

                $table.addClass('admin-data-table');
            }

            function ensureToolbar($table) {
                let tableId = $table.attr('id') || ('dt-' + Math.random().toString(36).slice(2, 8));
                $table.attr('id', tableId);

                let $container = getContainer($table);
                if (!$container.length) {
                    return;
                }

                if ($container.find('.admin-table-toolbar-block[data-table-id="' + tableId + '"]').length) {
                    return;
                }

                let toolbarHtml = `
                    <div class="admin-table-toolbar-block" data-table-id="${tableId}">
                        <div class="admin-table-toolbar">
                            <div class="admin-table-toolbar__actions">
                                <div class="admin-table-toolbar__buttons buttons">
                                    <button type="button" class="admin-tb-btn admin-tb-btn--reload reloadTable">
                                        <i class="feather icon-refresh-cw"></i>
                                        <span>{{ __('Refresh') }}</span>
                                    </button>
                                    <button type="button" class="admin-tb-btn admin-tb-btn--delete delete_all_button" hidden>
                                        <i class="feather icon-trash"></i>
                                        <span>{{ __('Delete selected') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="admin-table-toolbar__search-wrap">
                                <label class="admin-table-toolbar__search">
                                    <i class="feather icon-search"></i>
                                    <input type="text" class="admin-table-toolbar__search-input" placeholder="{{ __('Search') }}" autocomplete="off">
                                </label>
                            </div>

                            <div class="admin-table-toolbar__end">
                                <div class="admin-tb-perpage dropdown">
                                    <button type="button" class="admin-tb-btn admin-tb-btn--meta dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="feather icon-list"></i>
                                        <span class="admin-tb-perpage__label" data-suffix="{{ __('per page') }}">10 {{ __('per page') }}</span>
                                        <i class="feather icon-chevron-down admin-tb-perpage__chev"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right admin-tb-perpage__menu">
                                        <button type="button" class="dropdown-item admin-tb-perpage__option" data-per-page="10">10</button>
                                        <button type="button" class="dropdown-item admin-tb-perpage__option" data-per-page="20">20</button>
                                        <button type="button" class="dropdown-item admin-tb-perpage__option" data-per-page="30">30</button>
                                        <button type="button" class="dropdown-item admin-tb-perpage__option" data-per-page="50">50</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $container.prepend(toolbarHtml);
            }

            function getBulkRows($table) {
                return $table.find('tbody tr').filter(function () {
                    return $(this).find('.checkSingle').length > 0;
                });
            }

            function syncBulkSelectionState($table) {
                let tableId = $table.attr('id');
                let $toolbar = $('.admin-table-toolbar-block[data-table-id="' + tableId + '"]');
                let $rows = getBulkRows($table);
                let total = $rows.find('.checkSingle').length;
                let checked = $rows.find('.checkSingle:checked').length;
                let $selectAll = $toolbar.find('.admin-tb-select-all');
                let $selectAllInput = $selectAll.find('.checked-all');

                $selectAll.toggleClass('is-visible', total > 0);
                $selectAllInput.prop('disabled', total === 0);
                $selectAllInput.prop('checked', total > 0 && total === checked);

                $toolbar.find('.delete_all_button')
                    .prop('hidden', checked === 0)
                    .toggleClass('is-visible', checked > 0);
            }

            function prepareBulkRows($table) {
                $table.find('tbody tr').each(function () {
                    let $row = $(this);
                    let $deleteBtn = $row.find('.btn-danger[data-href], .admin-table-action--delete[data-href]').first();
                    let $firstCell = $row.find('td').first();

                    if ($deleteBtn.length === 0 || $firstCell.length === 0 || $firstCell.find('.checkSingle').length > 0) {
                        return;
                    }

                    let deleteUrl = $deleteBtn.data('href');
                    if (!deleteUrl) {
                        return;
                    }

                    $firstCell.prepend(`
                        <input type="checkbox"
                            class="checkSingle bulk-row-checkbox mr-50"
                            data-delete-url="${deleteUrl}"
                            title="{{ __('Select') }}">
                    `);
                });
            }

            function normalizeActionButtons($table) {
                $table.find('tbody tr').each(function () {
                    let $row = $(this);
                    let $actionsCell = $row.find('td.product-action, td:last-child').last();
                    if (!$actionsCell.length) {
                        return;
                    }

                    let $buttons = $actionsCell.find('a.btn, button.btn, span.btn');
                    if (!$buttons.length) {
                        return;
                    }

                    if (!$actionsCell.find('.admin-table-actions').length) {
                        $buttons.wrapAll('<div class="admin-table-actions"></div>');
                    }

                    $buttons.each(function () {
                        let $btn = $(this);
                        let classes = 'admin-table-action';
                        let text = ($btn.text() || '').toLowerCase();
                        let iconClasses = ($btn.find('i').attr('class') || '').toLowerCase();

                        if (iconClasses.includes('trash') || text.includes('delete') || $btn.hasClass('btn-danger')) {
                            classes += ' admin-table-action--delete';
                        } else if (iconClasses.includes('edit') || iconClasses.includes('pencil') || text.includes('edit') || $btn.hasClass('btn-warning')) {
                            classes += ' admin-table-action--edit';
                        } else if (iconClasses.includes('eye') || text.includes('show') || text.includes('view') || $btn.hasClass('btn-info')) {
                            classes += ' admin-table-action--view';
                        }

                        if (!$btn.hasClass('admin-table-action')) {
                            $btn.removeClass('btn btn-icon btn-info btn-warning btn-danger').addClass(classes);
                        }
                    });
                });
            }

            async function runBulkDelete($table) {
                let token = getDeleteToken();
                let selectedUrls = getBulkRows($table)
                    .find('.checkSingle:checked')
                    .map(function () { return $(this).data('delete-url'); })
                    .get()
                    .filter(Boolean);

                if (selectedUrls.length === 0) {
                    return;
                }

                const result = await Swal.fire({
                    title: '{{ __('Do you want to delete the selected items?') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('Confirm') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                });

                if (!result.isConfirmed) {
                    return;
                }

                let failed = 0;
                for (const url of selectedUrls) {
                    try {
                        await $.ajax({
                            url: url,
                            method: 'POST',
                            data: { _method: 'DELETE', _token: token }
                        });
                    } catch (e) {
                        failed++;
                    }
                }

                if (failed > 0) {
                    toastr.warning(`{{ __('Deleted') }} ${selectedUrls.length - failed} - {{ __('Failed') }} ${failed}`);
                } else {
                    toastr.success('{{ __('Selected items deleted successfully') }}');
                }

                if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                    $table.DataTable().ajax.reload(null, false);
                } else {
                    window.location.reload();
                }
            }

            function bindToolbarEvents($table) {
                let tableId = $table.attr('id');
                let $toolbar = $('.admin-table-toolbar-block[data-table-id="' + tableId + '"]');

                if (!$toolbar.length || $toolbar.data('bound')) {
                    return;
                }
                $toolbar.data('bound', true);

                $toolbar.find('.admin-table-toolbar__buttons').prepend(`
                    <label class="admin-tb-btn admin-tb-btn--meta admin-tb-select-all mb-0">
                        <input type="checkbox" class="checked-all" style="margin-inline-end:6px;" disabled>
                        <span>{{ __('Select all') }}</span>
                    </label>
                `);

                $toolbar.on('input', '.admin-table-toolbar__search-input', function () {
                    let dataTableApi = $.fn.DataTable && $.fn.DataTable.isDataTable($table) ? $table.DataTable() : null;
                    let val = $(this).val();
                    if (dataTableApi) {
                        dataTableApi.search(val).draw();
                    }
                });

                $toolbar.on('click', '.reloadTable', function () {
                    let dataTableApi = $.fn.DataTable && $.fn.DataTable.isDataTable($table) ? $table.DataTable() : null;
                    $toolbar.find('.admin-table-toolbar__search-input').val('');
                    if (dataTableApi) {
                        dataTableApi.search('').draw();
                        dataTableApi.ajax.reload(null, false);
                    }
                });

                $toolbar.on('click', '.admin-tb-perpage__option', function () {
                    let dataTableApi = $.fn.DataTable && $.fn.DataTable.isDataTable($table) ? $table.DataTable() : null;
                    let size = $(this).data('per-page');
                    $toolbar.find('.admin-tb-perpage__label').text(size + ' {{ __('per page') }}');
                    if (dataTableApi) {
                        dataTableApi.page.len(size).draw();
                    }
                });

                $toolbar.on('change', '.checked-all', function () {
                    let checked = $(this).prop('checked');
                    getBulkRows($table).find('.checkSingle').prop('checked', checked);
                    syncBulkSelectionState($table);
                });

                $toolbar.on('click', '.admin-tb-select-all', function (e) {
                    if ($(e.target).is('.checked-all')) {
                        return;
                    }

                    e.preventDefault();
                    let $input = $(this).find('.checked-all');
                    if ($input.prop('disabled')) {
                        return;
                    }

                    $input.prop('checked', ! $input.prop('checked')).trigger('change');
                });

                $toolbar.on('click', '.delete_all_button', function () {
                    runBulkDelete($table);
                });
            }

            function decorateTable($table) {
                ensureTableWrap($table);
                ensureToolbar($table);
                prepareBulkRows($table);
                normalizeActionButtons($table);
                bindToolbarEvents($table);
                syncBulkSelectionState($table);
            }

            function initializeTables() {
                $('table.dataTable, table.dataex-html5-selectors').each(function () {
                    decorateTable($(this));
                });
            }

            $('.dataex-html5-selectors').DataTable({
                dom: 'Brtip',
                order: [[0, 'desc']],
                responsive: true,
                buttons: []
            });

            $(document).on('init.dt draw.dt', function (e, settings) {
                let $table = settings && settings.nTable ? $(settings.nTable) : null;
                if ($table && $table.length) {
                    decorateTable($table);
                } else {
                    initializeTables();
                }
            });

            $(document).on('change', '.checkSingle', function () {
                let $table = $(this).closest('table');
                if ($table.length) {
                    syncBulkSelectionState($table);
                }
            });

            $(document).ready(function () {
                initializeTables();
            });
        })();
    </script>
@endpush
@push('styles')
     <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/vendors/css/vendors-rtl.min.css') }}">
     <link rel="stylesheet" href="{{ asset('datatables/datatables.min.css') }}">
@endpush
