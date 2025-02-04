@extends('layouts.master')
{{-- @section('content-header')
<div class="row">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">Leave Responsible</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{route('home')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{route('leave-responsible.index')}}">Leave Responsible</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </div>
</div>
@endsection --}}
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Update</h3>
            </div>
            {{ Form::model($leaveResponsible, ['route' => ['leave-responsible.update', $leaveResponsible->id], 'method' => 'PUT']) }}
            <div class="card-body">
                <div class="col-md-6 form-group">
                    {{ Form::label('employee_role_id', 'Employee Role', ['class' => 'col-sm-3 col-form-label']) }}
                    {{ Form::select('employee_role_id', $leaveRoles, null, ['class' => 'form-control select2','required','data-placeholder' => 'Select an Option','placeholder' => 'Select an Option']) }}
                </div>
                <div class="col-md-6 form-group">
                    {{ Form::label('first_approval', 'First Approval', ['class' => 'col-sm-3 col-form-label']) }}
                    {{ Form::select('first_approval', $leaveRoles, null, ['class' => 'form-control select2','required','data-placeholder' => 'Select an Option','placeholder' => 'Select an Option']) }}
                </div>
                <div class="col-md-6 form-group">
                    {{ Form::label('second_approval', 'Second Approval', ['class' => 'col-sm-3 col-form-label']) }}
                    {{ Form::select('second_approval', $leaveRoles, null, ['class' => 'form-control select2','data-placeholder' => 'Select an Option','placeholder' => 'Select an Option']) }}
                </div>
                <div class="col-md-6 form-group">
                    {{ Form::label('third_approval', 'Third Approval', ['class' => 'col-sm-3 col-form-label']) }}
                    {{ Form::select('third_approval', $leaveRoles, null, ['class' => 'form-control select2','data-placeholder' => 'Select an Option','placeholder' => 'Select an Option']) }}
                </div>
                <div class="col-md-6 form-group">
                    {{ Form::label('fourth_approval', 'Fourth Approval', ['class' => 'col-sm-3 col-form-label']) }}
                    {{ Form::select('fourth_approval', $leaveRoles, null, ['class' => 'form-control select2','data-placeholder' => 'Select an Option','placeholder' => 'Select an Option']) }}
                </div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
            {{Form::close()}}
        </div>
    </div>
</div>
@endsection