@php
    use App\Models\Tax;

    $vat = (int) Tax::where('name', 'VAT')->where('building_id', Auth::user()->currentBuilding())->first()?->rate ?? 5;
@endphp
{{ Form::model($debitNote, ['route' => ['bill.edit.debit.note', $debitNote->bill, $debitNote->id], 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group  col-md-6">
            {{ Form::label('vender', __('Vender'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" id="vender" disabled name="vender">
                <option value="">{{ __('Select Vender') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ $vendor->id == $debitNote->vendor ? 'selected' : '' }}>{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('bill', __('Bill'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" id="bill" disabled name="bill">
                <option value="">{{ __('Select Bill') }}</option>
                @foreach ($bill as $bills)
                    <option value="{{ $bills->id }}" 
                        {{ $debitNote->bill == $bills->id ? 'selected' : '' }}>
                        {{ \Auth::user()->billNumberFormat($bills->bill_id) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('bill_due', __('Bill Due'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('bill_due', $billDue, ['class' => 'form-control', 'disabled' => 'disabled']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::date('date', null, ['class' => 'form-control', 'required' => 'required']) }}

            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('amount', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('vat_percentage', __('VAT Percentage'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('vat_percentage', $vat, ['class' => 'form-control', 'disabled' => 'disabled', 'id' => 'vat_percentage']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('vat_amount', __('VAT Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-money-bill-alt"></i></span>
                {{ Form::number('vat_amount', null, ['class' => 'form-control', 'required' => 'required', 'readonly' => 'readonly', 'id' => 'vat_amount']) }}
            </div>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3']) !!}
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
<script>
    function calculateVAT() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const vatPercentage = parseFloat(document.getElementById('vat_percentage').value) || 0;
        const vatAmount = (amount * vatPercentage / 100).toFixed(2);
        const totalAmount = (parseFloat(amount) + parseFloat(vatAmount)).toFixed(2);
        
        document.getElementById('vat_amount').value = vatAmount;
        document.getElementById('total_amount').value = totalAmount;
    }
    // Amount input event
    document.getElementById('amount').addEventListener('input', function() {
        const maxAmount = document.getElementById('bill_due').value;
        const enteredAmount = parseFloat(this.value);
        if (maxAmount && enteredAmount > maxAmount) {
            this.value = maxAmount;
            alert(`Amount cannot exceed ${maxAmount}`);
        }
        
        calculateVAT();
    });
</script>