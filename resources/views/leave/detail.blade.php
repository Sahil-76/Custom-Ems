@extends('layouts.master')
@section('headerLinks')
    <style>
        
        .modal-content {
            width: 100% !important;
        }

        .card.card-tale,
        .card.card-tale:hover {
            background-color: white;
        }
        .card.card-tale{
            padding: 0;
        }

        .card .card-title {
            margin-bottom: 0;
            line-height: 1;
        }
        .card-header{
            background-color: transparent;
        }

        .timeline {
            z-index: 1;
        }

        .timeline::before {
            z-index: -1;
            left: 36px;
            background-color: #dee2e6;
        }

        .time-label {
            display: flex;
        }

        .time-label span {
            background-color: #dc3545 !important;
            border-radius: 4px;
            font-weight: 600;
            padding: 5px;
            font-size: 12px;
        }

        .time-container {
            display: flex;
            margin: 15px 0;
            align-items: center;
            justify-content: space-between;
            margin-left: 20px;
        }

        .time-container >i {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            /* background-color: #ffc107 !important; */
            border-radius: 50%;
            color: black;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            flex-direction: row-reverse;
            justify-content: space-between;
            width: calc(100% - 45px);
            border-bottom: 1px solid rgba(0, 0, 0, .125);
            padding: 10px;
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
            border-radius: 0.25rem;
        }

        .timeline-header {
            font-size: 14px;
            margin-bottom: 0;
        }

        .timeline-item .time {
            display: flex;
            align-items: center;
            font-size: 12px;
        }

        .timeline-item .time i {
            width: 12px;
            height: 12px;
            margin-right: 4px;
        }
    </style>
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
    }elseif($currentUser->hasRole('Manager') && $responsibleArray['approval_role']=="Manager" && $leave->canApprove()){
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
                <div class="card-title mb-0 text-white" style="font-size: 14px;">Employee Details</div>
            </div>
            <div class="card-body">
                <div class="row justify-content-between" style="font-size: 12px; font-weight: 600;">
                    <li class="detail-list col-md-5 d-flex justify-content-between p-2"> Name   <span style="font-weight: 500;">{{ $user->name }} @isset($employee->emp_id) ({{ $employee->emp_id }}) @endisset</span> </li>
                    {{-- <li class="detail-list col-md-6"> Joining Date <span>{{ getFormattedDate($employee->joining_date) }}</span> </li> --}}
                    <li class="detail-list col-md-5 d-flex justify-content-between p-2"> Email  <span style="font-weight: 500;">{{ $user->email }}</span> </li>
                    <li class="detail-list col-md-5 d-flex justify-content-between p-2"> Phone  <span style="font-weight: 500;">{{ $employee->phone }}</span> </li>
                    <li class="detail-list col-md-5 d-flex justify-content-between p-2"> Team Leader<span style="font-weight: 500;">{{ $employee->teamLeader->name ?? null }}</span> </li>
                    <li class="detail-list col-md-5 d-flex justify-content-between p-2"> Manager<span style="font-weight: 500;">{{ $employee->manager->name ?? null }}</span> </li>
                    <li class="detail-list col-md-5 d-flex justify-content-between p-2">Senior Manager<span style="font-weight: 500;">{{ $employee->lineManager->name ?? null }}</span> </li>
                    {{-- <li class="detail-list col-md-5 d-flex justify-content-between p-2"> Region<span style="font-weight: 500;">{{ $employee->region->name ?? '' }}</span> </li> --}}
                    {{-- <li class="detail-list col-md-6"> Team<span>{{ $employee->team }}</span> </li> --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endif
    <div class="row mt-4">
        <div class="col-md-7">
            <div class="card card-light">
                <div class="card-header">
                    <h3 class="card-title mb-0">Leave Details</h3>
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
                    <div class="row " style="font-weight: 600; font-size: 12px;">
                        <li class="detail-list col-md-12 border-bottom d-flex justify-content-between p-2"> Leave Type <span>{{ $leave->leaveType->name }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom d-flex justify-content-between p-2"> Session <span>{{ $leave->leave_session }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom d-flex justify-content-between p-2"> Timings <span>{{ $leave->timing }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom d-flex justify-content-between p-2"> From Date <span>{{ formatDate($leave->from_date) }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom d-flex justify-content-between p-2"> To Date <span>{{ formatDate($leave->to_date) }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom d-flex justify-content-between p-2"> Applied At <span>{{ getDateTime($leave->created_at) }}</span> </li>
                        <li class="detail-list col-md-12 border-bottom d-flex justify-content-between p-2"> Status <span>{{ $leave->status }}</span> </li>
                        @if(!empty($leave->attachment))
                        <li class="detail-list col-md-12 d-flex justify-content-between p-2"> Attachment
                            <span><a
                                href="{{url("/leave-document/download/$leave->user_id/$leave->attachment")}}"
                                class="btn btn-outline-info btn-sm"><i class="fa fa-file-download"></i></a></span>
                        </li>
                        @endif
                        <li class="detail-list col-md-12 d-flex justify-content-between p-2"> Reason <span>{{$leave->reason}}</span></li>
                    </div>
                </div>
            </div>
            {{-- @if($canApprove && $leave->status != 'Rejected' || !empty($leave->remarks)||$leave->status != 'Cancelled') --}}
            @if(($canApprove && $leave->status != 'Rejected' && $leave->status != 'Cancelled') || !empty($leave->remarks))
                <div class="card mt-4">
                    <div class="card-body">
                        {{ Form::open() }}
                        {{ Form::hidden('leave_id', $leave->id) }}
                            <div class="form-group">
                                <label for="">Remarks</label>
                                <textarea name="remarks" class="form-control" id="remarks-field" @if(!empty($leave->remarks)) disabled @endif>{{$leave->remarks ?? ''}}</textarea>
                            </div>
                            @if ($canApprove && $leave->status != 'Rejected')
                            <div class="col-md-12 p-0">
                                <button type="submit" name="action" value="Approved" class="btn btn-success leave-action p-2 rounded">Approve</button>
                                <button type="submit" name="action" value="Rejected" class="btn btn-danger leave-action p-2 rounded">Reject</button>
                            </div>
                            @endif
                        {{Form::close()}}
                    </div>
                </div>
            @endif
        </div>

        @if($authenticated)
        <div class="col-md-5">
            {{-- <div class="row">
                <a class="btn btn-app bg-primary col py-1 mx-1">
                    <p class="text-light">Taken</p>
                    <span class="text-light d-block">{{ $totalApprovedLeaves ?? 0 }}</span>
                </a>
                <a class="btn btn-app bg-primary col py-1 mx-1">
                    <p class="text-light">Waiting</p>
                    <span class="text-light d-block">{{ $leaveBalance->waiting ?? 0 }}</span>
                </a>
                <a class="btn btn-app bg-primary col py-1 mx-1">
                    <p class="text-light">Balance</p>
                    <span class="text-light d-block">{{ $leaveBalance->final_balance ?? 0 }}</span>
                </a>
            </div> --}}


            <div class="card card-tale mt-3" style="min-height:30rem">
                <div class="card-header">
                    <div class="card-title">
                        Leave Logs
                    </div>
                </div>
                @if($leave->status !=='Cancelled')
                <div class="card-body">
                    <div class="timeline mt-3">
                        @foreach ($leave->approvalStatus as $leaveLog)
                        @php
                            if($leaveLog->action == 'Approved'){
                                $bgColor    = 'bg-success text-light';
                                $icon       = 'fas fa-check';
                            }elseif($leaveLog->action == 'Pending'){
                                $bgColor    = 'bg-warning';
                                $icon       = 'far fa-clock';
                            }elseif($leaveLog->action == 'Rejected'){
                                $bgColor    = 'bg-danger text-light';
                                $color      = 
                                $icon       = 'fas fa-times';
                            }

                            $leaveStage = $leaveLog->role($leaveLog->stage);

                        @endphp
                        
                            <div class="time-label">
                                <span class="bg-red">{{ $leaveStage }}</span>
                            </div>
                            <div class="time-container ">
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
                @else
                <div class="card-body">
                 <div class="timeline mt-3">
                <div class="time-label">
                    <span class="bg-red">Employee</span>
                </div>
                <div class="time-container ">
                    <i class="fas fa-times bg-danger text-light"></i>

                    <div class="timeline-item bg-danger">
                        <span class="time  text-white"></span>
                        <h3 class="timeline-header  text-white">
                           Leave is Cancelled By Employee
                        </h3>
                    </div>

                </div>
                 </div>
                </div>
             @endif
            </div>

        </div>

        @endif
    </div>
    {{-- @can('approval',new App\Models\Leave() ) --}}
    <div class="row">
        <div class="col-md-12 mt-3">
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
                                    <td>{{ $employeeLeave->user->user_type }}</td>
                                    <td>{{ $employeeLeave->from_date }}</td>
                                    <td>{{ $employeeLeave->to_date }}</td>
                                    <td>{{ $employeeLeave->leaveType->name }}</td>
                                    <td>{{ $employeeLeave->leave_session }}</td>

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