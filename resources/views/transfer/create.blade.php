@php
    $plan = \App\Models\Utility::getChatGPTSettings();
@endphp
{{ Form::open(['url' => 'transfer']) }}
<div class="modal-body">
    <div class="row">
        {{-- @if ($plan->enable_chatgpt == 'on')
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['transfer']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Generate') }}" data-title="{{ __('Generate content with AI') }}"
                    class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif --}}
        <div class="form-group col-md-6">
            {{ Form::label('from_account', __('From Account'), ['class' => 'form-label']) }}
            {{ Form::select('from_account', 
                collect(['' => __('Select Bank')])->union($bankAccount), 
                null, 
                [
                    'class' => 'form-control', 
                    'required' => 'required',
                    'id' => 'from_account',
                ]
            ) }}
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('to_account', __('To Account'), ['class' => 'form-label']) }}
            {{ Form::select('to_account', 
                collect(['' => __('Select Bank')])->union($bankAccount), 
                null, 
                [
                    'class' => 'form-control', 
                    'required' => 'required',
                    'id' => 'to_account',
                ]
            ) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('amount', '', ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('reference', __('Reference'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::text('reference', '', ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', '', ['class' => 'form-control', 'rows' => 3]) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
<script>
(function() {
    const fromAccountSelect = document.getElementById('from_account');
    const toAccountSelect = document.getElementById('to_account');

    // Function to disable selected option in the other dropdown
    function updateAccountOptions() {
        const fromValue = fromAccountSelect.value;
        const toValue = toAccountSelect.value;

        // Reset all options to enabled first
        Array.from(toAccountSelect.options).forEach(option => {
            option.disabled = false;
        });
        Array.from(fromAccountSelect.options).forEach(option => {
            option.disabled = false;
        });

        // Disable the selected option in the opposite dropdown
        if (fromValue) {
            Array.from(toAccountSelect.options).forEach(option => {
                if (option.value === fromValue) {
                    option.disabled = true;
                }
            });
        }

        if (toValue) {
            Array.from(fromAccountSelect.options).forEach(option => {
                if (option.value === toValue) {
                    option.disabled = true;
                }
            });
        }
    }

    // Add event listeners
    if (fromAccountSelect && toAccountSelect) {
        fromAccountSelect.addEventListener('change', updateAccountOptions);
        toAccountSelect.addEventListener('change', updateAccountOptions);

        // Initial check on page load
        updateAccountOptions();
    }

    // Form validation before submission
    document.getElementById('transfer-form').addEventListener('submit', function(e) {
        const fromValue = fromAccountSelect.value;
        const toValue = toAccountSelect.value;

        if (fromValue === toValue) {
            e.preventDefault();
            alert('From Account and To Account cannot be the same. Please select different accounts.');
            return false;
        }
    });
})();
</script>
