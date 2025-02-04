@extends('layouts.master')
@section('content-header')
<div class="row">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">Holiday</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{route('holiday.index')}}">Holidays</a></li>
            <li class="breadcrumb-item active">Form</li>
        </ol>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Form</h3>
            </div>
            {{Form::model($holiday, ['route'=>$submitRoute, 'method'=>$method])}}
            <div class="card-body">
                <div class="form-group">
                    {{Form::label('title','Title')}}
                    {{Form::text('title',null,['class'=>'form-control'])}}
                </div>

                <div class="form-group">
                    {{Form::label('date','Date')}}
                    {{Form::date('date',null,['class'=>'form-control'])}}
                </div>

                <div class="form-group">
                    {{Form::label('type','Holiday Type')}}
                    {{Form::select('type', $holiday_types, null,['class'=>'form-control select2', 'placeholder'=>'Select    '])}}
                </div>

                <div class="form-group">
                    {{ Form::label('is_active', 'Is Active') }}
                    {{ Form::checkbox('is_active') }}
                </div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
            {{Form::close()}}
        </div>
    </div>
</div>
@endsection
