@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item "><a href="{{ route('leave-status.index') }}">Leave Status</a></li>
                    {{-- <li class="breadcrumb-item active">{{ $user->name }}</li> --}}
                </ol>
            </nav>
        </div>

        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{$title}}</h4>
                    {{Form::model($leaves ?? null,array('route'=>$submitRoute,'method'=>'post'))}}
                    @if ($method === 'put')
                        @method('PUT')
                    @endif
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group row">
                                {{ Form::label('type', 'Type', ['class' => 'col-sm-3 col-form-label']) }}

                                <div class="col-sm-9">
                                    {{ Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => 'Leave Type']) }}
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2">{{$button}}</button>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footerScripts')
<script>

</script>




@endsection
