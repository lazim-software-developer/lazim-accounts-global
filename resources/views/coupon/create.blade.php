@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::open(['url' => 'coupons', 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        {{-- @if ($enable_chatgpt)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true" data-url="{{ route('generate', ['coupon']) }}"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                    data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif --}}
        <div class="form-group col-md-12">
            {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
            {{ Form::text('name', null, ['class' => 'form-control font-style', 'required' => 'required']) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('discount', __('Discount'), ['class' => 'form-label']) }}
            {{ Form::number('discount', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
            <span class="small">{{ __('Note: Discount in Percentage') }}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('limit', __('Limit'), ['class' => 'form-label']) }}
            {{ Form::number('limit', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>


        <div class="form-group col-md-12">
            {{ Form::label('code', __('Code'), ['class' => 'form-label']) }}
            <div class="d-flex radio-check">
                <div class="form-check form-check-inline form-group col-md-6">
                    <input type="radio" id="manual_code" value="manual" name="icon-input"
                        class="form-check-input code" checked="checked">
                    <label class="custom-control-label " for="manual_code">{{ __('Manual') }}</label>
                </div>
                <div class="form-check form-check-inline form-group col-md-6">
                    <input type="radio" id="auto_code" value="auto" name="icon-input" class="form-check-input code">
                    <label class="custom-control-label" for="auto_code">{{ __('Auto Generate') }}</label>
                </div>
            </div>
        </div>

        <div class="form-group col-md-12 d-block" id="manual">
            <input class="form-control font-uppercase" name="manualCode" type="text">
        </div>
        <div class="form-group col-md-12 d-none" id="auto">
            <div class="row">
                <div class="col-md-10">
                    <input class="form-control" name="autoCode" type="text" id="auto-code">
                </div>
                <div class="col-md-2">
                    <a href="#" class="btn btn-primary" id="code-generate"><i class="ti ti-history"></i></a>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
