@extends('layouts.admin')
@section('page-title')
    {{ __('Product & Services') }}
@endsection
@push('script-page')
    <!-- Include Your Plugin Script -->
    <script src="{{ asset('assets/custom-plugins/utilties/utilities.js') }}"></script>
    <script src="{{ asset('assets/custom-plugins/ajax-grid/ajax-grid.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize the plugin for your table container
            var $tableContainer = $('#grid-services').ajaxGrid({
                url: "{{ route('productservice.ajax-load-services') }}", // Replace with your route
                container: '#grid-services', // The container where the table and pagination will be injected
                // searchField: '', // Dynamically create the search field
                // loader: null, // No loader passed, it will be created dynamically
                // extraParams: {}, // Any extra parameters you want to send with the request
                showSearchField: true, // Whether to create the search field dynamically
            });

            $(document).on("click", "[data-button-search]", (event) => {
                event.preventDefault(); // Prevent default action of anchor tag
                // Assuming 'type' is defined somewhere in your code, for example:
                const type = $(event.currentTarget).data('button-search');

                const tableInstance = $('#grid-services').data('ajaxGrid');
                if (type === "CLEAR" && tableInstance) {
                    tableInstance.refresh({}); // Refresh the table with the new parameters for clearing
                    return;
                }

                // Get form and query parameters combined
                const combinedParams = Utilities.serializeFormToParams('#product_service');

                // Get the table instance and refresh with combined parameters
                if (tableInstance) {
                    tableInstance.refresh(combinedParams); // Refresh the table with the new parameters
                }
            });
        });
    </script>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Product & Services') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">

        <!-- <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{ __('Filter') }}">
                                                                                                                                                                                                                                                                                                                                                                                    <i class="ti ti-filter"></i>
                                                                                                                                                                                                                                                                                                                                                                                </a> -->

        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>

        <a href="{{ route('productservice.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>

        <a href="#" data-size="lg" data-url="{{ route('productservice.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create Product & Service') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>

    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" multi-collapse mt-2 {{ isset($_GET['category']) ? 'show' : '' }}" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['productservice.index'], 'method' => 'GET', 'id' => 'product_service']) }}
                        <div class="row d-flex align-items-center justify-content-end">
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="btn-box">
                                    {{--                                    {{ Form::label('category', __('Category'), ['class' => 'text-type form-label']) }} --}}
                                    {{ Form::select('category', $category, null, ['class' => 'form-control select', 'id' => 'choices-multiple', 'required' => 'required']) }}

                                </div>
                            </div>
                            <div class="col-auto float-end ms-2">
                                {{-- <a href="#" class="btn btn-sm btn-primary"
                                    onclick="document.getElementById('product_service').submit(); return false;"
                                    data-bs-toggle="tooltip" title="{{ __('apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a> --}}
                                <a href="javascript:void(0)" class="btn btn-sm btn-primary" data-button-search="SEARCH"
                                    data-bs-toggle="tooltip" title="{{ __('apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                    data-button-search="CLEAR" title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off "></i></span>
                                </a>
                            </div>

                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5></h5>
                    <!-- Table -->
                    <div id="grid-services">

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
