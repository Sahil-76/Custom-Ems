@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item "><a href="{{ route('leave-types.index') }}">Leave Type</a></li>
                    {{-- <li class="breadcrumb-item active">{{ $user->name }}</li> --}}
                </ol>
            </nav>
        </div>

        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class=" rounded h-100 p-4">
                        <div class="row">
                            <div class="col-md-6 mt-4 ">
                                <h3 class=" text-dark text-start">Manage Leave Type</h3>
                            </div>
                            <div class="col-md-6  d-flex justify-content-end">
                                    <a class="btn btn-primary mt-4 " href="{{ route('leave-types.create') }}">Add</a>
                            </div>
                            
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">S.No.</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($leaves as $leave)
                                        <tr>
                                            <td scope="row">{{ $loop->iteration }}</td>
                                            <td scope="row">{{ $leave->name }}</td>
                                            <td scope="row" class="d-flex">
                                                @if ($leave->trashed())
                                                    <!-- Restore icon when the record is trashed -->
                                                    <a href="{{ route('leave-types.restore', $leave->id) }}"
                                                        data-bs-toggle="tooltip" title="Restore Leave Type"
                                                        class="text-success btn">
                                                        <i class="fas fa-trash-restore"></i>
                                                    </a>
                                                @else
                                                    <!-- Edit and Delete icons when the record is not trashed -->
                                                    <a href="{{ route('leave-types.edit', $leave->id) }}"
                                                        data-bs-toggle="tooltip" title="Edit Leave Type"
                                                        class="text-primary btn"><i class="fa fa-edit"></i></a>
                                                    <form action="{{ route('leave-types.destroy', $leave->id) }}" method="post">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn  text-danger"
                                                            data-bs-toggle="tooltip" title="Move to Trash">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
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
    <script></script>
@endsection


