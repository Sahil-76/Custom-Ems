@extends('layouts.master')
@section('content')

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb" class="float-right">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
                <li class="breadcrumb-item ">Form</li>
            </ol>
        </nav>
    </div>

    <div class="col-12 grid-margin">
        <div class="card">
            {{Form::model($leaveType,array('route'=>$submitRoute,'method'=>$method))}}
            <div class="card-body">
                <h4 class="card-title">Leave Type</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group row">
                            {{Form::label('name','Name', ['class' => 'col-sm-3 col-form-label']) }}
                            <div class="col-sm-9">
                                {{Form::text('name',null,['class'=>'form-control','id'=>'name','placeholder'=>'Name'])}}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group row">
                            {{ Form::label('is_active', 'Is Active', ['class' => 'col-sm-1 col-form-label']) }}
                            <div class="col-sm-9">
                                <div class="col-1 mt-2 float-left">
                                    <div class="form-check">
                                        {!! Form::checkbox('is_active', 0, null, ['class' => 'form-check-input ml-0', 'style' => 'margin-top: 3px;']) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                    {{Form::close()}}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
