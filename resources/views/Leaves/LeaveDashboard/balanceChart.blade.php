<div class="col-md-5" id="balance-form">
    <div class="card">
        <div class="card-header text-white" style="background:#44318d">
            <div class="card-title">Leave Balance</div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th class="text-center">Allowance</th>
                        <th class="text-center">Prev.Bal</th>
                        <th class="text-center">Taken</th>
                        <th class="text-center">Waiting</th>
                        <th class="text-center">Balance</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($balanceChart as $type => $leaveBalance)
                        @php
                            $startDate = Carbon\Carbon::parse($leaveBalance['date'])
                                ->startOfMonth()
                                ->format('Y-m-d');
                            $endDate = Carbon\Carbon::parse($leaveBalance['date'])
                                ->endOfMonth()
                                ->format('Y-m-d');
                        @endphp

                        <tr>

                            <td>{{ $type }}</td>
                            <td class="text-center">{{ $leaveBalance['allowance'] }}</td>
                            <td class="text-center">{{ $leaveBalance['previous_balance'] }}</td>
                            @if (auth()->user()->can('viewDetailCalendar', auth()->user()))
                                <td class="text-center"><a
                                        href="{{ route('leave.request', ['user' => $leaveBalance['user_id'], 'status' => 'Approved', 'dateFrom' => $startDate, 'dateTo' => $endDate]) }}"
                                        target="_blank">{{ $leaveBalance['taken_leaves'] }}</a></td>
                            @else
                                <td class="text-center"><a
                                        href="{{ route('leave.index', ['status' => 'Approved', 'dateFrom' => $startDate, 'dateTo' => $endDate]) }}"
                                        target="_blank">{{ $leaveBalance['taken_leaves'] }}</a></td>
                            @endif
                            @if (auth()->user()->can('viewDetailCalendar', auth()->user()))
                                <td class="text-center"><a
                                        href="{{ route('leave.request', ['user' => $leaveBalance['user_id'], 'status' => 'Pending', 'dateFrom' => $startDate, 'dateTo' => $endDate]) }}"
                                        target="_blank">{{ $leaveBalance['waiting'] }}</a></td>
                            @else
                                <td class="text-center"><a
                                        href="{{ route('leave.index', ['status' => 'Pending', 'dateFrom' => $startDate, 'dateTo' => $endDate]) }}"
                                        target="_blank">{{ $leaveBalance['waiting'] }}</a></td>
                            @endif
                            <td class="text-center">{{ $leaveBalance['final_balance'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Leave Balance (Yearly Report)</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th class="text-center">Allowance</th>
                            <th class="text-center">Taken</th>
                            <th class="text-center">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($totalBalance as $type => $leaveBalance)
                            <tr>
                                <td>{{ $type }}</td>
                                <td class="text-center">{{ $leaveBalance['allowance'] }}</td>
                                <td class="text-center">
                                    <a target="_blank" href="{{ route('attendance.index', ['user_id' => $leaveBalance['user_id'], 'status' => $type, 'expiry' => $leaveBalance['expiry'], 'month' =>$leaveBalance['date']]) }}">
                                        {{ $leaveBalance['taken'] }}
                                    </a>
                                </td>
                                <td class="text-center">{{ $leaveBalance['balance'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
