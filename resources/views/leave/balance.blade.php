<div class="card">
    <div class="card-body">
        <div class="card-title ml-3 mb-4">Month : {{ getFormatedDate($myBalance->month) }}
        </div>
        <div class="col-md-12 p-0">
            <div class="row">
                <a class="btn btn-app bg-primary col py-1 mx-2">
                    <p class="text-light">Final Balance</p>
                    <span class="text-light d-block">{{ $myBalance->final_balance ?? '0' }}</span>
                </a>
                <a class="btn btn-app bg-primary col py-1 mx-1">
                    <p class="text-light">Paid Leaves</p>
                    <span class="text-light d-block">{{ $myBalance->paid_leaves ?? '0' }}</span>
                </a>
                <a class="btn btn-app bg-primary col py-1 mx-1">
                    <p class="text-light">Taken Leaves</p>
                    <span class="text-light d-block">{{ $myBalance->taken_leaves ?? '0' }}</span>
                </a>
                <a class="btn btn-app bg-primary col py-1 mx-1">
                    <p class="text-light">Final Deduction</p>
                    <span class="text-light d-block">{{ $myBalance->lwp_leaves ?? '0' }}</span>
                </a>
            </div>
                
        </div>

    </div>
</div>

<div class="card"  style="margin-top: 25px" id="balance-form">
    <div class="card-body">
        <div class="card-title ml-3 mb-4">My Balance
            @if(Carbon\Carbon::createFromFormat('Y-m-d', $myBalance->month)->format('M') == Carbon\Carbon::now()->startofMonth()->format('M'))
                <button class="btn btn-primary btn-sm float-lg-right" onclick="raiseComplaint({{$myBalance->id}})" type="button">Have a Query?</button>
            @endif
        </div>

        <div class="col-md-12 p-0">
            <div class="table-responsive">
                <table class="table mt-2">
                    <thead>
                        <tr style="border-top: 1px solid #CED4DA;">
                            <th>Type</th>
                            <th>Taken</th>
                            <th>Waiting</th>
                        </tr>
                    </thead>
                    <tbody >
                        @foreach ($leaveTypes as $type)
                            <tr>
                                <td>{{ $type }}</td>
                                <td>{{ $approvedLeaveTypesData[$type] ?? '0' }}</td>
                                <td>{{ $pendingLeaveTypesData[$type]?? '0' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

