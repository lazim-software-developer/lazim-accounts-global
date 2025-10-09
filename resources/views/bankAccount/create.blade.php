{{ Form::open(['url' => 'bank-account', 'method' => 'POST', 'id' => 'bank-account-form']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('chart_account_id', __('Account'), ['class' => 'form-label']) }}
            <select name="chart_account_id" class="form-control" required="required">
                <option value="" selected>Select Account</option>
                @foreach ($chartAccounts as $key => $chartAccount)
                    @if (strtolower(trim($chartAccount)) !== 'select account')
                        <option value="{{ $key }}" class="subAccount">{{ $chartAccount }}</option>
                        @foreach ($subAccounts as $subAccount)
                            @if ($key == $subAccount['account'])
                                <option value="{{ $subAccount['id'] }}" class="ms-5"> &nbsp; &nbsp;&nbsp;
                                    {{ $subAccount['name'] }}</option>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('holder_name', __('Bank Holder Name'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-address-card"></i></span>
                {{ Form::text('holder_name', '', ['class' => 'form-control', 'required' => 'required','placeholder'=>'Bank Holder Name']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('bank_name', __('Bank Name'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-university"></i></span>
                {{ Form::text('bank_name', '', ['class' => 'form-control', 'required' => 'required','placeholder'=>'Enter Bank Name']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('account_number', __('Account Number'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-notes-medical"></i></span>
                {{ Form::text('account_number', '', ['class' => 'form-control', 'required' => 'required','placeholder'=>'Enter Account Number']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('opening_balance', __('Opening Balance'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-dollar-sign"></i></span>
                {{ Form::number('opening_balance', '', ['class' => 'form-control', 'required' => 'required', 'step' => '0.01','placeholder'=>'Enter Opening Balance']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('contact_number', __('Contact Number'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-mobile-alt"></i></span>
                {{ Form::text('contact_number', '', ['class' => 'form-control','placeholder'=>'Enter Contact Number']) }}
            </div>
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('bank_address', __('Bank Address'), ['class' => 'form-label']) }}
            {{ Form::textarea('bank_address', '', ['class' => 'form-control', 'rows' => 2,'placeholder'=>'Enter Bank Address']) }}
        </div>
        @if (!$customFields->isEmpty())
            <div class="col-md-12">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary" id="submit-btn" data-submitted="false">
        <span class="submit-text">{{ __('Create') }}</span>
        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    </button>

</div>

{{ Form::close() }}
<script>
    document.getElementById('bank-account-form').addEventListener('submit', function(e) {
        let submitBtn = document.getElementById('submit-btn');

        // Prevent double submit
        if (submitBtn.dataset.submitted === 'true') {
            e.preventDefault();
            return;
        }

        submitBtn.dataset.submitted = 'true'; // mark as submitted

        submitBtn.querySelector('.submit-text').classList.add('d-none');
        submitBtn.querySelector('.spinner-border').classList.remove('d-none');
    });
</script>
