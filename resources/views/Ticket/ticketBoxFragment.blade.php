<li>
    <div class="mb-3" style="border-left: 4px solid #44318d;">
        <a class="text-decoration-none" href="{{ route('ticket.show', ['ticket' => $ticket->id])}}" target="_blank">

            <ul class=" card-inverse-light ui-sortable margin-bottom card-footer p-2" style="font-size: 13px;">
                <span class="text-muted pull-right">{{ getDateTime($ticket->created_at) }}</span>
                <span class="d-md-inline-flex" style="color: black">
                    <strong></strong>
                </span><!-- /.username -->
                <li style="list-style-type: none;">
                    <span style="color: black"><strong>{{ $ticket->type->name ?? '' }}</strong></span>
                </li>
                <li style="list-style-type: none;">
                    <span style="color: black">
                        <strong>Responsible Person:</strong>
                        @if ($ticket->type && str_contains($ticket->type->name, 'HR Queries'))
                        HR and Payroll Team
                    @elseif ($ticket->type && $ticket->type->responsibleUsers && $ticket->type->responsibleUsers->count() > 0)
                        @php
                            $responsibleUsers = $ticket->type->responsibleUsers->pluck('name', 'id')->toArray();
                        @endphp
                        {{ implode(', ', $responsibleUsers) }}
                    @else
                        <!-- Handle the case when $ticket->type or $ticket->type->responsibleUsers is null or empty -->
                    @endif
                    </span>
                </li>
                <li style="list-style-type: none;">
                    <span style="color: black"><strong>Raised
                            by: </strong>{{ $ticket->createdBy->name ?? '' }}</span>
                </li>
            </ul>
        </a>
    </div>
</li>