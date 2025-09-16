@extends('layouts.admin')
@section('page-title')
    {{ __('Tax Summary') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Report') }}</li>
    <li class="breadcrumb-item">{{ __('Tax Summary') }}</li>
@endsection
@push('css-page')
    <style>
        .pdf-header {
            display: none;
        }

        @media print {
            .pdf-header {
                display: block;
            }
        }
    </style>
@endpush
@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var year = '{{ $currentYear }}';

        var filename = $('#filename').val();


        function saveAsPDF() {
            var element = document.getElementById('printableArea');

            // SETTING CSS OF PDF HEADER FOR SHOWING

            $('.pdf-header').css('display', 'block');

            // Add timestamp div before generating PDF
            var timestampDiv = document.createElement('div');
            timestampDiv.className = 'timestamp-footer';
            timestampDiv.style.cssText =
                'text-align: right; margin-top: 25px; padding: 10px 0; font-size: 12px; color: #666; border-top: 1px solid #eee;';
            timestampDiv.innerHTML = 'Generated on: ' + new Date().toLocaleString();
            element.querySelector('.table-responsive').after(timestampDiv);
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
            html2pdf().set(opt).from(element).save().then(function() {
                // Hide header and remove timestamp after PDF is generated
                $('.pdf-header').css('display', 'none');
                element.querySelector('.timestamp-footer').remove();
            });
        }
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        <!-- <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{ __('Filter') }}">
                                                                                                                                                                            <i class="ti ti-filter"></i>
                                                                                                                                                                        </a> -->

        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()"data-bs-toggle="tooltip"
            title="{{ __('Download') }}" data-original-title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>

    </div>
@endsection


@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" multi-collapse mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">

                        {{ Form::open(['route' => ['report.tax.summary'], 'method' => 'GET', 'id' => 'report_tax_summary']) }}
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
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::select('year', $yearList, isset($_GET['year']) ? $_GET['year'] : '', ['class' => 'form-control']) }}
                                        </div>
                                    </div>


                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto">

                                        <a href="#" class="btn btn-sm btn-primary"
                                            onclick="document.getElementById('report_tax_summary').submit(); return false;"
                                            data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                            data-original-title="{{ __('apply') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>

                                        <a href="{{ route('report.tax.summary') }}" class="btn btn-sm btn-danger "
                                            data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                            data-original-title="{{ __('Reset') }}">
                                            <span class="btn-inner--icon"><i
                                                    class="ti ti-refresh text-white-off "></i></span>
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
        <input type="hidden"
            value="{{ __('Tax Summary') . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}"
            id="filename">

        {{-- <div class="row mt-3 hide-in-print">
            <div class="col">

                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                    <h6 class="report-text mb-0">{{ __('Tax Summary') }}</h6>
                </div>
            </div>
            <div class="col">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Duration') }} :</h7>
                    <h6 class="report-text mb-0">{{ $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}</h6>
                </div>
            </div>
        </div> --}}

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        <div class="pdf-header">
                            <div class="row">
                                <div class="col-md-8">
                                    <img src="{{ $img }}" style="max-width: 250px" />
                                </div>
                                <div class="col-md-4 text-end">
                                    <strong class="invoice-number">{{ $settings['company_name'] ?? '' }}</strong><br>
                                    <strong class="invoice-number">{{ $settings['company_email'] ?? '' }}</strong><br>
                                    <strong class="invoice-number">{{ $settings['company_address'] ?? '' }}</strong><br>
                                    <strong class="invoice-number">{{ $settings['company_city'] ?? '' }}</strong>,
                                    <strong class="invoice-number">{{ $settings['company_state'] ?? '' }}</strong><br>
                                    <strong class="invoice-number">{{ $settings['company_zipcode'] ?? '' }}</strong>,
                                    <strong class="invoice-number">{{ $settings['company_country'] ?? '' }}</strong><br>
                                    <strong class="invoice-number">{{ $settings['company_telephone'] ?? '' }}</strong><br>
                                </div>
                            </div><br>
                            <div class="row justify-content-end">
                                <div class="col-md-auto text-end">
                                    <strong>
                                        <h5>{{ __('Statement of Accounts') }}</h5>
                                        <hr class="text-dark text-end my-2">
                                    </strong>
                                    <strong>{{ \Carbon\Carbon::parse($filter['startDateRange'])->format('d-m-Y') . '  ' . 'to' . '  ' . \Carbon\Carbon::parse($filter['endDateRange'])->format('d-m-Y') }}</strong>
                                    <hr class="text-dark my-2">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <h5>{{ __('Invoice') . ' ' . '(Outwards)' }}</h5>
                            <div class="table-responsive mt-3 mb-3">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Tax') }}</th>
                                            <th>{{ __('Taxable') . ' ' . __('Amount') }}</th>
                                            <th>{{ __('Tax') . ' ' . __('Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @forelse($incomeTaxesData as $k => $v)
                                            <tr>
                                                <td>{{ $k }}</td>
                                                <td>{{ \Auth::user()->priceFormat($v['taxableAmount']) }}</td>
                                                <td>{{ \Auth::user()->priceFormat($v['tax']) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="13" class="text-center">{{ __('Income tax not found') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                        <tr>
                                            <td class="text-dark"><span></span><strong>{{ __('Total :') }}</strong></td>
                                            <td class="text-dark">
                                                <span></span><strong>{{ \Auth::user()->priceFormat(array_sum(array_column($incomeTaxesData, 'taxableAmount'))) }}</strong>
                                            </td>
                                            <td class="text-dark">
                                                <span></span><strong>{{ \Auth::user()->priceFormat(array_sum(array_column($incomeTaxesData, 'tax'))) }}</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <h5>{{ __('Bill') . ' ' . __('(Inwards)') }}</h5>
                            <div class="table-responsive mt-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Tax') }}</th>
                                            <th>{{ __('Taxable') . ' ' . __('Amount') }}</th>
                                            <th>{{ __('Tax') . ' ' . __('Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expenseTaxesData as $k => $v)
                                            <tr>
                                                <td>
                                                    {{ $k }}
                                                </td>
                                                <td>{{ \Auth::user()->priceFormat($v['taxableAmount']) }}</td>
                                                <td>{{ \Auth::user()->priceFormat($v['tax']) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="13" class="text-center">{{ __('Expense tax not found') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                        <tr>
                                            <td class="text-dark"><span></span><strong>{{ __('Total :') }}</strong></td>
                                            <td class="text-dark">
                                                <span></span><strong>{{ \Auth::user()->priceFormat(array_sum(array_column($expenseTaxesData, 'taxableAmount'))) }}</strong>
                                            </td>
                                            <td class="text-dark">
                                                <span></span><strong>{{ \Auth::user()->priceFormat(array_sum(array_column($expenseTaxesData, 'tax'))) }}</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="table-responsive mt-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Total') . ' ' . __('Payable') }}</th>
                                            <th></th>
                                            <th>
                                                {{ \Auth::user()->priceFormat(array_sum(array_column($incomeTaxesData, 'tax'))) -
                                                    array_sum(array_column($expenseTaxesData, 'tax')) }}
                                            </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
