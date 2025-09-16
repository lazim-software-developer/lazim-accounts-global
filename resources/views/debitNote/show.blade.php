@extends('layouts.admin')

@section('page-title')
    {{ __('Debit Note Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('debit.note') }}">{{ __('Debit Note') }}</a></li>
    <li class="breadcrumb-item">{{ __('Debit Note Details') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" onclick="saveAsPDF()" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Print') }}">
            <i class="ti ti-printer"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="printableArea">
                <div class="card-header">
                    <h5>{{ __('Debit Note Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>{{ __('Bill Number') }}</th>
                                    <td>{{ Auth::user()->billNumberFormat($bill->bill_id) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Vender') }}</th>
                                    <td>{{ !empty($bill->vender) ? $bill->vender->name : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($debitNote->date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Amount') }}</th>
                                    <td>{{ Auth::user()->priceFormat($debitNote->amount) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('VAT Amount') }}</th>
                                    <td>{{ Auth::user()->priceFormat($debitNote->amount * ($debitNote->vat_percentage / 100)) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Description') }}</th>
                                    <td>{{ !empty($debitNote->description) ? $debitNote->description : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Reference Number') }}</th>
                                    <td>{{ !empty($debitNote->reference) ? $debitNote->reference : '-' }}</td>
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
