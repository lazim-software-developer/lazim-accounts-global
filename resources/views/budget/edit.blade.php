@extends('layouts.admin')
@section('page-title')
    {{__('Edit Budget Planner')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('budget.index')}}">{{__('Budget Planner')}}</a></li>
    <li class="breadcrumb-item">{{__('Budget Edit')}}</li>
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
            var month_inputs = $(el.parent().find('.' + month_income+'_income'));
            var month_totalincome = 0;
            for (var i = 0; i < month_inputs.length; i++) {
                var month_price = $(month_inputs[i]).val();
                month_totalincome = parseFloat(month_totalincome) + parseFloat(month_price);
            }
            var month_total_income = month_income + '_total_income';
            el.parent().find('.' + month_total_income).html(month_totalincome);

            //all total //
            var total_inputs = $(el.parent().find('.totalIncome'));
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
            var month_inputs = $(el.parent().find('.' + month_expense+'_expense'));
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
        $(document).on('change', '.period', function() {
            var period = $(this).val();

            $('.budget_plan').removeClass('d-block');
            $('.budget_plan').addClass('d-none');
            $('#'+ period).removeClass('d-none');
            $('#'+ period).addClass('d-block');



        });

        // trigger
        $('.period').trigger('change');

        document.addEventListener('DOMContentLoaded', function () {
            let rowCounters = { monthly: 1, quarterly: 1, 'half-yearly': 1, yearly: 1 };
            let budgetPeriod = `{{$budget->period}}`;
            let expenseCount = {{ count($budget['expense_data']) }};

            for (let key in rowCounters) {
                if (key === budgetPeriod) {
                    rowCounters[key] = expenseCount;
                }
            }

            let expenseServices = @json($expenseServices);

            function handleCategoryChange(target) {
                const [period, rowId] = extractPeriodAndId(target.id);
                const selectedCategory = target.value;
                const serviceSelect = document.querySelector(`#service_${period}_${rowId}`);
                const amountInputs = document.querySelectorAll(`#expense_row_${period}_${rowId} .expense_data`);

                if (selectedCategory) {
                    serviceSelect.disabled = false;
                    serviceSelect.innerHTML = `<option value="">-- Select Service --</option>${getServiceOptionsForCategory(selectedCategory)}`;
                } else {
                    serviceSelect.disabled = true;
                    serviceSelect.innerHTML = `<option value="">-- Select Service --</option>`;
                    disableAmountInputs(amountInputs);
                }
            }

            function handleServiceChange(target) {
                const [period, rowId] = extractPeriodAndId(target.id);
                const selectedService = target.value;
                const amountInputs = document.querySelectorAll(`#expense_row_${period}_${rowId} .expense_data`);
                const selectedCategory = document.querySelector(`#category_${period}_${rowId}`).value;

                if (selectedService) {
                    amountInputs.forEach(input => {
                        input.disabled = false;
                        const month = input.getAttribute('data-month');
                        input.setAttribute('name', `expense[${selectedCategory}][${selectedService}][${month}]`);
                    });
                } else {
                    disableAmountInputs(amountInputs);
                }
            }

            function disableAmountInputs(inputs) {
                inputs.forEach(input => {
                    input.disabled = true;
                    input.removeAttribute('name');
                });
            }

            function getServiceOptionsForCategory(categoryId) {
                let services = expenseServices.filter(s => s.category_id == categoryId);
                return services.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
            }

            function addNewRow(period) {
                rowCounters[period]++;
                const newRow = createNewRow(period, rowCounters[period]);

                // Get the last row and insert the new row after it
                const lastRow = document.querySelector(`#expense_row_${period}_${rowCounters[period] - 1}`);
                lastRow.insertAdjacentElement('afterend', newRow);
            }

            function createNewRow(period, rowId) {
                const row = document.createElement('tr');
                row.classList.add('expense-row');
                row.id = `expense_row_${period}_${rowId}`;
                row.innerHTML = `
                    <td class="category_dropdown" style="display: flex; align-items: center;">
                        <span class="btn btn-sm add-new-row" data-period="${period}"><i class="ti ti-plus"></i></span>
                        <select class="form-control category_select" name="category[]" id="category_${period}_${rowId}">
                            <option value="">-- Select Category --</option>
                            @foreach ($expenseproduct as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="service_dropdown">
                        <select class="form-control service_select" name="service[]" id="service_${period}_${rowId}" disabled>
                            <option value="">-- Select Service --</option>
                        </select>
                    </td>
                    ${generateMonthInputs(period, rowId)}
                    <td class="text-end totalExpense text-dark">0.00</td>
                `;
                return row;
            }

            function generateMonthInputs(period, rowId) {
                let monthList = [];
                switch(period) {
                    case 'monthly': monthList = @json($monthList); break;
                    case 'quarterly': monthList = @json($quarterly_monthlist); break;
                    case 'half-yearly': monthList = @json($half_yearly_monthlist); break;
                    case 'yearly': monthList = @json($yearly_monthlist); break;
                }
                return monthList.map(month => `
                    <td class="month_list_${month}">
                        <input type="number" class="form-control expense_data ${month}_expense" data-month="${month}" name="expense[${period}][Category][Service][${month}]" id="expense_data_${month}_${period}_${rowId}" disabled>
                    </td>
                `).join('');
            }

            function extractPeriodAndId(elementId) {
                const parts = elementId.split('_');
                return [parts[1], parts[2]];  // Assuming format like category_monthly_1
            }

            document.addEventListener('change', function (event) {
                const target = event.target;
                if (target.classList.contains('category_select')) {
                    handleCategoryChange(target);
                } else if (target.classList.contains('service_select')) {
                    handleServiceChange(target);
                }
            });

            // Use event delegation for dynamic buttons
            document.addEventListener('click', function (event) {
                const target = event.target;
                if (target.classList.contains('add-new-row') || target.closest('.add-new-row')) {
                    const period = target.closest('.add-new-row').dataset.period;
                    addNewRow(period);
                }
            });
        });


    </script>
