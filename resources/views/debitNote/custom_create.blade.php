@php
    use App\Models\Tax;

    $vat =
        (int) Tax::where('name', 'VAT')
            ->where('building_id', Auth::user()->currentBuilding())
            ->first()?->rate ?? 5;
@endphp
{{ Form::open(['route' => ['bill.custom.debit.note'], 'mothod' => 'post']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('bill', __('Bill'), ['class' => 'form-label']) }}
            <select class="form-control select" required="required" id="bill" name="bill">
                <option value="">{{ __('Select Bill') }}</option>
                @foreach ($bills as $key => $bill)
                    <option value="{{ $key }}">{{ \Auth::user()->billNumberFormat($bill) }}</option>
                @endforeach
            </select>
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
{{ Form::close() }}

<script>
    $(document).ready(function() {
        function calculateVatAmount() {
            let amount = parseFloat($('#amount').val()) || 0;
            let vatPercentage = parseFloat($('#vat_percentage').val()) || 0;
            let vatAmount = (vatPercentage / 100) * amount;
            $('#vat_amount').val(vatAmount ? vatAmount.toFixed(2) : '');
        }

        $('#amount').on('input', function() {
            calculateVatAmount();
        });

        $('#bill').on('change', function() {
            // Wait for amount to update from backend if any
            setTimeout(function() {
                calculateVatAmount();
            }, 3000); // You can adjust the delay if needed
        });
    });
</script>
