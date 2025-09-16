@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Create') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bill') }}</a></li>
@endsection

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    
    <script>
        // Initialize repeater for service items
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    $('.select2').select2();
                    // Recalculate totals when new item is added
                    calculateTotals();
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();
                        // Recalculate totals when item is deleted
                        calculateTotals();
                    }
                },
                isFirstItemUndeletable: true
            });
        }

        // Vendor selection handler (existing functionality)
        $(document).on('change', '#vender', function() {
            $('#vender_detail').removeClass('d-none').addClass('d-block');
            $('#vender-box').removeClass('d-block').addClass('d-none');
            
            var id = $(this).val();
            var url = $(this).data('url');
            
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: { 'id': id },
                cache: false,
                success: function(data) {
                    if (data != '') {
                        $('#vender_detail').html(data);
                    } else {
                        $('#vender-box').removeClass('d-none').addClass('d-block');
                        $('#vender_detail').removeClass('d-block').addClass('d-none');
                    }
                }
            });
        });

        // Remove vendor handler
        $(document).on('click', '#remove', function() {
            $('#vender-box').removeClass('d-none').addClass('d-block');
            $('#vender_detail').removeClass('d-block').addClass('d-none');
        });

        // Item selection handler - populate item details
        $(document).on('change', '.item-select', function() {
            var itemId = $(this).val();
            var url = $(this).data('url');
            var $row = $(this).closest('tr');
            
            if (!itemId) return;

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: { 'product_id': itemId },
                cache: false,
                success: function(data) {
                    var item = JSON.parse(data);
                    
                    // Auto-fill item details
                    $row.find('.quantity').val(1);
                    // $row.find('.unit-price').val(item.product.purchase_price);
                    // $row.find('.item-description').val(item.product.description);
                    
                    // Handle taxes
                    var taxes = '';
                    var tax = [];
                    var totalItemTaxRate = 0;
                    
                    if (item.taxes == 0) {
                        taxes += '-';
                    } else {
                        for (var i = 0; i < item.taxes.length; i++) {
                            taxes += '<span class="badge bg-primary mt-1 mr-2">' + 
                                    item.taxes[i].name + ' (' + item.taxes[i].rate + '%)</span>';
                            tax.push(item.taxes[i].id);
                            totalItemTaxRate += parseFloat(item.taxes[i].rate);
                        }
                    }
                    
                    $row.find('.taxes-display').html(taxes);
                    $row.find('.tax-ids').val(tax.join(','));
                    $row.find('.tax-rate').val(totalItemTaxRate);
                    $row.find('.unit-display').html(item.unit || '');
                    $row.find('.discount').val(0);
                    
                    calculateRowTotal($row);
                }
            });
        });

        // Amount input handler - auto-fill price when amount is entered
        $(document).on('keyup change', '.amount-input', function() {
            var $row = $(this).closest('tr');
            var amount = parseFloat($(this).val()) || 0;
            
            // Auto-fill the unit price with the amount value
            $row.find('.unit-price').val(amount.toFixed(2));
            
            calculateRowTotal($row);
        });

        // Quantity, Unit Price, Discount change handlers
        $(document).on('keyup change', '.quantity, .unit-price, .discount', function() {
            var $row = $(this).closest('tr');
            
            // If unit price was manually changed, remove the "set from amount" flag
            if ($(this).hasClass('unit-price')) {
                $(this).removeAttr('data-set-from-amount');
            }
            
            calculateRowTotal($row);
        });

        // Calculate individual row total
        function calculateRowTotal($row) {
            var quantity = parseFloat($row.find('.quantity').val()) || 0;
            var unitPrice = parseFloat($row.find('.unit-price').val()) || 0;
            var discount = parseFloat($row.find('.discount').val()) || 0;
            var taxRate = parseFloat($row.find('.tax-rate').val()) || 0;
            
            // Calculate subtotal (before tax and discount)
            var subtotal = quantity * unitPrice;
            
            // Apply discount
            var afterDiscount = subtotal - discount;
            
            // Calculate tax
            var taxAmount = (afterDiscount * taxRate) / 100;
            
            // Total amount
            var totalAmount = afterDiscount + taxAmount;
            
            // Update row displays
            $row.find('.row-total').text(totalAmount.toFixed(2));
            $row.find('.tax-amount').val(taxAmount.toFixed(2));
            
            // Recalculate overall totals
            calculateTotals();
        }

        // Calculate overall totals
        function calculateTotals() {
            var subtotal = 0;
            var totalDiscount = 0;
            var totalTax = 0;
            var grandTotal = 0;
            
            $('.service-row').each(function() {
                var quantity = parseFloat($(this).find('.quantity').val()) || 0;
                var unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
                var discount = parseFloat($(this).find('.discount').val()) || 0;
                var taxAmount = parseFloat($(this).find('.tax-amount').val()) || 0;
                
                subtotal += (quantity * unitPrice);
                totalDiscount += discount;
                totalTax += taxAmount;
                grandTotal += parseFloat($(this).find('.row-total').text()) || 0;
            });
            
            // Update totals display
            $('.subTotal').text(subtotal.toFixed(2));
            $('.totalDiscount').text(totalDiscount.toFixed(2));
            $('.totalTax').text(totalTax.toFixed(2));
            $('.totalAmount').text(grandTotal.toFixed(2));
        }

        // Initialize vendor if pre-selected
        var vendorId = '{{ $vendorId ?? 0 }}';
        if (vendorId > 0) {
            $('#vender').val(vendorId).change();
        }

        // Initialize SearchBox
        function JsSearchBox() {
            $('.js-searchBox').each(function() {
                // Add search functionality if needed
            });
        }
        
        $(document).ready(function() {
            JsSearchBox();
            calculateTotals(); // Initial calculation
        });
    </script>
