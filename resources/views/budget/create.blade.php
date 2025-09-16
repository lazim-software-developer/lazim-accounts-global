@extends('layouts.admin')
@section('page-title')
    {{ __('Create Budget Planner') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('budget.index') }}">{{ __('Budget Planner') }}</a></li>
    <li class="breadcrumb-item">{{ __('Budget Create') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script>
        //Income Total
        $(document).on('keyup', '.income_data', function() {
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
            var income = 0;
            for (var i = 0; i < total_inputs.length; i++) {
                var price = $(total_inputs[i]).html();
                income = parseFloat(income) + parseFloat(price);
            }
            el.parent().find('.income').html(income);

        })


        //Expense Total
        $(document).on('keyup', '.expense_data', function() {
            //category wise total
            var el = $(this).parent().parent();
            var inputs = $(el.find('.expense_data'));

            let taxRate = 0;
            let $tdTax = el.find("[data-column-tax]");
            if ($tdTax.length > 0) taxRate = parseFloat($tdTax.find('input:eq(1)').val()) ?? 0;

            var totalExpense = 0;
            // for (var i = 0; i < inputs.length; i++) {
            //     var price = $(inputs[i]).val();
            //     totalexpense = parseFloat(totalexpense) + parseFloat(price);
            // }
            // let deductedTax = deductAndCalculateTax(totalexpense, taxRate);
            // let fixedTotalExpense = deductedTax.deductedAmount.toFixed(2);
            // el.find('.totalExpense').html(fixedTotalExpense);

            // month wise total //
            var month_expense = $(this).data('month');
            // var month_inputs = $(el.parent().find('.' + month_expense + '_expense'));
            // var month_totalexpense = 0;
            // for (var i = 0; i < month_inputs.length; i++) {
            //     var month_price = $(month_inputs[i]).val();
            //     month_totalexpense = parseFloat(month_totalexpense) + parseFloat(month_price);
            // }
            var month_total_expense = month_expense + '_total_expense';
            // el.parent().find('.' + month_total_expense).html(month_totalexpense);

            // finding row inputs
            var rowInputs = $(el.find('input[type=number]'));
            var rowInputTotal = 0;
            for (var i = 0; i < rowInputs.length; i++) {
                var price = $(rowInputs[i]).val();
                rowInputTotal = parseFloat(rowInputTotal) + parseFloat(price);
            }
            let calculatedWithTaxRow = calculateTotalWithTax(rowInputTotal, taxRate);
            let fixedTotalExpenseRow = isNaN(calculatedWithTaxRow) ? 0 : calculatedWithTaxRow.toFixed(2);
            el.find('.totalExpense').html(fixedTotalExpenseRow);

            //find all textboxes //
            var totalInputs = $(el.parent().find('input[type=number]'));
            var inputTotal = 0;
            for (var i = 0; i < totalInputs.length; i++) {
                var price = $(totalInputs[i]).val();
                inputTotal = parseFloat(inputTotal) + parseFloat(price);
            }
            // console.log("total is ", inputTotal);
            let calculatedWithTax = calculateTotalWithTax(inputTotal, taxRate);
            let fixedTotalExpense = isNaN(calculatedWithTax) ? 0 : calculatedWithTax.toFixed(2);
            el.parent().find('.' + month_total_expense).html(inputTotal);
            el.parent().find('.expense').html(fixedTotalExpense);

        })

        //Hide & Show
        $(document).on('change', '.period', function() {
            var period = $(this).val();

            $('.budget_plan').removeClass('d-block');
            $('.budget_plan').addClass('d-none');
            $('#' + period).removeClass('d-none');
            $('#' + period).addClass('d-block');
        });

        document.addEventListener('DOMContentLoaded', function() {
            let rowCounters = {
                monthly: 1,
                quarterly: 1,
                'half-yearly': 1,
                yearly: 1
            };
            let expenseServices = @json($expenseServices);
            // console.log(expenseServices);

            function handleCategoryChange(target) {
                const [period, rowId] = extractPeriodAndId(target.id);
                const selectedCategory = target.value;
                const serviceSelect = document.querySelector(`#service_${period}_${rowId}`);
                const amountInputs = document.querySelectorAll(`#expense_row_${period}_${rowId} .expense_data`);

                if (selectedCategory) {
                    serviceSelect.disabled = false;
                    serviceSelect.innerHTML =
                        `<option value="">-- Select Service --</option>${getServiceOptionsForCategory(selectedCategory)}`;
                } else {
                    serviceSelect.disabled = true;
                    serviceSelect.innerHTML = `<option value="">-- Select Service --</option>`;
                    disableAmountInputs(amountInputs);
                }
            }



            function handleServiceChange(target) {
                const [period, rowId] = extractPeriodAndId(target.id);
                const selectedService = target.value;
                const trId = `expense_row_${period}_${rowId}`;
                const amountInputs = document.querySelectorAll(`#${trId} .expense_data`);
                const selectedCategory = document.querySelector(`#category_${period}_${rowId}`).value;

                if (selectedService) {
                    let tax = getServiceTax(selectedService); // returning tax from json
                    calculateAndDisplayTax(tax, trId);
                    amountInputs.forEach(input => {
                        input.disabled = false;
                        const month = input.getAttribute('data-month');
                        input.setAttribute('name',
                            `expense[${selectedCategory}][${selectedService}][${month}]`);
                    });
                } else {
                    disableAmountInputs(amountInputs);
                }
            }

            function getServiceTax(categoryId) {
                const taxes = expenseServices
                    .filter(service => service.id == categoryId)
                    .map(service => service.taxes);
                if (taxes.length > 0)
                    return taxes[0];
                return null;
            }

            function calculateAndDisplayTax(tax, rowId) {
                let $row = $(document).find(`tr#${rowId}`);
                if ($row.length === 0 || !tax)
                    return;

                const {
                    rate,
                    name,
                    id: taxId
                } = tax;

                let $tdTax = $row.find("[data-column-tax]");
                if ($tdTax.length === 0)
                    return;

                let spanInnerHtml = name + ' ' + '(' + rate + '%)';
                $tdTax.find("span").html(spanInnerHtml);
                $tdTax.find('input:eq(0)').val(taxId);
                $tdTax.find('input:eq(1)').val(rate);
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
                switch (period) {
                    case 'monthly':
                        monthList = @json($monthList);
                        break;
                    case 'quarterly':
                        monthList = @json($quarterly_monthlist);
                        break;
                    case 'half-yearly':
                        monthList = @json($half_yearly_monthlist);
                        break;
                    case 'yearly':
                        monthList = @json($yearly_monthlist);
                        break;
                }
                const result = monthList.map(month => `
                    <td class="month_list_${month}">
                        <input type="number" class="form-control expense_data ${month}_expense" data-month="${month}" name="expense[${period}][Category][Service][${month}]" id="expense_data_${month}_${period}_${rowId}" disabled>
                    </td>
                    
                `).join('');

                // Get the last month from the monthList
                const lastMonth = monthList[monthList.length - 1]; // Get the last month

                // Append the tax section with the last month
                const finalResult = result + `
                <td data-column-tax="true">
                    <div class="input-group colorpickerinput flex-nowrap">
                        <div class="taxes">
                            <span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">No Tax (0%)</span>
                        </div>
                        <input type="hidden"
                            name="expense[Category][Service][${lastMonth}][tax_id]"
                            class="form-control tax" value="0">
                        <input type="hidden"
                            name="expense[Category][Service][${lastMonth}][tax_rate]"
                            class="form-control itemTaxRate" value="">
                      
                    </div>
                </td>
            `;
                return finalResult;
            }

            function extractPeriodAndId(elementId) {
                const parts = elementId.split('_');
                return [parts[1], parts[2]]; // Assuming format like category_monthly_1
            }

            document.addEventListener('change', function(event) {
                const target = event.target;
                if (target.classList.contains('category_select')) {
                    handleCategoryChange(target);
                } else if (target.classList.contains('service_select')) {
                    handleServiceChange(target);
                }
            });

            // Use event delegation for dynamic buttons
            document.addEventListener('click', function(event) {
                const target = event.target;
                if (target.classList.contains('add-new-row') || target.closest('.add-new-row')) {
                    const period = target.closest('.add-new-row').dataset.period;
                    addNewRow(period);
                }
            });
        });

        function calculateTotalWithTax(totalAmount, taxRate) {
            // Calculate the tax amount
            const taxAmount = totalAmount * (taxRate / 100);

            // Add the tax to the total amount
            const totalWithTax = totalAmount + taxAmount;

            // Return the final total including tax
            return totalWithTax;
        }
    </script>
@endpush

@section('content')
    {{ Form::open(['url' => 'budget', 'class' => 'w-100']) }}
    <div class="card bg-none card-box mt-3">
        <div class="card-body">

            <div class="row">

                <div class="form-group col-md-4">
                    {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
                    {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
                </div>

                <div class="form-group col-md-4">
                    {{ Form::label('period', __('Budget Period'), ['class' => 'form-label']) }}
                    {{ Form::select('period', $periods, null, ['class' => 'form-control select period', 'required' => 'required']) }}

                </div>

                <div class="form-group  col-md-4">
                    <div class="btn-box">
                        {{ Form::label('year', __('Year'), ['class' => 'form-label']) }}
                        {{ Form::select('year', $yearList, isset($_GET['year']) ? $_GET['year'] : '', ['class' => 'form-control select']) }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body table-border-style">

                <!---- Start Monthly Budget ------------------------------------------------------------------------>
                <div class="table-responsive budget_plan d-block" id="monthly">
                    <table class="table  mb-0" id="dataTable-manual">
                        <thead>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Service') }}</th>
                                @foreach ($monthList as $month)
                                    <td class="total text-dark">{{ $month }}</td>
                                @endforeach
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Total :') }}</th>
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
                                @foreach ($monthList as $month)
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
                            @foreach ($monthList as $month)
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
                                <th colspan="14" class="text-dark light_blue"><span>{{ __('Expense :') }}</span></th>
                            </tr>

                            <tr class="expense-row" id="expense_row_monthly_1">
                                <td class="category_dropdown" style="display: flex; align-items: center;">
                                    <span class="btn btn-sm add-new-row" data-period="monthly"><i
                                            class="ti ti-plus"></i></span>
                                    <select class="form-control category_select" name="category[]" id="category_monthly_1">
                                        <option value="">-- {{ __('Select Category') }} --</option>
                                        @foreach ($expenseproduct as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="service_dropdown">
                                    <select class="form-control service_select" name="service[]" id="service_monthly_1"
                                        disabled>
                                        <option value="">-- {{ __('Select Service') }} --</option>
                                    </select>
                                </td>
                                @foreach ($monthList as $month)
                                    <td class="month_list_{{ $month }}">
                                        <input type="number" class="form-control expense_data {{ $month }}_expense"
                                            data-month="{{ $month }}"
                                            name="expense[Category][Service][{{ $month }}]"
                                            id="expense_data_{{ $month }}_monthly_1" disabled>
                                    </td>
                                @endforeach
                                <td data-column-tax="true">
                                    <div class="input-group colorpickerinput flex-nowrap">
                                        <div class="taxes">
                                            <span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">No Tax (0%)</span>
                                        </div>
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_id]"
                                            class="form-control tax" value="0">
                                        {{-- <input type="hidden"
                                        name="expense[Category][Service][{{ $month }}][itemTaxPrice]"
                                        class="form-control itemTaxPrice"> --}}
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_rate]"
                                            class="form-control itemTaxRate" value="">
                                    </div>
                                </td>
                                <td class="text-end totalExpense text-dark">0.00</td>
                            </tr>

                            <tr>
                                <td>{{ __('Total :') }}</span></td>
                                <th></th>
                                @foreach ($monthList as $month)
                                    <td>
                                        <span class="{{ $month }}_total_expense text-dark">0.00</span>
                                    </td>
                                @endforeach
                                <td></td>
                                <td>
                                    <span class="expense text-dark">0.00</span>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                    <div class="modal-footer">
                        <input type="button" value="{{ __('Cancel') }}"
                            onclick="location.href = '{{ route('budget.index') }}';" class="btn btn-light">
                        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
                    </div>

                </div>

                <!---- End Monthly Budget ----->


                <!---- Start Quarterly Budget ----------------------------------------------------------------------->
                <div class="table-responsive budget_plan d-none" id="quarterly">
                    <table class="table mb-0" id="dataTable-manual">
                        <thead>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Service') }}</th>
                                @foreach ($quarterly_monthlist as $month)
                                    <td class="total text-dark">{{ $month }}</td>
                                @endforeach
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Total :') }}</th>
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
                                @foreach ($quarterly_monthlist as $month)
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
                            @foreach ($quarterly_monthlist as $month)
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
                                <th colspan="14" class="text-dark light_blue"><span>{{ __('Expense :') }}</span></th>
                            </tr>

                            <tr class="expense-row" id="expense_row_quarterly_1">
                                <td class="category_dropdown" style="display: flex; align-items: center;">
                                    <span class="btn btn-sm add-new-row" data-period="quarterly"><i
                                            class="ti ti-plus"></i></span>
                                    <select class="form-control category_select" name="category[]"
                                        id="category_quarterly_1">
                                        <option value="">-- {{ __('Select Category') }} --</option>
                                        @foreach ($expenseproduct as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="service_dropdown">
                                    <select class="form-control service_select" name="service[]" id="service_quarterly_1"
                                        disabled>
                                        <option value="">-- {{ __('Select Service') }} --</option>
                                    </select>
                                </td>
                                @foreach ($quarterly_monthlist as $month)
                                    <td class="month_list_{{ $month }}">
                                        <input type="number" class="form-control expense_data {{ $month }}_expense"
                                            data-month="{{ $month }}"
                                            name="expense[Category][Service][{{ $month }}]"
                                            id="expense_data_{{ $month }}_quarterly_1" disabled>
                                    </td>
                                @endforeach
                                <td data-column-tax="true">
                                    <div class="input-group colorpickerinput flex-nowrap">
                                        <div class="taxes">
                                            <span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">No Tax (0%)</span>
                                        </div>
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_id]"
                                            class="form-control tax" value="0">
                                        {{-- <input type="hidden"
                                        name="expense[Category][Service][{{ $month }}][itemTaxPrice]"
                                        class="form-control itemTaxPrice"> --}}
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_rate]"
                                            class="form-control itemTaxRate" value="">
                                    </div>
                                </td>
                                <td class="text-end totalExpense text-dark">0.00</td>
                            </tr>

                            <tr>
                                <td class="text-dark">{{ __('Total :') }}</span></td>
                                <td></td>
                                @foreach ($quarterly_monthlist as $month)
                                    <td>
                                        <span class="{{ $month }}_total_expense  text-dark">0.00</span>
                                    </td>
                                @endforeach
                                <td></td>
                                <td class="text-end">
                                    <span class="expense  text-dark">0.00</span>
                                </td>

                            </tr>

                        </tbody>

                    </table>
                    <div class="modal-footer">
                        <input type="button" value="{{ __('Cancel') }}"
                            onclick="location.href = '{{ route('budget.index') }}';" class="btn btn-light">
                        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
                    </div>
                </div>

                <!---- End Quarterly Budget ----->


                <!---Start Half-Yearly Budget --------------------------------------------------------------------->
                <div class="table-responsive budget_plan d-none" id="half-yearly">
                    <table class="table  mb-0" id="dataTable-manual">
                        <thead>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Service') }}</th>
                                @foreach ($half_yearly_monthlist as $month)
                                    <td class="total text-dark">{{ $month }}</td>
                                @endforeach
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Total :') }}</th>
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
                                @foreach ($half_yearly_monthlist as $month)
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
                            @foreach ($half_yearly_monthlist as $month)
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
                                <th colspan="14" class="text-dark light_blue"><span>{{ __('Expense :') }}</span></th>
                            </tr>

                            <tr class="expense-row" id="expense_row_half-yearly_1">
                                <td class="category_dropdown" style="display: flex; align-items: center;">
                                    <span class="btn btn-sm add-new-row" data-period="half-yearly"><i
                                            class="ti ti-plus"></i></span>
                                    <select class="form-control category_select" name="category[]"
                                        id="category_half-yearly_1">
                                        <option value="">-- {{ __('Select Category') }} --</option>
                                        @foreach ($expenseproduct as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="service_dropdown">
                                    <select class="form-control service_select" name="service[]"
                                        id="service_half-yearly_1" disabled>
                                        <option value="">-- {{ __('Select Service') }} --</option>
                                    </select>
                                </td>
                                @foreach ($half_yearly_monthlist as $month)
                                    <td class="month_list_{{ $month }}">
                                        <input type="number"
                                            class="form-control expense_data {{ $month }}_expense"
                                            data-month="{{ $month }}"
                                            name="expense[Category][Service][{{ $month }}]"
                                            id="expense_data_{{ $month }}_half-yearly_1" disabled>
                                    </td>
                                @endforeach
                                <td data-column-tax="true">
                                    <div class="input-group colorpickerinput flex-nowrap">
                                        <div class="taxes">
                                            <span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">No Tax (0%)</span>
                                        </div>
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_id]"
                                            class="form-control tax" value="0">
                                        {{-- <input type="hidden"
                                        name="expense[Category][Service][{{ $month }}][itemTaxPrice]"
                                        class="form-control itemTaxPrice"> --}}
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_rate]"
                                            class="form-control itemTaxRate" value="">
                                    </div>
                                </td>
                                <td class="text-end totalExpense text-dark">0.00</td>
                            </tr>

                            <tr>
                                <td class="text-dark">{{ __('Total :') }}</span></td>
                                <td></td>
                                @foreach ($half_yearly_monthlist as $month)
                                    <td>
                                        <span class="{{ $month }}_total_expense text-dark">0.00</span>
                                    </td>
                                @endforeach
                                <td></td>
                                <td class="text-end">
                                    <span class="expense text-dark">0.00</span>
                                </td>

                            </tr>

                        </tbody>

                    </table>
                    <div class="modal-footer">
                        <input type="button" value="{{ __('Cancel') }}"
                            onclick="location.href = '{{ route('budget.index') }}';" class="btn btn-light">
                        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
                    </div>
                </div>

                <!---End Half-Yearly Budget ----->

                <!---Start Yearly Budget --------------------------------------------------------------------------------->
                <div class="table-responsive budget_plan d-none" id="yearly">
                    <table class="table  mb-0" id="dataTable-manual">
                        <thead>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Service') }}</th>
                                @foreach ($yearly_monthlist as $month)
                                    <td class="total text-dark">{{ $month }}</td>
                                @endforeach
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Total :') }}</th>
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

                                @foreach ($yearly_monthlist as $month)

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
                            @foreach ($yearly_monthlist as $month)
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
                                <th colspan="14" class="text-dark light_blue"><span>{{ __('Expense :') }}</span></th>
                            </tr>

                            {{-- @foreach ($expenseproduct as $productService) --}}
                            <tr class="expense-row" id="expense_row_yearly_1">
                                <td class="category_dropdown" style="display: flex; align-items: center;">
                                    <span class="btn btn-sm add-new-row" data-period="yearly"><i
                                            class="ti ti-plus"></i></span>
                                    <select class="form-control category_select" name="category[]"
                                        id="category_yearly_1">
                                        <option value="">-- {{ __('Select Category') }} --</option>
                                        @foreach ($expenseproduct as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="service_dropdown">
                                    <select class="form-control service_select" name="service[]" id="service_yearly_1"
                                        disabled>
                                        <option value="">-- {{ __('Select Service') }} --</option>
                                    </select>
                                </td>
                                @foreach ($yearly_monthlist as $month)
                                    <td class="month_list_{{ $month }}">
                                        <input type="number"
                                            class="form-control expense_data {{ $month }}_expense"
                                            data-month="{{ $month }}"
                                            name="expense[Category][Service][{{ $month }}]"
                                            id="expense_data_{{ $month }}_yearly_1" disabled>
                                    </td>
                                @endforeach
                                <td data-column-tax="true">
                                    <div class="input-group colorpickerinput flex-nowrap">
                                        <div class="taxes">
                                            <span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">No Tax (0%)</span>
                                        </div>
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_id]"
                                            class="form-control tax" value="0">
                                        {{-- <input type="hidden"
                                        name="expense[Category][Service][{{ $month }}][itemTaxPrice]"
                                        class="form-control itemTaxPrice"> --}}
                                        <input type="hidden"
                                            name="expense[Category][Service][{{ $month }}][tax_rate]"
                                            class="form-control itemTaxRate" value="">
                                    </div>
                                </td>
                                <td class="text-end totalExpense text-dark">0.00</td>
                            </tr>
                            {{-- @endforeach --}}

                            <tr>
                                <td class="text-dark">{{ __('Total :') }}</span></td>
                                <td></td>

                                @foreach ($yearly_monthlist as $month)
                                    <td>
                                        <span class="{{ $month }}_total_expense text-dark">0.00</span>
                                    </td>
                                @endforeach
                                <td></td>
                                <td class="text-end">
                                    <span class="expense text-dark">0.00</span>
                                </td>

                            </tr>

                        </tbody>

                    </table>
                    <div class="modal-footer">
                        <input type="button" value="{{ __('Cancel') }}"
                            onclick="location.href = '{{ route('budget.index') }}';" class="btn btn-light">
                        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
                    </div>
                </div>

                <!---End Yearly Budget ----->



            </div>
        </div>
    </div>
    {{ Form::close() }}
@endsection