@endpush

@section('content')
{{ Form::model($budget, ['route' => ['budget.update', $budget->id], 'method' => 'PUT']) }}
    <div class="card bg-none card-box mt-3">
        <div class="card-body">

            <div class="row">

                <div class="form-group col-md-4">
                    {{ Form::label('name', __('Name'),['class'=>'form-label']) }}
                    {{ Form::text('name',$budget->name, array('class' => 'form-control','required'=>'required')) }}
                </div>

                <div class="form-group col-md-4">
                    {{ Form::label('period', __('Budget Period'),['class'=>'form-label']) }}
                    {{ Form::select('period', $periods,$budget->period, array('class' => 'form-control select period','required'=>'required')) }}

                </div>

                <div class="form-group  col-md-4">
                    <div class="btn-box">
                        {{ Form::label('year', __('Year'),['class'=>'form-label']) }}
                        {{ Form::select('year',$yearList,isset($_GET['year'])?$_GET['year']: $budget->year, array('class' => 'form-control select')) }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body table-border-style">

                <!---- Start Monthly Budget ------------------------------------------------------------------------>
                <div class="table-responsive budget_plan d-block"  id="monthly">
                    <table class="table  mb-0" id="dataTable-manual">
                        <thead>
                        <tr>
                            <th>{{__('Category')}}</th>
                            <th>{{__('Service')}}</th>
                            @foreach($monthList as $month)
                                <td class="total text-dark">{{$month}}</td>
                            @endforeach
                            <th>{{__('Total :')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!------------------   Income Category ----------------------------------->
                        {{-- <tr>
                            <th colspan="14" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td>{{$productService->name}}</td>
                                @foreach($monthList as $month)
                                    <td>
                                        <input type="number" class="form-control pl-1 pr-1 income_data {{$month}}_income" data-month="{{$month}}" name="income[{{$productService->id}}][{{$month}}]" value="0" id="income_data_{{$month}}">
                                    </td>
                                @endforeach
                                <td class="totalIncome text-dark">
                                    0.00
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td class="text-dark">{{__('Total :')}}</td>
                            @foreach($monthList as $month)
                                <td>
                                    <span class="{{$month}}_total_income text-dark">0.00</span>
                                </td>
                            @endforeach
                            <td>
                                <span class="income text-dark">0.00</span>
                            </td>
                        </tr> --}}

                        <!------------------   Expense Category ----------------------------------->

                        <tr>
                            <th colspan="14" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>

                        @php
                            $serviceWithCategoryArray = [];
                            // Iterate through the main array
                            foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                                foreach ($innerArray as $innerKey => $value) {
                                    $serviceWithCategoryArray[$innerKey] = $outerKey;
                                }
                            }

                            $currentIndex = 0;
                            $totalExpenseByMonth = [];
                        @endphp

                        @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                            @php
                                $productService = $expenseproduct->where('id', $categoryId)->first();
                                $service = $expenseServices->where('id', $serviceId)->first();
                                $currentIndex++;
                                $totalExpense = 0;
                            @endphp
                            <tr class="expense-row" id="expense_row_monthly_{{$currentIndex}}">
                                <td class="category_dropdown" style="display: flex; align-items: center;">
                                    <span class="btn btn-sm add-new-row" data-period="monthly"><i class="ti ti-plus"></i></span>
                                    <select class="form-control category_select" name="category[]" id="category_monthly_{{$currentIndex}}">
                                        <option value="">-- {{ __("Select Category") }} --</option>
                                        @foreach ($expenseproduct as $option)
                                            <option value="{{$option->id}}" @if($option->id == $categoryId) selected @endif>{{$option->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="service_dropdown">
                                    <select class="form-control service_select" name="service[]" id="service_monthly_{{$currentIndex}}">
                                        <option value="">-- {{ __("Select Service") }} --</option>
                                        @foreach ($expenseServices as $option)
                                            <option value="{{$option->id}}" @if($option->id == $serviceId) selected @endif>{{$option->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach($monthList as $month)
                                    @php
                                        $totalExpense +=  isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0;
                                        $totalExpenseByMonth[$month] = (isset($totalExpenseByMonth[$month]) ? $totalExpenseByMonth[$month] : 0) + ( isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0);
                                    @endphp
                                    <td class="month_list_{{$month}}">
                                        <input type="number" class="form-control expense_data {{$month}}_expense" data-month="{{$month}}" name="expense[{{$categoryId}}][{{$serviceId}}][{{$month}}]" value="{{  isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0 }}" id="expense_data_{{$month}}_monthly_{{$currentIndex}}">
                                    </td>
                                @endforeach
                                <td class="text-end totalExpense text-dark">
                                    {{$totalExpense}}
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td  class="text-dark">{{__('Total :')}}</span></td>
                            <td></td>
                            @foreach($monthList as $month)
                                <td>
                                    <span class="{{$month}}_total_expense text-dark">{{$totalExpenseByMonth[$month]}}</span>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <span class="expense text-dark">{{ array_sum($totalExpenseByMonth) }}</span>
                            </td>

                        </tr>

                        </tbody>

                    </table>

                    <div class="modal-footer">
                        <input type="button" value="{{__('Cancel')}}" onclick="location.href = '{{route("budget.index")}}';" class="btn btn-light">
                        <input type="submit" value="{{__('Edit')}}" class="btn  btn-primary">
                    </div>

                </div>

                <!---- End Monthly Budget ----->


                <!---- Start Quarterly Budget ----------------------------------------------------------------------->
                <div class="table-responsive budget_plan d-none" id="quarterly">
                    <table class="table mb-0" id="dataTable-manual">
                        <thead>
                        <tr>
                            <th>{{__('Category')}}</th>
                            <th>{{__('Service')}}</th>
                            @foreach($quarterly_monthlist as $month)
                                <td class="total text-dark">{{$month}}</td>
                            @endforeach
                            <th>{{__('Total :')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!------------------   Income Category ----------------------------------->
                        {{-- <tr>
                            <th colspan="37" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td>{{$productService->name}}</td>
                                @foreach($quarterly_monthlist as $month)
                                    <td>
                                        <input type="number" class="form-control income_data {{$month}}_income" data-month="{{$month}}"
                                               name="income[{{$productService->id}}][{{$month}}]" value="0" id="income_data_{{$month}}">
                                    </td>
                                @endforeach
                                <td class="text-end totalIncome  text-dark">
                                    0.00
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td class="text-dark">{{__('Total :')}}</td>
                            @foreach($quarterly_monthlist as $month)
                                <td>
                                    <span class="{{$month}}_total_income  text-dark">0.00</span>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <span class="income  text-dark">0.00</span>
                            </td>
                        </tr> --}}



                        <!------------------   Expense Category ----------------------------------->

                        <tr>
                            <th colspan="14" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>

                        @php
                        $serviceWithCategoryArray = [];
                        // Iterate through the main array
                        foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                            foreach ($innerArray as $innerKey => $value) {
                                $serviceWithCategoryArray[$innerKey] = $outerKey;
                            }
                        }

                        $currentIndex = 0;
                        $totalExpenseByMonth = [];
                    @endphp

                    @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                        @php
                            $productService = $expenseproduct->where('id', $categoryId)->first();
                            $service = $expenseServices->where('id', $serviceId)->first();
                            $currentIndex++;
                            $totalExpense = 0;
                        @endphp
                        <tr class="expense-row" id="expense_row_quarterly_{{$currentIndex}}">
                            <td class="category_dropdown" style="display: flex; align-items: center;">
                                <span class="btn btn-sm add-new-row" data-period="quarterly"><i class="ti ti-plus"></i></span>
                                <select class="form-control category_select" name="category[]" id="category_quarterly_{{$currentIndex}}">
                                    <option value="">-- {{ __("Select Category") }} --</option>
                                    @foreach ($expenseproduct as $option)
                                        <option value="{{$option->id}}" @if($option->id == $categoryId) selected @endif>{{$option->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="service_dropdown">
                                <select class="form-control service_select" name="service[]" id="service_quarterly_{{$currentIndex}}">
                                    <option value="">-- {{ __("Select Service") }} --</option>
                                    @foreach ($expenseServices as $option)
                                        <option value="{{$option->id}}" @if($option->id == $serviceId) selected @endif>{{$option->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            @foreach($quarterly_monthlist as $month)
                                @php
                                    $totalExpense +=  isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0;
                                    $totalExpenseByMonth[$month] = (isset($totalExpenseByMonth[$month]) ? $totalExpenseByMonth[$month] : 0) + ( isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0);
                                @endphp
                                <td class="month_list_{{$month}}">
                                    <input type="number" class="form-control expense_data {{$month}}_expense" data-month="{{$month}}" name="expense[{{$categoryId}}][{{$serviceId}}][{{$month}}]" value="{{  isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0 }}" id="expense_data_{{$month}}_quarterly_{{$currentIndex}}">
                                </td>
                            @endforeach
                            <td class="text-end totalExpense text-dark">
                                {{$totalExpense}}
                            </td>
                        </tr>
                    @endforeach

                    <tr>
                        <td  class="text-dark">{{__('Total :')}}</span></td>
                        <td></td>
                        @foreach($quarterly_monthlist as $month)
                            <td>
                                <span class="{{$month}}_total_expense text-dark">{{$totalExpenseByMonth[$month]}}</span>
                            </td>
                        @endforeach
                        <td class="text-end">
                            <span class="expense text-dark">{{ array_sum($totalExpenseByMonth) }}</span>
                        </td>

                    </tr>

                        </tbody>

                    </table>
                    <div class="modal-footer">
                        <input type="button" value="{{__('Cancel')}}" onclick="location.href = '{{route("budget.index")}}';" class="btn btn-light">
                        <input type="submit" value="{{__('Edit')}}" class="btn  btn-primary">
                    </div>
                </div>

                <!---- End Quarterly Budget ----->


                <!---Start Half-Yearly Budget --------------------------------------------------------------------->
                <div class="table-responsive budget_plan d-none" id="half-yearly">
                    <table class="table  mb-0" id="dataTable-manual">
                        <thead>
                        <tr>
                            <th>{{__('Category')}}</th>
                            <th>{{__('Service')}}</th>
                            @foreach($half_yearly_monthlist as $month)
                                <td class="total text-dark">{{$month}}</td>
                            @endforeach
                            <th>{{__('Total :')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!------------------   Income Category ----------------------------------->
                        {{-- <tr>
                            <th colspan="14" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td>{{$productService->name}}</td>
                                @foreach($half_yearly_monthlist as $month)
                                    <td>
                                        <input type="number" class="form-control income_data {{$month}}_income" data-month="{{$month}}" name="income[{{$productService->id}}][{{$month}}]" value="0" id="income_data_{{$month}}">
                                    </td>
                                @endforeach
                                <td class="text-end totalIncome  text-dark">
                                    0.00
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td class="text-dark">{{__('Total :')}}</td>
                            @foreach($half_yearly_monthlist as $month)
                                <td>
                                    <span class="{{$month}}_total_income  text-dark">0.00</span>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <span class="income text-dark">0.00</span>
                            </td>
                        </tr> --}}

                        <!------------------   Expense Category ----------------------------------->

                        <tr>
                            <th colspan="14" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>

                        @php
                            $serviceWithCategoryArray = [];
                            // Iterate through the main array
                            foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                                foreach ($innerArray as $innerKey => $value) {
                                    $serviceWithCategoryArray[$innerKey] = $outerKey;
                                }
                            }

                            $currentIndex = 0;
                            $totalExpenseByMonth = [];
                        @endphp

                        @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                            @php
                                $productService = $expenseproduct->where('id', $categoryId)->first();
                                $service = $expenseServices->where('id', $serviceId)->first();
                                $currentIndex++;
                                $totalExpense = 0;
                            @endphp
                            <tr class="expense-row" id="expense_row_half-yearly_{{$currentIndex}}">
                                <td class="category_dropdown" style="display: flex; align-items: center;">
                                    <span class="btn btn-sm add-new-row" data-period="half-yearly"><i class="ti ti-plus"></i></span>
                                    <select class="form-control category_select" name="category[]" id="category_half-yearly_{{$currentIndex}}">
                                        <option value="">-- {{ __("Select Category") }} --</option>
                                        @foreach ($expenseproduct as $option)
                                            <option value="{{$option->id}}" @if($option->id == $categoryId) selected @endif>{{$option->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="service_dropdown">
                                    <select class="form-control service_select" name="service[]" id="service_half-yearly_{{$currentIndex}}">
                                        <option value="">-- {{ __("Select Service") }} --</option>
                                        @foreach ($expenseServices as $option)
                                            <option value="{{$option->id}}" @if($option->id == $serviceId) selected @endif>{{$option->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach($half_yearly_monthlist as $month)
                                    @php
                                        $totalExpense +=  isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0;
                                        $totalExpenseByMonth[$month] = (isset($totalExpenseByMonth[$month]) ? $totalExpenseByMonth[$month] : 0) + ( isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0);
                                    @endphp
                                    <td class="month_list_{{$month}}">
                                        <input type="number" class="form-control expense_data {{$month}}_expense" data-month="{{$month}}" name="expense[{{$categoryId}}][{{$serviceId}}][{{$month}}]" value="{{  isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0 }}" id="expense_data_{{$month}}_half-yearly_{{$currentIndex}}">
                                    </td>
                                @endforeach
                                <td class="text-end totalExpense text-dark">
                                    {{$totalExpense}}
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td  class="text-dark">{{__('Total :')}}</span></td>
                            <td></td>
                            @foreach($half_yearly_monthlist as $month)
                                <td>
                                    <span class="{{$month}}_total_expense text-dark">{{$totalExpenseByMonth[$month]}}</span>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <span class="expense text-dark">0.00</span>
                            </td>

                        </tr>

                        </tbody>

                    </table>
                    <div class="modal-footer">
                        <input type="button" value="{{__('Cancel')}}" onclick="location.href = '{{route("budget.index")}}';" class="btn btn-light">
                        <input type="submit" value="{{__('Edit')}}" class="btn  btn-primary">
                    </div>
                </div>

                <!---End Half-Yearly Budget ----->

                <!---Start Yearly Budget --------------------------------------------------------------------------------->
                <div class="table-responsive budget_plan d-none" id="yearly">
                    <table class="table  mb-0" id="dataTable-manual">
                        <thead>
                        <tr>
                            <th>{{__('Category')}}</th>
                            <th>{{__('Service')}}</th>
                            @foreach($yearly_monthlist as $month)
                                <td class="total text-dark">{{$month}}</td>
                            @endforeach
                            <th>{{__('Total :')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!------------------   Income Category ----------------------------------->
                        {{-- <tr>
                            <th colspan="14" class="text-dark light_blue"><span>{{__('Income :')}}</span></th>
                        </tr>

                        @foreach ($incomeproduct as $productService)
                            <tr>
                                <td>{{$productService->name}}</td>

                                @foreach($yearly_monthlist as $month)

                                    <td>
                                        <input type="number" class="form-control income_data {{$month}}_income" data-month="{{$month}}" name="income[{{$productService->id}}][{{$month}}]" value="{{!empty($budget['income_data'][$productService->id][$month])?$budget['income_data'][$productService->id][$month]:0}}" id="income_data_{{$month}}">
                                    </td>
                                @endforeach
                                <td class="text-end totalIncome text-dark">
                                    0.00
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td class="text-dark">{{__('Total :')}}</td>
                            @foreach($yearly_monthlist as $month)
                                <td>
                                    <span class="{{$month}}_total_income text-dark">0.00</span>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <span class="income text-dark">0.00</span>
                            </td>
                        </tr> --}}

                        <!------------------   Expense Category ----------------------------------->

                        <tr>
                            <th colspan="14" class="text-dark light_blue"><span>{{__('Expense :')}}</span></th>
                        </tr>
                        @php
                            $serviceWithCategoryArray = [];
                            // Iterate through the main array
                            foreach ($budget['expense_data'] as $outerKey => $innerArray) {
                                foreach ($innerArray as $innerKey => $value) {
                                    $serviceWithCategoryArray[$innerKey] = $outerKey;
                                }
                            }

                            $currentIndex = 0;
                            $totalExpenseByMonth = [];
                        @endphp

                        @foreach ($serviceWithCategoryArray as $serviceId => $categoryId)
                            @php
                                $productService = $expenseproduct->where('id', $categoryId)->first();
                                $service = $expenseServices->where('id', $serviceId)->first();
                                $currentIndex++;
                                $totalExpense = 0;
                            @endphp
                            <tr class="expense-row" id="expense_row_yearly_{{$currentIndex}}">
                                <td class="category_dropdown" style="display: flex; align-items: center;">
                                    <span class="btn btn-sm add-new-row" data-period="yearly"><i class="ti ti-plus"></i></span>
                                    <select class="form-control category_select" name="category[]" id="category_yearly_{{$currentIndex}}">
                                        <option value="">-- {{ __("Select Category") }} --</option>
                                        @foreach ($expenseproduct as $option)
                                            <option value="{{$option->id}}" @if($option->id == $categoryId) selected @endif>{{$option->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="service_dropdown">
                                    <select class="form-control service_select" name="service[]" id="service_yearly_{{$currentIndex}}">
                                        <option value="">-- {{ __("Select Service") }} --</option>
                                        @foreach ($expenseServices as $option)
                                            <option value="{{$option->id}}" @if($option->id == $serviceId) selected @endif>{{$option->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach($yearly_monthlist as $month)
                                    @php
                                        $totalExpense += isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0;
                                        $totalExpenseByMonth[$month] = (isset($totalExpenseByMonth[$month]) ? $totalExpenseByMonth[$month] : 0) + (isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0);
                                    @endphp
                                    <td class="month_list_{{$month}}">
                                        <input type="number" class="form-control expense_data {{$month}}_expense" data-month="{{$month}}" name="expense[{{$categoryId}}][{{$serviceId}}][{{$month}}]" value="{{ isset($budget['expense_data'][$categoryId][$serviceId][$month]) ? $budget['expense_data'][$categoryId][$serviceId][$month] : 0 }}" id="expense_data_{{$month}}_yearly_{{$currentIndex}}">
                                    </td>
                                @endforeach
                                <td class="text-end totalExpense text-dark">
                                    {{$totalExpense}}
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td  class="text-dark">{{__('Total :')}}</span></td>
                            <td></td>
                            @foreach($yearly_monthlist as $month)
                                <td>
                                    <span class="{{$month}}_total_expense text-dark">
                                    {{$totalExpenseByMonth[$month]}}
                                    </span>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <span class="expense text-dark">0.00</span>
                            </td>

                        </tr>

                        </tbody>

                    </table>
                    <div class="modal-footer">
                        <input type="button" value="{{__('Cancel')}}" onclick="location.href = '{{route("budget.index")}}';" class="btn btn-light">
                        <input type="submit" value="{{__('Update')}}" class="btn  btn-primary">
                    </div>
                </div>

                <!---End Yearly Budget ----->



            </div>
        </div>
    </div>
    {{ Form::close() }}

@endsection
