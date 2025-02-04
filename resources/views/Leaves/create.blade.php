@extends('layouts.master')
@section('content-header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">Leave Form</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            {{-- @can('create', new App\Models\NewLeave()) --}}
            <a href="{{ route('leave.index') }}" class="btn btn-success btn-sm mr-2">Leave History</a>
            {{-- @endcan --}}
            {{-- @can('approval', new App\Models\NewLeave()) --}}
            <a href="{{ route('leave.request') }}" class="btn btn-info btn-sm mr-2"> Leave Request <span class="badge bg-gradient-light"> {{totalPendingLeaveRequestCount()}}</span></a>
            {{-- @endcan --}}
            <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
            <li class="breadcrumb-item active">Leave</li>
            <li class="breadcrumb-item active">Form</li>
        </ol>
    </div>
</div>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-7 card card-outline ml-4">
            {{ Form::open(['route' => 'leave.store', 'files' => 'true']) }}
            <div class="card-header">
                <div class="card-title">Apply Leave</div>
                <div class="card-tools">
                    <i class="text-red float-lg-right">* Fields are required.</i>
                    <br>
                    <i class="text-red">Note! Default leave is full day</i>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Leave Type<i class="text-danger">*</i></label>
                            {{-- {{ Form::label('leave_type', 'Leave Type *', ['class' => 'col-md-3 col-form-label']) }} --}}
                            <div class="col-sm-9">
                                {{ Form::select('leave_type', $leaveTypes, null, ['class' => 'form-control select2','id' => 'leave_type','placeholder' => 'Select an option','required' => 'required']) }}
                                <span class="text-red probation-error" style="display:none;">You are under provisional
                                    period,
                                    you cant apply this type of leave.
                                    yet</span>
                                <span class="text-red festivalError" style="display:none;"></span>
                                <br>
                            </div>

                        </div>
                        {{-- <div class="text-red earnedError float-lg-right" style="display:none;">Probation period is not over
                            yet</div> --}}
                        <div class="form-group leave_nature" style="display: none;">
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" value="1" name="leave_nature" id="halfDayType">
                                <label for="halfDayType">
                                    Half Day
                                </label>
                            </div>
                        </div>

                        <div class="form-group row" style="display:none;" id="holidayShow">
                            {{ Form::label('Holiday', 'Holiday *', ['class' => 'col-md-3 col-form-label']) }}
                            <div class="col-sm-9">
                                {{ Form::select('holiday', $holidays, null, ['class' => 'form-control select2','id' => 'holiday','placeholder' => 'Select an option']) }}

                                <br>
                            </div>

                        </div>
                        <div id="halfday-category" style="display: none;">
                            <div class="form-group row">

                                <div class="form-check col-sm-5">
                                    <input class="form-check-input" type="radio" name="halfDayType" value="First half">
                                    <span class="" for="">First Half
                                        {{ !empty($region) ? '(' . $region->work_from ."-".$region->first_session_till . ')' : '' }}</span>
                                </div>
                                <div class="form-check col-sm-5">
                                    <input class="form-check-input" type="radio" name="halfDayType" value="Second half">
                                    <span class="" for="">Second Half
                                        {{ !empty($region) ? '(' . $region->second_session_from ."-".$region->work_till . ')' : '' }}</span>
                                </div>

                            </div>
                        </div>
                        <div>
                            <div class="form-group row leaveDate">
                                <label class="col-sm-3 col-form-label">From Date<i class="text-danger">*</i></label>
                                {{-- {{ Form::label('from_date', 'From Date *', ['class' => 'col-sm-3 col-form-label']) }} --}}
                                <div class="col-sm-9">
                                    {{ Form::date('from_date', null, ['class' => 'form-control','id' => 'from_date','min' => $today,'required' => 'required']) }}

                                    @error('from_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group row leaveDate">
                                <label class="col-sm-3 col-form-label">To Date<i class="text-danger">*</i></label>
                                {{-- {{ Form::label('to_date', 'To Date *', ['class' => 'col-sm-3 col-form-label']) }} --}}
                                <div class="col-sm-9">
                                    {{ Form::date('to_date', null, ['class' => 'form-control date','id' => 'to_date','placeholder' => 'choose to date','required' => 'required','readonly']) }}
                                    <span class="text-red" id="check_off_day" style="display:none;"></span>
                                    @error('to_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group row">
                                {{ Form::label('Days', 'Leave Duration', ['class' => 'col-sm-3 col-form-label']) }}
                                <div class="col-sm-9">
                                    {{ Form::text('days', null, ['class' => 'form-control', 'id' => 'days', 'disabled', 'required' => 'required']) }}

                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group row balanceField" style="display: none;">
                                {{ Form::label('Balance', 'Balance', ['class' => 'col-sm-3 col-form-label']) }}
                                <div class="col-sm-9">
                                    {{ Form::text('balance', null, ['class' => 'form-control','disabled','id' => 'balance','required' => 'required']) }}
                                    <span class="text-red balanceError" style="display:none;"></span>

                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Enter Reason<i class="text-danger">*</i></label>
                                {{-- {{ Form::label('reason', 'Enter Reason *', ['class' => 'col-sm-3 col-form-label']) }} --}}
                                <div class="col-sm-9">
                                    {{ Form::textarea('reason', null, ['class' => 'form-control','rows' => '4','cols' => '4','placeholder' => 'Enter Reason','required' => 'required']) }}
                                    @error('reason')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group row">
                                <label for="attachment" id="attachment-label" class="col-sm-3 col-form-label">Attach document<i class="text-danger"style="display:none;" id="attachment-star">*</i></label>
                                <div class="col-sm-9 float-left">
                                    <input type="file" name="attachment" accept="application/pdf"  id="attachment" />
                                </div>
                                @error('attachment')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary" id="btndisable">Apply Leave</button>
                    </div>
                </div>

            </div>

            {{ Form::close() }}
        </div>
        <div class="col-md-4 card ml-5" style="height:400px;display: none;" id="description-box">

            <div class="card-body">
                <div class="col-md-12">
                    <span id="description" class="m-2"></span>
                </div>
            </div>
            <div class="card-footer">
            </div>
        </div>
    </div>
@endsection
@section('footerScripts')
    <script>
        $('input:checkbox').change(function() {
            let date = $('#from_date').val();
            if ($('#halfDayType').is(':checked')) {
                if (date != '') {
                    $('#days').val(0.5);
                }
                setDate();
                getData();
                $('#halfday-category').show().find('input:radio').prop('checked', false).prop('required', true);
            } else {

                $('#halfday-category').hide().find('input:radio').prop('checked', false).removeAttr('required');
                $('#to_date').prop({
                    'max': ''
                }).prop('readonly', false);
                if (date != '') {
                    $('#days').val(1);
                }
            }
        });

        $('#leave_type').change(function() {
            resetFields();
            resetDates();
            if (this.value == '') {
                return false;
            }
            getData();

            if ($(this).val() == '7') {
                $('#attachment-label').text('Attach Sick Form');
            }else{
                $('#attachment-label').text('Attach document');

            }
        });

        $('#holiday').change(function() {
            if (this.value == '') {
                resetFields();
                return false;
            }
            let data = {
                holiday_id: this.value
            };

            getData(data);
        });
        $("#from_date").change(function() {
            let date=$('#from_date').val();
            if ($('#halfDayType').is(':checked')) {
                $('#to_date').val(date).prop('readonly', true);
                $('#halfday-category').show().find('input:radio').attr('required', true);
                if (date != '') {
                    $('#days').val(0.5);
                }
                getData();
            }
            if ($("#to_date").val() != "") {
                $('#halfday-category').hide().find('input:radio').prop('checked', false).removeAttr('required');
                $('#to_date').prop({
                    'max': ''
                }).prop('readonly', false);
                if (date != '') {
                    $('#days').val(1);
                }
                getData();
            }
        });
        $("#to_date").change(function() {

            getData();
        });



        function getData(props = {}) {
            var from_date = $('#from_date').val();
            var to_date = $('#to_date').val();
            var id = $('#leave_type').val();

            if ($('#halfDayType').is(':checked')) {
                is_half_day = 1
            }else{
                is_half_day = null
            }
            var additionalFields = {
                'to_date': to_date,
                'from_date': from_date,
                'id': id,
                'is_half_day': is_half_day
            };
            $.extend(props, additionalFields);
            $.ajax({
                url: "{{ route('getLeaveType') }}",
                type: 'get',
                data: props,
                success: function(response) {
                    if (props.hasOwnProperty('holiday_id')) {
                        festivalLeave(response);
                        return false;
                    }

                    setNoticePeriod(response);
                    setDescription(response);
                    // if($('#from_date').val()!="")
                    if(from_date != "")
                    {
                        $('#days').val(response.days);
                        if ($('#halfDayType').is(':checked')) {
                            $('#days').val(0.5);
                        }
                    }
                    checkOffDay(response);
                    balanceError(response);
                    checkAttachment(response);
                    checkHalfDay(response);
                    if (response.leaveType.name == 'Optional Leave') {
                        setOptionalLeaveBalance();
                        checkFestivalLeaveExists(response);
                    }
                    showBalance(response);
                }

            });
        }

        function showBalance(response)
        {
            if(response.leaveType.balance==1)
            {
                $('.balanceField').show();
            }
            else
            {
                $('.balanceField').hide();
            }
        }

        function resetFields() {
            $('#holidayShow').hide();
            $('#description-box').hide();
            $('#description').text('');
            $('#halfDayType').prop('checked', false);
            $('#halfday-category').find('input:radio').prop('checked', false).removeAttr('required');
            $('#halfday-category').hide();
            $('#balance').val('');
            $('#days').val('');
            $('.balanceError').hide();
            $('.earnedError').hide();
            $('#check_off_day').hide();
            $('.balanceField').show();
            $('.festivalError').hide();
            $('.probation-error').hide();
            $('.leaveDate').show();
        }
        function resetDates() {
            $('#from_date').val('');
            $('#to_date').val('');
        }

        function setNoticePeriod(response) {
            $('#from_date').prop({
                'min': response.notice_period_date_before,
                'readonly': false
            });
            $('#to_date').prop({
                'min': response.notice_period_date_before
            });
            if (!$('#halfDayType').is(':checked')) {
                $('#to_date').prop({'readonly': false});
            }
        }

        function setOptionalLeaveBalance() {

            $('.balanceField').hide();
            $('.leave_nature').hide();
            $('#holidayShow').show();
            $('.leaveDate').hide();
            $('#days').val(1);
        }

        function setDescription(response) {
            if (response.leaveType.description != '' || response.leaveType.description !=
                null) {
                $('#description-box').show();
                $('#description').html(response.leaveType.description);
            } else {
                $('#description-box').hide();
                $('#description').text('');
            }
        }

        function balanceError(response) {
            let can_negative = response.leaveType === undefined ? null : response.leaveType.can_negative;
            var days = document.getElementById('days');
            if (typeof response.final_balance === 'undefined' || response.final_balance === null || response.final_balance - days.value <  0) {
            // if (typeof response.final_balance === 'undefined' || response.final_balance === null || response.final_balance <= 0) {
                if (can_negative != 1) {

                    if (response.final_balance == 0) {
                        $('.balanceError').text("You can't apply for leave as you have no leave credit").show();
                    }else{
                       $('.balanceError').text("You can't apply for leave as you have applied leave more than your balance").show();
                    }

                    $('#btndisable').prop('disabled', true);
                    return false;
                } else {
                    $('.balanceError').hide();
                    $('#btndisable').prop('disabled', false);
                    if ($('#to_date').val() != "") {
                        checkProbation(response);
                    }
                }
            } else {
                $('.balanceError').hide();
                $('#holiday').hide();
                if(!response.check_off_day)
                {
                    $('#check_off_day').hide();
                }
                $('#btndisable').prop('disabled', false);
                $('#balance').val(response.final_balance);
                if ($('#to_date').val() != "") {
                    checkProbation(response);
                }
            }
            setDate();
        }

        function checkProbation(response) {
            if (response.canApplyProbation != 1) {
                $('.probation-error').show();
                $('#btndisable').prop('disabled', true);
                throw new Error('Probation period not over yet');
            } else {
                $('.probation-error').hide();
                $('#btndisable').prop('disabled', false);
            }
            setDate();
        }

        function checkAttachment(response) {
            if (response.leaveType.attachment_required == 1) {
                $('#attachment').prop('required', true);
                $('#attachment-star').show();
            } else {
                $('#attachment').prop('required', false);
                $('#attachment-star').hide();
            }
            setDate();
        }

        function checkHalfDay(response) {
            if (response.leaveType.half_day_allowed == 1) {
                $('.leave_nature').show();
            } else {
                $('.leave_nature').hide();
            }
            setDate();
        }

        function festivalLeave(response) {
            $('.leaveDate').show();
            $('#from_date').val(response.from_date).prop('readonly', true);
            $('#to_date').val(response.to_date).prop('readonly', true);
            $('#holiday').prop('required', true);
            // $('#balance').val(response.leaveCount);
            $('#balance').val(response.final_balance);
        }

        function checkFestivalLeaveExists(response) {
            if (response.leaveCount >= 2) {

                $('#btndisable').prop('disabled', true);
                $('.festivalError').text('You have already taken 2 festival leaves').show();
            }
            if (response.leavePendingCount >= 2) {

                $('#btndisable').prop('disabled', true);
                $('.festivalError').text('Your 2 leaves are already in waiting').show();
            }
        }

        function checkOffDay(response) {
            if (response.check_off_day) {
                $('#check_off_day').text(`You have already a off day between dates`).show();
                $('#btndisable').prop('disabled', true);
            } else {
                $('#check_off_day').hide();
                if (!parseInt(response.days) > parseInt(response.final_balance)) {
                    $('#btndisable').prop('disabled', false);
                }
            }
        }

        function setDate()
        {
            let date = $('#from_date').val();
            if ($('#halfDayType').is(':checked') && date != '') {
            $('#days').val(0.5);
            $('#from_date').val(date);
            $('#to_date').val(date).prop('readonly', true);
            $('#halfday-category').show().find('input:radio').attr('required', true);
            }
        }


    </script>
@endsection
