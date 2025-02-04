@extends('layouts.master')

@push('styles')
    <style>
.card.draggable {
    margin-bottom: 1rem;
    cursor: grab;
}

.droppable {
    background-color: var(--success);
    min-height: 120px;
    margin-bottom: 1rem;
}
    </style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Filter</h3>
                {{-- <div class="card-tools">
                    <a href="{{route('ticket-type.index')}}" class="btn btn-dark">Ticket Type</a>
                </div> --}}
            </div>
            <div class="card-body">
                {!! Form::open(['method' => 'get', 'id' => 'date-form']) !!}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="ticket_no">Search Ticket No.</label>
                            {{ Form::text('ticket_no', request()->ticket_no, ['class' => 'form-control', 'placeholder' => 'Search Ticket No.', 'autocomplete' => 'off']) }}
                        </div>
                    </div>
                    @if (empty($teamOnly))
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="responsibleUser">Select Responsible User</label>
                            {{ Form::select('responsibleUser', $responsibleUsers, request()->responsibleUser ?? null, ['class' => 'form-control select2','data-placeholder' => 'Select Responsible Person','placeholder' => 'Select Responsible Person']) }}
                        </div>
                    </div>

                    @endif

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="user">Select User</label>
                            {{ Form::select('created_by', $users, request()->created_by ?? null, ['class' => 'form-control select2','data-placeholder' => 'Select User','placeholder' => 'Select User']) }}
                        </div>
                    </div>
                    @if (empty($teamOnly))
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="priority">Select Priority</label>
                            {{ Form::select('priority', $priorities, request()->priority ?? null, ['class' => 'form-control select2','data-placeholder' => 'Select Priority','placeholder' => 'Select Priority']) }}
                        </div>
                    </div>
                    @endif
                    @if (empty($teamOnly))
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="priority">Select Category</label>
                            {{ Form::select('type_id', $types, request()->type_id ?? null, ['class' => 'form-control select2','data-placeholder' => 'Select Category','placeholder' => 'Select Category']) }}
                        </div>
                    </div>
                    @endif
                    {{-- @if (empty($teamOnly))
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="priority">Select Branch</label>
                            {{ Form::select('branch_id', $branches, request()->branch_id ?? null, ['class' => 'form-control select2','data-placeholder' => 'Select Branch','placeholder' => 'Select Branch']) }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="priority">Select Region</label>
                            {{ Form::select('region[]', $regions, request()->region ?? null, ['class' => 'form-control select2', 'multiple' => 'multiple', 'data-placeholder' => 'Select Region']) }}
                        </div>
                    </div>
                    @endif --}}

                    <div class="col-md-3">
                        <div class="form-group">
                            {{ Form::hidden('dateFrom', request()->dateFrom ?? null, ['id' => 'dateFrom']) }}
                            {{ Form::hidden('dateTo', request()->dateTo ?? null, ['id' => 'dateTo']) }}
                            <button type="button" id="eventDateRange-btn" class="btn btn-sm  btn-primary"
                                style="width: 184px;margin-top: 31px;" id="daterange-btn">
                                @if (request()->has('dateFrom') && request()->has('dateTo'))
                                    <span>
                                        {{ Carbon\Carbon::parse(request()->get('dateFrom'))->format('d/m/Y') }} -
                                        {{ Carbon\Carbon::parse(request()->get('dateTo'))->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span>
                                        <i class="fa fa-calendar" value=""></i> &nbsp;Filter Date&nbsp;
                                    </span>
                                @endif
                                <i class="fa fa-caret-down"></i>
                            </button>
                        </div>
                    </div>


                </div>
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ request()->url() }}" class="btn btn-success">Clear Filter</a>
                    </div>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>

<div class="row" id="ticket-types">

</div>
@endsection


@section('footerScripts')
<!-- TrustBox script -->

<script>
    $('#eventDateRange-btn').daterangepicker({

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
            $('#eventDateRange-btn span').html(start.format('DD/ MM/ YYYY') + ' - ' + end.format('DD/ MM/ YYYY'))
            $('#dateFrom').val(start.format('YYYY-MM-DD'));
            $('#dateTo').val(end.format('YYYY-MM-DD'));
        }
    );
    $('#eventDateRange-btn').on('cancel.daterangepicker', function(ev, picker) {
        clearDateFilters('eventDateRange-btn', 'date');
        $('#date-form').closest('form').submit();
    });

    function autoLoadTickets() {
        $.ajax({
            url: "{!! route('loadTickets', http_build_query(request()->query())) !!}",
            method: 'GET',
            success: function(response) {
                $('#ticket-types').html(' ');
            },
            complete: function(response) {
                $.each(response.responseJSON, function(type, tickets) {
                    $('#ticket-types').append(tickets);
                });
            }
        })
    }


    $(window).on('load', function() {
        autoLoadTickets();
        setInterval(() => {
            autoLoadTickets();
    },30000);
    });

    function loadMoreTickets(requestType, count,loader) {
        var loader  =   event.target;
        count = eval(parseInt(count) + 3);
        $.ajax({
            url: "{!! route('loadTickets', http_build_query(request()->query())) !!}",
            method: 'GET',
            data: {
                ticket_type_load: requestType,
                count: count
            },
            beforeSend: function() {

                $('.loader').css('display', 'flex');

            },
            success: function(response) {
                console.log(requestType);
                $('.loader').css('display', 'none');
                $('#' + requestType).replaceWith(response);
                var d = $('#' + requestType).find('ul');
                d.scrollTop(d.prop("scrollHeight"));
            }
        });
    }
</script>
@endsection