{{ Form::open(['url' => 'bill-payment', 'enctype' => 'multipart/form-data', 'method' => 'POST', 'id' => 'bill-payment-form']) }}

<div class="modal-body">
    <div class="row">
        <!-- Date -->
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>

        <!-- Amount -->
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Payment Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('amount', '', ['class' => 'form-control', 'required' => 'required', 'step' => '1', 'min' => 1]) }}
            </div>
        </div>

        <!-- Payment Account -->
        <div class="form-group col-md-6">
            {{ Form::label('account_id', __('Payment Account'), ['class' => 'form-label']) }}
            {{ Form::select('account_id', $accounts, null, ['class' => 'form-control select', 'required' => 'required']) }}
        </div>

        <!-- Payment Description -->
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', '', ['class' => 'form-control', 'rows' => 3]) }}
        </div>

        <!-- Payment Receipt -->
        <div class="col-md-6">
            {{ Form::label('payment_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" name="payment_receipt" id="files" class="form-control">
                </label>
                <p class="upload_file"></p>
                <img id="image" class="mt-2" style="width:25%;" />
            </div>
        </div>

        <!-- Error Message -->
        <div class="col-md-12">
            <div id="amount-error" class="alert alert-danger d-none"></div>
        </div>

        <!-- Bill Repeater Section -->
        <div id="bill-repeater">
            {{-- @dd($bills) --}}
            <div class="repeater-item row">
                <!-- Bill -->
                <div class="form-group col-md-6">
                    {{ Form::label('bill_id[]', __('Bill'), ['class' => 'form-label']) }}
                    {{ Form::select('bill_id[]', $bills, null, ['class' => 'form-control select bill-select', 'required' => 'required', 'id' => 'bill_id']) }}
                </div>
                <!-- Adjusted Amount -->
                <div class="form-group col-md-6">
                    {{ Form::label('adjusted_amount[]', __('Adjusted Amount'), ['class' => 'form-label']) }}
                    <div class="form-icon-user">
                        {{ Form::number('adjusted_amount[]', '', ['class' => 'form-control adjusted-amount', 'step' => '1', 'required' => 'required', 'min' => 1]) }}
                        <small class="max-amount-label text-muted"></small>
                        <p class="error-msg d-none">The adjusted amount exceeds the maximum allowed value.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group col-md-12">
            <button type="button" id="add-bill-item" class="btn btn-primary">Add Another Bill</button>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    {{-- <input type="submit" value="{{ __('Submit') }}" class="btn btn-primary"> --}}
    <button type="submit" class="btn btn-primary" id="submit-btn">
        <span class="submit-text">{{ __('Submit') }}</span>
        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    </button>

</div>


{{ Form::close() }}

<script>
    document.getElementById('bill-payment-form').addEventListener('submit', function(e) {
        let submitBtn = document.getElementById('submit-btn');
        // Disable the button to prevent multiple clicks
        submitBtn.disabled = true;

        // Hide the text, show the loader
        submitBtn.querySelector('.submit-text').classList.add('d-none');
        submitBtn.querySelector('.spinner-border').classList.remove('d-none');
    });
    // Image preview for uploaded receipt
    document.getElementById('files').onchange = function() {
        var src = URL.createObjectURL(this.files[0]);
        document.getElementById('image').src = src;
    };

    // Add another bill item
    document.getElementById('add-bill-item').onclick = function() {
        var repeaterItem = document.querySelector('.repeater-item');
        var clone = repeaterItem.cloneNode(true);

        clone.querySelectorAll('input, select').forEach(function(input) {
            input.value = '';
        });

        document.getElementById('bill-repeater').appendChild(clone);

        applyBillSelectionLogic();
        validateAmounts();
    };

    // Remove logic for bill item
    document.getElementById('bill-repeater').addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-repeater-item')) {
            event.target.closest('.repeater-item').remove();
            applyBillSelectionLogic();
            validateAmounts();
        }
    });

    // Ensure bills are unique across items
    function applyBillSelectionLogic() {
        let selectedBills = Array.from(document.querySelectorAll('.bill-select'))
            .map(select => select.value)
            .filter(value => value !== '');

        document.querySelectorAll('.bill-select').forEach(select => {
            select.querySelectorAll('option').forEach(option => {
                option.disabled = selectedBills.includes(option.value) && option.value !== select.value;
            });
        });
    }

    // Validate adjusted amounts
    function validateAmounts() {
        let totalAdjustedAmount = Array.from(document.querySelectorAll('.adjusted-amount'))
            .map(input => parseFloat(input.value) || 0)
            .reduce((a, b) => a + b, 0);

        let paymentAmount = parseFloat(document.querySelector('[name="amount"]').value) || 0;

        let invalid = totalAdjustedAmount !== paymentAmount || paymentAmount <= 0 || totalAdjustedAmount <= 0;

        document.querySelector('[type="submit"]').disabled = invalid;
        document.getElementById('add-bill-item').disabled = invalid;

        // Show or hide the error message
        const amountError = document.getElementById('amount-error');
        if (invalid) {
            amountError.textContent =
                'The payment amount must be equal to the sum of the adjusted amounts and greater than 0.';
            amountError.classList.remove('d-none');
        } else {
            amountError.classList.add('d-none');
        }
    }

    // Fetch max amount for selected bill
    document.getElementById('bill-repeater').addEventListener('change', function(event) {
        if (event.target.classList.contains('bill-select')) {
            let billId = event.target.value;

            fetch(`/get-bill-due-amount/${billId}`)
                .then(response => response.json())
                .then(data => {
                    let repeaterItem = event.target.closest('.repeater-item');
                    let adjustedAmountInput = repeaterItem.querySelector('.adjusted-amount');
                    adjustedAmountInput.setAttribute('max', data.due_amount);
                    adjustedAmountInput.placeholder = `Max: ${data.due_amount}`;
                })
                .catch(error => console.error('Error:', error));

            validateAmounts();
        }
    });

    // Initialize validation on page load
    document.addEventListener('DOMContentLoaded', function() {
        validateAmounts();
        applyBillSelectionLogic();
    });

    // Validate adjusted amounts on input change
    document.querySelector('[name="amount"]').addEventListener('input', validateAmounts);
    document.getElementById('bill-repeater').addEventListener('input', function(event) {
        if (event.target.classList.contains('adjusted-amount')) {
            validateAmounts();
        }
    });

    // Handle form submission
    document.getElementById('bill-payment-form').onsubmit = function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        const bills = Array.from(document.querySelectorAll('.repeater-item')).map(item => ({
            bill_id: item.querySelector('.bill-select').value,
            adjusted_amount: item.querySelector('.adjusted-amount').value
        }));

        formData.append('bills', JSON.stringify(bills));

        fetch('/bill-payment', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    // Show the error message above the bills selection
                    const amountError = document.getElementById('amount-error');
                    amountError.textContent = data.errors.amount[0];
                    amountError.classList.remove('d-none');
                }
            })
            .catch(error => console.error('Error:', error));
    };
</script>
