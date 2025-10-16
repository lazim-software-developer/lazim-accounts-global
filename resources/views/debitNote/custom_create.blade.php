@php
    use App\Models\Tax;

    $vat =
        (int) Tax::where('name', 'VAT')
            ->where('building_id', Auth::user()->currentBuilding())
            ->first()?->rate ?? 5;
@endphp
{{ Form::open(['route' => ['bill.custom.debit.note'], 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            {{ Form::label('vender', __('Vender'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" id="vender" name="vender">
                <option value="">{{ __('Select Vender') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            {{ Form::label('bill', __('Bill'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" id="bill" name="bill">
                <option value="">{{ __('Select Bill First') }}</option>
            </select>
        </div>

        <!-- Invoice Details Section -->
        <div class="col-md-12 mt-3" id="invoice-details-section" style="display: none;">
            <div class="card border">
                <div class="card-header bg-light">
                    <h6 class="mb-0">{{ __('Bill Details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>{{ __('Bill Number') }}:</strong> 
                                <span id="detail-invoice-number">-</span>
                            </p>
                            <p class="mb-2">
                                <strong>{{ __('Bill Date') }}:</strong> 
                                <span id="detail-invoice-date">-</span>
                            </p>
                            <p class="mb-2">
                                <strong>{{ __('Due Date') }}:</strong> 
                                <span id="detail-due-date">-</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>{{ __('Total Amount') }}:</strong> 
                                <span id="detail-total-amount" class="text-primary">-</span>
                            </p>
                            <p class="mb-2">
                                <strong>{{ __('Due Amount') }}:</strong> 
                                <span id="detail-due-amount" class="text-danger">-</span>
                            </p>
                            <p class="mb-2">
                                <strong>{{ __('Product') }}:</strong> 
                                <span id="detail-product">-</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('amount', null, ['class' => 'form-control', 'required' => 'required', 'id' => 'amount']) }}
            </div>
            <small class="text-muted" id="max-amount-hint" style="display: none;">
                {{ __('Maximum') }}: <span id="max-amount-value">0.00</span>
            </small>
        </div>
        
        <div class="form-group col-md-6">
            {{ Form::label('vat_percentage', __('VAT Percentage'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('vat_percentage', $vat, ['class' => 'form-control', 'readonly' => 'readonly', 'id' => 'vat_percentage']) }}
            </div>
        </div>
        
        <div class="form-group col-md-6">
            {{ Form::label('vat_amount', __('VAT Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('vat_amount', null, ['class' => 'form-control', 'required' => 'required', 'readonly' => 'readonly', 'step' => '0.01', 'id' => 'vat_amount']) }}
            </div>
        </div>
        
        <div class="form-group col-md-6">
            {{ Form::label('total_amount', __('Total Amount (Inc. VAT)'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('total_amount', null, ['class' => 'form-control', 'readonly' => 'readonly', 'step' => '0.01', 'id' => 'total_amount']) }}
            </div>
        </div>
        
        
        
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3', 'required' => 'required']) !!}
        </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>

<script>
    // Calculate VAT
    function calculateVAT() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const vatPercentage = parseFloat(document.getElementById('vat_percentage').value) || 0;
        const vatAmount = (amount * vatPercentage / 100).toFixed(2);
        const totalAmount = (parseFloat(amount) + parseFloat(vatAmount)).toFixed(2);
        
        document.getElementById('vat_amount').value = vatAmount;
        document.getElementById('total_amount').value = totalAmount;
    }

    // Customer change event - Load invoices
    document.getElementById('vender').addEventListener('change', function() {
        const customerId = this.value;
        const invoiceSelect = document.getElementById('bill');
        const invoiceDetailsSection = document.getElementById('invoice-details-section');
        
        // Reset invoice dropdown
        invoiceSelect.innerHTML = '<option value="">{{ __("Select Bill") }}</option>';
        invoiceDetailsSection.style.display = 'none';
        
        // Reset amount fields
        document.getElementById('amount').value = '';
        document.getElementById('vat_amount').value = '';
        document.getElementById('total_amount').value = '';
        document.getElementById('max-amount-hint').style.display = 'none';
        
        if (customerId) {
            // Show loading
            invoiceSelect.innerHTML = '<option value="">{{ __("Loading...") }}</option>';
            
            // Fetch invoices for selected customer
            fetch(`/bills-by-vendor/${customerId}`)
                .then(response => response.json())
                .then(data => {
                    invoiceSelect.innerHTML = '<option value="">{{ __("Select Bill") }}</option>';
                    
                    if (Object.keys(data).length > 0) {
                        Object.keys(data).forEach(key => {
                            const option = document.createElement('option');
                            option.value = key;
                            option.textContent = data[key];
                            invoiceSelect.appendChild(option);
                        });
                    } else {
                        invoiceSelect.innerHTML = '<option value="">{{ __("No Bills Found") }}</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching bills:', error);
                    invoiceSelect.innerHTML = '<option value="">{{ __("Error loading bills") }}</option>';
                });
        }
    });

    // Invoice change event - Load invoice details
    document.getElementById('bill').addEventListener('change', function() {
        const invoiceId = this.value;
        const invoiceDetailsSection = document.getElementById('invoice-details-section');
        
        if (invoiceId) {
            // Show loading state
            invoiceDetailsSection.style.display = 'block';
            document.getElementById('detail-invoice-number').textContent = 'Loading...';
            
            // Fetch invoice details
            fetch(`/get-bill-details/${invoiceId}`)
                .then(response => response.json())
                .then(data => {
                    // Update invoice details
                    document.getElementById('detail-invoice-number').textContent = data.bill_number || '-';
                    document.getElementById('detail-invoice-date').textContent = data.bill_date || '-';
                    document.getElementById('detail-due-date').textContent = data.due_date || '-';
                    document.getElementById('detail-total-amount').textContent = data.total_amount || '0.00';
                    document.getElementById('detail-due-amount').textContent = data.due_amount || '0.00';
                    document.getElementById('detail-product').textContent = data.product_name || '-';
                    
                    // Update status badge
                    // const statusBadge = document.getElementById('detail-status');
                    // statusBadge.textContent = data.status || '-';
                    // statusBadge.className = 'badge ' + getStatusClass(data.status);
                    
                    // Set maximum amount for credit note
                    if (data.due_amount) {
                        const maxAmount = data.due_amount;
                        document.getElementById('amount').setAttribute('max', maxAmount);
                        document.getElementById('max-amount-value').textContent = maxAmount;
                        document.getElementById('max-amount-hint').style.display = 'block';
                    }
                    
                    calculateVAT();
                })
                .catch(error => {
                    console.error('Error fetching invoice details:', error);
                    alert('Error loading invoice details');
                    invoiceDetailsSection.style.display = 'none';
                });
        } else {
            invoiceDetailsSection.style.display = 'none';
            document.getElementById('amount').value = '';
            document.getElementById('vat_amount').value = '';
            document.getElementById('total_amount').value = '';
            document.getElementById('max-amount-hint').style.display = 'none';
            document.getElementById('max-amount-value').textContent = '0.00';
        }
    });

    // Helper function to get status badge class
    function getStatusClass(status) {
        const statusClasses = {
            'paid': 'bg-success',
            'unpaid': 'bg-danger',
            'partially_paid': 'bg-warning',
            'draft': 'bg-secondary',
            'sent': 'bg-info'
        };
        
        return statusClasses[status?.toLowerCase()] || 'bg-secondary';
    }

    // Amount input event
    document.getElementById('amount').addEventListener('input', function() {
        const maxAmount = this.getAttribute('max');
        const enteredAmount = parseFloat(this.value);
        if (maxAmount && enteredAmount > maxAmount) {
            this.value = maxAmount;
            alert(`Amount cannot exceed ${maxAmount}`);
        }
        
        calculateVAT();
    });

    // Form validation before submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const amount = document.getElementById('amount').value;
        const maxAmount = document.getElementById('amount').getAttribute('max');
        if (maxAmount > 0 && amount > maxAmount) {
            e.preventDefault();
            alert(`Credit note amount cannot exceed the due amount of ${maxAmount.toFixed(2)}`);
            return false;
        }
    });
</script>

{{ Form::close() }}