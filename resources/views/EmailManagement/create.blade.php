@extends('layouts.master')
@section('content-header')
<div class="row">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">Email Management</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{route('home')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{route('email.index')}}">Email Management</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-md-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Create</h3>
            </div>
            {{Form::open(['route'=>'email.store'])}}
            <div class="card-body">
                <div class="form-group">
                    {{Form::label('reference','Reference')}}
                    {{Form::text('reference',null,['class'=>'form-control'])}}
                </div>

                <div class="form-group">
                    {{Form::label('subject','Subject')}}
                    {{Form::text('subject',null,['class'=>'form-control'])}}
                </div>

                <div class="form-group">
                    {{Form::label('template','Template Path')}}
                    {{Form::text('template',null,['class'=>'form-control'])}}
                </div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
            {{Form::close()}}
        </div>
    </div>
</div>
@endsection
