@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Tickets</li>
                </ol>
            </nav>
        </div>

        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class=" rounded h-100 p-4">
                        <div class="row">
                            <div class="col-md-6 mt-4 ">
                                <h3 class=" text-dark text-start">Tickets</h3>
                            </div>
                            {{-- <div class="col-md-6  d-flex justify-content-end">
                                    <a class="btn btn-primary mt-4 " href="{{ route('leave-status.create') }}">Add</a>
                            </div> --}}
                            
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table" id="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ticket Type</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tickets as $ticket)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{ $ticket->type->name ?? '' }}</td>
                                        <td>{{ $ticket->priority }}</td>
                                        <td>
                                            @if($ticket->status == 'Waiting on Decision')
                                            Need Response
                                            @else
                                            {{ $ticket->status }}
                                            @endif
                                        </td>
                                        <td><a target="_blank" href="{{ route('ticket.show', ['ticket' => $ticket->id]) }}" class="btn btn-primary" style="border-radius:4px;height:40px;text-align:center;line-height:38px;">Details</a></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footerScripts')
<script>
    $('#data-table').DataTable();
</script>
@endsection