<div class="col" id="closed_tickets">
    <div class="card" style="height: fit-content; border-radius: 4px !important;">
        <div class="card-body p-2">
            <div class="row pl-4 pt-2">
                <i class="fa fa-ticket"></i>
                <h4 class="card-title">Closed
                    ({{ $ticketClosed->total() }})
                </h4>
            </div>

            <ul class="overflow-auto p-2"
            style="list-style-type: none padding-left:0px;">
                @foreach ($ticketClosed as $ticket)
                    @include('Ticket.ticketBoxFragment')
                @endforeach
                @if ($ticketClosed->total() != $ticketClosed->count())
                    <i class="btn btn-sm btn-primary"
                        onclick="loadMoreTickets('closed_tickets','{{ $ticketClosed->count() }}')">Load More</i>
                @endif
            </ul>
        </div>
    </div>
</div>
