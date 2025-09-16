@section('page-title')
    {{ __('Add Transfer Method') }}
@endsection

@section('content')
    <div class="modal-body">
        {!! Form::open([
            'route' => ['billPayment.updateTransfer', $billPayment->id],
            'method' => 'POST',
            'id' => 'transferForm',
        ]) !!}
        <div class="form-group">
            {{ Form::label('transfer_method', __('Transfer Method')) }}
            {{ Form::select('transfer_method', $transferMethods, null, ['class' => 'form-control', 'id' => 'transfer_method', 'required' => 'required']) }}
        </div>
        <div class="form-group">
            {{ Form::label('reference_number', __('Reference Number')) }}
            {{ Form::text('reference_number', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group">
            {{ Form::label('transfer_date', __('Transfer Date')) }}
            {{ Form::date('transfer_date', \Carbon\Carbon::now(), ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="saveBtn">
                <span id="saveText">{{ __('Save') }}</span>
                <span id="loader" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
        </div>
        {{ Form::close() }}
    </div>

    <script>
        document.getElementById('transferForm').addEventListener('submit', function(event) {
            if (this.dataset.submitted) {
                event.preventDefault(); // Prevent multiple submissions
                return;
            }

            this.dataset.submitted = true; // Mark form as submitted

            let saveText = document.getElementById('saveText');
            let loader = document.getElementById('loader');

            saveText.classList.add('d-none'); // Hide "Save" text
            loader.classList.remove('d-none'); // Show Loader
        });
    </script>
