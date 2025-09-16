@extends('layouts.admin')

@section('page-title')
    {{ __('Credit Note Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('credit.note') }}">{{ __('Credit Note') }}</a></li>
    <li class="breadcrumb-item">{{ __('Credit Note Details') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" onclick="saveAsPDF()" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Print') }}">
            <i class="ti ti-printer"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="printableArea">
                <div class="card-header">
                    <h5>{{ __('Credit Note Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>{{ __('Invoice Number') }}</th>
                                    <td>{{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <td>{{ !empty($invoice->customer) ? $invoice->customer->name : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($creditNote->date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Amount') }}</th>
                                    <td>{{ Auth::user()->priceFormat($creditNote->amount) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('VAT Amount') }}</th>
                                    <td>{{ Auth::user()->priceFormat($creditNote->amount * ($creditNote->vat_percentage/100)) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Description') }}</th>
                                    <td>{{ !empty($creditNote->description) ? $creditNote->description : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Reference Number') }}</th>
                                    <td>{{ !empty($creditNote->reference) ? $creditNote->reference : '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        function saveAsPDF() {
            var printContents = document.getElementById('printableArea').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
@endpush