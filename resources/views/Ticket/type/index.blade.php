@extends('layouts.master')
@section('headerLinks')
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Ticket Type</li>
                </ol>
            </nav>
        </div>

        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class=" rounded h-100 p-4">
                        <div class="row">
                            <div class="col-md-6 mb-4 ">
                                <h3 class=" text-dark text-start">Ticket Type List</h3>
                            </div>
                            <div class="col-md-6  d-flex justify-content-end">
                                    <a class="btn btn-primary mb-4 " href="{{route('ticket-type.create')}}">Create</a>
                            </div>
                            
                        </div>

                        <div class="table-responsive" style="background-color: white">
                            <table class="table table-hover" id="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Responsible Person</th>
                                        <th class="text-center">Is Active</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable">
                                    @foreach ($ticketTypes as $ticketType)
                                        <tr class="sortable-handle" data-type="{{$ticketType->name}}">
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{ $ticketType->name }}</td>
                                            @if($ticketType->responsibleUsers->isNotEmpty())
                                            <td>{{ implode(', ',$ticketType->responsibleUsers->pluck('name')->toArray()) }}</td>
                                            @else
                                            <td></td>
                                            @endif
                                            <td class="text-center">
                                                @if ($ticketType->is_active == 1)
                                                    <i class="fa fa-check-circle text-success"></i>
                                                @else
                                                    <i class="fa fa-times-circle text-danger"></i>
                                                @endif
                                            </td>
                                            <td><a href="{{ route('ticket-type.edit', $ticketType->id) }}"
                                                        class="btn btn-primary">Edit</a></td>
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
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.js"></script>
<script>
    $('#data-table').DataTable({
        ordering: false,
        pageLength: 50,
    });
</script>
@endsection



