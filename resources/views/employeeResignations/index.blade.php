@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item "><a href="{{ route('resignations.index') }}">Resignations</a></li>
                </ol>
            </nav>
        </div>
        <div class="col-12 mb-3">

            <div class="row">
                <div class="col-12">
                    <div class="card">
    
                        {{ Form::open(['method' => 'GET']) }}
                        {{-- {{ Form::open(['route' => $data['submitRoute'], 'method' => $data['GET']]) }} --}}
                        <div class="card-body">
                            <p class="card-title">Filter</p>
                            <div class="form-group row">
                                {{ Form::label('department_id', 'Select Department', ['class' => 'col-sm-2 col-form-label']) }}
                             <div class="col-sm-4">
                              {{ Form::select('department_id', $data['department_id'], request()->department_id, ['onchange'=>'getEmployees(this.value)', 'class' => 'form-control selectJS', 'placeholder' => 'Select your department']) }}

                            </div>
                                
                              {{ Form::label('office_email', 'Select Email', ['class' => 'col-sm-2 col-form-label']) }}
                                <div class="col-sm-4">
                                    {{ Form::select('office_email', $data['office_emails'], request()->office_email, ['class' =>
                                    'form-control selectJS','id'=>'emails' ,'placeholder' => 'Select your email']) }}
                                </div>
                                
                                {{ Form::label('gender', 'Select Gender', ['class' => 'col-sm-2 col-form-label']) }}
                                <div class="col-sm-4">
                                    {{ Form::select('gender', $data['gender'], request()->gender, ['class' => 'form-control selectJS', 'placeholder' => 'Select Gender']) }}
                                </div>


                                {{ Form::label('shift_type', 'Select Shift Type', ['class' => 'col-sm-2 col-form-label']) }}
                                <div class="col-sm-4">
                                    {{ Form::select('shift_type', $data['shift_types'], request()->shift_type, ['class' => 'form-control selectJS', 'placeholder' => 'Select Shift Type']) }}
                                </div>    
                                {{-- {{ Form::label('user_id', 'Select Employee', ['class' => 'col-sm-2 col-form-label']) }} --}}
                                {{-- <div class="col-sm-4">
                                    <select style='width:100%;' name="user_id" data-placeholder="select an option"
                                        id="employees" placeholder="select an option" class='form-control selectJS'>
                                        <option value="" disabled selected>Select your option</option>
                                        @foreach ($employeeDepartments as $department => $employee)
                                        <optgroup label="{{ $department }}">
                                            @foreach ($employee as $user)
                                            <option value="{{$user->user_id}}" @if($user->user_id == request()->user_id)
                                                selected @endif>{{$user->name.' ('.$user->biometric_id.')'}}</option>
                                            @endforeach
                                        </optgroup>
                                        @endforeach
                                    </select>
                                </div> --}}

                    
                                <div class="col-md-6">
                                    {{ Form::submit('Filter', ['class' => 'btn m-2 btn-primary']) }}
                                    <a href="{{ request()->url() }}" class="btn m-2 btn-success">Clear Filter</a>
                                </div>
                            {{ Form::close() }}


                            <div class="form-group">
                                        <input id="dateFrom" name="dateFrom" type="hidden">
                                        <input id="dateTo" name="dateTo" type="hidden">
                                        <button type="button" id="date-btn" class="btn btn-sm btn-primary mt-2"
                                            style="width:185px">
                                            @if (!empty(request()->get('dateFrom')) && !empty(request()->get('dateTo')))
    
                                            <span>
    
                                                {{ Carbon\Carbon::parse(request()->get('dateFrom'))->format('d/m/y') }} -
    
                                                {{ Carbon\Carbon::parse(request()->get('dateTo'))->format('d/m/y') }}
    
                                            </span>
    
                                            @else
                                            <span>
                                                <i class="fa fa-calendar"></i> &nbsp;Select Date&nbsp;
                                            </span>
                                            @endif
                                            <i class="fa fa-caret-down"></i>
                                        </button>
                                    </div>

                
                            {{-- @can('hrUpdateEmployee', new App\Models\Employee())
                            <div class="col-md-6">


                                <a href="{{ route('exportEmployee',request()->query()) }}"
                                    class="btn m-2 float-right btn-primary">Export</a>
                                <a href="{{ route('createEmployee') }}" class="btn m-2 float-right btn-success">Add new
                                    Record <i class=""></i></a>
                            </div>
                            @endcan --}}

                        </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class=" rounded h-100 p-4">
                        <div class="table-responsive mt-3">
                            <table id="data-table" class="table table-bordered table-hover dataTable dtr-inline w-100"
                                aria-describedby="example2_info">
                                <thead>
                                    <tr style="background-color: #4B49AC; color: white;">
                                        <th colspan="8" class="text-center">Employee Resignation</th>
                                    </tr>
                                    <tr>
                                        <th style="min-width: 124px;">User</th>
                                        <th style="min-width: 124px;">Department</th>
                                        <th style="min-width: 124px;">Reason</th>
                                        <th style="min-width: 124px;">Action By</th>
                                        <th style="min-width: 124px;">Leaving Date</th>
                                        <th style="min-width: 124px;">Is Exit</th>
                                        <th style="min-width: 124px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- @php
                                        $serialNo = 1;
                                    @endphp --}}
                                    {{-- @foreach ($resignations as $resignation)
                                        <tr>
                                            <td>{{ $resignation->user->name }}</td>
                                            <td>{{ $resignation->user->employee->department->name }}</td>
                                            <td>{{ $resignation->reason->name ?? null }}</td>
                                            <td>
                                                @if ($resignation->action_by)
                                                    {{ $resignation->actionByUser->name }}
                                                @else
                                                    Unknown User
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($resignation->leaving_date)->format('d-m-y') }}
                                            </td>
                                            <td>{{ $resignation->is_exit ? 'Yes' : 'No' }}</td>
                                            <td>
                                                <a
                                                    href="{{ route('resignations.edit', ['resignation' => $resignation->id]) }}">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="post"
                                                    action="{{ route('resignations.destroy', ['resignation' => $resignation->id]) }}"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn"><i class="fas fa-trash-alt"
                                                            style="cursor: pointer; color:red;"
                                                            onclick="return confirm('Are you sure you want to delete this resignation?')"></i></button>
                                                </form>
                                            </td>

                                        </tr>
                                    @endforeach --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('footerScripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        $(document).ready(function() {
            var dataTable = $('#data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{!! route('resignations.index') !!}",
                columns: [{
                        data: 'user_name',
                        name: 'user_name'
                    },
                    {
                        data: 'department_name',
                        name: 'department_name'
                    },
                    {
                        data: 'resson_name',
                        name: 'resson_name'
                    },
                    {
                        data: 'actionByUser_name',
                        name: 'actionByUser_name'
                    },
                    {
                        data: 'leaving_date',
                        name: 'leaving_date'
                    },
                    {
                        data: 'is_exit',
                        name: 'is_exit'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ]
            });

            $('#data-table').on('click', '.delete-btn', function() {
                var data = dataTable.row($(this).closest('tr')).data();
                var resignationId = data.id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteForm' + resignationId).submit();
                    }
                });

                return false;
            });
        });
    </script>
<script>
    $('body').addClass('sidebar-icon-only');
    $('#date-btn').daterangepicker({
                opens: 'left',
                locale: {
                    cancelLabel: 'Clear'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 5 Days': [moment().subtract(4, 'days'), moment()],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 14 Days': [moment().subtract(13, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf(
                        'month')],
                },
                // startDate: moment().subtract(29, 'days'),
                //endDate  : moment()
            },
            function(start, end) {
                $('#date-btn span').html(start.format('D/ M/ YY') + ' - ' + end.format('D/ M/ YY'))
                $('#dateFrom').val(start.format('YYYY-M-DD'));
                $('#dateTo').val(end.format('YYYY-M-DD'));
                $('#date-form').closest('form').submit();
            }
        );

        $('#date-btn').on('cancel.daterangepicker', function(ev, picker) {
            clearDateFilters('date-btn', 'date');
            $('#date-form').closest('form').submit();
        });

        function clearDateFilters(id, inputId) {
            $('#' + id + ' span').html('<span> <i class="fa fa-calendar"></i>  &nbsp;Select Date&nbsp;</span>')
            $('#' + inputId + 'From').val('');
            $('#' + inputId + 'To').val('');
        }
   

    </script>

@endsection
