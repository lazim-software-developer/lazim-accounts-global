@php
    $plan = \App\Models\Utility::getChatGPTSettings();
@endphp
{{ Form::model($contract, ['route' => ['contract.update', $contract->id], 'method' => 'PUT']) }}
<div class="modal-body">
    <div class="row">
        {{-- @if ($plan->enable_chatgpt == 'on')
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['contract']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Generate') }}" data-title="{{ __('Generate content with AI') }}"
                    class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif --}}
        <div class="form-group col-md-12">
            {{ Form::label('subject', __('Subject'), ['class' => 'col-form-label']) }}
            {{ Form::text('subject', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('customer', __('Customer'), ['class' => 'col-form-label']) }}
            {{ Form::select('customer', $customers, null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('type', __('Contract Type'), ['class' => 'col-form-label']) }}
            {{ Form::select('type', $contractTypes, null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('value', __('Contract Value'), ['class' => 'col-form-label']) }}
            {{ Form::number('value', null, ['class' => 'form-control', 'required' => 'required', 'stage' => '0.01']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('start_date', __('Start Date'), ['class' => 'col-form-label']) }}
            {{ Form::date('start_date', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('end_date', __('End Date'), ['class' => 'col-form-label']) }}
            {{ Form::date('end_date', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'col-form-label']) }}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3']) !!}
        </div>
    </div>
    {{-- <div class="col-md-12 mb-2">
        <label class="form-label">{{__('Status')}}</label>
        <div class="d-flex radio-check">
            <div class="custom-control custom-radio custom-control-inline m-1" >
                <input  type="radio" id="start" name="status" value="start" class="form-check-input" {{ ($contract->status == 'start') ? 'checked' : '' }} />
                <label class="form-check-labe" for="start">{{__('Start')}}</label>
            </div>
            <div class="custom-control custom-radio custom-control-inline m-1">
                <input type="radio" id="close" name="status" value="close" class="form-check-input" {{ ($contract->status == 'close') ? 'checked' : '' }} />
                <label class="form-check-labe" for="close">{{__('Close')}}</label>
            </div>
        </div>
    </div>   --}}
    <div class="modal-footer pr-0">
        <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
        {{ Form::submit(__('Update'), ['class' => 'btn  btn-primary']) }}
    </div>
</div>

{{ Form::close() }}


<script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
<script>
    if ($(".multi-select").length > 0) {
        $($(".multi-select")).each(function(index, element) {
            var id = $(element).attr('id');
            var multipleCancelButton = new Choices(
                '#' + id, {
                    removeItemButton: true,
                }
            );
        });
    }
</script>
