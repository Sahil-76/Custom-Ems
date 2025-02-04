@extends('layouts.master')
@section('content')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"> Leaves</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                @can('create', new App\Models\Leave())
                    <a href="{{ route('leave.create') }}" class="btn btn-danger btn-sm mr-2">Apply Leave</a>
                    <a href="{{ route('leave.index') }}" class="btn btn-success btn-sm mr-2">Leave History</a>
                @endcan
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Leaves</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-4 stretch-card transparent">
            <div class="card card-tale">
                <div class="card-body">
                    <p class="mb-4 text-center"><i class="mdi mdi-account"></i> Total Pending</p>
                    <p class="fa-3x mb-2 text-center">{{ $pendingLeaves }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4 stretch-card transparent">
            <div class="card card-light-danger">
                <a style="color: white;"
                    href="{{ route('leave.request', array_merge(['waiting' => 1], request()->all())) }}">
                    <div class="card-body">
                        <p class="mb-4 text-center "><i class="fa fa-user-circle"></i> Waiting for you</p>
                        <p class="fa-2x mb-2 text-center"> {{ pendingLeaveRequestCount() }}</p>

                    </div>
                </a>
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
                        @if (empty(auth()->user()->employee))
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::label('manager_id', 'Select a Sales Manager') !!}
                                    {!! Form::select('manager_id', $managers, request()->manager_id ?? null, [
                                        'class' => 'form-control select2',
                                        'placeholder' => 'All',
                                    ]) !!}
                                </div>
                            </div>
                        @endif


                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('Select Employee', 'Select Employee') !!}
                                {!! Form::select('user', $users, request()->user ?? null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => 'All',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('biometric_id', 'Search Employee ID') !!}
                                {!! Form::text('biometric_id', request()->biometric_id ?? null, [
                                    'class' => 'form-control',
                                    'placeholder' => 'Employee ID',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('leave_type', 'Select Leave Type') !!}
                                {!! Form::select('leave_type', $leaveTypes, request()->leave_type ?? null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => 'All',
                                ]) !!}
                            </div>
                        </div>


                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('status', 'Status') !!}
                                {!! Form::select('status[]', $status, request()->status ?? null, [
                                    'class' => 'form-control select2',
                                    'multiple',
                                    'data-placeholder' => 'Select Status',
                                ]) !!}
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
                        <div class="col-md-6">
                            <button class="btn btn-primary" type="submit">Filter</button>
                            <a href="{{ request()->url() }}" class="btn btn-success">Clear Filter</a>
                        </div>
                        <div class="col-md-6">
                            @can('export', new App\Models\Employee())
                                <a href="{{ route('leave.export', http_build_query(request()->query())) }}" target="_blank"
                                    class="btn btn-primary float-right"> <i class="fa fa-file-excel"></i> Export
                                </a>
                            @endcan
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">

                <div class="card-header">
                    <div class="card-title">Leave Requests</div>
                    <div class="card-tools">Total: {{ $leaves->total() }} records</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Type</th>
                                    <th>Leave Nature</th>
                                    <th>From Date</th>
                                    <th>Till Date</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $serial = ($leaves->currentPage() - 1) * $leaves->perPage();
                                @endphp
                                @foreach ($leaves as $leave)
                                    @php
                                        $responsibleArray = $leave->responsiblePerson();

                                    @endphp
                                    {{-- @dd($responsibleArray) --}}
                                    <tr @if ($leave->status == 'Rejected') class="bg-red" @endif>
                                        <td>{{ ++$serial }}</td>

                                        <td>{{ $leave->user->name }}</td>
                                        <td>{{ $leave->user->user_type }}</td>

                                        <td>{{ $leave->leaveType->name ?? '' }}</td>
                                        <td>{{ $leave->leave_session }}</td>

                                        <td>{{ getFormattedDate($leave->from_date) }}</td>
                                        <td>{{ getFormattedDate($leave->to_date) }}</td>
                                        <td>{{ $leave->duration }} {{ Str::plural('Day', $leave->duration) }}</td>
                                        <td>
                                            @if ($leave->status == 'Pending')
                                                {{ $leave->status . ' by ' . $leave->pending_approval_role }}
                                            @else
                                                {{ $leave->status }}
                                            @endif
                                        </td>


                                        <td>

                                            @if (auth()->user()->hasRole($responsibleArray['approval_role']) &&
                                                    $leave->status == 'Pending' &&
                                                    !empty($responsibleArray['approval_user']->email) == auth()->user()->email)
                                                <a href="{{ route('leave.show', $leave->id) }}"
                                                    class="btn btn-primary">Action</a>
                                            @elseif(auth()->user()->hasRole('Leave Admin') && $responsibleArray['approval_role'] == 'Leave Admin')
                                                <a href="{{ route('leave.show', $leave->id) }}"
                                                    class="btn btn-primary">Action</a>
                                            @elseif(auth()->user()->hasRole('Manager') &&
                                                    $leave == 'Pending' &&
                                                    $responsibleArray['approval_role'] == 'Manager')
                                                <a href="{{ route('leave.show', $leave->id) }}"
                                                    class="btn btn-primary">Action</a>
                                            @else
                                                <a href="{{ route('leave.show', $leave->id) }}"
                                                    class="btn btn-warning">Detail</a>
                                            @endif

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row float-right">
                        <div class="col-md-12">
                            {{ $leaves->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
@section('footerScripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
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
                endDate: moment()
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