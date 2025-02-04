@extends('layouts.master')
@section('header')
    <link href='{{ asset('adminlte/fullcalendar.min.css') }}' rel='stylesheet' />
@endsection
@section('content-header')
    <div class="row">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">Leave</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Leave</a></li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 text-md-right text-sm-left">
            @can('create', new App\Models\Leave())
            <a href="{{ route('leave.create') }}" class="btn btn-danger btn-sm mr-2">Apply Leave</a>
            <a href="{{ route('leave.index') }}" class="btn btn-success btn-sm mr-2">Leave History</a>
            @endcan
            @can('approval', new App\Models\Leave())
            <a href="{{ route('leave.request') }}" class="btn btn-info btn-sm mr-2"> Leave Request <span class="badge bg-gradient-light"> {{totalPendingLeaveRequestCount()}}</span></a>
            @endcan
        </div>
    </div>
@endsection
@section('content')

@if (auth()->user()->hasRole('Employee View - BLR') || auth()->user()->can('viewDetailCalendar', auth()->user()))

<div class="row">
    <div class="col-md-12">
        <div class="card">
          <form action="" method="get" id="date-form">
            <div class="card-body">
              <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::select('user_id', $users ?? [], request()->user_id, ['class' => 'form-control select2', 'id' => 'country', 'onchange' => 'this.form.submit()', 'placeholder' => 'Select Employee', 'data-placeholder' => 'Select Employee']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::text('emp_id', request()->emp_id, ['class' => 'form-control', 'placeholder' => 'Search Employee ID', 'autocomplete' => 'off']) !!}
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <div class="button-group">
                        <button type="submit" class="btn btn-success">Search</button>
                        <a href={{ request()->url() }} class="btn btn-primary">Clear Filter</a>
                    </div>
                </div>
              </div>
            </div>
          </form>
        </div>
    </div>
</div>
@endif
    <div class="row" id="balance-chart">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header text-white" style="background:#44318d">
                    <div class="card-title">Leave Dashboard</div>
                    <div class="card-title float-md-right bs-tooltip-right" id="calendar-name">{{ $user->name }} @isset($user->employee->emp_id) - {{ $user->employee->emp_id }} @endisset @if(!empty($user->employee->joining_date))  ({{ getFormattedDate($user->employee->joining_date, 'd-M-Y') ?? 'NA' }}) @endif</div>
                </div>
                <div class="card-body">
                    <div id='calendar'></div>
                </div>

            </div>
        </div>
    </div>
@endsection
@section('footerScripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.10.3/dist/fullcalendar.min.js"
        integrity="sha256-ffvzdyprWDmzu6FMDohWirF+ovgL0DCsJI8uPKiG+zU=" crossorigin="anonymous"></script>
    <script>
        $(window).on('load', function() {
            $.ajax({
                url: "{!! route('leave.dashboardData', http_build_query(request()->query())) !!}",
                type: 'GET',
                success: function(response) {
                    var employeeLeaves = response.employeeLeaves;
                    employeeLeaves = JSON.parse(employeeLeaves.replace(/&quot;/g, '"'));
                    customDate = response.customDate;
                    makeCalendar(customDate, employeeLeaves);
                    $('#balance-chart').find('#balance-form').remove();
                    $('#balance-chart').append(response.balanceChart);
                    // $('#calendar-name').html(response.user.name);
                }
            });
        });


        function makeCalendar(customDate, employeeLeaves) {
            $('#calendar').fullCalendar({
                header: {
                    left:   'title',
                    center: '',
                    right:  'today prev,next'
                },
                titleFormat: 'MMM YYYY',
                events: employeeLeaves,
                eventClick: function(event) {
                    if (event.url) {
                        var url = event.url.replace(/&amp;/g, '&');
                        window.open(url, "_blank");
                        return false;
                    }
                },
                eventRender: function(event, element) {
                    if(event.status=='Pending'){
                        element.find(".fc-title").prepend("<i class='fa fa-clock'></i>");
                    }
                    else if(event.status=='Approved')
                    {
                        element.find(".fc-title").prepend("<i class='fa fa-check'></i>");
                    }
                    @can('viewDetailCalendar', auth()->user())

                    if (event.description != '') {
                        $(element).tooltip({
                            title: "<b>Remarks : </b>"+ event.description,
                            html: true,
                            container: "body",
                        });
                    }
                    @endcan

                },
            });
            var tglCurrent = $('#calendar').fullCalendar('getDate');
            $('body').on('click', 'button.fc-prev-button', function() {
                var tglCurrent = $('#calendar').fullCalendar('getDate');
                var date = formatDate(tglCurrent._d);
                getLeaveBalance(date);

            });

            $('body').on('click', 'button.fc-next-button', function() {
                var tglCurrent = $('#calendar').fullCalendar('getDate');
                var date = formatDate(tglCurrent._d);
                getLeaveBalance(date);
            });
            $(".fc-today-button").click(function() {
                var date = moment().format('YYYY-MM-DD');
                getLeaveBalance(date);
            });

            function formatDate(str) {
                var date = new Date(str),
                    mnth = ("0" + (date.getMonth() + 1)).slice(-2),
                    day = ("0" + date.getDate()).slice(-2);
                return [date.getFullYear(), mnth, day].join("-");
            }

            function getLeaveBalance(date) {
                $.ajax({
                    url: "{!! route('leave.dashboardData', http_build_query(request()->query())) !!}",
                    type: 'GET',
                    data: {
                        date: date
                    },
                    success: function(response) {
                        $('#balance-chart').find('#balance-form').remove();
                        $('#balance-chart').append(response.balanceChart);
                        // $('#calendar-name').html(response.user.name);
                    }
                });
            }
        }
    </script>
@endsection
