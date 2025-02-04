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
                <h4 class="card-title">Edit Resignation Detail for {{ $resignation->user->name }}</h4>
                {!! Form::model($resignation, ['method' => 'put', 'route' => ['resignations.update','resignation' => $resignation->id]]) !!}

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('reason_id', 'Reason:', ['class' => 'control-label']) !!}
                            {!! Form::select('reason_id', $reasons, $resignation->resignation_reason_id, ['class' => 'form-control selectJS', 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('leaving_date', 'Leaving Date:', ['class' => 'control-label']) !!}
                            {!! Form::date('leaving_date', $resignation->leaving_date, ['class' => 'form-control', 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="form-check">
                                {!! Form::checkbox('is_exit', 1, $resignation->is_exit, ['class' => 'form-check-input ml-0', 'style' => 'margin-top: 3px;']) !!}
                                {!! Form::label('is_exit', 'Is Exit', ['class' => 'form-check-label mb-0', 'style' => 'margin-left: 1.2rem;']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                {!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@endsection
@section('footerScripts')
@endsection