@extends('layouts.master')
@section('header')
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb" class="float-right">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Leave Type</li>
                {{-- <li class="breadcrumb-item active">{{ $user->name }}</li> --}}
            </ol>
        </nav>
    </div>

    <div class="col-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <div class=" rounded h-100 p-4">
                    <div class="row">
                        <div class="col-md-6 mt-4 ">
                            <h3 class=" text-dark text-start">List</h3>
                        </div>
                        <div class="col-md-6  d-flex justify-content-end">
                            <a href="{{ route('leave-types.create') }}" class="btn btn-dark">Create</a>
                        </div>
                        
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Allowance Type</th>
                                    <th>Allowance</th>
                                    <th>Show Balance</th>
                                    <th>Need Approval</th>
                                    <th>Minimum Notice Period</th>
                                    <th>Can Apply</th>
                                    <th>Carry Forward</th>
                                    <th>Active</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaveTypes as $leaveType)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $leaveType->name }}</td>
                                        <td>{{ $leaveType->allowance_type }}</td>
                                        <td>{{ $leaveType->allowance }}</td>
                                        <td> <i
                                                @if ($leaveType->balance == 1) class="fa fa-check" style="color: green;"    @else  class="fa fa-remove" style="color: red;" @endif></i>
                                        </td>
                                        <td> <i
                                                @if ($leaveType->leave_permission == '1') class="fa fa-check" style="color: green;"    @else  class="fa fa-remove" style="color: red;" @endif></i>
                                        </td>
                                        <td>{{ $leaveType->notice_period }}</td>
                                        <td> <i
                                                @if ($leaveType->can_apply == '1') class="fa fa-check" style="color: green;"    @else  class="fa fa-remove" style="color: red;" @endif></i>
                                        </td>
                                        <td> <i
                                                @if ($leaveType->carry_forward == '1') class="fa fa-check" style="color: green;"    @else  class="fa fa-remove" style="color: red;" @endif></i>
                                        </td>
                                        <td> <i
                                                @if ($leaveType->is_active == '1') class="fa fa-check" style="color: green;"    @else  class="fa fa-remove" style="color: red;" @endif></i>
                                        </td>
                                        <td><a href="{{ route('leave-types.edit', $leaveType->id) }}"
                                                class="btn btn-primary">Edit</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
