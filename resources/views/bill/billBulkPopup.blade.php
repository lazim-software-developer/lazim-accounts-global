{{ Form::open(array('route' => array('bill.bulkSync'),'method'=>'post', 'enctype' => "multipart/form-data")) }}
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
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Sync')}}" class="btn  btn-primary">
</div>
{{ Form::close() }}
