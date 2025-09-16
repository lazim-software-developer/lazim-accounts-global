@extends('layouts.admin')
@section('page-title')
    {{ __('Trial Balance') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Trial Balance') }}</li>
@endsection

@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var printContents = document.getElementById('printableArea').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#filter").click(function() {
                $("#show_filter").toggle();
            });
        });
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        <a href="#" onclick="saveAsPDF()" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
            title="{{ __('Print') }}" data-original-title="{{ __('Print') }}"><i class="ti ti-printer"></i></a>
    </div>

    <div class="float-end me-2">
        {{ Form::open(['route' => ['trial.balance.export']]) }}
        <input type="hidden" name="start_date" class="start_date">
        <input type="hidden" name="end_date" class="end_date">
        <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            data-original-title="{{ __('Export') }}"><i class="ti ti-file-export"></i></button>
        {{ Form::close() }}
    </div>
    <div class="float-end me-2" id="filter">
        <button id="filter" class="btn btn-sm btn-primary"><i class="ti ti-filter"></i></button>
    </div>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card" id="show_filter" style="display:none;">
                    <div class="card-body">
                        {{ Form::open(['route' => ['trial.balance'], 'method' => 'GET', 'id' => 'report_trial_balance']) }}
                        <div class="col-xl-12">

                            <div class="row justify-content-between">
                                <div class="mt-4 col-xl-3">
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons"
                                        aria-label="Basic radio toggle button group">
                                        <label class="btn btn-primary month-label">
                                            <a href="{{ route('trial.balance', ['collapse']) }}" class="text-white"
                                                id="collapse"> {{ __('Collapse') }} </a>
                                        </label>

                                        <label class="btn btn-primary year-label active">
                                            <a href="{{ route('trial.balance', ['expand']) }}" class="text-white">
                                                {{ __('Expand') }} </a>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xl-9">
                                    <div class="row justify-content-end align-items-center">
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
                                            </div>
                                        </div>

                                        <div class="col-auto mt-4">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('report_trial_balance').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>

                                            <a href="{{ route('trial.balance') }}" class="btn btn-sm btn-danger"
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off"></i></span>
                                            </a>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    @php
        use App\Models\ChartOfAccountType;
        $authUser = \Auth::user()->creatorId();
        $user = App\Models\User::find($authUser);
    @endphp

    <div class="row justify-content-center" id="printableArea">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body {{ $view == 'collapse' ? 'collapse-view' : '' }}">
                    <div class="mb-5 account-main-title">
                        <h5>{{ 'Trial Balance of ' . $user->name . ' as of ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}
                            </h4>
                    </div>
                    <div
                        class="py-2 aacount-title d-flex align-items-center justify-content-between border-top border-bottom">
                        <h6 class="mb-0">{{ __('Account') }}</h6>
                        {{-- <h6 class="mb-0 text-center">{{ _('Account Code') }}</h6> --}}
                        <h6 class="mb-0 text-center">{{ __('Opening Balance') }}</h6>
                        <h6 class="mb-0 text-end me-5">{{ __('Debit') }}</h6>
                        <h6 class="mb-0 text-end">{{ __('Credit') }}</h6>
                        <h6 class="mb-0 text-center">{{ __('Closing Balance') }}</h6>
                    </div>
                    @php
                        $totalCredit = 0;
                        $totalDebit = 0;
                        $openingBalance = 0;
                        $closingBalance = 0;
                    @endphp
                    @foreach ($totalAccounts as $type => $accounts)
                        <div class="py-2 account-main-inner border-bottom">
                            <p class="mb-2 fw-bold ps-2">{{ $type }}</p>
                            @if ($view == 'collapse')
                                @foreach ($accounts as $key => $record)
                                    @if ($record['account'] == 'parentTotal')
                                        <div class="account-inner d-flex align-items-center justify-content-between ps-3">
                                            <div class="mb-2 account-arrow">
                                                <a href="{{ route('trial.balance', ['expand']) }}"><i
                                                        class="ti ti-chevron-down account-icon"></i></a>
                                                <a href="{{ route('report.ledger', $record['account_id']) }}?account={{ $record['account_id'] }}"
                                                    class="text-primary">{{ str_replace('Total ', '', $record['account_name']) }}</a>
                                            </div>

                                            {{-- <p class="mb-2 text-center">{{ $record['account_code'] }}</p> --}}
                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['opening_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}
                                            </p>
                                            <p class="mb-2 text-primary text-end me-5">
                                                {{ \Auth::user()->priceFormat($record['totalDebit'] ?? 0) }}</p>
                                            <p class="mb-2 text-primary float-end text-end">
                                                {{ \Auth::user()->priceFormat($record['totalCredit'] ?? 0) }}</p>

                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['closing_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}
                                            </p>
                                        </div>
                                    @elseif($record['account'] == 'parentTotal' || $record['account'] == '')
                                        <div class="account-inner d-flex align-items-center justify-content-between ps-3">
                                            <p class="mb-2 ms-3"><a
                                                    href="{{ route('report.ledger', $record['account_id']) }}?account={{ $record['account_id'] }}"
                                                    class="text-primary">{{ $record['account_name'] }}</a>
                                            </p>
                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['opening_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}
                                            </p>
                                            <p class="mb-2 text-primary text-end me-5">
                                                {{ \Auth::user()->priceFormat($record['totalDebit'] ?? 0) }}</p>
                                            <p class="mb-2 text-primary float-end text-end">
                                                {{ \Auth::user()->priceFormat($record['totalCredit'] ?? 0) }}</p>

                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['closing_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}
                                            </p>
                                        </div>
                                    @endif
                                    @php
                                        if ($record['account'] != 'parent' && $record['account'] != 'subAccount') {
                                            $totalDebit += $record['totalDebit'];
                                            $totalCredit += $record['totalCredit'];
                                            if (in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE)) {
                                                $closingBalance += $record['closing_balance'] ?? 0;
                                                $openingBalance += $record['opening_balance'] ?? 0;
                                            } else {
                                                $closingBalance -= $record['closing_balance'] ?? 0;
                                                $openingBalance -= $record['opening_balance'] ?? 0;
                                            }
                                        }
                                    @endphp
                                @endforeach
                            @else
                                @foreach ($accounts as $key => $record)
                                    @if ($record['account'] == 'parent')
                                        <div class="account-inner d-flex align-items-center justify-content-between ps-3">
                                            <div class="mb-2 account-arrow">
                                                <a href="{{ route('trial.balance', ['collapse']) }}"><i
                                                        class="ti ti-chevron-down account-icon"></i></a>
                                                <a href="{{ route('report.ledger', $record['account_id']) }}?account={{ $record['account_id'] }}"
                                                    class="text-primary fw-bold">{{ str_replace('Total ', '', $record['account_name']) }}</a>
                                            </div>

                                            {{-- <p class="mb-2 text-center">{{ $record['account_code'] }}</p> --}}
                                            {{-- <p class="mb-2 text-primary fw-bold text-end me-5">
                                                {{ \Auth::user()->priceFormat($record['totalDebit']) }}</p>
                                            <p class="mb-2 text-primary fw-bold float-end text-end">
                                                {{ \Auth::user()->priceFormat($record['totalCredit']) }}</p> --}}
                                        </div>
                                    @elseif($record['account'] == 'parentTotal')
                                        <div class="account-inner d-flex align-items-center justify-content-between ps-3">
                                            <p class="mb-2"><a href="#"
                                                    class="text-dark fw-bold">{{ $record['account_name'] }}</a>
                                            </p>
                                            {{-- <p class="mb-2 text-center ms-3">{{ $record['account_code'] }}</p> --}}
                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['opening_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}

                                            </p>
                                            <p class="mb-2 text-dark fw-bold text-end me-5">
                                                {{ \Auth::user()->priceFormat($record['totalDebit'] ?? 0) }}</p>
                                            <p class="mb-2 text-dark fw-bold float-end text-end">
                                                {{ \Auth::user()->priceFormat($record['totalCredit'] ?? 0) }}</p>
                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['closing_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="account-inner d-flex align-items-center justify-content-between ps-3">
                                            <p class="mb-2 ms-3"><a
                                                    href="{{ route('report.ledger') }}?account={{ $record['account_id'] }}"
                                                    class="text-primary">{{ $record['account_name'] }}</a>
                                            </p>
                                            {{-- <p class="mb-2 text-center">{{ $record['account_code'] }}</p> --}}
                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['opening_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}

                                            </p>

                                            <p class="mb-2 text-primary text-end me-5">
                                                {{ \Auth::user()->priceFormat($record['totalDebit'] ?? 0) }}</p>
                                            <p class="mb-2 text-primary float-end text-end">
                                                {{ \Auth::user()->priceFormat($record['totalCredit'] ?? 0) }}</p>
                                            <p class="mb-2 text-center">
                                                {{ \Auth::user()->priceFormat($record['closing_balance'] ?? 0) }}
                                                {{ in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE) ? 'DR' : 'CR' }}

                                            </p>
                                        </div>
                                    @endif
                                    @php
                                        if ($record['account'] != 'parent' && $record['account'] != 'subAccount') {
                                            $totalDebit += $record['totalDebit'] ?? 0;
                                            $totalCredit += $record['totalCredit'] ?? 0;
                                            if (in_array(strtolower($type), ChartOfAccountType::DEBIT_ACCOUNT_TYPE)) {
                                                $closingBalance += $record['closing_balance'] ?? 0;
                                                $openingBalance += $record['opening_balance'] ?? 0;
                                            } else {
                                                $closingBalance -= $record['closing_balance'] ?? 0;
                                                $openingBalance -= $record['opening_balance'] ?? 0;
                                            }
                                        }

                                    @endphp
                                @endforeach
                            @endif
                        </div>
                    @endforeach

                    @if ($totalAccounts != [])
                        <div
                            class="px-2 py-2 aacount-title d-flex align-items-center justify-content-between border-top border-bottom pe-0">
                            <h6 class="mb-0 fw-bold">{{ 'Total' }}</h6>
                            <h6 class="mb-0 fw-bold">{{ '' }}</h6>
                            <h6 class="mb-0 fw-bold text-end me-5">
                                {{-- @php
                                    dd(\Auth::user()->priceFormat($openingBalance),\Auth::user()->priceFormat($totalDebit),\Auth::user()->priceFormat($totalCredit),\Auth::user()->priceFormat($closingBalance));
                                @endphp --}}
                                {{ $openingBalance >= 0 ? \Auth::user()->priceFormat($openingBalance) . ' DR' : \Auth::user()->priceFormat($openingBalance) . ' CR' }}
                            </h6>
                            <h6 class="mb-0 fw-bold text-end me-5">{{ \Auth::user()->priceFormat($totalDebit) }}</h6>
                            <h6 class="mb-0 fw-bold text-end">{{ \Auth::user()->priceFormat($totalCredit) }}</h6>
                            <h6 class="mb-0 fw-bold text-end me-5">
                                {{ is_numeric($closingBalance) ? ($closingBalance >= 0 ? \Auth::user()->priceFormat($closingBalance) . ' DR' : \Auth::user()->priceFormat(-$closingBalance) . ' CR') : 'Invalid Balance' }}
                            </h6>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
@endsection


@push('script-page')
    <script>
        $(document).ready(function() {
            callback();

            function callback() {
                var start_date = $(".startDate").val();
                var end_date = $(".endDate").val();

                $('.start_date').val(start_date);
                $('.end_date').val(end_date);

            }
        });
    </script>
@endpush
