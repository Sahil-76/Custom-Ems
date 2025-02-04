@extends('layouts.master')
@section('header')
    <style>
        .custom-font-size {
            font-size: 12px;
        }
    </style>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="" method="get" id="date-form">
                        <div class="row">
                            {{-- <div class="col-md-3 form-group">
                                {!! Form::select('region[]', $regions ?? [], request()->region ?? null, ['class' => 'form-control select2', 'multiple' => 'multiple', 'onchange' => 'this.form.submit()', 'data-placeholder' => 'Select Region']) !!}
                            </div> --}}
                            <div class="col-md-6">
                                <div class="form-group float-lg-left">
                                    {{ Form::hidden('dateFrom', request()->dateFrom ?? null, ['id' => 'dateFrom', 'onchange' => 'this.form.submit()']) }}
                                    {{ Form::hidden('dateTo', request()->dateTo ?? null, ['id' => 'dateTo']) }}
                                    <button type="button" id="ticketDateRange-btn" class="btn btn-block btn-primary"
                                        id="daterange-btn">
                                        <span>
                                            <i class="fa fa-calendar"></i>
                                                {{ getFormattedDate($dateFrom) }} - {{ getFormattedDate($dateTo) }}
                                        </span>
                                        <i class="fa fa-caret-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="float-lg-right">
                                    <a href="{{ route('ticket.dashboard') }}" class="btn btn-dark">Clear Filter</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
    </div>
{{-- 
    <div class="row">
        <div class="col">
            <a href="{!! route('ticket.dashboard', ['branch' => 'Bengaluru', 'dateFrom' => request()->dateFrom, 'dateTo' => request()->dateTo]) !!}">
                <div class="info-box border border-dark @if (request()->branch == 'Bengaluru' || empty(request()->branch)) bg-primary @endif">
                    <div class="info-box-content">
                        <span class="info-box-text">Bengaluru</span>
                        <span class="info-box-number">
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{!! route('ticket.dashboard', ['branch' => 'Jalandhar', 'dateFrom' => request()->dateFrom, 'dateTo' => request()->dateTo]) !!}">
                <div class="info-box border border-dark @if (request()->branch == 'Jalandhar') bg-primary @endif">
                    <div class="info-box-content">
                        <span class="info-box-text">Jalandhar</span>
                        <span class="info-box-number">
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
            </a>
            <!-- /.info-box -->
        </div>
        <div class="col">
            <a
                href="{{ route('ticket.dashboard', ['branch' => ['Jalandhar', 'Bengaluru'], 'dateFrom' => request()->dateFrom, 'dateTo' => request()->dateTo]) }}">
                <div class="info-box border border-dark @if (is_array(request()->branch)) bg-primary @endif">

                    <div class="info-box-content">
                        <span class="info-box-text">Total</span>
                        <span class="info-box-number">
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
            </a>
            <!-- /.info-box -->
        </div>
    </div> --}}

    <div class="row">
        <div class="col mb-4 stretch-card transparent">
            <div class="card card-tale">
                <a style="color: white;" href="{{ route('loadTickets', ['status' => 'total']) }}">
                    <div class="card-body">
                        <p class="mb-4"><i class="mdi mdi-ticket-outline"></i> Total</p>
                        <p class="fa-3x mb-2">{{ $total }}</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="col mb-4 stretch-card transparent">
            <div class="card card-dark-blue">
                <a style="color: white;" href="{{ route('loadTickets', ['status' => 'new']) }}">
                    <div class="card-body">
                        <p class="mb-4"><i class="mdi mdi-ticket-outline"></i> New</p>
                        <p class="fa-3x mb-2">{{ $new }}</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="col mb-4 stretch-card transparent">
            <div class="card card-tale">
                <a style="color: white;" href="{{ route('loadTickets', ['status' => 'needResponseded']) }}">
                    <div class="card-body">
                        <p class="mb-4"><i class="mdi mdi-ticket-outline"></i> Need Responseded</p>
                        <p class="fa-3x mb-2">{{ $needResponse }}</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="col mb-4 stretch-card transparent">
            <div class="card card-light-blue">
                <a style="color: white;" href="{{ route('loadTickets', ['status' => 'responded']) }}">
                    <div class="card-body">
                        <p class="mb-4"><i class="mdi mdi-ticket-outline"></i> Responded</p>
                        <p class="fa-3x mb-2">{{ $responded }}</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="col mb-4 stretch-card transparent">
            <div class="card card-light-danger">
                <a style="color: white;" href="{{ route('loadTickets', ['status' => 'waitingOnDecision']) }}">
                    <div class="card-body">
                        <p class="mb-4"><i class="mdi mdi-ticket-outline"></i> Waiting On Decision</p>
                        <p class="fa-3x mb-2">{{ $waitingOnDecision }}</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="col mb-4 stretch-card transparent">
            <div class="card card-tale">
                <a style="color: white;" href="{{ route('loadTickets', ['status' => 'closed']) }}">
                    <div class="card-body">
                        <p class="mb-4"><i class="mdi mdi-ticket-outline"></i> Closed</p>
                        <p class="fa-3x mb-2">{{ $closed }}</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-7">
            <div class="card">
                <div class="card-body">
                    <canvas id="chart1"></canvas>
                </div>
            </div>
        </div>

        <div class="col-sm-5">
            <div class="card" style="min-height: 490px;">
                <div class="card-body">
                    <canvas id="chart2"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2" >
        <div class="col-md-12" style="background-color: white;">
            <div class="card">
                <div class="col-md-12 mt-4">
                    <div class="card-title">Tickets Count</div>
                </div>
                <div class="card-body">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table data-table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="custom-font-size" style="vertical-align: middle">#</th>
                                        @foreach ($userTickets['types'] as $typeName)
                                            <th class="custom-font-size" style="vertical-align: middle">{{ $typeName }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                  
                                    {{-- @foreach ($userTickets['tickets'] as $tickets)
                                        <tr>
                                            @foreach ($tickets as $type => $count)
                                                <td class="custom-font-size text-center">{{ $count }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach --}}
                                    @foreach ($userTickets['tickets'] as $tickets)
                                        <tr>
                                            @foreach ($tickets as $type => $count)
                                                <td class="custom-font-size text-center">
                                                    @if ($type== 'name')
                                                        
                                                    {{ $count }}
                                                    @else
                                                        
                                                    <a href="{{ route('loadTickets', ['type_name' => $type]) }}">
                                                        {{ $count }}
                                                    </a>
                                                    @endif
                                                    
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                                {{-- <tfoot>
                <tr>
                    <th>Total</th>
                    {{dd($typeTotal)}}
                    @foreach ($userTickets['types'] as $typeName)
                    <th class="custom-font-size"  style="vertical-align: middle">{{$typeName}}</th>
                    @endforeach
                </tr>
                </tfoot> --}}
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footerScripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $('.data-table').dataTable();
    </script>
    <script>
        $('#ticketDateRange-btn').daterangepicker({

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
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf(
                        'month')]
                },
                startDate: moment().subtract(6, 'days'),
                endDate: moment()
            },
            function(start, end) {
                $('#ticketDateRange-btn span').html(start.format('DD/ MM/ YYYY') + ' - ' + end.format('DD/ MM/ YYYY'))
                $('#dateFrom').val(start.format('YYYY-MM-DD'));
                $('#dateTo').val(end.format('YYYY-MM-DD'));
                $('#date-form').closest('form').submit();
            }
        );
        $('#ticketDateRange-btn').on('cancel.daterangepicker', function(ev, picker) {
            clearDateFilters('ticketDateRange-btn', 'date');
            $('#date-form').closest('form').submit();
        });
    </script>

