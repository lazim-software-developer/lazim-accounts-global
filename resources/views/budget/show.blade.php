@extends('layouts.admin')
@section('page-title')
    {{__('Budget Vs Actual: ')}}{{ $budget->name }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('budget.index')}}">{{__('Budget Planner')}}</a></li>
    <li class="breadcrumb-item">{{ $budget->name }}</li>

@endsection
@push('script-page')
    <script src="{{asset('js/jquery-ui.min.js')}}"></script>
    <script>
        //Income Total
        $(document).on('keyup', '.income_data', function () {
            //category wise total
            var el = $(this).parent().parent();
            var inputs = $(el.find('.income_data'));

            var totalincome = 0;
            for (var i = 0; i < inputs.length; i++) {
                var price = $(inputs[i]).val();
                totalincome = parseFloat(totalincome) + parseFloat(price);
            }
            el.find('.totalIncome').html(totalincome);

            // month wise total //
            var month_income = $(this).data('month');
            var month_inputs = $(el.parent().find('.' + month_income + '_income'));
            var month_totalincome = 0;
            for (var i = 0; i < month_inputs.length; i++) {
                var month_price = $(month_inputs[i]).val();
                month_totalincome = parseFloat(month_totalincome) + parseFloat(month_price);
            }
            var month_total_income = month_income + '_total_income';
            el.parent().find('.' + month_total_income).html(month_totalincome);

            //all total //
            var total_inputs = $(el.parent().find('.totalIncome'));
            console.log(total_inputs)
            var income = 0;
            for (var i = 0; i < total_inputs.length; i++) {
                var price = $(total_inputs[i]).html();
                income = parseFloat(income) + parseFloat(price);
            }
            el.parent().find('.income').html(income);

        })


        //Expense Total
        $(document).on('keyup', '.expense_data', function () {
            //category wise total
            var el = $(this).parent().parent();
            var inputs = $(el.find('.expense_data'));

            var totalexpense = 0;
            for (var i = 0; i < inputs.length; i++) {
                var price = $(inputs[i]).val();
                totalexpense = parseFloat(totalexpense) + parseFloat(price);
            }
            el.find('.totalExpense').html(totalexpense);

            // month wise total //
            var month_expense = $(this).data('month');
            var month_inputs = $(el.parent().find('.' + month_expense + '_expense'));
            var month_totalexpense = 0;
            for (var i = 0; i < month_inputs.length; i++) {
                var month_price = $(month_inputs[i]).val();
                month_totalexpense = parseFloat(month_totalexpense) + parseFloat(month_price);
            }
            var month_total_expense = month_expense + '_total_expense';
            el.parent().find('.' + month_total_expense).html(month_totalexpense);

            //all total //
            var total_inputs = $(el.parent().find('.totalExpense'));
            console.log(total_inputs)
            var expense = 0;
            for (var i = 0; i < total_inputs.length; i++) {
                var price = $(total_inputs[i]).html();
                expense = parseFloat(expense) + parseFloat(price);
            }
            el.parent().find('.expense').html(expense);

        })

        //Hide & Show
        $(document).on('change', '.period', function () {
            var period = $(this).val();

            $('.budget_plan').removeClass('d-block');
            $('.budget_plan').addClass('d-none');
            $('#' + period).removeClass('d-none');
            $('#' + period).addClass('d-block');
        });

        // trigger
        $('.period').trigger('change');

    </script>
@endpush


@section('action-btn')
    <!-- <div class="float-end">
         <a href="{{ route('budget.index') }}" class="btn btn-sm btn-primary">{{__('Back')}}</a>
    </div> -->
@endsection

<style type="text/css">
.custom_temp{
            overflow-x: scroll;
            /* width: 1140px !important; */
        }
</style>

