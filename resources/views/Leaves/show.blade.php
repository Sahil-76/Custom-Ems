@extends('layouts.master')
@section('content-header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">My Leaves</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            {{-- @can('create', new App\Models\Leave()) --}}
            <a href="{{ route('leave.create') }}" class="btn btn-danger btn-sm mr-2">Apply Leave</a>
            {{-- @endcan --}}
            {{-- @can('approval', new App\Models\Leave()) --}}
            <a href="{{ route('leave.request') }}" class="btn btn-info btn-sm mr-2"> Leave Request <span class="badge bg-gradient-light"> {{totalPendingLeaveRequestCount()}}</span></a>
            {{-- @endcan --}}
            <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
            <li class="breadcrumb-item active">Leaves</li>
        </ol>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col">
        <div class="info-box border border-warning">
            <div class="info-box-content">
                <span class="info-box-text">Pending</span>
                <span class="info-box-number">{{$pendingLeavesCount}}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box border border-success">
            <div class="info-box-content">
                <span class="info-box-text">Approved</span>
                <span class="info-box-number">{{$approvedLeavesCount}}</span>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="info-box border border-danger">
            <div class="info-box-content">
                <span class="info-box-text">Rejected</span>
                <span class="info-box-number">{{$rejectedLeavesCount}}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box border border-dark">
            <div class="info-box-content">
                <span class="info-box-text">Cancelled</span>
                <span class="info-box-number">{{$cancelledLeavesCount}}</span>
            </div>
        </div>
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


<div class="col-md-12">
    <div class="card">

        <div class="card-header">
            <div class="card-title"> List</div>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Leave Nature</th>
                            <th>From Date</th>
                            <th>Till Date</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Action</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaves as $leave)
                        <tr>


                            @if (request()->page == 1)
                            <td>{{ $loop->iteration }}</td>
                            @else
                            <td>{{ $loop->iteration + request()->page }}</td>
                            @endif


                            <td>{{ $leave->leaveType->name }}</td>
                            <td>{{ $leave->leave_nature }}</td>
                            <td>{{ getFormattedDate($leave->from_date) }}</td>
                            <td>{{ getFormattedDate($leave->to_date) }}</td>
                            <td>{{ $leave->duration." "}} {{$leave->duration>1 ? "Days" :'Day'}}</td>
                            @if($leave->status=="Approved")
                            <td><small class="badge badge-success"><i class="fas fa-check"></i> Approved</small></td>
                            @elseif($leave->status=="Rejected")
                            <td><small class="badge badge-danger"><i class="fas fa-times"></i> Rejected</small></td>
                            @elseif($leave->status =="Cancelled")
                            <td><small class="badge badge-dark"><i class="fa fa-window-close"></i> Cancelled</small></td>
                            @else
                            <td><small class="badge badge-warning"><i class="far fa-clock"></i> Pending</small></td>
                            @endif
                            <td><a href="{{route('leave.show', $leave)}}" class="btn btn-primary">Detail</a>
                                {{-- @if($leave->status=='Approved')
                                <button class="btn btn-danger text-white" @if($leave->leaveCancellation()) disabled @else onclick="alterLeave('{{ $leave->id }}')" @endif>Cancel</button>
                            @elseif($leave->status=='Pending')
                                <button class="btn btn-danger text-white" onclick="alterLeave('{{ $leave->id }}')">Cancel</button>
                            @endif --}}
                            </td>
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


<script>


$('#dateRangePicker-btn').daterangepicker({

opens: 'left',
locale: {
    cancelLabel: 'Clear'
},
ranges: {
    'Today': [moment(), moment()],
    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
    'Last 5 Days': [moment().subtract(4, 'days'), moment()],
    'Last 14 Days': [moment().subtract(13, 'days'), moment()],
    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
    'This Month': [moment().startOf('month'), moment().endOf('month')],
    'last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf(
        'month')]
},
startDate: moment().subtract(6, 'days'),
endDate  : moment()
},
function(start, end) {
$('#dateRangePicker-btn span').html(start.format('D/ M/ YY') + ' - ' + end.format('D/ M/ YY'))
$('#dateFrom').val(start.format('YYYY-M-DD'));
$('#dateTo').val(end.format('YYYY-M-DD'));
}
);
$('#dateRangePicker-btn').on('cancel.daterangepicker', function(ev, picker) {
clearDateFilters('dateRangePicker-btn', 'event');
});


</script>
@endsection
