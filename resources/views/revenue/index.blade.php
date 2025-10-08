@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Revenues') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Revenue') }}</li>
@endsection

@php
    $date = isset($_GET['date']) ? $_GET['date'] : 0;
    $CurrentBuilding = \Auth::user()->currentBuilding();
@endphp

@section('action-btn')
    <div class="float-end">
        <!-- <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{ __('Filter') }}">
                                                                                                            <i class="ti ti-filter"></i>
                                                                                                        </a> -->
        @if ($CurrentBuilding)
            {{-- <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Sync Receipts') }}"
                data-url="{{ route('customer.sync.bulkreceipt') }}" data-ajax-popup="true"
                data-title="{{ __('Sync Receipts') }}" class="btn btn-sm btn-primary">
                {{ __('Sync Receipts') }}
            </a> --}}
            <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import Receipts') }}"
                data-url="{{ route('receipt.file.import') }}" data-ajax-popup="true"
                data-title="{{ __('Import Receipt CSV file') }}" class="btn btn-sm btn-primary">
                {{ __('Import Receipts') }}
            </a>
        @endif
        <a href="{{ route('revenue.export', $date) }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>

        @can('create revenue')
            <a href="#" data-url="{{ route('revenue.create') }}" data-size="xl" data-ajax-popup="true"
                data-title="{{ __('Create New Revenue') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan

    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2  multi-collapse" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['revenue.index'], 'method' => 'GET', 'id' => 'revenue_form']) }}

                        <div class="d-flex align-items-center justify-content-end">
                            <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="m-2 btn-box">
                                    {{ Form::text('date', isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'), ['class' => 'month-btn form-control pc-datepicker-1', 'id' => 'pc-daterangepicker-1', 'placeholder' => 'YYYY-MM-DD']) }}
                                </div>
                            </div>

                            <div class="col col-lg-2 col-md-6 col-sm-12 col-12">
                                <div class="m-2 btn-box">
                                    {{ Form::select('account', $account, isset($_GET['account']) ? $_GET['account'] : '', ['class' => 'form-control select']) }}
                                </div>
                            </div>

                            <div class="col col-lg-2 col-md-6 col-sm-12 col-12">
                                <div class="m-2 btn-box">
                                    {{ Form::select('customer', $customer, isset($_GET['customer']) ? $_GET['customer'] : '', ['class' => 'form-control select']) }}
                                </div>
                            </div>
                            <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="m-2 btn-box">
                                    {{ Form::select('category', $category, isset($_GET['category']) ? $_GET['category'] : '', ['class' => 'form-control select']) }}
                                </div>
                            </div>
                            <div class="col-auto float-end ms-2">

                                <a href="#" class="btn btn-sm btn-primary"
                                    onclick="document.getElementById('revenue_form').submit(); return false;"
                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                    data-original-title="{{ __('apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>


                                <a href="{{ route('revenue.index') }}" class="btn btn-sm btn-danger"
                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                    data-original-title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off"></i></span>
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
        <div class="col-md-12">
            <div class="card">
                <div class="mt-2 card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('Date') }}</th>
                                    <th> {{ __('Amount') }}</th>
                                    <th> {{ __('Account') }}</th>
                                    <th> {{ __('Owner') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Reference') }}</th>
                                    <th> {{ __('Description') }}</th>
                                    <th>{{ __('Transfer Receipt') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($revenues as $revenue)
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
                                                    href="{{ $revenuepath . '/' . $revenue->add_receipt }}" download=""
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}">
                                                    <i class="text-white ti ti-download"></i>
                                                </a>

                                                {{-- <a href="{{asset(Storage::url('uploads/revenue')).'/'.$revenue->add_receipt}}"
                                                                            download="" class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                                            data-bs-toggle="tooltip" title="{{__('Download')}}" target="_blank"><span
                                                                                class="btn-inner--icon"><i class="text-white ti ti-download"></i></span></a>
                                                                        --}}
                                                <a href="{{ $revenuepath . '/' . $revenue->add_receipt }}"
                                                    class="mx-3 action-btn bg-secondary ms-2 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Preview') }}"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="text-white ti ti-crosshair"></i></span></a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($revenue->is_attend == 1)
                                                <i class="fas fa-check text-success"> {{ __('Attend') }}</i>
                                            @else
                                                <i class="fas fa-times text-danger"> {{ __('Not Attend') }}</i>
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
                                                                    data-ajax-popup="true" data-size="xl"
                                                                    data-bs-toggle="tooltip"
                                                                    data-title="{{ __('Edit Revenue') }}"
                                                                    title="{{ __('Edit') }}"
                                                                    data-original-title="{{ __('Edit') }}">
                                                                    <i class="text-white ti ti-edit"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @if ($revenue->is_mollak == 0)
                                                        <div class="action-btn bg-secondary ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                data-url="{{ route('revenue.transfer', $revenue->id) }}"
                                                                data-ajax-popup="true" data-size="lg"
                                                                data-bs-toggle="tooltip"
                                                                data-title="{{ __('Add Transfer Method') }}"
                                                                title="{{ __('Transfer') }}"
                                                                data-original-title="{{ __('Transfer') }}">
                                                                <i class="text-white ti ti-credit-card"></i>
                                                            </a>
                                                        </div>
                                                    @endif
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
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $revenue->id }}').submit();">
                                                                    <i class="text-white ti ti-trash"></i>
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
