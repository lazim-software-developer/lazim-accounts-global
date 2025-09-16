@extends('layouts.admin')

@section('page-title')
    {{ __('Revenue Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('revenue.index') }}">{{ __('Revenue') }}</a></li>
    <li class="breadcrumb-item">{{ __('Revenue Details') }}</li>
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
                    <h5>{{ __('Receipt Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <td>{{ Auth::user()->dateFormat($revenue->date) }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Receipt Amount') }}</th>
                                <td>{{ Auth::user()->priceFormat($revenue->amount) }}</td>
                            </tr>
                            <!-- <tr>
                                <th>{{ __('Account') }}</th>
                                <td>{{ !empty($revenue->bankAccount) ? $revenue->bankAccount->bank_name . ' ' . $revenue->bankAccount->holder_name : '' }}
                                </td>
                            </tr> -->
                            <tr>
                                <th>{{ __('Owner') }}</th>
                                <td>{{ !empty($revenue->customer) ? $revenue->customer->name : '-' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Unit') }}</th>
                                <td>{{ !empty($revenue->customer->property_number) ? $revenue->customer->property_number : '-' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <td>{{ !empty($revenue->category) ? $revenue->category->name : '-' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <td>{{ !empty($revenue->reference) ? $revenue->reference : '-' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Description') }}</th>
                                <td>{{ !empty($revenue->description) ? $revenue->description : '-' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Payment Method') }}</th>
                                <td>{{ !empty($revenue->transaction_method) ? $revenue->transaction_method : '-' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Payment Receipt') }}</th>
                                <td>
                                    @if (!empty($revenue->add_receipt))
                                        <a href="{{ \App\Models\Utility::get_file('uploads/revenue') . '/' . $revenue->add_receipt }}"
                                            download="">
                                            {{ __('Download') }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h5>{{ __('Attached Invoices') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Owner') }}</th>
                                    <th>{{ __('Issue Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($revenue->invoices as $invoice)
                                    <tr>
                                        <td>
                                            <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                class="btn btn-outline-primary">
                                                <!-- {{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }} -->
                                                {{$invoice->ref_number}}
                                            </a>
                                        </td>
                                        <td>{{ !empty($invoice->customer) ? $invoice->customer->name : '' }}</td>
                                        <td>{{ Auth::user()->dateFormat($invoice->issue_date) }}</td>
                                        <td>{{ $invoice->due_date < date('Y-m-d') ? Auth::user()->dateFormat($invoice->due_date) : Auth::user()->dateFormat($invoice->due_date) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h5>{{ __('Bank Account') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Created Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bankAllocations as $bankAllocation)
                                    <tr>
                                        <td>{{ $bankAllocation->bankAccount->bank_name . ' ' . $bankAllocation->bankAccount->holder_name }}</td>
                                        <td>{{ $bankAllocation->amount }}</td>
                                        <td>{{ Auth::user()->dateFormat($bankAllocation->created_at) }}</td>
                                        <!-- <td>
                                            {!! Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['transfer-type.delete', $bankAllocation->id],
                                                'class' => 'delete-form-btn',
                                                'id' => 'delete-form-' . $bankAllocation->id,
                                            ]) !!}
                                            <a href="#" class="btn btn-sm btn-danger bs-pass-para"
                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                data-confirm-yes="document.getElementById('delete-form-{{ $bankAllocation->id }}').submit();">
                                                <i class="ti ti-trash"></i>
                                            </a>
                                            {!! Form::close() !!}
                                        </td> -->
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h5>{{ __('Transfer Methods') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Reference Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transferTypes as $transferType)
                                    <tr>
                                        <td>{{ $transferType->transfer_type }}</td>
                                        <td>{{ $transferType->reference_number }}</td>
                                        <td>{{ Auth::user()->dateFormat($transferType->date) }}</td>
                                        <td>
                                            {!! Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['transfer-type.delete', $transferType->id],
                                                'class' => 'delete-form-btn',
                                                'id' => 'delete-form-' . $transferType->id,
                                            ]) !!}
                                            <a href="#" class="btn btn-sm btn-danger bs-pass-para"
                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                data-confirm-yes="document.getElementById('delete-form-{{ $transferType->id }}').submit();">
                                                <i class="ti ti-trash"></i>
                                            </a>
                                            {!! Form::close() !!}
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