@endpush

@section('content')
    <div class="row">
        {{ Form::open(['url' => 'bill', 'class' => 'w-100']) }}
        <div class="col-12">
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            
            <!-- Bill Header Information -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <!-- Vendor Selection -->
                        <div class="col-md-6">
                            <div class="form-group" id="vender-box">
                                {{ Form::label('vender_id', __('Vendor'), ['class' => 'form-label']) }}
                                {{ Form::select('vender_id', $venders, $vendorId ?? null, ['class' => 'form-control select', 'id' => 'vender', 'data-url' => route('bill.vender'), 'required' => 'required']) }}
                            </div>
                            <div id="vender_detail" class="d-none"></div>
                        </div>
                        
                        <!-- Bill Details -->
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('bill_date', __('Bill Date'), ['class' => 'form-label']) }}
                                        {{ Form::date('bill_date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('due_date', __('Due Date'), ['class' => 'form-label']) }}
                                        {{ Form::date('due_date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('bill_number', __('Bill Number'), ['class' => 'form-label']) }}
                                        <input type="text" class="form-control" value="{{ $bill_number ?? '' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('category_id', __('Category'), ['class' => 'form-label']) }}
                                        {{ Form::select('category_id', $category ?? [], null, ['class' => 'form-control select', 'required' => 'required']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('order_number', __('Order Number'), ['class' => 'form-label']) }}
                                        {{ Form::number('order_number', '', ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                
                                @if (!empty($customFields) && !$customFields->isEmpty())
                                    <div class="col-md-6">
                                        <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                            @include('customFields.formBuilder')
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="col-12">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Services & Items') }}</h5>
            <div class="card repeater">
                <div class="item-section py-4">
                    <div class="row justify-content-between align-items-center">
                        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                            <div class="all-button-box">
                                <a href="javascript:void(0)" data-repeater-create="" class="btn btn-primary mr-2">
                                    <i class="ti ti-plus"></i> {{ __('Add Item') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table mb-0" data-repeater-list="services" id="services-table">
                            <thead>
                                <tr>
                                    <th width="15%">{{ __('Account') }}</th>
                                    <th width="12%">{{ __('Amount') }}</th>
                                    <th width="20%">{{ __('Description') }}</th>
                                    <th width="15%">{{ __('Item') }}</th>
                                    <th width="10%">{{ __('Quantity') }}</th>
                                    <th width="12%">{{ __('Price') }}</th>
                                    <th width="10%">{{ __('Discount') }}</th>
                                    <th width="10%">{{ __('Tax') }}
                                        @if($vatCharge)
                                        <br>
                                        <small class="text-danger font-bold">{{ @$vatCharge }}</small>
                                        @endif
                                    </th>
                                    <th width="12%" class="text-end">{{ __('Total') }}</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody data-repeater-item>
                                <tr class="service-row">
                                    <!-- Account Selection -->
                                    <td class="form-group">
                                        <select name="chart_account_id" class="form-control account-select" required>
                                            <option value="">{{ __('Select Account') }}</option>
                                            @if(!empty($chartAccounts))
                                                @foreach ($chartAccounts as $key => $chartAccount)
                                                    <option value="{{ $key }}">{{ $chartAccount }}</option>
                                                    @if(!empty($subAccounts))
                                                        @foreach ($subAccounts as $subAccount)
                                                            @if ($key == $subAccount['account'])
                                                                <option value="{{ $subAccount['id'] }}" class="ms-3">
                                                                    &nbsp;&nbsp;&nbsp;{{ $subAccount['name'] }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>
                                    </td>
                                    
                                    <!-- Amount -->
                                    <td class="form-group">
                                        <div class="input-group">
                                            {{ Form::text('amount', '', ['class' => 'form-control amount-input', 'placeholder' => __('Amount'), 'required' => 'required']) }}
                                            <span class="input-group-text">{{ \Auth::user()->currencySymbol() ?? '$' }}</span>
                                        </div>
                                    </td>
                                    
                                    <!-- Description -->
                                    <td class="form-group">
                                        {{ Form::textarea('description', '', ['class' => 'form-control item-description', 'rows' => '2', 'placeholder' => __('Description')]) }}
                                    </td>
                                    
                                    <!-- Item Selection -->
                                    <td class="form-group">
                                        {{ Form::select('item_id', $product_services ?? [], '', ['class' => 'form-control item-select', 'data-url' => route('bill.product'), 'placeholder' => __('Select Item')]) }}
                                    </td>
                                    
                                    <!-- Quantity -->
                                    <td class="form-group">
                                        <div class="input-group">
                                            {{ Form::number('quantity', '1', ['class' => 'form-control quantity', 'min' => '1', 'step' => '0.01', 'required' => 'required','readonly' => 'readonly']) }}
                                            <span class="unit-display input-group-text"></span>
                                        </div>
                                    </td>
                                    
                                    <!-- Unit Price -->
                                    <td class="form-group">
                                        <div class="input-group">
                                            {{ Form::number('unit_price', '0', ['class' => 'form-control unit-price', 'step' => '0.01', 'placeholder' => __('Price'), 'required' => 'required','readonly' => 'readonly']) }}
                                            <span class="input-group-text">{{ \Auth::user()->currencySymbol() ?? '$' }}</span>
                                        </div>
                                    </td>
                                    
                                    <!-- Discount -->
                                    <td class="form-group">
                                        <div class="input-group">
                                            {{ Form::number('discount', '0', ['class' => 'form-control discount', 'step' => '0.01', 'placeholder' => __('Discount')]) }}
                                            <span class="input-group-text">{{ \Auth::user()->currencySymbol() ?? '$' }}</span>
                                        </div>
                                    </td>
                                    
                                    <!-- Tax -->
                                    <td class="form-group">
                                        <div class="taxes-display">-</div>
                                        {{ Form::hidden('tax_ids', '', ['class' => 'tax-ids']) }}
                                        {{ Form::hidden('tax_amount', '0', ['class' => 'tax-amount']) }}
                                        {{ Form::hidden('tax_rate', '0', ['class' => 'tax-rate']) }}
                                    </td>
                                    
                                    <!-- Total Amount -->
                                    <td class="text-end">
                                        <span class="row-total">0.00</span>
                                    </td>
                                    
                                    <!-- Delete Button -->
                                    <td>
                                        <a href="javascript:void(0)" class="ti ti-trash text-white repeater-action-btn bg-danger ms-2" data-repeater-delete></a>
                                    </td>
                                </tr>
                            </tbody>
                            
                            <!-- Totals Footer -->
                            <tfoot>
                                <tr>
                                    <td colspan="8" class="text-end"><strong>{{ __('Sub Total') }} ({{ \Auth::user()->currencySymbol() ?? '$' }})</strong></td>
                                    <td class="text-end"><strong class="subTotal">0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="text-end"><strong>{{ __('Discount') }} ({{ \Auth::user()->currencySymbol() ?? '$' }})</strong></td>
                                    <td class="text-end"><strong class="totalDiscount">0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="text-end"><strong>{{ __('Tax') }} ({{ \Auth::user()->currencySymbol() ?? '$' }})</strong></td>
                                    <td class="text-end"><strong class="totalTax">0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="text-end"><strong class="text-primary">{{ __('Total Amount') }} ({{ \Auth::user()->currencySymbol() ?? '$' }})</strong></td>
                                    <td class="text-end"><strong class="text-primary totalAmount">0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="modal-footer">
            <input type="button" value="{{ __('Cancel') }}" onclick="location.href = '{{ route('bill.index') }}';" class="btn btn-light mx-3">
            <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
        </div>
        
        {{ Form::close() }}
    </div>
@endsection