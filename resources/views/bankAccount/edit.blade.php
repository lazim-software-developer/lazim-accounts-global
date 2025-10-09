{{ Form::model($bankAccount, ['route' => ['bank-account.update', $bankAccount->id], 'method' => 'PUT']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('chart_account_id', __('Account'), ['class' => 'form-label']) }}
            <select name="chart_account_id" class="form-control" required="required" disabled>
                @foreach ($chartAccounts as $key => $chartAccount)
                    <option value="{{ $key }}" class="subAccount"
                        {{ $bankAccount->chart_account_id == $key ? 'selected' : '' }}>{{ $chartAccount }}</option>
                    @foreach ($subAccounts as $subAccount)
                        @if ($key == $subAccount['account'])
                            <option value="{{ $subAccount['id'] }}" class="ms-5"
                                {{ $bankAccount->chart_account_id == $subAccount['id'] ? 'selected' : '' }}> &nbsp;
                                &nbsp;&nbsp; {{ $subAccount['name'] }}</option>
                        @endif
                    @endforeach
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('holder_name', __('Bank Holder Name'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-address-card"></i></span>
                {{ Form::text('holder_name', null, ['class' => 'form-control', 'required' => 'required','placeholder'=>'Bank Holder Name']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('bank_name', __('Bank Name'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-university"></i></span>
                {{ Form::text('bank_name', null, ['class' => 'form-control', 'required' => 'required','placeholder'=>'Enter Bank Name']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('account_number', __('Account Number'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-notes-medical"></i></span>
                {{ Form::text('account_number', null, ['class' => 'form-control', 'required' => 'required','placeholder'=>'Enter Account Number']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('opening_balance', __('Opening Balance'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-dollar-sign"></i></span>
                @if($transactionLinesCount>1)
                {{ Form::number('opening_balance', $openingBalance, ['class' => 'form-control', 'required' => 'required','placeholder'=>'Enter Opening Balance','disabled'=>true]) }}
                @else
                {{ Form::number('opening_balance', $openingBalance, ['class' => 'form-control', 'required' => 'required','placeholder'=>'Enter Opening Balance']) }}
                @endif
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('contact_number', __('Contact Number'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-mobile-alt"></i></span>
                {{ Form::text('contact_number', null, ['class' => 'form-control','placeholder'=>'Enter Contact Number']) }}
            </div>
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('bank_address', __('Bank Address'), ['class' => 'form-label']) }}
            {{ Form::textarea('bank_address', null, ['class' => 'form-control', 'rows' => 2,'placeholder'=>'Enter Bank Address']) }}
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
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
