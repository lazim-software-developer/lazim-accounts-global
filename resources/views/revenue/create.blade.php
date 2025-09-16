{{ Form::open(['url' => 'revenue', 'enctype' => 'multipart/form-data']) }}

<div class="modal-content">
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
                <span class="text-danger">*</span> {{-- Required field asterisk --}}
                <div class="form-icon-user">
                    {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
                </div>
            </div>
            {{-- <div class="form-group col-md-6">
            {{ Form::label('customer_id', __('Customer'), ['class' => 'form-label']) }}
            {{ Form::select('customer_id', $customers, null, ['class' => 'form-control select', 'required' => 'required', 'id' => 'customer_id']) }}
            {{ Form::select('customer_id', $customers, null, ['class' => 'form-control select', 'required' => 'required', 'id' => 'customer_id', 'data-url' => route('revenue.customer')]) }}
        </div> --}}
            <!-- Repeater section ends here -->
            <div id="invoice-repeater">
                <div class="repeater-item row" data-item-id="1">
                    <div class="form-group col-md-6">
                        {{ Form::label('customer_id', __('Customer'), ['class' => 'form-label']) }}
                        <span class="text-danger">*</span> {{-- Required field asterisk --}}
                        {{ Form::select('customer_details[0][customer_id]', $customers, null, [
                            'class' => 'form-control select customer-select',
                            'required' => 'required',
                            'id' => 'customer_id',
                        ]) }}
                    </div>


                    <div class="form-group col-md-6">
                        {{ Form::label('reference_type', __('Reference Type'), ['class' => 'form-label']) }}
                        <span class="text-danger">*</span> {{-- Required field asterisk --}}
                        {{ Form::select(
                            'customer_details[0][reference_type]',
                            [
                                '' => 'Select Reference Type', // placeholder ke liye empty option
                                'new_ref' => 'New Ref',
                                'advance' => 'Advance',
                                'against_ref' => 'Against Ref',
                                'on_account' => 'On Account',
                            ],
                            'new_ref',
                            [
                                'class' => 'form-control select reference-type-select',
                                // 'placeholder' => 'Select Reference Type',
                                'required' => 'required',
                            ],
                        ) }}
                    </div>
                    <div class="form-group col-md-4">
                        {{ Form::label('ref_details', __('Reference Details'), ['class' => 'form-label']) }}
                        <div class="form-icon-user">
                            {{ Form::text('customer_details[0][ref_details]', '', ['class' => 'form-control ref_details']) }}
                        </div>
                    </div>
                    <div class="form-group col-md-4 invoice-field">
                        {{ Form::label('invoice_number[]', __('Invoice'), ['class' => 'form-label']) }}
                        {{ Form::select('customer_details[0][invoice_number]', [], null, [
                            'class' => 'form-control select invoice-select',
                            // 'id' => 'invoice_number',
                        ]) }}
                    </div>

                    <div class="form-group col-md-4">
                        {{ Form::label('adjusted_amount[]', __('Invoice Amount'), ['class' => 'form-label']) }}
                        <span class="text-danger">*</span> {{-- Required field asterisk --}}
                        <div class="form-icon-user">
                            {{ Form::number('customer_details[0][adjusted_amount]', '', ['class' => 'form-control adjusted-amount', 'step' => '0.01', 'required' => 'required', 'id' => 'adjusted_amount_1']) }}
                            <p class="error-msg d-none">The amount exceeds the maximum allowed value.</p>
                        </div>
                    </div>
                    <div
                        class="form-group col-md-2 d-flex align-items-end justify-content-center remove-button-container">
                        <button type="button" class="btn btn-danger remove-repeater-item" style="display: none;"><i
                                class="ti ti-trash"></i></button>
                    </div>
                    <hr class="divider">
                </div>

            </div>



            <!-- Repeater section starts here -->
            <div class="form-group col-md-12 clearfix">
                <button type="button" id="add-invoice-item" class="btn btn-primary">Add Another</button>
                <p id="invoice-total" class="float-end">Total <span id="invoice-total-value">0.00</span></p>
            </div>

            <!-- Repeater section ends here -->
            <div class="account-repeater">
                <div class="account-repeater-item row" data-item-id="1">
                    <div class="form-group col-md-6">
                        {{ Form::label('account_id[]', __('Account'), ['class' => 'form-label']) }}
                        <span class="text-danger">*</span> {{-- Required field asterisk --}}
                        {{ Form::select('bank_details[0][account_id]', $accounts, null, ['class' => 'form-control select account-select', 'required' => 'required', 'id' => 'account_id_1']) }}
                    </div>
                    <div class="form-group col-md-6">
                        {{ Form::label('amount[]', __('Amount'), ['class' => 'form-label']) }}
                        <span class="text-danger">*</span> {{-- Required field asterisk --}}
                        <div class="form-icon-user">
                            {{ Form::number('bank_details[0][amount]', '', ['class' => 'form-control amount-input', 'required' => 'required', 'step' => '0.01', 'id' => 'amount_1']) }}
                        </div>
                    </div>
                    <div
                        class="form-group col-md-2 d-flex align-items-end justify-content-center remove-button-container">
                        <button type="button" class="btn btn-danger remove-account-item" style="display: none;"><i
                                class="ti ti-trash"></i></button>
                    </div>
                    <hr class="divider">
                </div>
            </div>
            <!-- Repeater section starts here -->
            <div class="form-group col-md-12 clearfix">
                <button type="button" class="btn btn-primary" id="add-account-item">{{ __('Add Account') }}</button>
                <p id="account-total" class="float-end">Total <span id="account-total-value">0.00</span></p>
            </div>

            {{-- <div class="d-none form-group col-md-12" id="invoice-repeater-container">

            </div> --}}

            <div class="form-group col-md-6">
                {{ Form::label('category_id', __('Category'), ['class' => 'form-label']) }}
                <span class="text-danger">*</span> {{-- Required field asterisk --}}
                {{ Form::select('category_id', $categories, null, ['class' => 'form-control select', 'required' => 'required']) }}
            </div>
            <div class="form-group col-md-6">
                {{ Form::label('reference', __('Reference'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('reference', '', ['class' => 'form-control', 'id' => 'reference']) }}
                </div>
            </div>
            <div class="form-group col-md-12">
                {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
                {{ Form::textarea('description', '', ['class' => 'form-control', 'rows' => 3, 'id' => 'description']) }}
            </div>
            {{-- <div class="form-group col-md-6">
            {{ Form::label('flat_id', __('unit'),['class'=>'form-label']) }}
            {{ Form::select('flat_id', $flats,null, array('class' => 'form-control select','required'=>'required')) }}
        </div> --}}
            {{-- <div class="form-group col-md-6">
            {{ Form::label('invoice_number', __('Invoice'),['class'=>'form-label']) }}
            {{ Form::select('invoice_number', $invoices,null, array('class' => 'form-control select','required'=>'required', 'id'=>'invoice_number')) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('adjusted_amount', __('Invoice Amount'),['class'=>'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('adjusted_amount', '', array('class' => 'form-control','step'=>'0.01','required'=>'required', 'id'=>'adjusted_amount')) }}
            </div>
        </div> --}}

            <div class="col-md-6">
                {{ Form::label('add_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
                <div class="choose-file form-group">
                    <label for="file" class="form-label">
                        <input type="file" name="add_receipt" id="files" class="form-control">
                    </label>
                    <p class="upload_file"></p>
                    <img id="image" class="mt-2" style="width:25%;" />
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</div>

{{ Form::close() }}

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
                if (invoiceSelect) invoiceSelect.id = `invoice_number_${newId}`;
                if (adjustedAmount) adjustedAmount.id = `adjusted_amount_${newId}`;
                // update name tags
                if (customerSelect) customerSelect.name = `customer_details[${newId - 1}][customer_id]`;
                if (referenceTypeSelect) referenceTypeSelect.name =
                    `customer_details[${newId - 1}][reference_type]`;
                if (refDetails) refDetails.name = `customer_details[${newId - 1}][ref_details]`;
                if (invoiceSelect) invoiceSelect.name = `customer_details[${newId - 1}][invoice_number]`;
                if (adjustedAmount) adjustedAmount.name = `customer_details[${newId - 1}][adjusted_amount]`;

                // Show/hide remove button based on number of items
                if (removeButton) {
                    // Only show remove button if there's more than one item
                    removeButton.style.display = items.length > 1 ? 'inline-block' : 'none';
                }
            });

            // Update the add button state
            const addButton = document.getElementById('add-invoice-item');
            if (addButton) {
                addButton.disabled = false; // Always enable add button as there's no upper limit
            }
        }

        // Handle add invoice item
        document.getElementById('add-invoice-item').onclick = function() {
            formState.itemCounter++;
            var repeaterItem = document.querySelector('.repeater-item');
            var clone = repeaterItem.cloneNode(true);

            // Clear the values in the cloned input fields
            clone.querySelectorAll('input, select').forEach(function(input) {
                input.value = '';
                input.classList.remove('is-invalid');
                input.placeholder = '';
                const maxLabel = input.closest('.repeater-item').querySelector('.max-amount-label');
                if (maxLabel) maxLabel.remove();
            });

            // Update IDs for the new item
            // Update IDs for the new item
            clone.setAttribute('data-item-id', formState.itemCounter);
            clone.querySelector('.customer-select').id = `customer_id_${formState.itemCounter}`;
            clone.querySelector('.reference-type-select').id = `reference_type_${formState.itemCounter}`;
            clone.querySelector('.ref_details').id = `ref_details_${formState.itemCounter}`;
            clone.querySelector('.invoice-select').id = `invoice_number_${formState.itemCounter}`;
            clone.querySelector('.adjusted-amount').id = `adjusted_amount_${formState.itemCounter}`;
            clone.querySelector('.remove-repeater-item').style.display = 'inline-block';
            // update name tag for input
            clone.querySelector('.customer-select').name =
                `customer_details[${formState.itemCounter - 1}][customer_id]`;
            clone.querySelector('.reference-type-select').name =
                `customer_details[${formState.itemCounter - 1}][reference_type]`;
            clone.querySelector('.ref_details').name =
                `customer_details[${formState.itemCounter - 1}][ref_details]`;
            clone.querySelector('.invoice-select').name =
                `customer_details[${formState.itemCounter - 1}][invoice_number]`;
            clone.querySelector('.adjusted-amount').name =
                `customer_details[${formState.itemCounter - 1}][adjusted_amount]`;

            // Add the clone to the repeater container
            document.getElementById('invoice-repeater').appendChild(clone);

            // Update all item IDs
            updateItemIds();

            // Trigger change event manually to apply the disabled logic
            applyInvoiceSelectionLogic();
            calculateTotalAdjustedAmount(); // Validate after adding a new item
        };

        document.getElementById('invoice-repeater').addEventListener('click', function(event) {
            // Check if the click was on the button or the icon inside the button
            const isRemoveButton = event.target.classList.contains('remove-repeater-item');
            const isTrashIcon = event.target.classList.contains('ti-trash');

            if (isRemoveButton || isTrashIcon) {
                console.log('Removing item');
                const items = document.querySelectorAll('.repeater-item');
                if (items.length <= 1) {
                    // Prevent removing the last item
                    alert('At least one invoice item is required.');
                    return;
                }

                // Get the repeater item to remove
                const itemToRemove = isTrashIcon ?
                    event.target.closest('.repeater-item') :
                    event.target.closest('.repeater-item');

                if (itemToRemove) {
                    itemToRemove.remove();

                    // Update the IDs of remaining items
                    updateItemIds();

                    // Reapply the selection logic after removing an item
                    applyInvoiceSelectionLogic();
                    validateAmountAndToggleButtons(); // Validate after removing an item
                }
            }
        });

        function applyInvoiceSelectionLogic() {
            // Get all selected invoice IDs
            var selectedInvoices = Array.from(document.querySelectorAll('.invoice-select'))
                .map(function(select) {
                    return select.value;
                })
                .filter(function(value) {
                    return value !== ''; // Exclude empty values
                });

            // Disable selected options in all selects
            document.querySelectorAll('.invoice-select').forEach(function(select) {
                select.querySelectorAll('option').forEach(function(option) {
                    option.disabled = selectedInvoices.includes(option.value) && option.value !==
                        select
                        .value;
                });
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

        document.getElementById('invoice-repeater').addEventListener('change', function(event) {
            if (event.target.classList.contains('invoice-select')) {
                var invoiceId = event.target.value;
                var repeaterItem = event.target.closest('.repeater-item');

                if (invoiceId.length > 0) {
                    fetch(`/get-adjusted-amount?invoice_number=${invoiceId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.adjusted_amount !== undefined) {
                                updateAdjustedAmountUI(repeaterItem, data.maxValue);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching adjusted amount:', error);
                        });
                } else {
                    updateAdjustedAmountUI(repeaterItem);
                }

                // validateAmountAndToggleButtons(); // Validate after changing an invoice selection
            }
        });

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
            document.querySelectorAll('.amount-input').forEach(function(input) {
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
            const accountAmounts = Array.from(document.querySelectorAll('.amount-input'));
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
            const addInvoiceButton = document.getElementById('add-invoice-item');
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
            const receiptAmount = Array.from(document.querySelectorAll('.amount-input')).reduce((total,
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
            const amountInputs = document.querySelectorAll('.amount-input');
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
        const invoiceRepeater = document.getElementById('invoice-repeater');

        if (accountRepeater) {
            new MutationObserver(setupValidationListeners)
                .observe(accountRepeater, {
                    childList: true,
                    subtree: true
                });
        }

        if (invoiceRepeater) {
            new MutationObserver(setupValidationListeners)
                .observe(invoiceRepeater, {
                    childList: true,
                    subtree: true
                });
        }


        // Initial setup to disable already selected invoices and validate amounts on page load
        document.querySelectorAll('.invoice-select').forEach(function(select) {
            select.addEventListener('change', function() {
                applyInvoiceSelectionLogic();
                validateAmountAndToggleButtons(); // Validate when an invoice is selected
            });
        });

        $(document).on('change', '.customer-select', function() {
            let customerId = $(this).val(); // selected customer ka ID
            let $invoiceSelect = $(this).closest('.row').find(
                '.invoice-select'); // usi row ka invoice select

            if (customerId) {
                $.ajax({
                    url: '{{ url('/get-invoices-by-customer') }}/' + customerId,
                    type: "GET",
                    data: {
                        customer_id: customerId
                    },
                    success: function(response) {
                        $invoiceSelect.empty(); // pehle saari invoices hata do
                        $invoiceSelect.append(
                            '<option value="">{{ __('Select Invoice') }}</option>');

                        $.each(response, function(key, value) {
                            $invoiceSelect.append('<option value="' + key + '">' +
                                value + '</option>');
                        });
                    }
                });
            } else {
                $invoiceSelect.empty();
                $invoiceSelect.append('<option value="">{{ __('Select Invoice') }}</option>');
            }
        });


        // $(document).on('change', '.customer-select', function() {
        //     var customerId = $(this).val();
        //     var rowIndex = $(this).data('index'); // row ka index nikal lo
        //     var invoiceDropdown = $('#invoice_number_' + rowIndex);

        //     if (customerId) {
        //         $.ajax({
        //             url: '{{ url('/get-invoices-by-customer') }}/' + customerId,
        //             type: 'GET',
        //             dataType: 'json',
        //             success: function(data) {
        //                 invoiceDropdown.empty();
        //                 invoiceDropdown.append('<option value="">Select an invoice</option>');
        //                 $.each(data, function(key, value) {
        //                     invoiceDropdown.append('<option value="' + key + '">' +
        //                         value + '</option>');
        //                 });
        //             },
        //             error: function(error) {
        //                 console.log('Error:', error);
        //             }
        //         });
        //     } else {
        //         // $('#invoice-repeater-container').addClass('d-none');
        //         $('#invoice_number').empty();
        //         $('#invoice_number').append('<option value="">Select an invoice</option>');
        //     }
        // });

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

        // $(document).ready(function() {
        //     // Form submit handle
        //     $('form').on('submit', function(e) {
        //         e.preventDefault();

        //         let customerDetails = [];
        //         let bankDetails = [];

        //         $('.repeater-item').each(function() {
        //             let customerId = $(this).find('.customer-select').val();
        //             let referenceType = $(this).find('.reference-type-select').val();
        //             let referenceDetail = $(this).find('.ref_details').val() || null;
        //             let invoiceId = $(this).find('.invoice-select').val() || null;
        //             let invoiceAmount = $(this).find('.adjusted-amount').val() || null;
        //             let receiptAmount = $(this).find('.amount-input').val() || null;

        //             customerDetails.push({
        //                 customer_id: customerId ? parseInt(customerId) : null,
        //                 reference_type: referenceType,
        //                 ref_detail: referenceDetail,
        //                 invoice_number: invoiceId ? parseInt(invoiceId) : null,
        //                 adjusted_amount: invoiceAmount ? parseFloat(invoiceAmount) :
        //                     null,

        //                 // amount: receiptAmount ? parseFloat(receiptAmount) : null
        //             });
        //         });
        //         $('.account-repeater-item').each(function() {
        //             let accountId = $(this).find('.account-select').val();
        //             let amount = $(this).find('.amount-input').val();

        //             bankDetails.push({
        //                 account_id: accountId ? parseInt(accountId) : null,
        //                 amount: amount,


        //                 // amount: receiptAmount ? parseFloat(receiptAmount) : null
        //             });
        //         });
        //         let selectedDate = $('input[name="date"]').val();
        //         let reference = $("input[name='reference']").val();
        //         let description = $("textarea[name='description']").val();
        //         let category_id = $("select[name='category_id']").val();
        //         let payload = {
        //             date: selectedDate,
        //             reference: reference,
        //             description: description,
        //             category_id: category_id,
        //             customer_detail: customerDetails,
        //             bank_details: bankDetails
        //         };

        //         console.log(payload); // check console me array aa raha h
        //         // console.log(this.submit()); // check console me array aa raha h

        //         // Ab chahe toh normal form submit kara do

        //         // dd(this.submit());
        //         this.submit();
        //         //  // AJAX call
        //         //                 $.ajax({
        //         //                     url: $(this).attr('action'),
        //         //                     type: $(this).attr('method'),
        //         //                     data: payload,
        //         //                     success: function(response) {
        //         //                         console.log("Saved!", response);
        //         //                         alert("Data saved successfully!");
        //         //                         // window.location.reload(); // optional reload
        //         //                     },
        //         //                     error: function(xhr) {
        //         //                         console.error(xhr.responseText);
        //         //                         alert("Something went wrong!");
        //         //                     }
        //         //                 });
        //         // ya AJAX se payload bhejna h toh yaha bhej sakte ho
        //     });
        // });

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
            //update name tag for input
            clone.querySelector('.account-select').name =
                `bank_details[${formState.accountCounter - 1}][account_id]`;
            clone.querySelector('.amount-input').name =
                `bank_details[${formState.accountCounter  - 1}][amount]`;
            clone.querySelector('.remove-account-item').style.display = 'inline-block';
            // Add the clone to the repeater container
            document.querySelector('.account-repeater').appendChild(clone);
            updateAccountIds();
        };

        document.getElementById('invoice-repeater').addEventListener('keyup', function(event) {
            if (event.target.classList.contains('adjusted-amount')) {
                // console.log("Keyup detected for adjusted-amount: ", event.target.value);
                if (parseFloat(event.target.value) > parseFloat(event.target.getAttribute('max'))) {
                    event.target.classList.add('is-invalid');
                    event.target.value = event.target.getAttribute('max');
                } else {
                    event.target.classList.remove('is-invalid');
                }

                calculateTotalAdjustedAmount();
            }
        });

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
                // update name tags for bank account
                item.querySelector('.account-select').name = `bank_details[${newId - 1}][account_id]`;
                item.querySelector('.amount-input').name = `bank_details[${newId - 1}][amount]`;
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
