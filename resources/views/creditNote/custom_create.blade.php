@php
    use App\Models\Tax;

    $vat =
        (int) Tax::where('name', 'VAT')
            ->where('building_id', Auth::user()->currentBuilding())
            ->first()?->rate ?? 5;
@endphp
{{ Form::open(['route' => ['invoice.custom.credit.note'], 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            {{ Form::label('invoice', __('Invoice'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" id="invoice" name="invoice">
                <option value="">{{ __('Select Invoice') }}</option>
                @foreach ($invoices as $key => $invoice)
                    <option value="{{ $key }}">{{ \Auth::user()->invoiceNumberFormat($invoice) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::number('amount', null, ['class' => 'form-control', 'required' => 'required', 'step' => '1', 'id' => 'amount']) }}
            </div>
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
                {{ Form::number('vat_amount', null, ['class' => 'form-control', 'required' => 'required', 'readonly' => 'readonly', 'id' => 'vat_amount']) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <div class="form-icon-user">
                {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}


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
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
<script>
    function calculateVAT() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const vatPercentage = parseFloat(document.getElementById('vat_percentage').value) || 0;
        const vatAmount = (amount * vatPercentage / 100).toFixed(2);
        document.getElementById('vat_amount').value = vatAmount;
    }

    document.getElementById('amount').addEventListener('input', calculateVAT);

    // Invoice change event
    document.getElementById('invoice').addEventListener('change', function() {

        // You can trigger the VAT calculation
        setTimeout(() => {
            calculateVAT(); // Call VAT calculation after amount is filled
        }, 3000);
    });
</script>

{{ Form::close() }}
