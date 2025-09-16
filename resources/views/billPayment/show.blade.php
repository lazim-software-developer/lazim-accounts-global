@extends('layouts.admin')

@section('page-title')
    {{ __('Bill Payment Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('BillPayment.index') }}">{{ __('Bill Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Payment Details') }}</li>
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
        <div class="col-12" id="printableArea">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Bill Payment Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ __('Date') }}:</strong>
                            <p>{{ \Auth::user()->dateFormat($billPayment->date) }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Amount') }}:</strong>
                            <p>{{ \Auth::user()->priceFormat($billPayment->amount) }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Description') }}:</strong>
                            <p>{{ $billPayment->description }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Bill') }}:</strong>
                            <p>{{ \Auth::user()->billNumberFormat($billPayment->bill->bill_id) }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Payment Receipt') }}:</strong>
                            @if ($billPayment->add_receipt)
                                <a href="{{ Storage::disk('s3')->url($billPayment->add_receipt) }}" target="_blank">{{ __('Download') }}</a>
                            @else
                                <p>{{ __('No receipt uploaded') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Transfer Methods') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Transfer Method') }}</th>
                                    <th>{{ __('Reference Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transferTypes as $transferType)
                                    <tr>
                                        <td>{{ $transferType->transfer_type }}</td>
                                        <td>{{ $transferType->reference_number }}</td>
                                        <td>{{ \Auth::user()->dateFormat($transferType->date) }}</td>
                                        <td>
                                            <div class="action-btn bg-danger ms-2">
                                                {!! Form::open(['method' => 'DELETE', 'route' => ['transfer-type.delete', $transferType->id], 'class' => 'delete-form-btn', 'id' => 'delete-form-' . $transferType->id]) !!}
                                                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{ __('Delete') }}" data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}" data-confirm-yes="document.getElementById('delete-form-{{ $transferType->id }}').submit();">
                                                    <i class="ti ti-trash text-white"></i>
                                                </a>
                                                {!! Form::close() !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
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