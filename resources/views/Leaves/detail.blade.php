@extends('layouts.master')
@section('content-header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">Leave Details</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                {{-- @can('create', new App\Models\Leave()) --}}
                <a href="{{ route('leave.create') }}" class="btn btn-danger btn-sm mr-2">Apply Leave</a>
                <a href="{{ route('leave.index') }}" class="btn btn-success btn-sm mr-2">Leave History</a>
                {{-- @endcan
                @can('approval', new App\Models\Leave()) --}}
                <a href="{{ route('leave.request') }}" class="btn btn-info btn-sm mr-2"> Leave Request <span class="badge bg-gradient-light"> {{totalPendingLeaveRequestCount()}}</span></a>
                {{-- @endcan --}}
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Leave</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </div>
    </div>
@endsection
@section('content')
@php
    $user       = $leave->user;
    $employee   = $user->employee;
    $canApprove = false;
    $currentUser = auth()->user();
    $responsibleArray = $leave->responsiblePerson();

    if($currentUser->hasRole($responsibleArray['approval_role'])  &&  !empty($responsibleArray['approval_user']->email) && $responsibleArray['approval_user']->email==$currentUser->email && $leave->canApprove()){
        $canApprove = true;
    }elseif( $currentUser->hasRole('Leave Admin') && $responsibleArray['approval_role']=="Leave Admin"){
        $canApprove = true;
    }elseif($currentUser->hasRole('Sales Senior Manager') && $responsibleArray['approval_role']=="Sales Senior Manager" && $leave->canApprove()){
        $canApprove = true;
    }

    $authenticated = false;
    if ($currentUser->id != $leave->user_id) {
        $authenticated = true;
    }

    if($leave->status == 'Approved'){
        $badge      = 'badge-success';
        $icon       = 'fas fa-check';
    }elseif($leave->status == 'Pending'){
        $badge      = 'badge-warning';
        $icon       = 'far fa-clock';
    }elseif($leave->status == 'Rejected'){
        $badge      = 'bg-danger';
        $icon       = 'fas fa-times';
    }
@endphp
@if ($authenticated)
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header text-white" style="background:#44318d">
                <div class="card-title">Employee Details</div>
            </div>
            <div class="card-body">
                <div class="row">
                    <li class="detail-list col-md-6"> Name   <span>{{ $user->name }} @isset($employee->emp_id) ({{ $employee->emp_id }}) @endisset</span> </li>
                    {{-- <li class="detail-list col-md-6"> Joining Date <span>{{ getFormattedDate($employee->joining_date) }}</span> </li> --}}
                    <li class="detail-list col-md-6"> Email  <span>{{ $user->email }}</span> </li>
                    <li class="detail-list col-md-6"> Phone  <span>{{ $employee->mobile }}</span> </li>
                    <li class="detail-list col-md-6"> Team Leader<span>{{ $employee->teamLeader->name ?? null }}</span> </li>
                    <li class="detail-list col-md-6"> Manager<span>{{ $employee->manager->name ?? null }}</span> </li>
                    <li class="detail-list col-md-6">Senior Manager<span>{{ $employee->seniorManager->name ?? null }}</span> </li>
                    <li class="detail-list col-md-6"> Region<span>{{ $employee->region->name ?? '' }}</span> </li>
                    {{-- <li class="detail-list col-md-6"> Team<span>{{ $employee->team }}</span> </li> --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endif
    <div class="row">
        <div class="col-md-7">
            <div class="card card-light">
                <div class="card-header">
                    <h3 class="card-title">Leave Details</h3>
                    <div class="card-tools">
                        @if($leave->canCancel())
                        @php
                            $leaveCancelUrl = route('leave.cancel', $leave->id);
                        @endphp
                        <a href="#" class="btn btn-danger btn-sm text-white" onclick="deleteItem('{{ $leaveCancelUrl }}')">Mark Cancelled</a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <li class="detail-list col-md-12 border-bottom"> Leave Type <span>{{ $leave->leaveType->name }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom"> Session <span>{{ $leave->leave_nature }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom"> Timings <span>{{ $leave->timing }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom"> From Date <span>{{ formatDate($leave->from_date) }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom"> To Date <span>{{ formatDate($leave->to_date) }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom"> Applied At <span>{{ getDateTime($leave->created_at) }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom"> Status <span>{{ $leave->status }}</span> </li>
                        @if(!empty($leave->attachment))
                        <li class="detail-list col-md-12"> Attachment
                            <span><a
                                href="{{url("/leave-document/download/$leave->user_id/$leave->attachment")}}"
                                class="btn btn-outline-info btn-sm"><i class="fa fa-file-download"></i></a></span>
                        </li>
                        @endif
                        <li class="detail-list col-md-12"> Reason <span>{{$leave->reason}}</span></li>
                    </div>
                </div>
            </div>
            @if($canApprove && $leave->status != 'Rejected' || !empty($leave->remarks))
                <div class="card">
                    <div class="card-body">
                        {{ Form::open() }}
                        {{ Form::hidden('leave_id', $leave->id) }}
                            <div class="form-group">
                                <label for="">Remarks</label>
                                <textarea name="remarks" class="form-control" id="remarks-field" @if(!empty($leave->remarks)) disabled @endif>{{$leave->remarks ?? ''}}</textarea>
                            </div>
                            @if ($canApprove && $leave->status != 'Rejected')
                            <div class="col-md-12">
                                <button type="submit" name="action" value="Approved" class="btn btn-success leave-action">Approve</button>
                                <button type="submit" name="action" value="Rejected" class="btn btn-danger leave-action">Reject</button>
                            </div>
                            @endif
                        {{Form::close()}}
                    </div>
                </div>
            @endif
        </div>

        @if($authenticated)
        <div class="col-md-5">
            <div class="row">
                <a class="btn btn-app bg-secondary col py-1">
                    <span class="text-lg d-block">{{ $leaveBalance->allowance ?? 0 }}</span>
                    Allowance
                </a>
                <a class="btn btn-app bg-secondary col py-1">
                     <span class="text-lg d-block">{{ $leaveBalance->previous_balance ?? 0 }}</span>
                    Prev. Bal
                </a>
                <a class="btn btn-app bg-secondary col py-1">
                     <span class="text-lg d-block">{{ $leaveBalance->taken_leaves ?? 0 }}</span>
                    Taken
                </a>
                <a class="btn btn-app bg-secondary col py-1">
                     <span class="text-lg d-block">{{ $leaveBalance->waiting ?? 0 }}</span>
                    Waiting
                </a>
                <a class="btn btn-app bg-secondary col py-1">
                     <span class="text-lg d-block">{{ $leaveBalance->final_balance ?? 0 }}</span>
                    Balance
                </a>
            </div>

            <div class="card card-light" style="min-height:30rem">
                <div class="card-header">
                    <div class="card-title">
                        Leave Logs
                    </div>
                </div>
                <div class="card-body">
                    <div class="timeline mt-3">
                        @foreach ($leave->approvalStatus as $leaveLog)
                        @php
                            if($leaveLog->action == 'Approved'){
                                $bgColor    = 'bg-green';
                                $icon       = 'fas fa-check';
                            }elseif($leaveLog->action == 'Pending'){
                                $bgColor    = 'bg-warning';
                                $icon       = 'far fa-clock';
                            }elseif($leaveLog->action == 'Rejected'){
                                $bgColor    = 'bg-danger';
                                $icon       = 'fas fa-times';
                            }

                            $leaveStage = $leaveLog->role($leaveLog->stage);

                        @endphp
                            <div class="time-label">
                                <span class="bg-red">{{ $leaveStage }}</span>
                            </div>
                            <div>
                                <i class="{{$icon}} {{ $bgColor }}"></i>

                                <div class="timeline-item {{ $bgColor }}">
                                    <span class="time  text-white"><i class="fas fa-clock"></i> {{ getDateTime($leaveLog->updated_at) }}</span>
                                    <h3 class="timeline-header  text-white">
                                        @if(!empty($leaveLog->actionBy))
                                            @if ($leaveStage == 'Leave Admin' && auth()->user()->cannot('manageLeaveType', $leave))
                                            {{ $leaveLog->action. ' by HR Department'}}
                                            @else
                                            {{ $leaveLog->action. ' by '.$leaveLog->actionBy->name}}

                                            @endif

                                        @else
                                        Pending
                                        @endif
                                    </h3>
                                    @if(!empty($leaveLog->remarks))
                                    <div class="timeline-body">
                                        Remarks: {{ $leaveLog->remarks }}
                                    </div>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        @endif
    </div>
    {{-- @can('approval',new App\Models\Leave() ) --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Employees on Leave on same period </div>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-striped data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>From Date</th>
                                    <th>Till Date</th>
                                    <th>Type</th>
                                    <th>Session</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($onLeaveToday as $employeeLeave)
                                <tr>
                                    @if (request()->page == 1)
                                        <td>{{ $loop->iteration }}</td>
                                    @else
                                        <td>{{ $loop->iteration + request()->page }}</td>
                                    @endif

                                    <td>{{ $employeeLeave->user->name }}</td>
                                    <td>{{ $employeeLeave->user->designation }}</td>
                                    <td>{{ $employeeLeave->from_date }}</td>
                                    <td>{{ $employeeLeave->to_date }}</td>
                                    <td>{{ $employeeLeave->leaveType->name }}</td>
                                    <td>{{ $employeeLeave->leave_nature }}</td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- @endcan --}}
@endsection
@section('footerScripts')
<script>

    $('.leave-action').on('click', function() {
            var action = $(this).val();
            if (action == 'Rejected') {
                var remarks = $(this).closest('.action').siblings('.remarks').find('#remarks-field').val();
                if (remarks == '') {
                    alert('Remarks Required');
                    return false;
                }
            }
            $(this).html('Please wait').append(
                '<i class="mdi mdi-rotate-right mdi-spin ml-1" aria-hidden="true"></i>');

            $('form').on('submit', function() {
                event.preventDefault();
                var formData = $(this).serialize() + "&action=" + action;
                $(this).find('.action button').attr('disabled', true);
                var link = "{{ route('leaveAction') }}";
                $.ajax({
                    url: link,
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': $("meta[name='csrf-token']").attr('content')
                    },
                    data: formData,
                    success: function(response) {
                        toastr.info(response);
                        location.reload();
                    },
                    error: function(error) {
                        alert('something went wrong');
                    }
                });
            });
        });
</script>
<script type="text/javascript">
    $('.data-table').dataTable({});
</script>
@endsection
