@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ticket-type.index') }}">Ticket Types</a></li>
                    <li class="breadcrumb-item active"><a>Form</a></li>
                </ol>
            </nav>
        </div>

        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Create Ticket Type</h4>
                    {{ Form::model($ticket_types ?? null, ['route' => 'ticket-type.store', 'method' => 'post']) }}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group row">
                                {{ Form::label('name', 'Name', ['class' => 'col-sm-2 col-form-label']) }}
                                <div class="col-sm-8">
                                    {{ Form::text('name', null, ['class' => 'form-control', 'required']) }}
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group row">
                                {{ Form::label('user_ids', 'Responsible Person', ['class' => 'col-sm-2 col-form-label']) }}
                                <div class="col-sm-8">
                                    {{ Form::select('user_ids[]', $users, [], ['class' => 'form-control selectJS','multiple','data-placeholder' => 'Select User', 'required']) }}
                                    @error('user_ids')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <div class="form-check">
                                    {!! Form::checkbox('is_active', 1 , null, ['class' => 'form-check-input ml-0', 'style' => 'margin-top: 3px;']) !!}
                                    {!! Form::label('is_active', 'Is Active', ['class' => 'form-check-label mb-0', 'style' => 'margin-left: 1.2rem;']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2">Create</button>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footerScripts')
    <script></script>
@endsection
