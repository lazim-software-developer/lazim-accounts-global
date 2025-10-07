@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Chart of Accounts') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Chart of Account') }}</li>
@endsection
@push('css-page')
    <style>
        /* Action buttons fix */
        .tree-view .node-content .action {
            width: 10%;
            display: flex;
            gap: 6px;
            /* space between buttons */
            flex-wrap: wrap;
            /* agar jagah kam ho to neeche aa jaye */
            justify-content: flex-start;
        }

        .tree-view .node-content .action .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            padding: 4px 6px;
        }

        /* Action buttons small size */
        .tree-view .node-content .action .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            padding: 2px 4px;
            /* small padding */
            width: 20px;
            /* fixed small square size */
            height: 20px;
        }

        .tree-view .node-content .action .action-btn i {
            font-size: 14px;
            /* icon size */
            line-height: 1;
        }


        .tree-view {
            font-family: Arial, sans-serif;
        }

        .tree-view ul {
            position: relative;
            list-style: none;
            font-size: 12px;
            padding-left: 0px;
        }

        .tree-view li {
            position: relative;
            font-size: 12px;
            /* padding: 10px 0; */
            margin: 0;
        }

        .tree-view .node-toggle {
            cursor: pointer;
            position: absolute;
            left: 2px;
            top: 10px;
            font-size: 16px;
        }

        .tree-view .node-content {
            display: flex;
            align-items: center;
            padding: 6px;
            border-bottom: 1px solid #dee2e6;
        }

        .tree-view .node-content>div {
            text-align: left;
            padding: 0 8px;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        /* Code column */
        .tree-view .node-content .code {
            width: 10%;
            padding-left: 20px;
        }

        /* Name column - allow wrapping */
        .tree-view .node-content .name {
            width: 25%;
            white-space: normal !important;
            word-wrap: break-word;
            overflow: hidden;
        }

        /* Type column */
        .tree-view .node-content .type {
            width: 15%;
        }

        /* Parent column */
        .tree-view .node-content .parent {
            width: 20%;
        }

        /* Balance column */
        .tree-view .node-content .balance {
            width: 10%;
        }

        /* Initial Balance column */
        .tree-view .node-content .initial_balance {
            width: 10%;
        }

        /* Status column */
        .tree-view .node-content .status {
            width: 10%;
        }

        /* Action column */
        .tree-view .node-content .action {
            width: 10%;
        }

        .tree-view .child-nodes {
            display: none;
        }

        .tree-view .child-nodes.active {
            display: block;
        }

        .tree-view .header {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f8f9fa;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
        }

        .tree-view .header>div {
            font-size: 12px;
            text-align: left;
            padding: 0 18px;
        }



        .tree-view .header .code {
            width: 10%;
        }

        .tree-view .header .name {
            width: 25%;
        }

        .tree-view .header .type {
            width: 15%;
        }

        .tree-view .header .parent {
            width: 20%;
        }

        .tree-view .header .initial_balance {
            width: 10%;
        }

        .tree-view .header .balance {
            width: 10%;
        }

        .tree-view .header .status {
            width: 10%;
        }

        .tree-view .header .action {
            width: 10%;
        }

        .card-header {
            padding: 15px 10px 5px 30px;
        }

        /* Style for custom dropdown with checkboxes */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            max-width: 200px;
            margin-bottom: 15px;
            cursor: pointer;
        }


        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            padding: 10px;
            border-radius: 4px;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu label {
            display: block;
            padding: 5px;
            cursor: pointer;
        }

        .dropdown-menu input[type="checkbox"] {
            margin-right: 10px;
        }
    </style>
