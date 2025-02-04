@extends('layouts.master')
@section('content-header')
    <div class="row">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">Email Management</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('email.index') }}">Email Management</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Edit</h3>
                </div>
                {{ Form::open(['route' => ['email.update', $email]]) }}
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        {{ Form::label('reference', 'Reference') }}
                        {{ Form::text('reference', $email->reference, ['class' => 'form-control']) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('subject', 'Subject') }}
                        {{ Form::text('subject', $email->subject, ['class' => 'form-control']) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('template', 'Template Path') }}
                        {{ Form::text('template', $email->template, ['class' => 'form-control']) }}
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>

        <div class="col-md-7">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class="card-title">Reciepients</div>
                </div>
                <div class="card-body">
                    {{ Form::open(['route' => ['email.user.store', $email]]) }}
                    <div class="row">
                        <div class="col-md-8">
                            {{ Form::select('user_id[]', $users ?? [], null, ['class' => 'form-control select2','data-placeholder'=>'Select User', 'required', 'multiple' => 'multiple']) }}
                        </div>
                        <div class="col-md-2">
                            {{ Form::select('recipient_type', $types ?? [], null, ['class' => 'form-control select2']) }}
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-danger"> <i class="fa fa-plus"></i> Add</button>
                        </div>
                    </div>
                    {{ Form::close() }}

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="text-center">To</h4><hr>

                                    @foreach ($email->toRecipients as $user)
                                        <ul class="list-unstyled">
                                            @php
                                                $deleteUrl = route('email.user.destroy', ['email' => $email->id, 'user' => $user->id]);
                                            @endphp
                                            <li>{{ $user->email}} {!!  activeInactiveHTML($user->is_active) !!} <i onclick='deleteItem("{{ $deleteUrl }}")'
                                                    class="text-danger fa fa-trash"></i></li>
                                        </ul>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="text-center">CC</h4><hr>
                                    @foreach ($email->ccRecipients as $user)
                                        <ul class="list-unstyled">
                                            @php
                                                $deleteUrl = route('email.user.destroy', ['email' => $email->id, 'user' => $user->id]);
                                            @endphp
                                            <li>{{ $user->email }}  {!!  activeInactiveHTML($user->is_active) !!} <i onclick='deleteItem("{{ $deleteUrl }}")'
                                                    class="text-danger fa fa-trash"></i></li>
                                        </ul>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="text-center">BCC</h4><hr>
                                    @foreach ($email->bccRecipients as $user)
                                        <ul class="list-unstyled">
                                            @php
                                                $deleteUrl = route('email.user.destroy', ['email' => $email->id, 'user' => $user->id]);
                                            @endphp
                                            <li>{{ $user->email }}  {!!  activeInactiveHTML($user->is_active) !!} <i onclick='deleteItem("{{ $deleteUrl }}")'
                                                    class="text-danger fa fa-trash"></i></li>
                                        </ul>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="text-center">Exclude</h4><hr>
                                    @foreach ($email->excludeRecipients as $user)
                                        <ul class="list-unstyled">
                                            @php
                                                $deleteUrl = route('email.user.destroy', ['email' => $email->id, 'user' => $user->id]);
                                            @endphp
                                            <li>{{ $user->email }}  {!!  activeInactiveHTML($user->is_active) !!} <i onclick='deleteItem("{{ $deleteUrl }}")'
                                                    class="text-danger fa fa-trash"></i></li>
                                        </ul>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