<script>
    var barChart = '{!! json_encode($barChart) !!}';

    var typeName = [];
    var newTicketCount = [];
    var respondedCount = [];
    var needResponseCount = [];
    var waitingOnDecisionCount = [];
    var closedCount = [];

    $.each(JSON.parse(barChart), function(index, data) {
        typeName.push(data.type);
        newTicketCount.push(data.new);
        respondedCount.push(data.responded);
        needResponseCount.push(data.needResponse);
        waitingOnDecisionCount.push(data.waitingOnDecision);
        closedCount.push(data.closed);

    });

    const labels = typeName;
    const data = {
        labels: labels,
        datasets: [{
                label: 'New',
                backgroundColor: 'rgb(255, 71, 71)',
                borderColor: 'rgb(255, 71, 71)',
                data: newTicketCount,
            },
            {
                label: 'Responded',
                backgroundColor: 'rgb(75, 13, 172)',
                borderColor: 'rgb(75, 13, 172)',
                data: respondedCount,
            },
            {
                label: 'Need Response',
                backgroundColor: 'rgb(255, 193, 0)',
                borderColor: 'rgb(255, 193, 0)',
                data: needResponseCount,
            },
            {
                label: 'Waiting on Decision',
                backgroundColor: 'rgb(119, 136, 153)',
                borderColor: 'rgb(119, 136, 153)',
                data: waitingOnDecisionCount,
            },
            {
                label: 'Closed',
                backgroundColor: 'rgb(116, 166, 126)',
                borderColor: 'rgb(116, 166, 126)',
                data: closedCount,
            },

        ],
    };

    const config = {
        type: 'bar',
        data: data,
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {

                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    grid: {
                        display: false,
                    },
                    ticks: {
                        autoSkip: false,
                    }
                }
            },
            interaction: {
                intersect: true,
                mode: 'index'
            },

            plugins: {

                title: {
                    display: true,
                    text: 'Tickets Status'
                },
            },
            events: ['mousemove', 'click'],


        }
    };

    var myChart = new Chart(
        document.getElementById('chart1'),
        config
    );
</script>
    <script>
        const ticketsData = {
            labels: {!! json_encode($pieChartLabels) !!},
            datasets: [{
                // label: 'My First Dataset',
                data: {!! json_encode($pieChartValues) !!},
                backgroundColor: [
                    'rgb(255, 71, 71)',
                    'rgb(75, 13, 172)',
                    'rgb(255, 193, 0)',
                    'rgb(119, 136, 153)',
                    'rgb(116, 166, 126)'
                ],
                hoverOffset: 4
            }]
        };

        const configure = {
            type: 'pie',
            data: ticketsData,
            options: {
                maintainAspectRatio: false,
                responsive: true,
                layouts: {
                    margin: 20
                },
                plugins: {

                    title: {
                        display: true,
                        text: 'Tickets Status'
                    },
                },
            }
        };

        var myChart = new Chart(
            document.getElementById('chart2'),
            configure
        );
    </script>
@endsection