@endpush
@push('script-page')
    <script>
        $(document).on('change', '#sub_type', function() {
            $('.acc_check').removeClass('d-none');
            var type = $(this).val();
            $.ajax({
                url: '{{ route('charofAccount.subType') }}',
                type: 'POST',
                data: {
                    "type": type,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('#parent').empty();
                    $('#parent').append('<option value="">Select an account</option>');
                    $.each(data, function(key, value) {
                        $('#parent').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                },
                error: function() {
                    alert('Failed to load parent accounts. Please try again.');
                }
            });
        });
        $(document).on('click', '#account', function() {
            const isChecked = $(this).is(':checked');
            const parentSelect = $('#parent');
            if (isChecked) {
                $('.acc_type').removeClass('d-none');
                parentSelect.prop('required', true);
            } else {
                $('.acc_type').addClass('d-none');
                parentSelect.prop('required', false);
            }
        });
        // Tree view toggle
        $(document).on('click', '.node-toggle', function() {
            $(this).toggleClass('ti-plus ti-minus');
            $(this).siblings('.child-nodes').toggleClass('active');
        });
        // Custom Dropdown with Checkboxes for Hiding Columns
        $(document).ready(function() {
            // Toggle dropdown visibility
            $('.dropdown-toggle').on('click', function() {
                // Close all other dropdowns
                $('.dropdown-menu').not($(this).siblings('.dropdown-menu')).removeClass('show');
                // Toggle the current dropdown
                $(this).siblings('.dropdown-menu').toggleClass('show');
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.dropdown').length) {
                    $('.dropdown-menu').removeClass('show');
                }
            });

            // Load saved columns from localStorage
            var savedColumns = JSON.parse(localStorage.getItem('hiddenColumns')) || [];
            if (savedColumns.length > 0) {
                savedColumns.forEach(function(column) {
                    $('.tree-view .header .' + column + ', .tree-view .node-content .' + column).css(
                        'display', 'none');
                    $('.dropdown-menu input[value="' + column + '"]').prop('checked', true);
                });
            }

            // Handle checkbox changes
            $('.dropdown-menu input[type="checkbox"]').on('change', function() {
                var hiddenColumns = [];
                $('.dropdown-menu input[type="checkbox"]:checked').each(function() {
                    hiddenColumns.push($(this).val());
                });

                // Save to localStorage
                localStorage.setItem('hiddenColumns', JSON.stringify(hiddenColumns));

                // Reset all columns to visible
                $('.tree-view .header > div, .tree-view .node-content > div').css('display', 'flex');

                // Hide selected columns
                hiddenColumns.forEach(function(column) {
                    $('.tree-view .header .' + column + ', .tree-view .node-content .' + column)
                        .css('display', 'none');
                });
            });

            // View All/View Less Toggle
            $('#view-toggle').on('click', function() {
                var currentUrl = new URL(window.location.href);
                var viewParam = currentUrl.searchParams.get('view');
                var button = $(this);

                if (viewParam === 'all') {
                    currentUrl.searchParams.delete('view');
                    button.html('<i class="ti ti-eye">View All</i>');
                } else {
                    currentUrl.searchParams.set('view', 'all');
                    button.html('<i class="ti ti-eye-off">View Less</i>');
                }

                window.location.href = currentUrl.toString();
            });

        });
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        @can('create chart of account')
            <a href="#" data-url="{{ route('chart-of-account.create') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                data-size="lg" data-ajax-popup="true" data-title="{{ __('Create New Account') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
            <a href="#" id="view-toggle"
                title="{{ request()->has('view') && request()->get('view') === 'all' ? __('View Less') : __('View All') }}"
                class="btn btn-sm btn-primary">
                <i class="ti {{ request()->has('view') && request()->get('view') === 'all' ? 'ti-eye-off' : 'ti-eye' }}">
                    {{ request()->has('view') && request()->get('view') === 'all' ? __('View Less') : __('View All') }}
                </i>
            </a>
        @endcan
    </div>
@endsection
@section('content')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card" id="show_filter">
                    <div class="card-body">
                        {{ Form::open(['route' => ['chart-of-account.index', array_merge(['view' => request()->has('view') ? request()->get('view') : ''], request()->query())], 'method' => 'GET', 'id' => 'report_bill_summary']) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('start_date', $filter['startDateRange'], ['class' => 'startDate form-control']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('end_date', $filter['endDateRange'], ['class' => 'endDate form-control']) }}
                                            @if (request()->has('view'))
                                                {{ Form::hidden('view', request()->get('view')) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto mt-4">
                                <div class="row">
                                    <div class="col-auto">
                                        <a href="#" class="btn btn-sm btn-primary"
                                            onclick="document.getElementById('report_bill_summary').submit(); return false;"
                                            data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                            data-original-title="{{ __('apply') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="{{ route('chart-of-account.index') }}" class="btn btn-sm btn-danger"
                                            data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                            data-original-title="{{ __('Reset') }}">
                                            <span class="btn-inner--icon"><i
                                                    class="ti ti-trash-off text-white-off"></i></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="card-body d-flex justify-content-end">
            {{-- <label>{{ __('Hide Columns') }}</label> --}}
            <div class="dropdown me-4">
                <button class="btn btn-sm  dropdown-toggle btn-primary"
                    type="button">{{ __('Select Columns to Hide') }}</button>
                <div class="dropdown-menu">
                    <label><input type="checkbox" value="code"> {{ __('Code') }}</label>
                    <label><input type="checkbox" value="name"> {{ __('Name') }}</label>
                    <label><input type="checkbox" value="type"> {{ __('Type') }}</label>
                    <label><input type="checkbox" value="parent"> {{ __('Parent') }}</label>
                    <label><input type="checkbox" value="balance"> {{ __('Balance') }}</label>
                    <label><input type="checkbox" value="status"> {{ __('Status') }}</label>
                    <label><input type="checkbox" value="action"> {{ __('Action') }}</label>
                </div>
            </div>
        </div>
        @foreach ($chartAccounts as $type => $accounts)
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6>{{ $type }}</h6>
                    </div>
                    <div class=" tree-view">
                        <!-- Table Header -->
                        <div class="header">
                            <div class="code">{{ __('Code') }}</div>
                            <div class="name">{{ __('Name') }}</div>
                            <div class="type">{{ __('Type') }}</div>
                            <div class="parent">{{ __('Parent Account Name') }}</div>
                            <div class="initial_balance">{{ __('Initial Balance') }}</div>
                            <div class="balance">{{ __('Balance') }}</div>
                            <div class="status">{{ __('Status') }}</div>
                            <div class="action">{{ __('Action') }}</div>
                        </div>
                        <!-- Tree View Content -->
                        <ul>
                            @foreach ($accounts as $account)
                                @php
                                    $balance = 0;
                                    $totalDebit = 0;
                                    $totalCredit = 0;
                                    $totalBalance = App\Models\Utility::getAccountBalance(
                                        $account->id,
                                        $filter['startDateRange'],
                                        $filter['endDateRange'],
                                    );
                                    $request = request();
                                    $newTotalBalance = 0;
                                    if ($account->is_sync == 1) {
                                        $amount = App\Models\Utility::getNewChartOfAccountBalance(
                                            $account->id,
                                            $filter['startDateRange'],
                                            $filter['endDateRange'],
                                        );
                                        $newTotalBalance = $amount;
                                    }
                                @endphp
                                <li
                                    style="{{ isset($request->view) && $request->view == 'all' ? '' : ($account->is_sync == 1 && $newTotalBalance == 0 ? 'display: none;' : '') }}">
                                    @if (isset($account->children) && !empty($account->children))
                                        <i class="ti ti-plus node-toggle"></i>
                                    @endif
                                    <div class="node-content">
                                        <div class="code">{{ $account->code }}</div>
                                        <div class="name">
                                            <a
                                                href="{{ route('report.ledger') }}?account={{ $account->id }}">{{ $account->name }}</a>
                                        </div>
                                        <div class="type">{{ !empty($account->subType) ? $account->subType->name : '-' }}
                                        </div>
                                        <div class="parent">
                                            {{ !empty($account->parentAccount) ? $account->parentAccount->name : '-' }}
                                        </div>
                                        <div class="initial_balance">
                                            {{ \Auth::user()->priceFormat($account->initial_balance) }}
                                        </div>
                                        <div class="balance">
                                            @if ($account->is_sync == 0)
                                                @if (!empty($totalBalance))
                                                    {{ \Auth::user()->priceFormat($totalBalance) }}
                                                @else
                                                    -
                                                @endif
                                            @else
                                                @if (!empty($newTotalBalance))
                                                    {{ \Auth::user()->priceFormat($newTotalBalance) }}
                                                @else
                                                    -
                                                @endif
                                            @endif
                                        </div>
                                        <div class="status">
                                            @if ($account->is_enabled == 1)
                                                <span class="p-2 px-3 rounded badge bg-success">{{ __('Enabled') }}</span>
                                            @else
                                                <span class="p-2 px-3 rounded badge bg-danger">{{ __('Disabled') }}</span>
                                            @endif
                                        </div>
                                        <div class="action">
                                            <div class="action-btn bg-warning ms-2">
                                                <a href="{{ route('report.ledger') }}?account={{ $account->id }}"
                                                    class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                    title="{{ __('Transaction Summary') }}"
                                                    data-original-title="{{ __('Ledger Summary') }}">
                                                    <i class="text-white ti ti-wave-sine"></i>
                                                </a>
                                            </div>
                                            @can('edit chart of account')
                                                <div class="action-btn bg-info ms-2">
                                                    <a class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('chart-of-account.edit', $account->id) }}"
                                                        data-ajax-popup="true" data-title="{{ __('Edit Account') }}"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                        data-original-title="{{ __('Edit') }}">
                                                        <i class="text-white ti ti-edit"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete chart of account')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['chart-of-account.destroy', $account->id],
                                                        'id' => 'delete-form-' . $account->id,
                                                    ]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                        data-original-title="{{ __('Delete') }}"
                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="document.getElementById('delete-form-{{ $account->id }}').submit();">
                                                        <i class="text-white ti ti-trash"></i>
                                                    </a>
                                                    {!! Form::close() !!}
                                                </div>
                                            @endcan
                                        </div>
                                    </div>
                                    @if (isset($account->children) && !empty($account->children))
                                        @include('chartOfAccount._account_item', [
                                            'accounts' => $account->children,
                                        ])
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
