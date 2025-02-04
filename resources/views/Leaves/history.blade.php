@extends('layouts.master')
@section('header')
    <style>


    </style>
@endsection
@section('content-header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark"> Leave History</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            {{-- @can('create', new App\Models\NewLeave()) --}}
            <a href="{{ route('leave.create') }}" class="btn btn-danger btn-sm mr-2">Apply Leave</a>
            <a href="{{ route('leave.index') }}" class="btn btn-success btn-sm mr-2">Leave History</a>
            {{-- @endcan
            @can('approval', new App\Models\NewLeave()) --}}
            <a href="{{ route('leave.request') }}" class="btn btn-info btn-sm mr-2"> Leave Request <span class="badge bg-gradient-light"> {{totalPendingLeaveRequestCount()}}</span></a>
            {{-- @endcan --}}
            <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
            <li class="breadcrumb-item active">Leave History</li>
        </ol>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Filter</h3>
            </div>
            <div class="card-body">
                {!! Form::open(['method' => 'get']) !!}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('Select Employee', 'Select Employee') !!}
                            {!! Form::select('user', $users, request()->user ?? null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('leave_type', 'Select Leave Type') !!}
                            {!! Form::select('leave_type', $leaveTypes, request()->leave_type ?? null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                        </div>
                    </div>



                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('status', 'Status') !!}
                            {!! Form::select('status', $status, request()->status ?? null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                        </div>
                    </div>


                    <div class="col-md-3" id="date-range">
                        <div class="form-group">
                            {{ Form::hidden('dateFrom', request()->dateFrom ?? null, ['id' => 'dateFrom']) }}
                            {{ Form::hidden('dateTo', request()->dateTo ?? null, ['id' => 'dateTo']) }}
                            <button type="button" id="dateRangePicker-btn" class="btn btn-primary"
                                style="margin-top: 31px;" id="daterange-btn">
                                @if (request()->has('dateFrom') && request()->has('dateTo'))
                                    <span>
                                        {{ Carbon\Carbon::parse(request()->get('dateFrom'))->format('d/m/Y') }} -
                                        {{ Carbon\Carbon::parse(request()->get('dateTo'))->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span>
                                        <i class="fa fa-calendar" value=""></i> &nbsp;Filter Start Date&nbsp;
                                    </span>
                               @endif
                               <i class="fa fa-caret-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ request()->url() }}" class="btn btn-success">Clear Filter</a>
                    </div>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>

@endsection
@section('content')



    <div class="col-md-12">
        <div class="card">

            <div class="card-header">
                <div class="card-title">Leave History</div>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Nature</th>
                                <th>Type</th>
                                <th>From Date</th>
                                <th>Till Date</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Detail</th>


                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leaves as $leave)



                                <tr @if ($leave->status == 'Rejected') class="bg-red" @endif>
                                    @if (request()->page == 1)
                                        <td>{{ $loop->iteration }}</td>
                                    @else
                                        <td>{{ $loop->iteration + request()->page }}</td>
                                    @endif

                                     <td>{{ $leave->user->name }}</td>
                                     <td>{{ $leave->user->designation }}</td>
                                    <td>{{ $leave->leave_nature }}</td>
                                    <td>{{ $leave->leaveType->name ?? '' }}</td>
                                    <td>{{ getFormattedDate($leave->from_date) }}</td>
                                    <td>{{ getFormattedDate($leave->to_date) }}</td>
                                    <td>{{ $leave->duration }}</td>
                                    <td>{{ $leave->status }}</td>
                                     <td><a href="{{ route('leave.show',$leave) }}" class="btn btn-warning">Detail</a></td>




                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                    <div class="pull-right">
                        {{ $leaves->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

@endsection
@section('footerScripts')



@endsection
