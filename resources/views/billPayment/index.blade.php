@extends('layouts.admin')

@section('page-title')
    {{ __('Bill Payments') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Payment') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create bill')
            <a href="#" data-url="{{ route('BillPayment.create') }}" data-size="lg" data-ajax-popup="true" data-title="{{ __('Add Bill Payment') }}" 
            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Create') }}" >
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@php
    $date = isset($_GET['date']) ? $_GET['date'] : 0;
@endphp

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Bill Payments') }}</h5>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($billpayments as $billpayment)
                                    <tr>
                                        <td>{{ \Auth::user()->dateFormat($billpayment->date) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($billpayment->amount) }}</td>
                                        <td>{{ \Auth::user()->billNumberFormat($billpayment->bill->bill_id) }}</td>
                                        <td>{{ $billpayment->description }}</td>
                                        <td class="Action">
                                            <span>
                                                <div class="action-btn bg-warning ms-2">
                                                    <a href="{{ route('billPayment.show', $billpayment->id) }}" class="mx-3 btn btn-sm align-items-center"
                                                        data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('BillPayment.edit', $billpayment->id) }}"
                                                        data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip"
                                                        data-title="{{ __('Edit Bill Payment') }}" title="{{ __('Edit') }}">
                                                        <i class="ti ti-edit text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-secondary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('billPayment.transfer', $billpayment->id) }}"
                                                        data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip"
                                                        data-title="{{ __('Add Transfer Method') }}" title="{{ __('Transfer') }}">
                                                        <i class="ti ti-credit-card text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['BillPayment.destroy', $billpayment->id],
                                                        'class' => 'delete-form-btn',
                                                        'id' => 'delete-form-' . $billpayment->id,
                                                    ]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="document.getElementById('delete-form-{{ $billpayment->id }}').submit();">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                    {!! Form::close() !!}
                                                </div>
                                            </span>
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
