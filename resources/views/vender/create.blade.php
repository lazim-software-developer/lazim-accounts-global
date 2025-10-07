{{ Form::open(['url' => 'vender', 'method' => 'post', 'id' => 'vendor-create-form']) }}
<div class="modal-body">

    <h5 class="sub-title">{{ __('Basic Info') }}</h5>
    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{ Form::label('name', __('Names'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required','placeholder'=>__('Enter Name')]) }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{ Form::label('contact', __('Contact'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('contact', null, ['class' => 'form-control', 'required' => 'required','placeholder'=>__('Enter Contact Number')]) }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('email', null, ['class' => 'form-control', 'required' => 'required','placeholder'=>__('Enter Email Address')]) }}
                </div>
            </div>
        </div>
        {!! Form::hidden('role', 'company', null, ['class' => 'form-control select2', 'required' => 'required']) !!}
        {{-- <div class="col-lg-4 col-md-4 form-group mt-4">
                <label for="password_switch">{{ __('Login is enable') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="password_switch" class="form-check-input input-primary pointer" value="on" id="password_switch">
                    <label class="form-check-label" for="password_switch"></label>
                </div>
            </div> --}}
        <div class="col-lg-4 col-md-4 col-sm-6 ps_div d-none">
            <div class="form-group">
                {{ Form::label('password', __('Password'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::password('password', ['class' => 'form-control', 'minlength' => '6']) }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{ Form::label('tax_number', __('Tax Number'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('tax_number', null, ['class' => 'form-control','placeholder'=>__('Enter Tax Number')]) }}
                </div>
            </div>
        </div>
        @if (!$customFields->isEmpty())
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif
    </div>
    <h5 class="sub-title">{{ __('BIlling Address') }}</h5>
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_name', __('Name'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_name', null, ['class' => 'form-control','placeholder'=>__('Enter Billing Name')]) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_phone', __('Phone'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_phone', null, ['class' => 'form-control','placeholder'=>__('Enter Billing Phone Number')]) }}
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('billing_address', __('Address'), ['class' => 'form-label']) }}
                <div class="input-group">
                    {{ Form::textarea('billing_address', null, ['class' => 'form-control', 'rows' => 3,'placeholder'=>__('Enter Billing Address')]) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_city', __('City'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_city', null, ['class' => 'form-control','placeholder'=>__('Enter Billing City')]) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_state', __('State'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_state', null, ['class' => 'form-control','placeholder'=>__('Enter Billing State')]) }}
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_country', __('Country'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_country', null, ['class' => 'form-control','placeholder'=>__('Enter Billing Country')]) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_zip', __('Zip Code'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_zip', null, ['class' => 'form-control', 'placeholder' => __('Enter Billing Zip Code')]) }}
                </div>
            </div>
        </div>

    </div>

    @if (App\Models\Utility::getValByName('shipping_display') == 'on')
        <div class="col-md-12 text-end">
            <input type="button" id="billing_data" value="{{ __('Shipping Same As Billing') }}"
                class="btn btn-primary">
        </div>
        <h5 class="sub-title">{{ __('Shipping Address') }}</h5>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{ Form::label('shipping_name', __('Name'), ['class' => 'form-label']) }}
                    <div class="form-icon-user">
                        {{ Form::text('shipping_name', null, ['class' => 'form-control','placeholder'=>__('Enter Shipping Name')]) }}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{ Form::label('shipping_phone', __('Phone'), ['class' => 'form-label']) }}
                    <div class="form-icon-user">
                        {{ Form::text('shipping_phone', null, ['class' => 'form-control','placeholder'=>__('Enter Shipping Phone Number')]) }}
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('shipping_address', __('Address'), ['class' => 'form-label']) }}
                    <div class="input-group">
                        {{ Form::textarea('shipping_address', null, ['class' => 'form-control', 'rows' => 3,'placeholder'=>__('Enter Shipping Address')]) }}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{ Form::label('shipping_city', __('City'), ['class' => 'form-label']) }}
                    <div class="form-icon-user">
                        {{ Form::text('shipping_city', null, ['class' => 'form-control','placeholder'=>__('Enter Shipping City')]) }}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{ Form::label('shipping_state', __('State'), ['class' => 'form-label']) }}
                    <div class="form-icon-user">
                        {{ Form::text('shipping_state', null, ['class' => 'form-control','placeholder'=>__('Enter Shipping State')]) }}
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{ Form::label('shipping_country', __('Country'), ['class' => 'form-label']) }}
                    <div class="form-icon-user">
                        {{ Form::text('shipping_country', null, ['class' => 'form-control','placeholder'=>__('Enter Shipping Country')]) }}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{ Form::label('shipping_zip', __('Zip Code'), ['class' => 'form-label']) }}
                    <div class="form-icon-user">
                        {{ Form::text('shipping_zip', null, ['class' => 'form-control', 'placeholder' => __('Enter Shipping Zip Code')]) }}
                    </div>
                </div>
            </div>

        </div>
    @endif

</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary" id="submit-btn">
        <span class="submit-text">{{ __('Create') }}</span>
        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    </button>
</div>
{{ Form::close() }}

<script>
    document.getElementById('vendor-create-form').addEventListener('submit', function(e) {
        let submitBtn = document.getElementById('submit-btn');

        // Agar already submitted, to dobara mat bhejo
        if (submitBtn.getAttribute('data-submit') === 'true') {
            e.preventDefault();
            return;
        }

        // Mark as submitted
        submitBtn.setAttribute('data-submit', 'true');

        // Hide the text, show the loader
        submitBtn.querySelector('.submit-text').classList.add('d-none');
        submitBtn.querySelector('.spinner-border').classList.remove('d-none');
    });
</script>

@push('script-page')
    <script>
        $(document).on('change', '#password_switch', function() {
            if ($(this).is(':checked')) {
                $('.ps_div').removeClass('d-none');
                $('#password').attr("required", true);

            } else {
                $('.ps_div').addClass('d-none');
                $('#password').val(null);
                $('#password').removeAttr("required");
            }
        });
        $(document).on('click', '.login_enable', function() {
            setTimeout(function() {
                $('.modal-body').append($('<input>', {
                    type: 'hidden',
                    val: 'true',
                    name: 'login_enable'
                }));
            }, 2000);
        });
    </script>
@endpush
