@extends('layouts.admin')
@section('page-title')
    {{ __('Aging Report') }}
@endsection
@push('script-page')
    <script>
        $(document).ready(function() {
            $('#report-dataTable').DataTable({
                dom: 'lBfrtip',
                buttons: [{
                        extend: 'excel',
                        title: filename
                    },
                    {
                        extend: 'pdf',
                        title: filename
                    }, {
                        extend: 'csv',
                        title: filename
                    }
                ]
            });
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Report') }}</li>

    <li class="breadcrumb-item">{{ __('Aging Report') }}</li>
@endsection


@section('action-btn')
    <div class="float-end">


    </div>
@endsection


@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Property Number') }}</th>
                                    <th>{{ __('Owner') }}</th>
                                    <th>{{ __('Outstanding Balance') }}</th>
                                    <th>{{ __('Aged Balance 0-90') }}</th>
                                    <th>{{ __('Aged Balance 91-180') }}</th>
                                    <th>{{ __('Aged Balance 180-270') }}</th>
                                    <th>{{ __('Aged Balance 270-360') }}</th>
                                    <th>{{ __('Aged Balance Above 360') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($reportData))
                                    @foreach ($reportData as $report)
                                        <tr>
                                            <td>{{ $report->property_number }}</td>
                                            <td>{{ $report->owner_name }}</td>
                                            <td>{{ $report->outstanding_balance }}</td>
                                            <td>{{ $report->balance_1 }}</td>
                                            <td>{{ $report->balance_2 }}</td>
                                            <td>{{ $report->balance_3 }}</td>
                                            <td>{{ $report->balance_4 }}</td>
                                            <td>{{ $report->over_balance }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
