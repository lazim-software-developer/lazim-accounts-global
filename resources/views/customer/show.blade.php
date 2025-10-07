@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{ __('Manage Resident-Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">{{ __('Resident') }}</a></li>
    <li class="breadcrumb-item">{{ $customer['name'] }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create invoice')
            <a href="{{ route('invoice.create', $customer->id) }}" class="btn btn-sm btn-primary">
                {{ __('Create Invoice') }}
            </a>
        @endcan
        {{-- @can('create proposal')
            <a href="{{ route('proposal.create', $customer->id) }}" class="btn btn-sm btn-primary">
                {{ __('Create Proposal') }}
            </a>
        @endcan --}}
        <a href="{{ route('customer.statement', $customer['id']) }}" class="btn btn-sm btn-primary">
            {{ __('Statement') }}
        </a>

        @can('edit customer')
            @if (!$customer['created_by_lazim'])
                <a href="#" data-size="xl" data-url="{{ route('customer.edit', $customer['id']) }}"
                    data-ajax-popup="true" title="{{ __('Edit Owner') }}" data-bs-toggle="tooltip"
                    data-original-title="{{ __('Edit') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-edit"></i>
                </a>
            @endif
        @endcan

        <!-- @can('delete customer')
        {!! Form::open([
            'method' => 'DELETE',
            'class' => 'delete-form-btn',
            'route' => ['customer.destroy', $customer['id']],
        ]) !!}

                                                    <a href="#" data-bs-toggle="tooltip" title="{{ __('Delete Customer') }}"
                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="document.getElementById('delete-form-{{ $customer['id'] }}').submit();"
                                                        class="btn btn-sm btn-danger bs-pass-para">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                    {!! Form::close() !!}
    @endcan -->
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-6 col-lg-6 col-xl-6">
            <div class="card customer-detail-box customer_card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Customer Info') }}&nbsp;
                        (<b>{{ __('Unit') }}:&nbsp;{{ $customer['property_number'] }}</b>)</h5>
                    <p class="card-text mb-0">{{ $customer['name'] }}</p>
                    <p class="card-text mb-0">{{ $customer['email'] }}</p>
                    <p class="card-text mb-0">{{ $customer['contact'] }}</p>
                    <p class="card-text mb-0">{{ $customer['tax_number'] }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-6 col-xl-6">
            <div class="card customer-detail-box customer_card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Billing Info') }}</h5>
                    <p class="card-text mb-0">{{ $customer['billing_name'] }}</p>
                    <p class="card-text mb-0">{{ $customer['billing_address'] }}</p>
                    <p class="card-text mb-0">
                        {{ $customer['billing_city'] . ', ' . $customer['billing_state'] . ', ' . $customer['billing_zip'] }}
                    </p>
                    <p class="card-text mb-0">{{ $customer['billing_country'] }}</p>
                    <p class="card-text mb-0">{{ $customer['billing_phone'] }}</p>
                </div>
            </div>
        </div>

        <!-- <div class="col-md-4 col-lg-4 col-xl-4">
                                <div class="card customer-detail-box customer_card">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ __('Shipping Info') }}</h5>
                                        <p class="card-text mb-0">{{ $customer['shipping_name'] }}</p>
                                        <p class="card-text mb-0">{{ $customer['shipping_address'] }}</p>
                                        <p class="card-text mb-0">
                                            {{ $customer['shipping_city'] . ', ' . $customer['shipping_state'] . ', ' . $customer['shipping_zip'] }}
                                        </p>
                                        <p class="card-text mb-0">{{ $customer['shipping_country'] }}</p>
                                        <p class="card-text mb-0">{{ $customer['shipping_phone'] }}</p>
                                    </div>
                                </div>
                            </div> -->
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h4 class="card-title text-primary mb-4">{{ __('Resident Info') }}</h4>
                    <div class="row text-center">
                        @php
                            $totalInvoiceSum = $customer->customerTotalInvoiceSum($customer['id']);
                            $totalInvoiceYearSum = $customer->customerTotalInvoiceYearSum($customer['id']);
                            $totalReceiptYearSum = $customer->customerTotalReceiptAmountYearSum($customer['id']);
                            $totalInvoiceCount = $customer->customerTotalInvoiceCurrentYear($customer['id']);
                            $totalInvoice = $customer->customerTotalInvoice($customer['id']);
                            $averageSale = $totalInvoiceSum != 0 ? $totalInvoiceSum / $totalInvoice : 0;
                        @endphp

                        <!-- Customer Info -->
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="bg-light p-4 rounded">
                                <h6 class="text-uppercase text-muted">{{ __('Customer') }}</h6>
                                <h5 class="font-weight-bold mb-3">{{ $customer['name'] }}</h5>
                                <h6 class="text-uppercase text-muted">{{ __('Total Sum of Invoices') }}</h6>
                                <h4 class="text-dark">{{ $totalInvoiceSum }}</h4>
                            </div>
                        </div>

                        <!-- Total Invoice & Quantity -->
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="bg-light p-4 rounded">
                                <h6 class="text-uppercase text-muted">{{ __('Total Invoice Sum') }}
                                    ({{ $totalInvoiceYearSum['year'] }})</h6>
                                <h4 class="font-weight-bold text-dark">{{ $totalInvoiceYearSum['total'] }}</h4>
                                <h6 class="text-uppercase text-muted mt-3">{{ __('Quantity of Invoices') }}
                                    ({{ $totalInvoiceCount['year'] }})</h6>
                                <h4 class="font-weight-bold text-dark">{{ $totalInvoiceCount['invoices'] }}</h4>
                            </div>
                        </div>

                        <!-- Balance & Receipt -->
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="bg-light p-4 rounded">
                                <h6 class="text-uppercase text-muted">{{ __('Balance') }}</h6>
                                <h4 class="font-weight-bold text-success mb-3">
                                    {{ \Auth::user()->priceFormat($customer['balance'], true) }}</h4>
                                <h6 class="text-uppercase text-muted">{{ __('Total Receipt Amount') }}
                                    ({{ $totalReceiptYearSum['year'] }})</h6>
                                <h4 class="font-weight-bold text-dark">{{ $totalReceiptYearSum['total'] }}</h4>
                            </div>
                        </div>

                        <!-- Overdue & Total Due -->
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="bg-light p-4 rounded">
                                <h6 class="text-uppercase text-muted">{{ __('Overdue') }}</h6>
                                <h4 class="font-weight-bold text-danger mb-3">
                                    {{ $customer->customerOverdue($customer['id']) }}</h4>
                                <h6 class="text-uppercase text-muted">{{ __('Total Due') }}</h6>
                                <h4 class="font-weight-bold text-danger">{{ $customer->customerTotaldue($customer['id']) }}
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h4 class="card-title text-primary mb-4">{{ __('Flat Owner Info') }}</h4>
                    <div class="row text-center">
                        @php
                            $FlatOwner = DB::connection('mysql_lazim')
                                ->table('flat_owner')
                                ->where('flat_id', $customer->flat_id)
                                ->get();
                        @endphp
                        @if ($FlatOwner->count() > 0)
                            @foreach ($FlatOwner as $owner)
                                <!-- Customer Info -->
                                @php
                                    $OwnerDetail = DB::connection('mysql_lazim')
                                        ->table('apartment_owners')
                                        ->where('id', $owner->owner_id)
                                        ->first();
                                @endphp
                                <div class="col-md-3 col-sm-6 mb-4">
                                    <div class="bg-light p-4 rounded">
                                        <h6 class="text-uppercase text-muted">{{ __('Owner') }}</h6>
                                        <h5 class="font-weight-bold mb-3">{{ $OwnerDetail->name }}</h5>
                                        <h6 class="text-uppercase text-muted">{{ __('Email') }}</h6>
                                        <h4 class="text-dark">{{ $OwnerDetail->email }}</h4>
                                        <h6 class="text-uppercase text-muted">{{ __('Phone') }}</h6>
                                        <h4 class="text-dark">{{ $OwnerDetail->mobile }}</h4>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-md-12">
                                <div class="bg-light p-4 rounded">
                                    <h6 class="text-uppercase text-muted">{{ __('Owner') }}</h6>
                                    <h5 class="font-weight-bold mb-3">{{ __('No Owner Found') }}</h5>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--
    <div class="row">
        <div class="col-12">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Proposal') }}</h5>
            <div class="card">
                <div class="card-body table-border-style table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Proposal') }}</th>
                                    <th>{{ __('Issue Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customer->customerProposal($customer->id) as $proposal)
                                    <tr>
                                        <td class="Id">
                                            @if (\Auth::guard('customer')->check())
                                                <a href="{{ route('customer.proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->proposalNumberFormat($proposal->proposal_id) }}
                                                </a>
                                            @else
                                                <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->proposalNumberFormat($proposal->proposal_id) }}
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($proposal->issue_date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($proposal->getTotal()) }}</td>
                                        <td>
                                            @if ($proposal->status == 0)
                                                <span
                                                    class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 1)
                                                <span
                                                    class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 2)
                                                <span
                                                    class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 3)
                                                <span
                                                    class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 4)
                                                <span
                                                    class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                            <td class="Action">
                                                <span>

                                                    @if ($proposal->is_convert == 0)
                                                        @if ($proposal->converted_invoice_id == 0)
                                                            @can('convert retainer proposal')
                                                                <div class="action-btn bg-success ms-2">
                                                                    {!! Form::open([
                                                                        'method' => 'get',
                                                                        'route' => ['proposal.convert', $proposal->id],
                                                                        'id' => 'proposal-form-' . $proposal->id,
                                                                    ]) !!}

                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip"
                                                                        title="{{ __('Convert into Retainer') }}"
                                                                        data-original-title="{{ __('Convert to Retainer') }}"
                                                                        data-original-title="{{ __('Delete') }}"
                                                                        data-confirm="{{ __('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back') }}"
                                                                        data-confirm-yes="document.getElementById('proposal-form-{{ $proposal->id }}').submit();">
                                                                        <i class="ti ti-exchange text-white"></i>
                                                                        {!! Form::close() !!}
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                        @endif
                                                    @else
                                                        @if ($proposal->converted_invoice_id == 0)
                                                            @can('convert retainer proposal')
                                                                <div class="action-btn bg-success ms-2">
                                                                    <a href="{{ route('retainer.show', \Crypt::encrypt($proposal->converted_retainer_id)) }}"
                                                                        class="mx-3 btn btn-sm  align-items-center"
                                                                        data-bs-toggle="tooltip"
                                                                        title="{{ __('Already convert to Retainer') }}"
                                                                        data-original-title="{{ __('Already convert to Invoice') }}"
                                                                        data-original-title="{{ __('Delete') }}">
                                                                        <i class="ti ti-eye text-white"></i>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                        @endif
                                                    @endif
                                                    @if ($proposal->converted_invoice_id == 0)
                                                        @if ($proposal->is_convert == 0)
                                                            @can('convert invoice proposal')
                                                                <div class="action-btn bg-warning ms-2">
                                                                    {!! Form::open([
                                                                        'method' => 'get',
                                                                        'route' => ['proposal.convertinvoice', $proposal->id],
                                                                        'id' => 'proposal-form-' . $proposal->id,
                                                                    ]) !!}

                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip"
                                                                        title="{{ __('Convert into Invoice') }}"
                                                                        data-original-title="{{ __('Convert to Invoice') }}"
                                                                        data-original-title="{{ __('Delete') }}"
                                                                        data-confirm="{{ __('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back') }}"
                                                                        data-confirm-yes="document.getElementById('proposal-form-{{ $proposal->id }}').submit();">
                                                                        <i class="ti ti-exchange text-white"></i>
                                                                        {!! Form::close() !!}
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                        @endif
                                                    @else
                                                        @can('show invoice')
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('invoice.show', \Crypt::encrypt($proposal->converted_invoice_id)) }}"
                                                                    class="mx-3 btn btn-sm  align-items-center"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ __('Already convert to Invoice') }}"
                                                                    data-original-title="{{ __('Already convert to Invoice') }}">
                                                                    <i class="ti ti-eye text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                    @endif

                                                    @can('duplicate proposal')
                                                        <div class="action-btn bg-secondary ms-2">

                                                            {!! Form::open([
                                                                'method' => 'get',
                                                                'route' => ['proposal.duplicate', $proposal->id],
                                                                'id' => 'duplicate-form-' . $proposal->id,
                                                            ]) !!}

                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('Duplicate Proposal') }}"
                                                                data-original-title="{{ __('Duplicate') }}"
                                                                data-confirm="{{ __('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back') }}"
                                                                data-confirm-yes="document.getElementById('duplicate-form-{{ $proposal->id }}').submit();">
                                                                <i class="ti ti-copy text-white text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}


                                                        </div>
                                                    @endcan
                                                    @can('show proposal')
                                                        @if (\Auth::guard('customer')->check())
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('customer.proposal.show', $proposal->id) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('edit proposal')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('proposal.edit', \Crypt::encrypt($proposal->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-edit text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan

                                                    @can('delete proposal')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['proposal.destroy', $proposal->id],
                                                                'id' => 'delete-form-' . $proposal->id,
                                                            ]) !!}

                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="Delete"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="document.getElementById('delete-form-{{ $proposal->id }}').submit();">
                                                                <i class="ti ti-trash text-white text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="row">
        <div class="col-12">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Invoice') }}</h5>
            <div class="float-end">
                @php
                    $CurrentBuilding = \Auth::user()->currentBuilding();
                @endphp
                @if ($CurrentBuilding)
                    @can('create invoice')
                        <a href="{{ route('invoice.create', $customer->id) }}" class="btn btn-sm btn-primary">
                            {{ __('Create Invoice') }}
                        </a>
                    @endcan
                @endif
            </div>
            <div class="card">
                <div class="card-body table-border-style table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Issue Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Amount Due') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('edit invoice') || Gate::check('delete invoice') || Gate::check('show invoice'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customer->customerInvoice($customer->id) as $invoice)
                                    <tr>
                                        <td class="Id">
                                            @if (\Auth::guard('customer')->check())
                                                <a href="{{ route('customer.invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                    class="btn btn-outline-primary">{{ @$invoice->invoice_number }}
                                                </a>
                                            @else
                                                <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                    class="btn btn-outline-primary">{{ @$invoice->invoice_number }}
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->dateFormat($invoice->issue_date) }}</td>
                                        <td>
                                            @if ($invoice->due_date < date('Y-m-d'))
                                                <p class="text-danger">
                                                    {{ \Auth::user()->dateFormat($invoice->due_date) }}</p>
                                            @else
                                                {{ \Auth::user()->dateFormat($invoice->due_date) }}
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat($invoice->getDue()) }}</td>
                                        <td>
                                            @if ($invoice->status == 0)
                                                <span
                                                    class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 1)
                                                <span
                                                    class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 2)
                                                <span
                                                    class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 3)
                                                <span
                                                    class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 4)
                                                <span
                                                    class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit invoice') || Gate::check('delete invoice') || Gate::check('show invoice'))
                                            <td class="Action">
                                                <span>
                                                    @can('duplicate invoice')
                                                        @if ($invoice->is_mollak == 0)
                                                            <div class="action-btn bg-secondary ms-2">

                                                                {!! Form::open([
                                                                    'method' => 'get',
                                                                    'route' => ['invoice.duplicate', $invoice->id],
                                                                    'id' => 'invoice-duplicate-form-' . $invoice->id,
                                                                ]) !!}

                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ __('Duplicate Invoice') }}"
                                                                    data-original-title="{{ __('Duplicate') }}"
                                                                    data-confirm="{{ __('You want to confirm this action. Press Yes to continue or Cancel to go back') }}"
                                                                    data-confirm-yes="document.getElementById('invoice-duplicate-form-{{ $invoice->id }}').submit();">
                                                                    <i class="ti ti-copy text-white text-white"></i>
                                                                </a>
                                                                {!! Form::close() !!}

                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('show invoice')
                                                        @if (\Auth::guard('customer')->check())
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('customer.invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('edit invoice')
                                                        @if ($invoice->is_mollak == 0)
                                                            <div class="action-btn bg-info ms-2">
                                                                <a href="{{ route('invoice.edit', \Crypt::encrypt($invoice->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                    data-original-title="{{ __('Edit') }}">
                                                                    <i class="ti ti-edit text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('delete invoice')
                                                        @if ($invoice->is_mollak == 0)
                                                            <div class="action-btn bg-danger ms-2">
                                                                {!! Form::open([
                                                                    'method' => 'DELETE',
                                                                    'route' => ['invoice.destroy', $invoice->id],
                                                                    'id' => 'delete-form-' . $invoice->id,
                                                                ]) !!}

                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $invoice->id }}').submit();">
                                                                    <i class="ti ti-trash text-white text-white"></i>
                                                                </a>
                                                                {!! Form::close() !!}
                                                            </div>
                                                        @endif
                                                    @endcan
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Receipt') }}</h5>
            <div class="float-end">
                @php
                    $CurrentBuilding = \Auth::user()->currentBuilding();
                @endphp
                @if ($CurrentBuilding)
                    {{-- <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Sync Receipt') }}"
                        data-url="{{ route('customer.sync.receipt', $customer->id) }}" data-ajax-popup="true"
                        data-title="{{ __('Sync Receipt') }}" class="btn btn-sm btn-primary">
                        {{ __('Sync Receipt') }}
                    </a> --}}
                    @can('create revenue')
                        <a href="#" data-url="{{ route('revenue.create') }}" data-size="xl" data-ajax-popup="true"
                            data-title="{{ __('Create New Revenue') }}" class="btn btn-sm btn-primary"
                            data-bs-toggle="tooltip" title="{{ __('Create Receipt') }}">
                            {{-- <i class="ti ti-plus"></i> --}}Create Receipt
                        </a>
                    @endcan
                @endif
            </div>
            <div class="card">
                <div class="card-body table-border-style table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Owner') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Payment Receipt') }}</th>
                                    @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customer->customerReceipt($customer->id) as $revenue)
                                    @php
                                        $revenuepath = \App\Models\Utility::get_file('uploads/revenue');
                                    @endphp
                                    <tr class="font-style">
                                        <td>{{ Auth::user()->dateFormat($revenue->date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($revenue->amount) }}</td>
                                        <td>{{ !empty($revenue->bankAccount) ? $revenue->bankAccount->bank_name . ' ' . $revenue->bankAccount->holder_name : '' }}
                                        </td>
                                        <td>{{ !empty($revenue->customer) ? $revenue->customer->name : '-' }}</td>
                                        <td>{{ !empty($revenue->category) ? $revenue->category->name : '-' }}</td>
                                        <td>{{ !empty($revenue->reference) ? $revenue->reference : '-' }}</td>
                                        <td>{{ !empty($revenue->description) ? $revenue->description : '-' }}</td>
                                        <td>
                                            @if (!empty($revenue->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ $revenuepath . '/' . $revenue->add_receipt }}"
                                                    download="" data-bs-toggle="tooltip"
                                                    title="{{ __('Download') }}">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ $revenuepath . '/' . $revenue->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Preview') }}"
                                                    target="_blank">
                                                    <i class="ti ti-crosshair text-white"></i>
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                            <td class="Action">
                                                <span>
                                                    <div class="action-btn bg-warning ms-2">
                                                        <a href="{{ route('revenue.show', \Crypt::encrypt($revenue->id)) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="Show"
                                                            data-original-title="{{ __('Detail') }}">
                                                            <i class="text-white ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                    @can('edit revenue')
                                                        @if ($revenue->is_mollak == 0)
                                                            <div class="action-btn bg-info ms-2">
                                                                <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                    data-url="{{ route('revenue.edit', $revenue->id) }}"
                                                                    data-ajax-popup="true" data-size="lg"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ __('Edit Revenue') }}">
                                                                    <i class="ti ti-edit text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('delete revenue')
                                                        @if ($revenue->is_mollak == 0)
                                                            <div class="action-btn bg-danger ms-2">
                                                                {!! Form::open([
                                                                    'method' => 'DELETE',
                                                                    'route' => ['revenue.destroy', $revenue->id],
                                                                    'class' => 'delete-form-btn',
                                                                    'id' => 'delete-form-' . $revenue->id,
                                                                ]) !!}
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action cannot be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $revenue->id }}').submit();">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                                {!! Form::close() !!}
                                                            </div>
                                                        @endif
                                                    @endcan
                                                </span>
                                            </td>
                                        @endif
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
