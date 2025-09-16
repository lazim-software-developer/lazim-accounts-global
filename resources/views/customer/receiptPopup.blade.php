{{ Form::open(array('route' => array('sync.receipt'),'method'=>'post', 'enctype' => "multipart/form-data", 'onsubmit' => 'handleFormSubmit(event)')) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mt-2">
            {{Form::label('from_date',__('From Date'),['class'=>'form-label'])}}
            <span class="text-danger">*</span>
            <div style="padding: 5px 0">
                {{ Form::date('from_date', null, array('class' => 'form-control','required'=>'required','max'=>date('Y-m-d'))) }}
            </div>
        </div>
        <div class="col-md-12 mt-2">
            {{Form::label('to_date',__('To Date'),['class'=>'form-label'])}}
            <span class="text-danger">*</span>
            <div style="padding: 5px 0">
                {{ Form::date('to_date', null, array('class' => 'form-control','required'=>'required','max'=>date('Y-m-d'))) }}
            </div>
        </div>
        <div class="col-md-12 mt-2" style="display: none;">
            {{Form::label('customer_id',__('Customer Id'),['class'=>'form-label'])}}
            {{ Form::number('customer_id',$id, array('class' => 'form-control','required'=>'required','hidden'=>'true')) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary" id="submit-btn">
        <span class="submit-text">{{__('Sync')}}</span>
        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    </button>
</div>
<script>
function handleFormSubmit(event) {
    let submitBtn = document.getElementById('submit-btn');
    
    // Prevent double submission
    if (submitBtn.disabled) {
        event.preventDefault();
        return false;
    }
    
    // Show loader and disable button
    submitBtn.disabled = true;
    submitBtn.querySelector('.submit-text').classList.add('d-none');
    submitBtn.querySelector('.spinner-border').classList.remove('d-none');
    
    // Allow form to submit naturally
    return true;
}
</script>
{{ Form::close() }}
