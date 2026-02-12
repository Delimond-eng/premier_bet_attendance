<!DOCTYPE html>
<html lang="en" data-theme="light" data-sidebar="darkgreen" data-color="primary" data-topbar="whiterock" data-layout="default" data-topbarcolor="white" data-card="bordered" data-size="default" data-width="fluid" data-loader="enable">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="description" content="SALAMA ATTENDANCE - Système de Gestion de Présence">
	<meta name="author" content="SALAMA GROUP LTD">
	<title>SALAMA ATTENDANCE | Web Admin</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/logo-4.svg') }}">



    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/icons/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/salama-logo.css') }}">

	@stack("styles")
    <style>
        [v-cloak] { display: none; }
    </style>
</head>
<body data-sidebarbg="sidebarbg4">

	<div id="global-loader">
		<div class="page-loader"></div>
	</div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">

        <!-- Header -->
        @include("components.header")
        <!-- /Header -->

        <!-- Sidebar -->
        @include("components.sidebar")
        <!-- /Sidebar -->


        <!-- Page Wrapper -->
        <div class="page-wrapper">

            @yield("content")

            <div class="footer d-sm-flex align-items-center justify-content-between border-top bg-white p-3">
                <p class="mb-0"> 2026 &copy; SALAMA ATTENDANCE.</p>
                <p>Designed &amp; Developed By <a href="javascript:void(0);" class="text-primary">Salama Group LTD. in Collaboration with Tango Protection</a></p>
            </div>

        </div>
        <!-- /Page Wrapper -->
    </div>
    <!-- /Main Wrapper -->

	<!-- Scripts -->
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
	<script src="{{ asset('assets/js/dataTables.bootstrap5.min.js') }}"></script>


    <!-- Chart JS -->
    <script src="{{asset("assets/plugins/apexchart/apexcharts.min.js")}}"></script>
    <script src="{{asset("assets/plugins/apexchart/chart-data.js")}}"></script>


    <script src="{{asset("assets/plugins/chartjs/chart.min.js")}}"></script>
    <script src="{{asset("assets/plugins/chartjs/chartjs-plugin-datalabels.min.js")}}"></script>
    <script src="{{asset("assets/plugins/chartjs/chart-data.js")}}"></script>

    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <!-- Color Picker JS -->
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/sweetalert/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/vue2.js') }}"></script>

	@stack("scripts")
</body>
</html>