@section('content')
    <div class="col-xl-3 col-md-6 col-lg-3">
            <div class="card p-4 mb-4">
                <h6 class="report-text mb-0">{{__('Year :')}} {{ $budget->from }}</h6>
            </div>
        </div>
        <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style custom_temp">


                {{--  Monthly Budget--}}
                @if($budget->period == 'monthly')
                    <table class="table table-bordered table-item data">
                        <thead>
                        <tr>
                            <td rowspan="2"></td>
                            @foreach($monthList as $month)
                                <th colspan="3" scope="colgroup" class="text-center br-1px">{{__($month)}}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($monthList as $month)
                                <th scope="col" class="br-1px">Budget</th>
                                <th scope="col" class="br-1px">Actual</th>
                                <th scope="col" class="br-1px">Surplus Budget</th>
                            @endforeach
                        </tr>
                        </thead>
                        <!----INCOME Category ---------------------->

                        {{-- <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @php
                            $overBudgetTotal=[];
                        @endphp

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td class="text-dark">{{$productService->name}}</td>
                                @foreach($monthList as $month)
                                    @php
                                        $budgetAmount= ($budget['income_data'][$productService->id][$month])?$budget['income_data'][$productService->id][$month]:0;
                                        $actualAmount=$incomeArr[$productService->id][$month];
                                        $surplusBudgetAmount=$actualAmount-$budgetAmount;
                                        $overBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;
                                    @endphp
                                    <td class="income_data {{$month}}_income">{{!empty (\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]))?\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]):0}}</td>
                                    <td>{{\Auth::user()->priceFormat($incomeArr[$productService->id][$month])}}
                                        <p>{{($budget['income_data'][$productService->id][$month] !=0)? (\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month])!=0) ? '('.(\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month]).'%)') :'':''}}</p>
                                    </td>
                                    <td>{{\Auth::user()->priceFormat($surplusBudgetAmount)}}
                                        <p class="{{($budget['income_data'][$productService->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['income_data'][$productService->id][$month] > $surplusBudgetAmount)? 'red-text':''}}" >{{($budget['income_data'][$productService->id][$month] !=0)? (\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$surplusBudgetAmount) !=0) ?'('.(\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$surplusBudgetAmount).'%)') :'':''}}</p>

                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        @php
                            $overBudgetTotalArr = array();
                              foreach($overBudgetTotal as $overBudget)
                              {
                                  foreach($overBudget as $k => $value)
                                  {
                                      $overBudgetTotalArr[$k] = (isset($overBudgetTotalArr[$k]) ? $overBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp
                        <tr class="total">
                            <td class="text-dark"><span></span><strong>{{__('Total :')}}</strong></td>
                            @if(!empty($budgetTotal) )
                                @foreach($monthList as $month)

                                    <td class="text-dark {{$month}}_total_income"><strong>{{\Auth::user()->priceFormat($budgetTotal[$month])}}</strong></td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($incomeTotalArr[$month])}}</strong>
                                        <p>{{($budgetTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month]).'%)') :'':''}}</p>

                                    </td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($overBudgetTotalArr[$month]) }}</strong>
                                        <p class="{{($budgetTotal[$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budgetTotal[$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budgetTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetTotal[$month],$overBudgetTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetTotal[$month],$overBudgetTotalArr[$month]).'%)') :'':''}}</p>

                                    </td>
                                @endforeach
                            @endif

                        </tr> --}}


                        <!------------ EXPENSE Category ---------------------->

                        <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>
                        @php
                            $surplusExpenseBudgetTotal=[];

                            $serviceWithCategoryArray = [];
                            // Iterate through the main array
                            foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                                foreach ($innerArray as $innerKey => $value) {
                                    $serviceWithCategoryArray[$innerKey] = $outerKey;
                                }
                            }
                        @endphp

                        @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                            @php
                                $productService = $expenseproduct->where('id', $categoryId)->first();
                                $service = $expenseServices->where('id', $serviceId)->first();
                            @endphp
                            <tr>
                                <td class="text-dark">{{ $productService->name }}</td>
                                @foreach($monthList as $month)
                                    @php
                                        $budgetAmount= ($budget['expense_data'][$productService->id][$service->id][$month])?$budget['expense_data'][$productService->id][$service->id][$month]:0;
                                        $actualAmount=$expenseArr[$productService->id][$month];
                                        $surplusBudgetAmount=$budgetAmount-$actualAmount;
                                        $surplusExpenseBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;

                                    @endphp
                                    <td class="expense_data {{$month}}_expense">{{!empty($budget['expense_data'][$productService->id][$service->id][$month])?$budget['expense_data'][$productService->id][$service->id][$month]:0}}</td>
                                    <td>{{$expenseArr[$productService->id][$month]}}
                                        <p>{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month])!=0) ?'('.(\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month]).'%)') :'':''}}</p>


                                    </td>
                                    <td>{{$surplusBudgetAmount}}
                                        <p class="{{($budget['expense_data'][$productService->id][$service->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['expense_data'][$productService->id][$service->id][$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount)!=0) ?'('.(\App\Models\Budget::percentage
                                        ($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount).'%)') :'':''}}</p>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        @php
                            $surplusExpenseBudgetTotalArr = array();
                              foreach($surplusExpenseBudgetTotal as $surplusExpenseBudget)
                              {
                                  foreach($surplusExpenseBudget as $k => $value)
                                  {
                                      $surplusExpenseBudgetTotalArr[$k] = (isset($surplusExpenseBudgetTotalArr[$k]) ? $surplusExpenseBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp

                        <tr class="total">
                            <td class="text-dark"><span></span><strong>{{__('Total :')}}</strong></td>
                            @foreach($monthList as $month)
                                @php
                                    $budgetExpenseTotal
                                @endphp
                                <td class="text-dark {{$month}}_total_expense"><strong>{{$budgetExpenseTotal[$month]}}</strong></td>
                                <td class="text-dark"><strong>{{$expenseTotalArr[$month]}}</strong>
                                    <p>{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month]).'%)') :'':''}}</p>

                                </td>
                                <td class="text-dark"><strong>{{$surplusExpenseBudgetTotalArr[$month] }}</strong>
                                    <p class="{{($budgetExpenseTotal[$month] < $surplusExpenseBudgetTotalArr[$month])? 'green-text':''}} {{($budgetExpenseTotal[$month] > $surplusExpenseBudgetTotalArr[$month])? 'red-text':''}}">{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month]).'%)') :'':''}}</p>
                                </td>
                            @endforeach

                        </tr>

                        <td></td>

                        {{-- <tfoot>
                        <tr class="total" style="background:#f8f9fd;">
                            <td class="text-dark"><span></span><strong>{{__('NET PROFIT :')}}</strong></td>
                            @php
                                // NET PROFIT OF OVER BUDGET
                                 $surplusbudgetprofit = [];
                                 $keys   = array_keys($overBudgetTotalArr + $surplusExpenseBudgetTotalArr);
                                 foreach($keys as $v)
                                 {
                                     $surplusbudgetprofit[$v] = (empty($overBudgetTotalArr[$v]) ? 0 : $overBudgetTotalArr[$v]) - (empty($surplusExpenseBudgetTotalArr[$v]) ? 0 : $surplusExpenseBudgetTotalArr[$v]);
                                 }
                                 $data['surplusbudgetprofit']              = $surplusbudgetprofit;
                            @endphp

                            @if(!empty($budgetprofit) )

                                @foreach($monthList as $month)
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($budgetprofit[$month]) }}</strong></td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($actualprofit[$month]) }}</strong>
                                        <p>{{($budgetprofit[$month] !=0)? (\App\Models\Budget::percentage($budgetprofit[$month],$actualprofit[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetprofit[$month],$actualprofit[$month]).'%)') :'':''}}</p>

                                    </td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($surplusbudgetprofit[$month]) }}</strong>
                                        <p class="{{($budgetprofit[$month] < $surplusbudgetprofit[$month])? 'green-text':''}} {{($budgetprofit[$month] < $surplusbudgetprofit[$month])? 'green-text':''}}">{{($budgetprofit[$month] !=0)? (\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month]).'%)') :'':''}}</p>

                                    </td>
                                @endforeach
                            @endif

                        </tr>
                        </tfoot> --}}


                    </table>

                    {{--  Quarterly Budget--}}

                @elseif($budget->period == 'quarterly')
                    <table class="table table-bordered table-item data">
                        <thead>
                        <tr>
                            <td rowspan="2"></td> <!-- merge two rows -->
                            @foreach($quarterly_monthlist as $month)
                                <th colspan="3" scope="colgroup" class="text-center br-1px">{{__($month)}}</th>
                            @endforeach`
                        </tr>
                        <tr>
                            @foreach($quarterly_monthlist as $month)
                                <th scope="col" class="br-1px">Budget</th>
                                <th scope="col" class="br-1px">Actual</th>
                                <th scope="col" class="br-1px">Surplus Budget</th>
                            @endforeach
                        </tr>
                        </thead>

                        <!----INCOME Category ---------------------->

                        {{-- <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @php
                            $overBudgetTotal=[];
                        @endphp

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td class="text-dark">{{$productService->name}}</td>
                                @foreach($quarterly_monthlist as $month)
                                    @php
                                        $budgetAmount= ($budget['income_data'][$productService->id][$month])?$budget['income_data'][$productService->id][$month]:0;
                                        $actualAmount=$incomeArr[$productService->id][$month];
                                        $surplusBudgetAmount=$actualAmount-$budgetAmount;
                                        $overBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;
                                    @endphp

                                    <td class="income_data {{$month}}_income">{{!empty (\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]))?\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]):0}}</td>
                                    <td>{{\Auth::user()->priceFormat($incomeArr[$productService->id][$month])}}
                                        <p>{{($budget['income_data'][$productService->id][$month] !=0)? (\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month])!=0) ? '('.(\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month]).'%)') :'':''}}</p>

                                    </td>
                                    <td>{{\Auth::user()->priceFormat($surplusBudgetAmount)}}
                                        <p class="{{($budget['income_data'][$productService->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['income_data'][$productService->id][$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budget['income_data'][$productService->id][$month] !=0)? '('.(\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$surplusBudgetAmount).'%)') :''}}</p>
                                    </td>

                                @endforeach
                            </tr>
                        @endforeach
                        @php
                            $overBudgetTotalArr = array();
                              foreach($overBudgetTotal as $overBudget)
                              {
                                  foreach($overBudget as $k => $value)
                                  {
                                      $overBudgetTotalArr[$k] = (isset($overBudgetTotalArr[$k]) ? $overBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp
                        <tr class="total">
                            <td class="text-dark"><strong>{{__('Total :')}}</strong></td>
                            @if(!empty($budgetTotal) )

                            @foreach($quarterly_monthlist as $month)

                                <td class="text-dark {{$month}}_total_income"><strong>{{\Auth::user()->priceFormat($budgetTotal[$month])}}</strong></td>
                                <td><strong>{{\Auth::user()->priceFormat($incomeTotalArr[$month])}}</strong>
                                    <p>{{($budgetTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month]) !=0)?'('.(\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month]).'%)') :'':''}}</p>
                                </td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($overBudgetTotalArr[$month]) }}</strong>
                                    <p class="{{($budgetTotal[$month] < $overBudgetTotalArr[$month])? 'green-text':''}} {{($budgetTotal[$month] > $overBudgetTotalArr[$month])? 'red-text':''}}">{{($budgetTotal[$month] !=0)? '('.(\App\Models\Budget::percentage($budgetTotal[$month],$overBudgetTotalArr[$month]).'%)') :''}}</p>
                                </td>
                            @endforeach
                            @endif

                        </tr> --}}


                        <!------------ EXPENSE Category ---------------------->

                        <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>

                        @php
                            $surplusExpenseBudgetTotal=[];

                            $serviceWithCategoryArray = [];
                            // Iterate through the main array
                            foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                                foreach ($innerArray as $innerKey => $value) {
                                    $serviceWithCategoryArray[$innerKey] = $outerKey;
                                }
                            }
                        @endphp

                        @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                            @php
                                $productService = $expenseproduct->where('id', $categoryId)->first();
                                $service = $expenseServices->where('id', $serviceId)->first();
                            @endphp
                            <tr>
                                <td class="text-dark">{{ $productService->name }}</td>
                                @foreach($quarterly_monthlist as $month)
                                    @php
                                        $budgetAmount= ($budget['expense_data'][$productService->id][$service->id][$month])?$budget['expense_data'][$productService->id][$service->id][$month]:0;
                                        $actualAmount=$expenseArr[$productService->id][$month];
                                        $surplusBudgetAmount=$budgetAmount-$actualAmount;
                                        $surplusExpenseBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;
                                        // dump($expenseArr);
                                    @endphp
                                    <td class="expense_data {{$month}}_expense">{{!empty($budget['expense_data'][$productService->id][$service->id][$month])?$budget['expense_data'][$productService->id][$service->id][$month]:0}}</td>
                                    <td>{{$expenseArr[$productService->id][$month]}}
                                        <p>{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month])!=0) ?'('.(\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month]).'%)') :'':''}}</p>


                                    </td>
                                    <td>{{$surplusBudgetAmount}}
                                        <p class="{{($budget['expense_data'][$productService->id][$service->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['expense_data'][$productService->id][$service->id][$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount)!=0) ?'('.(\App\Models\Budget::percentage
                                        ($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount).'%)') :'':''}}</p>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        @php
                            $surplusExpenseBudgetTotalArr = array();
                              foreach($surplusExpenseBudgetTotal as $surplusExpenseBudget)
                              {
                                  foreach($surplusExpenseBudget as $k => $value)
                                  {
                                      $surplusExpenseBudgetTotalArr[$k] = (isset($surplusExpenseBudgetTotalArr[$k]) ? $surplusExpenseBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp

                        <tr class="total">
                            <td class="text-dark"><span></span><strong>{{__('Total :')}}</strong></td>
                            @foreach($quarterly_monthlist as $month)
                                @php
                                    $budgetExpenseTotal
                                @endphp
                                <td class="text-dark {{$month}}_total_expense"><strong>{{$budgetExpenseTotal[$month]}}</strong></td>
                                <td class="text-dark"><strong>{{$expenseTotalArr[$month]}}</strong>
                                    <p>{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month]).'%)') :'':''}}</p>

                                </td>
                                <td class="text-dark"><strong>{{$surplusExpenseBudgetTotalArr[$month] }}</strong>
                                    <p class="{{($budgetExpenseTotal[$month] < $surplusExpenseBudgetTotalArr[$month])? 'green-text':''}} {{($budgetExpenseTotal[$month] > $surplusExpenseBudgetTotalArr[$month])? 'red-text':''}}">{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month]).'%)') :'':''}}</p>
                                </td>
                            @endforeach

                        </tr>

                        <td></td>

                        {{-- <tfoot>
                        <tr class="total" style="background:#f8f9fd;">
                            <td class="text-dark"><span></span><strong>{{__('NET PROFIT :')}}</strong></td>
                            @if(!empty($surplusExpenseBudgetTotalArr) )

                            @php
                                // NET PROFIT OF OVER BUDGET
                                 $surplusbudgetprofit = [];
                                 $keys   = array_keys($overBudgetTotalArr + $surplusExpenseBudgetTotalArr);
                                 foreach($keys as $v)
                                 {
                                     $surplusbudgetprofit[$v] = (empty($overBudgetTotalArr[$v]) ? 0 : $overBudgetTotalArr[$v]) - (empty($surplusExpenseBudgetTotalArr[$v]) ? 0 : $surplusExpenseBudgetTotalArr[$v]);
                                 }
                                 $data['surplusbudgetprofit']              = $surplusbudgetprofit;
                            @endphp
                            @endif

                            @if(!empty($budgetprofit) )
                                @foreach($quarterly_monthlist as $month)
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($budgetprofit[$month])}}</strong></td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($actualprofit[$month]) }}</strong></td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($surplusbudgetprofit[$month]) }}</strong>
                                    <p class="{{($budgetprofit[$month] < $surplusbudgetprofit[$month])? 'green-text':''}} {{($budgetprofit[$month] > $surplusbudgetprofit[$month])? 'red-text':''}}">{{($budgetprofit[$month] !=0)? (\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month]).'%)') :'':''}}</p>
                                </td>
                            @endforeach
                            @endif

                        </tr>
                        </tfoot> --}}


                    </table>

                    {{--  Half -Yearly Budget--}}

                @elseif($budget->period == 'half-yearly')
                    <table class="table table-bordered table-item data">
                        <thead>
                        <tr>
                            <td rowspan="2"></td>
                            @foreach($half_yearly_monthlist as $month)
                                <th colspan="3" scope="colgroup" class="text-center br-1px">{{$month}}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($half_yearly_monthlist as $month)
                                <th scope="col" class="br-1px">Budget</th>
                                <th scope="col" class="br-1px">Actual</th>
                                <th scope="col" class="br-1px">Surplus Budget</th>
                            @endforeach
                        </tr>
                        </thead>

                        <!----INCOME Category ---------------------->

                        {{-- <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @php
                            $overBudgetTotal=[];
                        @endphp

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td class="text-dark">{{$productService->name}}</td>
                                @foreach($half_yearly_monthlist as $month)
                                    @php
                                        // $budgetAmount= ($budget['income_data'][$productService->id][$month])?$budget['income_data'][$productService->id][$month]:0;
                                        $budgetAmount = isset($budget['income_data'][$productService->id][$month]) ? $budget['income_data'][$productService->id][$month] : 0;
                                        $actualAmount=$incomeArr[$productService->id][$month];
                                        $surplusBudgetAmount=$actualAmount-$budgetAmount;
                                        $overBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;

                                    @endphp

                                    <td class="income_data {{$month}}_income">{{!empty (\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]))?\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]):0}}</td>
                                    <td>{{\Auth::user()->priceFormat($incomeArr[$productService->id][$month])}}
                                        <p>{{($budget['income_data'][$productService->id][$month] !=0)? (\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month])!=0) ?'('.(\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month]).'%)') :'':''}}</p>

                                    </td>
                                    <td>{{\Auth::user()->priceFormat($surplusBudgetAmount)}}
                                        <p class="{{($budget['income_data'][$productService->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['income_data'][$productService->id][$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budget['income_data'][$productService->id][$month] !=0)? (\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$surplusBudgetAmount)!=0) ?'('.(\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],
                                        $surplusBudgetAmount).'%)') :'':''}}</p>

                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        @php
                            $overBudgetTotalArr = array();
                              foreach($overBudgetTotal as $overBudget)
                              {
                                  foreach($overBudget as $k => $value)
                                  {
                                      $overBudgetTotalArr[$k] = (isset($overBudgetTotalArr[$k]) ? $overBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp

                        <tr class="total">
                            <td class="text-dark"><span></span><strong>{{__('Total :')}}</strong></td>
                            @if(!empty($budgetTotal) )
                                @foreach($half_yearly_monthlist as $month)

                                    <td class="text-dark {{$month}}_total_income"><strong>{{\Auth::user()->priceFormat($budgetTotal[$month])}}</strong></td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($incomeTotalArr[$month])}}</strong>
                                        <p>{{($budgetTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month]).'%)') :'':''}}</p>
                                    </td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($overBudgetTotalArr[$month]) }}</strong>
                                        <p class="{{($budgetTotal[$month] < $overBudgetTotalArr[$month])? 'green-text':''}} {{($budgetTotal[$month] > $overBudgetTotalArr[$month])? 'red-text':''}}">{{($budgetTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetTotal[$month],$overBudgetTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetTotal[$month],$overBudgetTotalArr[$month]).'%)') :'':''}}</p>

                                    </td>
                                @endforeach
                            @endif

                        </tr> --}}


                        <!------------ EXPENSE Category ---------------------->

                        <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>

                        @php
                            $surplusExpenseBudgetTotal=[];

                            $serviceWithCategoryArray = [];
                            // Iterate through the main array
                            foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                                foreach ($innerArray as $innerKey => $value) {
                                    $serviceWithCategoryArray[$innerKey] = $outerKey;
                                }
                            }
                        @endphp

                        @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                            @php
                                $productService = $expenseproduct->where('id', $categoryId)->first();
                                $service = $expenseServices->where('id', $serviceId)->first();
                            @endphp
                            <tr>
                                <td class="text-dark">{{ $productService->name }}</td>
                                @foreach($half_yearly_monthlist as $month)
                                    @php
                                        $budgetAmount= ($budget['expense_data'][$productService->id][$service->id][$month])?$budget['expense_data'][$productService->id][$service->id][$month]:0;
                                        $actualAmount=$expenseArr[$productService->id][$month];
                                        $surplusBudgetAmount=$budgetAmount-$actualAmount;
                                        $surplusExpenseBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;

                                    @endphp
                                    <td class="expense_data {{$month}}_expense">{{!empty($budget['expense_data'][$productService->id][$service->id][$month])?$budget['expense_data'][$productService->id][$service->id][$month]:0}}</td>
                                    <td>{{$expenseArr[$productService->id][$month]}}
                                        <p>{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month])!=0) ?'('.(\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month]).'%)') :'':''}}</p>


                                    </td>
                                    <td>{{$surplusBudgetAmount}}
                                        <p class="{{($budget['expense_data'][$productService->id][$service->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['expense_data'][$productService->id][$service->id][$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount)!=0) ?'('.(\App\Models\Budget::percentage
                                        ($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount).'%)') :'':''}}</p>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        @php
                            $surplusExpenseBudgetTotalArr = array();
                              foreach($surplusExpenseBudgetTotal as $surplusExpenseBudget)
                              {
                                  foreach($surplusExpenseBudget as $k => $value)
                                  {
                                      $surplusExpenseBudgetTotalArr[$k] = (isset($surplusExpenseBudgetTotalArr[$k]) ? $surplusExpenseBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp

                        <tr class="total">
                            <td class="text-dark"><span></span><strong>{{__('Total :')}}</strong></td>
                            @foreach($half_yearly_monthlist as $month)
                                @php
                                    $budgetExpenseTotal
                                @endphp
                                <td class="text-dark {{$month}}_total_expense"><strong>{{$budgetExpenseTotal[$month]}}</strong></td>
                                <td class="text-dark"><strong>{{$expenseTotalArr[$month]}}</strong>
                                    <p>{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month]).'%)') :'':''}}</p>

                                </td>
                                <td class="text-dark"><strong>{{$surplusExpenseBudgetTotalArr[$month] }}</strong>
                                    <p class="{{($budgetExpenseTotal[$month] < $surplusExpenseBudgetTotalArr[$month])? 'green-text':''}} {{($budgetExpenseTotal[$month] > $surplusExpenseBudgetTotalArr[$month])? 'red-text':''}}">{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month]).'%)') :'':''}}</p>
                                </td>
                            @endforeach

                        </tr>
                        <td></td>
                        {{-- <tfoot>
                        <tr class="total" style="background:#f8f9fd;">
                            <td class="text-dark"><span></span><strong>{{__('NET PROFIT :')}}</strong></td>
                            @php
                                // NET PROFIT OF OVER BUDGET
                                 $surplusbudgetprofit = [];
                                 $keys   = array_keys($overBudgetTotalArr + $surplusExpenseBudgetTotalArr);
                                 foreach($keys as $v)
                                 {
                                     $surplusbudgetprofit[$v] = (empty($overBudgetTotalArr[$v]) ? 0 : $overBudgetTotalArr[$v]) - (empty($surplusExpenseBudgetTotalArr[$v]) ? 0 : $surplusExpenseBudgetTotalArr[$v]);
                                 }
                                 $data['surplusbudgetprofit']              = $surplusbudgetprofit;
                            @endphp
                            @if(!empty($budgetprofit) )
                                @foreach($half_yearly_monthlist as $month)
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($budgetprofit[$month])}}</strong></td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($actualprofit[$month]) }}</strong>
                                        <p>{{($budgetprofit[$month] !=0)? (\App\Models\Budget::percentage($budgetprofit[$month],$actualprofit[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetprofit[$month],$actualprofit[$month]).'%)') :'':''}}</p>
                                    </td>
                                    <td class="text-dark"><strong>{{\Auth::user()->priceFormat($surplusbudgetprofit[$month]) }}</strong>
                                        <p class="{{($budgetprofit[$month] < $surplusbudgetprofit[$month])? 'green-text':''}} {{($budgetprofit[$month] > $surplusbudgetprofit[$month])? 'red-text':''}}">{{($budgetprofit[$month] !=0)? (\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month]).'%)') :'':''}}</p>
                                    </td>
                                @endforeach
                            @endif

                        </tr>
                        </tfoot> --}}
                    </table>

                    {{-- Yearly Budget--}}
                @else
                    <table class="table table-bordered table-item data">
                        <thead>
                        <tr>
                            <td rowspan="2"></td> <!-- merge two rows -->
                            @foreach($yearly_monthlist as $month)
                                <th colspan="3" scope="colgroup" class="text-center br-1px">{{$month}}</th>
                            @endforeach`
                        </tr>
                        <tr>
                            @foreach($yearly_monthlist as $month)
                                <th scope="col" class="br-1px">Budget</th>
                                <th scope="col" class="br-1px">Actual</th>
                                <th scope="col" class="br-1px">Surplus Budget</th>
                            @endforeach
                        </tr>
                        </thead>

                        <!----INCOME Category ---------------------->

                        {{-- <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @php
                            $overBudgetTotal=[];
                        @endphp

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td class="text-dark">{{$productService->name}}</td>
                                @foreach($yearly_monthlist as $month)
                                    @php
                                        $budgetAmount= ($budget['income_data'][$productService->id][$month])?$budget['income_data'][$productService->id][$month]:0;
                                        $actualAmount=$incomeArr[$productService->id][$month];
                                        $surplusBudgetAmount=$actualAmount-$budgetAmount;
                                        $overBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;

                                    @endphp

                                    <td class="income_data {{$month}}_income">{{!empty (\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]))?\Auth::user()->priceFormat($budget['income_data'][$productService->id][$month]):0}}</td>
                                    <td>{{\Auth::user()->priceFormat($incomeArr[$productService->id][$month])}}
                                        <p>{{($budget['income_data'][$productService->id][$month] !=0)? (\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month])!=0) ?'('.(\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$incomeArr[$productService->id][$month]).'%)') :'':''}}</p>

                                    </td>
                                    <td>{{\Auth::user()->priceFormat($surplusBudgetAmount)}}
                                        <p class="{{($budget['income_data'][$productService->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['income_data'][$productService->id][$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budget['income_data'][$productService->id][$month] !=0)? (\App\Models\Budget::percentage($budget['income_data'][$productService->id][$month],$surplusBudgetAmount)!=0) ?'('.(\App\Models\Budget::percentage
                                        ($budget['income_data'][$productService->id][$month],$surplusBudgetAmount).'%)') :'':''}}</p>

                                    </td>

                                @endforeach

                            </tr>
                        @endforeach
                        @php
                            $overBudgetTotalArr = array();
                              foreach($overBudgetTotal as $overBudget)
                              {
                                  foreach($overBudget as $k => $value)
                                  {
                                      $overBudgetTotalArr[$k] = (isset($overBudgetTotalArr[$k]) ? $overBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp


                        <tr class="total text-dark">
                            <td class=""><span></span><strong>{{__('Total :')}}</strong></td>
                            @foreach($yearly_monthlist as $month)
                                @php
                                    @endphp
                                <td class="text-dark {{$month}}_total_income"><strong>{{\Auth::user()->priceFormat($budgetTotal[$month])}}</strong></td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($incomeTotalArr[$month])}}</strong>
                                    <p>{{($budgetTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month])!=0)?'('.(\App\Models\Budget::percentage($budgetTotal[$month],$incomeTotalArr[$month]).'%)') :'':''}}</p>

                                </td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($overBudgetTotalArr[$month]) }}</strong>
                                    <p class="{{($budgetTotal[$month] < $overBudgetTotalArr[$month])? 'green-text':''}} {{($budgetTotal[$month] > $overBudgetTotalArr[$month])? 'red-text':''}}">{{($budgetTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetTotal[$month],$overBudgetTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetTotal[$month],$overBudgetTotalArr[$month]).'%)') :'':''}}</p>

                                </td>
                            @endforeach
                        </tr> --}}


                        <!------------ EXPENSE Category ---------------------->

                        <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>
                        @php
                            $surplusExpenseBudgetTotal=[];

                            $serviceWithCategoryArray = [];
                            // Iterate through the main array
                            foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                                foreach ($innerArray as $innerKey => $value) {
                                    $serviceWithCategoryArray[$innerKey] = $outerKey;
                                }
                            }
                        @endphp

                        @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                            @php
                                $productService = $expenseproduct->where('id', $categoryId)->first();
                                $service = $expenseServices->where('id', $serviceId)->first();
                            @endphp
                            <tr>
                                <td class="text-dark">{{ $productService->name }}</td>
                                @foreach($yearly_monthlist as $month)
                                    @php
                                        $budgetAmount= ($budget['expense_data'][$productService->id][$service->id][$month])?$budget['expense_data'][$productService->id][$service->id][$month]:0;
                                        $actualAmount=$expenseArr[$productService->id][$month];
                                        $surplusBudgetAmount=$budgetAmount-$actualAmount;
                                        $surplusExpenseBudgetTotal[$productService->id][$month]=$surplusBudgetAmount;

                                    @endphp
                                    <td class="expense_data {{$month}}_expense">{{\Auth::user()->priceFormat(!empty($budget['expense_data'][$productService->id][$service->id][$month]))?\Auth::user()->priceFormat($budget['expense_data'][$productService->id][$service->id][$month]):0}}</td>
                                    <td>{{\Auth::user()->priceFormat($expenseArr[$productService->id][$month])}}
                                        <p>{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month])!=0) ?'('.(\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$expenseArr[$productService->id][$month]).'%)') :'':''}}</p>


                                    </td>
                                    <td>{{\Auth::user()->priceFormat($surplusBudgetAmount)}}
                                        <p class="{{($budget['expense_data'][$productService->id][$service->id][$month] < $surplusBudgetAmount)? 'green-text':''}} {{($budget['expense_data'][$productService->id][$service->id][$month] > $surplusBudgetAmount)? 'red-text':''}}">{{($budget['expense_data'][$productService->id][$service->id][$month] !=0)? (\App\Models\Budget::percentage($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount)!=0) ?'('.(\App\Models\Budget::percentage
                                        ($budget['expense_data'][$productService->id][$service->id][$month],$surplusBudgetAmount).'%)') :'':''}}</p>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        @php
                            $surplusExpenseBudgetTotalArr = array();
                              foreach($surplusExpenseBudgetTotal as $surplusExpenseBudget)
                              {
                                  foreach($surplusExpenseBudget as $k => $value)
                                  {
                                      $surplusExpenseBudgetTotalArr[$k] = (isset($surplusExpenseBudgetTotalArr[$k]) ? $surplusExpenseBudgetTotalArr[$k] + $value : $value);
                                  }
                              }
                        @endphp

                        <tr class="total">
                            <td class="text-dark"><span></span><strong>{{__('Total :')}}</strong></td>
                            @foreach($yearly_monthlist as $month)
                                @php
                                    $budgetExpenseTotal
                                @endphp
                                <td class="text-dark {{$month}}_total_expense"><strong>{{\Auth::user()->priceFormat($budgetExpenseTotal[$month])}}</strong></td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($expenseTotalArr[$month])}}</strong>
                                    <p>{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$expenseTotalArr[$month]).'%)') :'':''}}</p>

                                </td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($surplusExpenseBudgetTotalArr[$month]) }}</strong>
                                    <p class="{{($budgetExpenseTotal[$month] < $surplusExpenseBudgetTotalArr[$month])? 'green-text':''}} {{($budgetExpenseTotal[$month] > $surplusExpenseBudgetTotalArr[$month])? 'red-text':''}}">{{($budgetExpenseTotal[$month] !=0)? (\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month])!=0) ? '('.(\App\Models\Budget::percentage($budgetExpenseTotal[$month],$surplusExpenseBudgetTotalArr[$month]).'%)') :'':''}}</p>
                                </td>
                            @endforeach

                        </tr>
                        <td></td>
                        <tfoot>
                        {{-- <tr class="total" style="background:#f8f9fd;">
                            <td class="text-dark"><span></span><strong>{{__('NET PROFIT :')}}</strong></td>
                            @php
                                // NET PROFIT OF OVER BUDGET
                                 $surplusbudgetprofit = [];
                                 $overBudgetTotalArr = [];
                                 $keys   = array_keys($overBudgetTotalArr + $surplusExpenseBudgetTotalArr);
                                 foreach($keys as $v)
                                 {
                                     $surplusbudgetprofit[$v] = (empty($overBudgetTotalArr[$v]) ? 0 : $overBudgetTotalArr[$v]) - (empty($surplusExpenseBudgetTotalArr[$v]) ? 0 : $surplusExpenseBudgetTotalArr[$v]);
                                 }
                                 $data['surplusbudgetprofit']              = $surplusbudgetprofit;
                            @endphp

                            @foreach($yearly_monthlist as $month)
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($budgetprofit[$month])}}</strong></td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($actualprofit[$month]) }}</strong>
                                    <p>{{($budgetprofit[$month] !=0)? (\App\Models\Budget::percentage($budgetprofit[$month],$actualprofit[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetprofit[$month],$actualprofit[$month]).'%)') :'':''}}</p>

                                </td>
                                <td class="text-dark"><strong>{{\Auth::user()->priceFormat($surplusbudgetprofit[$month]) }}</strong>
                                    <p class="{{($budgetprofit[$month] < $surplusbudgetprofit[$month])? 'green-text':''}} {{($budgetprofit[$month] > $surplusbudgetprofit[$month])? 'red-text':''}}">{{($budgetprofit[$month] !=0)? (\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month])!=0) ?'('.(\App\Models\Budget::percentage($budgetprofit[$month],$surplusbudgetprofit[$month]).'%)') :'':''}}</p>
                                </td>
                            @endforeach

                        </tr> --}}
                        </tfoot>

                    </table>

                @endif

            </div>
            </div>
        </div>
    </div>





@endsection
