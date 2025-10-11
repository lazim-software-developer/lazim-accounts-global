@php
    use App\Models\Tax;

    $vat = (int) Tax::where('name', 'VAT')->where('building_id', Auth::user()->currentBuilding())->first()?->rate ?? 5;
@endphp
{{ Form::model($creditNote, ['route' => ['invoice.edit.credit.note', $creditNote->invoice, $creditNote->id], 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            {{ Form::label('customer', __('Customer'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" value="{{ $creditNote->customer }}" disabled id="customer" name="customer">
                <option value="">{{ __('Select Customer') }}</option>
                @foreach ($customers as $key => $customer)
                    <option value="{{ $key }}" {{ $key == $creditNote->customer ? 'selected' : '' }}>{{ $customer }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12">
            {{ Form::label('invoice', __('Invoice'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" value="{{ $creditNote->invoice }}" disabled id="invoice" name="invoice">
                <option value="">{{ __('Select Customer First') }}</option>
                @foreach ($invoices as $key => $invoice)
                    <option value="{{ $key }}" {{ $key == $creditNote->invoice ? 'selected' : '' }}>{{ $invoice }}</option>
                @endforeach
            </select>
        </div>
        {{ Form::hidden('due_amount', $dueAmount, ['class' => 'form-control', 'required' => 'required', 'step' => '1', 'id' => 'due_amount']) }}
        <div class="form-group  col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-money-bill-alt"></i></span>
                {{ Form::date('date', null, ['class' => 'form-control', 'required' => 'required']) }}

            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                <span><i class="ti ti-money-bill-alt"></i></span>
                {{ Form::number('amount', null, ['class' => 'form-control', 'required' => 'required', 'step' => '1', 'id' => 'amount']) }}
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
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
<script>
    document.getElementById('amount').addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const maxAmount = parseFloat(document.getElementById('due_amount').value) || 0;
        if (amount > maxAmount) {
            alert(`Amount cannot exceed ${maxAmount}`);
            this.value = maxAmount;
        }
        const vatPercentage = parseFloat(document.getElementById('vat_percentage').value) || 0;
        const vatAmount = (amount * vatPercentage / 100).toFixed(2);
        document.getElementById('vat_amount').value = vatAmount;
    });
</script>
{{ Form::close() }}
