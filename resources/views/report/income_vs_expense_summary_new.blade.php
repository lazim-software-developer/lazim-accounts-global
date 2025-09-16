@extends('layouts.admin')
@section('page-title')
    {{ __('Income Vs Expense Summary') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Income vs Expense Summary') }}</li>
@endsection

@push('theme-script')
    <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
@endpush

@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var year = '2025';
        var filename = $('#filename').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 72,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'A2'
                }
            };
            html2pdf().set(opt).from(element).save();

        }
    </script>
@endpush


@section('action-btn')
    <div class="float-end">
        {{--        <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}"> --}}
        {{--            <i class="ti ti-filter"></i> --}}
        {{--        </a> --}}

        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()"data-bs-toggle="tooltip"
            title="{{ __('Download') }}" data-original-title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>

    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['report.income.vs.expense.summary.new'], 'method' => 'GET', 'id' => 'income_vs_expense_summary']) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('start_date', __('Start Date'), ['class' => 'text-type']) }}
                                            {{ Form::date('start_date', isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d'), ['class' => 'month-btn form-control']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('end_date', __('End Date'), ['class' => 'text-type']) }}
                                            {{ Form::date('end_date', isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'), ['class' => 'month-btn form-control']) }}
                                        </div>
                                    </div>


                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto mt-4">
                                        <a href="#" class="btn btn-sm btn-primary"
                                            onclick="document.getElementById('income_vs_expense_summary').submit(); return false;"
                                            data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                            data-original-title="{{ __('apply') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="{{ route('report.income.vs.expense.summary.new') }}"
                                            class="btn btn-sm btn-danger " data-bs-toggle="tooltip"
                                            title="{{ __('Reset') }}" data-original-title="{{ __('Reset') }}">
                                            <span class="btn-inner--icon"><i
                                                    class="ti ti-trash-off text-white-off "></i></span>
                                        </a>
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
    <div id="printableArea">
        <div class="card my-2">
            <div class="card-body">
                <div>
                    {{-- <h4 class="text-center">Suntech Tower Owners Association</h4>
                    <p class="text-center mb-0">Office Number 1001, Suntech Tower</p>
                    <p class="text-center mb-0">Plot No: 26-061, Nadd Hessa</p>
                    <p class="text-center mb-2">Dubai Silicon Oasis</p>
                    <p class="text-center mb-4"><strong>Emirate:</strong> Not Applicable</p>

                    <h5 class="text-center text-decoration-underline mb-4">Balance Sheet</h5>
                    <p class="text-center mb-4">1-Jan-24 to 24-Jun-24</p> --}}

                    <div class="row">
                        <div class="col-md-12">
                            @foreach ($reportData as $subtype)
                                <div class="mb-2">
                                    <h6 class="text-decoration-underline">{{ $subtype['subtype_name'] }}</h6>
                                    @foreach ($subtype['accounts'] as $account)
                                        @if ($account->balance_difference > 0)
                                            <div class="d-flex justify-content-between ms-3 fst-italic">
                                                <span>{{ $account->account_name }}</span>
                                                <span
                                                    class="right-align">{{ number_format($account->balance_difference, 2) }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                    @if ($subtype['group_total'] > 0)
                                        <div
                                            class="d-flex justify-content-between fw-bold small py-1 mt-2 ms-3 text-uppercase">
                                            <span class="text-upercase">Total for {{ $subtype['subtype_name'] }}</span>
                                            <span
                                                class="right-align">{{ number_format($subtype['group_total'], 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            <hr>
                            <div class="d-flex justify-content-between fw-bold py-1 mt-2 ms-3 text-uppercase">
                                <span class="text-upercase">Grand Total</span>
                                <span class="right-align">{{ number_format($grandTotal, 2) }}</span>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
