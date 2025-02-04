<div class="col" id="waiting_on_decision">
    <div class="card" style="height: fit-content; border-radius: 4px !important;">
        <div class="card-body p-2">
            <div class="row pl-4 pt-2">
                <i class="fa fa-ticket"></i>
                <h4 class="card-title">Waiting on Decision
                    ({{ $ticketWaitingOnDecision->total() }})
                </h4>
            </div>

            <ul class="overflow-auto p-2"
            style="list-style-type: none padding-left:0px;">
                @foreach ($ticketWaitingOnDecision as $ticket)
                    @include('Ticket.ticketBoxFragment')
                @endforeach
                @if ($ticketWaitingOnDecision->total() != $ticketWaitingOnDecision->count())
                    <i class="btn btn-sm btn-primary"
                        onclick="loadMoreTickets('waiting_on_decision','{{ $ticketWaitingOnDecision->count() }}')">Load
                        More</i>
                @endif
            </ul>
        </div>
    </div>
</div>
