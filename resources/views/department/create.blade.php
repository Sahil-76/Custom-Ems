@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Department List</li>
                </ol>
            </nav>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Department</p>
                    {{ Form::open(['route' => ['departments.store'], 'method' => 'POST']) }}
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="">Name</label>
                            <input class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="">Short Name</label>
                            <input class="form-control" name="short_name" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="">Manager</label>
                            {{ Form::select('manager_id', $managers ?? [], null, ['class' => 'form-control selectJS', 'placeholder' => 'Select Manager', 'data-placeholder' => 'Select Manager']) }}
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="">Team Leader</label>
                            {{ Form::select('team_leader_id', $teamLeaders ?? [], null, ['class' => 'form-control selectJS', 'placeholder' => 'Select Team Leader', 'data-placeholder' => 'Select Team Leader']) }}
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="">Line Manager</label>
                            {{ Form::select('line_manager_id', $lineManagers ?? [], null, ['class' => 'form-control selectJS', 'placeholder' => 'Select Line Manager', 'data-placeholder' => 'Select Line Manager']) }}
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="">Required Employee</label>
                            <input type="number" class="form-control" name="required_emp" >
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-facebook">Create</button>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footerScripts')
@endsection
