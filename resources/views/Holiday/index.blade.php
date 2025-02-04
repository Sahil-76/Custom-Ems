@extends('layouts.master')
@section('header')
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
@endsection
@section('content-header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">General Holiday</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">General Holiday</li>
            </ol>
        </div>
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class="card-title">
                        List
                    </div>
                    <div class="card-tools">
                        <a href="{{ route('holiday.create') }}" class="btn btn-dark">Create</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="background-color: white">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                  
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($holidays as $holiday)
                                    <tr>
        
                                        <td>{{ $holiday->title }}</td>
                                        <td>{{ formatDate($holiday->date) }}</td>
                                        <td>{{ Carbon\Carbon::parse($holiday->date)->format('l') }}</td>
                                        <td>{{ $holiday->type }}</td>
                                        <td><i @if($holiday->is_active == 1) class="fas fa-check-circle text-green" @else class="fas fa-times-circle text-red" @endif></i></td>
                                        <td><a href="{{route('holiday.edit', ['holiday' => $holiday->id])}}" class="btn btn-primary">Edit</a></td> 

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
