@extends('layouts.master')
@section('content-header')
    <div class="row">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">Leave Type</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('leave-types.index') }}">Leave Types</a></li>
                <li class="breadcrumb-item active"><a>Form</a></li>
            </ol>
        </div>
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Create Form</h3>
                </div>
                {{ Form::open(['route' => ['leave-types.store']]) }}
                @method('POST')
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Name</label>
                                {{ Form::text('name', null, ['class' => 'form-control', 'required']) }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Short Name</label>
                                {{ Form::text('short_name', null, ['class' => 'form-control', 'maxlength' => '4', 'required']) }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Allowance Period</label>
                                {{ Form::select('allowance_type', $allowance_type, null, ['class' => 'form-control select2','placeholder' => 'Select an option']) }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Allowance</label>
                                {{ Form::text('allowance', null, ['class' => 'form-control']) }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Carry Forward Period</label>
                                {{ Form::select('carry_forward_type', $allowance_type, null, ['class' => 'form-control select2','placeholder' => 'Select an option']) }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Minimum Notice Period</label>
                                {{ Form::number('notice_period', null, ['class' => 'form-control', 'required']) }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Apply After Joining</label>
                                {{ Form::number('apply_after', null, ['class' => 'form-control']) }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="is_active" id="is_active">
                                        <label class="custom-control-label" for="is_active"> Is Active </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="can_apply" id="can_apply">
                                        <label class="custom-control-label" for="can_apply"> Can Apply </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="leave_permission" id="leave_permission">
                                        <label class="custom-control-label" for="leave_permission">Need Approval</label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="balance" id="balance">
                                        <label class="custom-control-label" for="balance">Show Balance</label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="carry_forward" id="carry_forward">
                                        <label class="custom-control-label" for="carry_forward"> Carry Forward </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="can_apply_probation" id="can_apply_probation">
                                        <label class="custom-control-label" for="can_apply_probation"> Can Apply Probation </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="can_negative" id="can_negative">
                                        <label class="custom-control-label" for="can_negative"> Can Go In Minus </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="half_day_allowed" id="half_day_allowed">
                                        <label class="custom-control-label" for="half_day_allowed"> Half Day Allowed </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="attachment_required" id="attachment_required">
                                        <label class="custom-control-label" for="attachment_required"> Attachment Required </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <div class="custom-control custom-switch custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" value="1" name="yearly_expiry" id="yearly_expiry">
                                        <label class="custom-control-label" for="yearly_expiry"> Yearly Expiry </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Color</label>
                                        {{ Form::color('color_code', null, ['class' => '', 'required']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Description</label>
                                {{ Form::textarea('description', null, ['class' => 'form-control summernote', 'rows' => '3']) }}
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
@section('footerScripts')
    <script>
      
    </script>
@endsection
