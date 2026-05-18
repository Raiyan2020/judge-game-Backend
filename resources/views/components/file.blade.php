<div class="col-{{$size}}">
    <div class="form-group">
        <label for="basicInputFile">{{$title}}</label>
        <div class="custom-file">
            {!! Form::file($name,['class'=>'custom-file-input','id'=>$name]+$options) !!}
            <label class="custom-file-label" for="{{$name}}">Choose file</label>
        </div>
        <p class="help-block"></p>
        @error($name)
        <span style="color: red">{{ $message }}</span>
        @enderror
        {{$slot}}
    </div>
</div>
