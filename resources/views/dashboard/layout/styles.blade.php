<link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600" rel="stylesheet">

<!-- BEGIN: Vendor CSS-->
@if (app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/vendors/css/vendors-rtl.min.css') }}">
@else
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/vendors/css/vendors.min.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('_dashboard/app-assets/vendors/css/ui/prism.min.css') }}"> <!-- END: Vendor CSS-->
<!-- BEGIN: Theme CSS-->
@if (app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/bootstrap-extended.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/components.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/themes/dark-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/themes/semi-dark-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/core/menu/menu-types/vertical-menu.css') }}">
@else
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/themes/dark-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/themes/semi-dark-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
@endif
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- BEGIN: Page CSS-->
@if (app()->getLocale() === 'ar')
     <link rel="stylesheet" href="{{asset('_dashboard/app-assets/css-rtl/core/menu/menu-types/vertical-menu.css')}}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/pages/app-chat.css') }}">
@else
    <link rel="stylesheet" href="{{asset('_dashboard/app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
    <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css/pages/app-chat.css') }}">
@endif
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/v4-shims.css">

<!-- BEGIN: Custom CSS-->
@if (app()->getLocale() === 'ar')
  <link rel="stylesheet" href="{{ asset('_dashboard/app-assets/css-rtl/custom-rtl.css') }}">
   <link rel="stylesheet" href="{{ asset('_dashboard/assets/css/style-rtl.css') }}">
@else
   <link rel="stylesheet" href="{{ asset('_dashboard/assets/css/style.css') }}">
@endif
<!-- END: Custom CSS-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css"
    integrity="sha512-EZSUkJWTjzDlspOoPSpUFR0o0Xy7jdzW//6qhUkoZ9c4StFkVsp9fbbd0O06p9ELS3H486m4wmrCELjza4JEog=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css"
    integrity="sha512-nMNlpuaDPrqlEls3IX/Q56H36qvBASwb3ipuo3MxeWbsQB1881ox0cRv7UPTgBlriqoynt35KjEwgGUeUXIPnw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.standalone.min.css"
    integrity="sha512-D5/oUZrMTZE/y4ldsD6UOeuPR4lwjLnfNMWkjC0pffPTCVlqzcHTNvkn3dhL7C0gYifHQJAIrRTASbMvLmpEug=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">

<style>
    .datepicker-dropdown {
        max-width: 300px;
    }

    .datepicker {
        float: right
    }

    .datepicker.dropdown-menu {
        right: auto
    }

    .btn-purple {
        background-color: rgb(79, 37, 110);
        color: white;
    }
</style>
@livewireStyles