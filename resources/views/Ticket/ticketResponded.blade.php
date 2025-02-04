<div class="col" id="responded_tickets">
    <div class="card" style="height: fit-content; border-radius: 4px !important;">
        <div class="card-body p-2">
            <div class="row pl-4 pt-2">
                <i class="fa fa-ticket"></i>
                <h4 class="card-title">Responded
                    ({{ $ticketResponded->total() }})
                </h4>
            </div>

            <ul class="overflow-auto p-2"
            style="list-style-type: none padding-left:0px;">
                @foreach ($ticketResponded as $ticket)
                @include('Ticket.ticketBoxFragment')
                @endforeach
                @if($ticketResponded->total() != $ticketResponded->count())
                <i class="btn btn-sm btn-primary" onclick="loadMoreTickets('responded_tickets','{{$ticketResponded->count()}}')">Load More</i>
                @endif

            </ul>
        </div>
    </div>
</div>
