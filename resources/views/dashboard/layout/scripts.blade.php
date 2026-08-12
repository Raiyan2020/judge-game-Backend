<!-- BEGIN: Vendor JS-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
    integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"
    integrity="sha512-2ImtlRlf2VVmiGZsjm9bEyhjGW4dU7B6TNwh/hx/iSByxNENtj3WVE6o/9Lj4TJeVXPi4bnOIMXFIJJAeufa0A=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('_dashboard/app-assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('_dashboard/app-assets/vendors/js/extensions/tether.min.js') }}"></script>
<!-- BEGIN Vendor JS-->

<!-- BEGIN: Page Vendor JS-->


<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->

<script src="{{ asset('_dashboard/app-assets/js/core/app-menu.js') }}"></script>
<script src="{{ asset('_dashboard/app-assets/js/core/app.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
    integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
</script>

<script src="{{ asset('_dashboard\app-assets\js\core\libraries\bootstrap.min.js') }}"></script>
<script src="{{ asset('_dashboard/app-assets/js/scripts/components.js') }}"></script>
<script src="{{ asset('_dashboard/app-assets/vendors/js/pickers/pickadate/picker.js') }}"></script>
<script src="{{ asset('_dashboard/app-assets/vendors/js/pickers/pickadate/picker.date.js') }}"></script>
<script src="{{ asset('_dashboard/app-assets/vendors/js/pickers/pickadate/picker.time.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"
    integrity="sha512-LsnSViqQyaXpD4mBBdRYeP6sRwJiJveh2ZIbW41EBrNmKxgr/LFZIiWT6yr+nycvhvauz8c2nYMhrP80YhG7Cw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('swal/sweeralert2.js') }}"></script>
<script defer src="https://use.fontawesome.com/releases/v5.15.4/js/all.js"></script>
<script defer src="https://use.fontawesome.com/releases/v5.15.4/js/v4-shims.js"></script>
<script src="{{ asset('ckeditor/ckeditor.js') }}"></script>


<form method="POST" id="delete-form">
    @csrf
    @method('DELETE')
</form>
<form method="POST" id="cancel-form">
    @csrf
    @method('PUT')
</form>

<form method="POST" id="accept-form">
    @csrf
    @method('PUT')
</form>
<form method="POST" id="store-form">
    @csrf
    @method('PUT')
</form>
<script type="text/javascript">
    var lis = $('.check-active');
    lis.each(function(index) {
        if ($(this).attr('href') === "{!! url('/') !!}" + window.location.pathname) {
            $(this).parent().addClass('active');
            // $(this).parent().parent().parent().addClass('active');
        }
    });

    function delete_form(element) {
        let url = $(element).data('href');
        Swal.fire({
            title: "{{ __('Do you want to delete the item ?') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('Confirm') }}",
            cancelButtonText: "{{ __('Cancel') }}"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#delete-form').attr('action', url).submit();
            }
        })
    }

    function cancel_form(element) {
        let url = $(element).data('href');
        Swal.fire({
            title: "{{ __('Do you want to cancel the order ?') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('Confirm') }}",
            cancelButtonText: "{{ __('Cancel') }}"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#cancel-form').attr('action', url).submit();
            }
        })
    }

    function reject_form(element) {
        let url = $(element).data('href');
        Swal.fire({
            title: "{{ __('Do you want to reject the order ?') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('Confirm') }}",
            cancelButtonText: "{{ __('Cancel') }}"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#reject-form').attr('action', url).submit();
            }
        })
    }
    function accept_form(element) {
        let url = $(element).data('href');
        Swal.fire({
            title: "{{ __('Do you want to accept the request ?') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('Confirm') }}",
            cancelButtonText: "{{ __('Cancel') }}"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#accept-form').attr('action', url).submit();
            }
        })
    }
    function new_store_form(element) {
        let url = $(element).data('href');
        Swal.fire({
            title: "{{ __('Do you want to accept the store ?') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('Confirm') }}",
            cancelButtonText: "{{ __('Cancel') }}"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#store-form').attr('action', url).submit();
            }
        })
    }




    $('.select2:not(n-select2)').select2({
        width: '100%'
    });
    $('.select2-multiple').select2({
        tags: true
    });

    $(document).ready(function() {});

    $('.pickadate').datepicker({
        format: 'yyyy-mm-dd',
        startDate: '-70y',
        rtl: true
    });
    $(".time").pickatime({
        format: 'HH:i'
    });
    $(document).ready(function($) {
        $('[data-toggle="popover"]').popover({
            trigger: 'focus'
        });
        CKEDITOR.replace('ckeditor');
    });
</script>

<script>
    $(document).ready(function() {
        // Check/uncheck all checkboxes
        $('#check_all').on('change', function() {
            $('.row_checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedIds();
        });

        // If all checkboxes are checked, check the "check all" checkbox
        $(document).on('change', '.row_checkbox', function() {
            if ($('.row_checkbox:checked').length == $('.row_checkbox').length) {
                $('#check_all').prop('checked', true);
            } else {
                $('#check_all').prop('checked', false);
            }
            updateSelectedIds();
        });

        function updateSelectedIds() {
            var selectedIds = [];
            $('.row_checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            $('.selected-ids').val(selectedIds.join(','));
        }
    });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"
    integrity="sha512-8QFTrG0oeOiyWo/VM9Y8kgxdlCryqhIxVeRpWSezdRRAvarxVtwLnGroJgnVW9/XBRduxO/z1GblzPrMQoeuew=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>

<script>
    $(document).ready(function() {
        $('.dropify').dropify();
    });
</script>

<!-- END: Theme JS-->

<!-- BEGIN: Page JS-->
@include('sweetalert::alert')
<!-- END: Page JS-->

<script>
$(function () {
    var currentLayout = localStorage.getItem('judge_currentLayout');

    if (currentLayout === null) {
        currentLayout = 'dark';
        localStorage.setItem('judge_currentLayout', 'dark');
    }

    $('#content_body').data('type', currentLayout);

    if (currentLayout === 'light') {
        $('#layout-mode').html('<i class="ficon feather icon-moon" onclick="changeMode()"></i>');
        $('#content_body').removeClass('dark-layout').addClass('light-mode');
    } else {
        $('#layout-mode').html('<i class="ficon feather icon-sun" onclick="changeMode()"></i>');
        $('#content_body').addClass('dark-layout').removeClass('light-mode');
    }
});

function changeMode() {
    var layoutOptions = $('#content_body').data('type');
    if (layoutOptions == 'dark') {
        localStorage.setItem('judge_currentLayout', 'light');
        $('#content_body').data('type', 'light').removeClass('dark-layout').addClass('light-mode');
        $('#layout-mode').html('<i class="ficon feather icon-moon" onclick="changeMode()"></i>');
    } else {
        localStorage.setItem('judge_currentLayout', 'dark');
        $('#content_body').data('type', 'dark').addClass('dark-layout').removeClass('light-mode');
        $('#layout-mode').html('<i class="ficon feather icon-sun" onclick="changeMode()"></i>');
    }
}
</script>
@livewireScripts