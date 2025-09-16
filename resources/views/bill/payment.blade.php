{{ Form::open(['route' => ['bill.payment', $bill->id], 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    <div class="header text-center">
        <h2>Bill Payment</h2>
        <p class="text-muted">Process payment for bill {{ Auth::user()->billNumberFormat($bill->bill_id) }}</p>
    </div>
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::date('date', null, ['class' => 'form-control', 'required' => 'required']) }}
                <!-- {{ Form::text('date', null, ['class' => 'form-control pc-datepicker-1', 'required' => 'required']) }} -->

            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::text('amount', $totalDue, ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="account-repeater">
                <div class="account-repeater-item row" data-item-id="1">
                    <div class="form-group col-md-6">
                        {{ Form::label('account_id[]', __('Account'), ['class' => 'form-label']) }}
                        {{ Form::select('bank_details[0][account_id]', $accounts, null, ['class' => 'form-control select account-select', 'required' => 'required', 'id' => 'account_id_1']) }}
                    </div>
                    <div class="form-group col-md-6">
                        {{ Form::label('amount[]', __('Amount'), ['class' => 'form-label']) }}
                        <div class="form-icon-user">
                            {{ Form::number('bank_details[0][amount]', '', ['class' => 'form-control amount-input', 'required' => 'required', 'step' => '0.01', 'id' => 'amount_1']) }}
                        </div>
                    </div>
                    <div class="form-group col-md-12">
                        {{ Form::label('add_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
                        <div class="choose-file form-group">
                            <label for="image" class="form-label">
                                <input type="file" name="bank_details[0][add_receipt]" id="image" class="form-control add_receipt-input"
                                    accept="image/*, .txt, .rar, .zip">
                            </label>
                            <p class="upload_file"></p>
                        </div>
                    </div>
                    <div
                        class="form-group col-md-2 d-flex align-items-end justify-content-center remove-button-container">
                        <button type="button" class="btn btn-danger remove-account-item" style="display: none;"><i
                                class="ti ti-trash"></i></button>
                    </div>
                </div>
            </div>
            <!-- Repeater section starts here -->
            <div class="form-group col-md-12 clearfix">
                <button type="button" class="btn btn-primary" id="add-account-item">{{ __('Add Account') }}</button>
            </div>

        <div class="form-group col-md-6">
            {{ Form::label('reference', __('Reference'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::text('reference', '', ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', '', ['class' => 'form-control', 'rows' => 3]) }}
        </div>

        <!-- <div class="form-group col-md-12">
            {{ Form::label('add_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
            <div class="choose-file form-group">
                <label for="image" class="form-label">
                    <input type="file" name="add_receipt" id="image" class="form-control"
                        accept="image/*, .txt, .rar, .zip">
                </label>
                <p class="upload_file"></p>
            </div>
        </div> -->



    </div>

</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Add Payment') }}" class="btn btn-primary">
</div>
{{ Form::close() }}

<script>
    document.querySelector(".pc-datepicker-1").flatpickr({
        mode: "range"
    });
</script>
<script>
    (function() {
        const form = document.getElementById('revenue-form');
        // Store form state in a closure
        let formState = {
            itemCounter: 1,
            accountCounter: 1
        };

        // Function to reset form state
        function resetFormState() {
            formState.itemCounter = 1;
            formState.accountCounter = 1;
        }

        // Initialize the form when shown
        function initRevenueForm() {

            resetFormState();
            updateItemIds();
            updateAccountIds();
            applyInvoiceSelectionLogic();
            validateAmountAndToggleButtons();
        }

        // Handle file input change
        if (document.getElementById('files')) {
            document.getElementById('files').onchange = function() {
                var src = URL.createObjectURL(this.files[0]);
                document.getElementById('image').src = src;
            }
        }

        // Initialize form on modal show
        function setupModalListeners() {
            const modal = document.querySelector('.modal');
            if (modal && !modal.dataset.hasInitListener) {
                modal.addEventListener('show.bs.modal', initRevenueForm);
                modal.dataset.hasInitListener = 'true';
            }
        }

        // Set up initial listeners
        document.addEventListener('DOMContentLoaded', function() {
            setupModalListeners();
            initRevenueForm();
        });

        // Initialize the revenue form functionality
        function initRevenueForm() {
            console.log('initRevenueForm');

            // Reset the counter when the form is initialized
            window.itemCounter = 1;

            // Ensure initial state is set up correctly
            const submitButton = document.querySelector('[type="submit"]');
            if (submitButton) submitButton.disabled = true;
            updateItemIds();
            applyInvoiceSelectionLogic();
            validateAmountAndToggleButtons();
        }

        function updateItemIds() {
            const items = document.querySelectorAll('.repeater-item');
            items.forEach((item, index) => {
                const newId = index + 1;
                item.setAttribute('data-item-id', newId);

                // Update IDs of child elements
                const customerSelect = item.querySelector('.customer-select');
                const referenceTypeSelect = item.querySelector('.reference-type-select');
                const refDetails = item.querySelector('.ref_details');
                const invoiceSelect = item.querySelector('.invoice-select');
                const adjustedAmount = item.querySelector('.adjusted-amount');
                const removeButton = item.querySelector('.remove-repeater-item');

                if (customerSelect) customerSelect.id = `customer_id_${newId}`;
                if (referenceTypeSelect) referenceTypeSelect.id = `reference_type_${newId}`;
                if (refDetails) refDetails.id = `ref_details_${newId}`;
                if (invoiceSelect) invoiceSelect.id = `invoice_id_${newId}`;
                if (adjustedAmount) adjustedAmount.id = `adjusted_amount_${newId}`;
                // update name tags
                if (customerSelect) customerSelect.name = `customer_details[${newId - 1}][customer_id]`;
                if (referenceTypeSelect) referenceTypeSelect.name =
                    `customer_details[${newId - 1}][reference_type]`;
                if (refDetails) refDetails.name = `customer_details[${newId - 1}][ref_details]`;
                if (invoiceSelect) invoiceSelect.name = `customer_details[${newId - 1}][invoice_id]`;
                if (adjustedAmount) adjustedAmount.name = `customer_details[${newId - 1}][adjusted_amount]`;

                // Show/hide remove button based on number of items
                if (removeButton) {
                    // Only show remove button if there's more than one item
                    removeButton.style.display = items.length > 1 ? 'inline-block' : 'none';
                }
            });

        }

        // Ensure the adjusted amount does not exceed the max value
        // Function to update UI elements for adjusted amount
        function updateAdjustedAmountUI(repeaterItem, maxValue = null) {
            var adjustedAmountInput = repeaterItem.querySelector('.adjusted-amount');
            var maxLabel = repeaterItem.querySelector('.max-amount-label');
            var removeButtonContainer = repeaterItem.querySelector('.remove-button-container');

            if (maxValue) {
                // Set max value and placeholder
                adjustedAmountInput.setAttribute('max', maxValue);
                adjustedAmountInput.setAttribute('placeholder', `Max: ${maxValue}`);
                // Update or create max label
                if (!maxLabel) {
                    maxLabel = document.createElement('small');
                    maxLabel.classList.add('max-amount-label');
                    adjustedAmountInput.parentNode.appendChild(maxLabel);
                }
                maxLabel.textContent = `Max allowed: ${maxValue}`;

                // Update remove button container alignment
                if (removeButtonContainer) {
                    removeButtonContainer.classList.remove('align-items-end');
                    removeButtonContainer.classList.add('align-items-center');
                }
            } else {
                // Clear max value and placeholder
                adjustedAmountInput.removeAttribute('max');
                adjustedAmountInput.setAttribute('placeholder', '');

                // Remove max label if it exists
                if (maxLabel) {
                    maxLabel.remove();
                }

                // Reset remove button container alignment
                if (removeButtonContainer) {
                    removeButtonContainer.classList.remove('align-items-center');
                    removeButtonContainer.classList.add('align-items-end');
                }
            }
        }

        // Function to calculate the total adjusted amount
        function calculateTotalAdjustedAmount() {
            let total = 0;
            document.querySelectorAll('.adjusted-amount').forEach(function(input) {
                let value = parseFloat(input.value);
                if (!isNaN(value)) {
                    total += value;
                }
            });
            const invoiceTotal = document.getElementById('invoice-total-value');
            invoiceTotal.textContent = `${total.toFixed(2)}`;
            return total;
        }

        // Function to calculate the total account amount
        function calculateTotalAccountAmount() {
            let total = 0;
            document.querySelectorAll('[name="amount[]"]').forEach(function(input) {
                let value = parseFloat(input.value);
                if (!isNaN(value)) {
                    total += value;
                }
            });
            const accountTotal = document.getElementById('account-total-value');
            accountTotal.textContent = `${total.toFixed(2)}`;
            return total;
        }

        // Function to validate the receipt amount against the total adjusted amount
        function validateAmountAndToggleButtons() {
            // Calculate total receipt amount from account amounts
            const accountAmounts = Array.from(document.querySelectorAll('[name="amount[]"]'));
            const totalReceiptAmount = accountAmounts.reduce((total, input) => {
                return total + (parseFloat(input.value) || 0);
            }, 0);
            // Calculate total adjusted amount
            const totalAdjustedAmount = calculateTotalAdjustedAmount();
            // Calculate total account amount
            const totalAccountAmount = calculateTotalAccountAmount();
            // Validate amounts
            const isInvalid = totalAdjustedAmount > totalReceiptAmount;
            // Update UI elements
            const submitButton = document.querySelector('[type="submit"]');
            // const addAccountButton = document.getElementById('add-account-item');

            if (submitButton) submitButton.disabled = isInvalid;
            if (addInvoiceButton) addInvoiceButton.disabled = isInvalid;
            // if (addAccountButton) addAccountButton.disabled = isInvalid;

            // Update validation feedback
            const allAmountInputs = [...accountAmounts, ...document.querySelectorAll('.adjusted-amount')];
            allAmountInputs.forEach(input => {
                const feedbackDiv = input.nextElementSibling || document.createElement('div');
                if (!feedbackDiv.classList.contains('invalid-feedback')) {
                    feedbackDiv.classList.add('invalid-feedback');
                    input.parentNode.appendChild(feedbackDiv);
                }
                if (isInvalid) {
                    input.classList.add('is-invalid');
                    feedbackDiv.textContent =
                        `Total Invoice amount (${totalAdjustedAmount}) exceeds total receipt amount (${totalReceiptAmount}) by ${parseFloat(totalAdjustedAmount) - parseFloat(totalReceiptAmount)}`;
                } else {
                    input.classList.remove('is-invalid');
                    feedbackDiv.textContent = '';
                }
            });
        }

        // Add validation before form submission
        document.querySelector('form').onsubmit = function(event) {
            const adjustedAmountInputs = document.querySelectorAll('.adjusted-amount');
            const receiptAmount = Array.from(document.querySelectorAll('[name="amount[]"]')).reduce((total,
                input) => {
                return total + (parseFloat(input.value) || 0);
            }, 0);
            const totalAdjustedAmount = calculateTotalAdjustedAmount();

            if (totalAdjustedAmount > receiptAmount) {
                alert(
                    `The total adjusted amount (${totalAdjustedAmount}) exceeds the receipt amount (${receiptAmount}).`
                );
                event.preventDefault(); // Prevent form submission
            }

            adjustedAmountInputs.forEach(function(input) {
                const maxValue = parseFloat(input.getAttribute('max'));
                const adjustedAmount = parseFloat(input.value);

                if (adjustedAmount > maxValue) {
                    alert('The adjusted amount cannot exceed the maximum allowed value of ' + maxValue);
                    event.preventDefault(); // Prevent form submission
                }
            });
        };

        // Listen to changes    // Function to set up validation listeners
        function setupValidationListeners() {
            const amountInputs = document.querySelectorAll('[name="amount[]"]');
            const adjustedAmounts = document.querySelectorAll('.adjusted-amount');

            // Remove existing listeners first
            amountInputs.forEach(input => {
                input.removeEventListener('input', validateAmountAndToggleButtons);
                input.addEventListener('input', validateAmountAndToggleButtons);
            });

            adjustedAmounts.forEach(input => {
                input.removeEventListener('input', calculateTotalAdjustedAmount);
                input.addEventListener('input', calculateTotalAdjustedAmount);
            });
        }

        // Set up initial listeners
        setupValidationListeners();

        // Add change event listeners to account selects
        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('account-select')) {
                updateAccountSelectionState();
            }
        });

        // Set up mutation observers for both repeaters
        const accountRepeater = document.querySelector('.account-repeater');

        if (accountRepeater) {
            new MutationObserver(setupValidationListeners)
                .observe(accountRepeater, {
                    childList: true,
                    subtree: true
                });
        }

        $(document).on('change', '.reference-type-select', function() {
            let paymentType = $(this).val();
            let invoiceSelectGroup = $(this).closest('.repeater-item').find('.invoice-select').closest(
                '.form-group');
            let invoiceSelect = invoiceSelectGroup.find('.invoice-select');

            // Invoice hide for these 3 types
            if (paymentType === 'new_ref' || paymentType === 'advance' || paymentType === 'on_account') {
                invoiceSelect.prop('required', false);
                invoiceSelectGroup.hide(); // hide the whole field
            } else {
                invoiceSelect.prop('required', true);
                invoiceSelectGroup.show(); // show the field
                // optional: add visual asterisk if not already there
                if (invoiceSelectGroup.find('label .required').length === 0) {
                    invoiceSelectGroup.find('label').append('<span class="required">*</span>');
                }
            }
        });


        // On page load / repeater init
        $('.reference-type-select').each(function() {
            $(this).trigger('change'); // Apply the above logic initially
        });


        // Handle add account item
        document.getElementById('add-account-item').onclick = function() {
            formState.accountCounter++;
            var repeaterItem = document.querySelector('.account-repeater-item');
            var clone = repeaterItem.cloneNode(true);

            // Clear the values in the cloned input fields
            clone.querySelectorAll('input, select').forEach(function(input) {
                input.value = '';
                input.classList.remove('is-invalid');
                input.placeholder = '';
            });

            // Update IDs for the new item
            clone.setAttribute('data-item-id', formState.accountCounter);
            clone.querySelector('.account-select').id = `account_id_${formState.accountCounter}`;
            clone.querySelector('.amount-input').id = `amount_${formState.accountCounter}`;
            clone.querySelector('.add_receipt-input').id = `add_receipt_${formState.accountCounter}`;
            //update name tag for input
            clone.querySelector('.account-select').name =
                `bank_details[${formState.accountCounter - 1}][account_id]`;
            clone.querySelector('.amount-input').name =
                `bank_details[${formState.accountCounter  - 1}][amount]`;
            clone.querySelector('.add_receipt-input').name =
                `bank_details[${formState.accountCounter  - 1}][add_receipt]`;
            clone.querySelector('.remove-account-item').style.display = 'inline-block';
            // Add the clone to the repeater container
            document.querySelector('.account-repeater').appendChild(clone);
            updateAccountIds();
        };

        // Handle remove account item
        document.addEventListener('click', function(event) {
            if (event.target.closest('.remove-account-item')) {
                const itemToRemove = event.target.closest('.account-repeater-item');
                if (itemToRemove && document.querySelectorAll('.account-repeater-item').length > 1) {
                    itemToRemove.remove();
                    updateAccountIds();
                }
            }
        });


        // Function to update account item IDs and handle selection logic
        function updateAccountIds() {
            document.querySelectorAll('.account-repeater-item').forEach(function(item, index) {
                const newId = index + 1;
                item.setAttribute('data-item-id', newId);
                item.querySelector('.account-select').id = `account_id_${newId}`;
                item.querySelector('.amount-input').id = `amount_${newId}`;
                item.querySelector('.add_receipt-input').id = `add_receipt_${newId}`;
                // update name tags for bank account
                item.querySelector('.account-select').name = `bank_details[${newId - 1}][account_id]`;
                item.querySelector('.amount-input').name = `bank_details[${newId - 1}][amount]`;
                item.querySelector('.add_receipt-input').name = `bank_details[${newId - 1}][add_receipt]`;
                // Show/hide remove button based on number of items
                const removeButton = item.querySelector('.remove-account-item');
                if (removeButton) {
                    removeButton.style.display = document.querySelectorAll('.account-repeater-item')
                        .length > 1 ? 'inline-block' : 'none';
                }
            });
            updateAccountSelectionState();
        }

        // Function to disable selected accounts in other dropdowns
        function updateAccountSelectionState() {
            const accountSelects = document.querySelectorAll('.account-select');
            const selectedAccounts = new Set();

            // First, collect all selected values
            accountSelects.forEach(select => {
                if (select.value) {
                    selectedAccounts.add(select.value);
                }
            });

            // Then, disable selected options in other dropdowns
            accountSelects.forEach(select => {
                Array.from(select.options).forEach(option => {
                    if (option.value && selectedAccounts.has(option.value) && option.value !==
                        select.value) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        }

        // Initial validation check on page load
        validateAmountAndToggleButtons();
    })();
</script>
