@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb" class="float-right">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item "><a href="{{ route('resignations.index') }}">Resignation</a></li>
            </ol>
        </nav>
    </div>

    <div class="col-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Enter Resignation Detail for {{ $user->name }}</h4>
                {!! Form::open(['method' => 'post', 'route' => ['resignations.store']]) !!}
    
                {!! Form::hidden('user_id', $user->id) !!}
        

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('reason_id', 'Reason:', ['class' => 'control-label']) !!}
                            {!! Form::select('reason_id', $reasons, null, ['class' => 'form-control selectJS', 'required','placeholder' => 'Choose one']) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('leaving_date', 'Leaving Date:', ['class' => 'control-label']) !!}
                            {!! Form::date('leaving_date', null, ['class' => 'form-control ', 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-6">

                        <div class="form-group">
                            <div class="form-check">
                                {!! Form::checkbox('is_exit', 1 , null, ['class' => 'form-check-input ml-0', 'style' => 'margin-top: 3px;']) !!}
                                {!! Form::label('is_exit', 'Is Exit', ['class' => 'form-check-label mb-0', 'style' => 'margin-left: 1.2rem;']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                {!! Form::submit('Create', ['class' => 'btn btn-primary']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@endsection
@section('footerScripts')
@endsection