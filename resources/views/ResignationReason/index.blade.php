@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item "><a href="{{ route('resignation-reason.index') }}">Resignation Reason</a></li>
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
                                <h3 class=" text-dark text-start">Manage Resignation Reason</h3>
                            </div>
                            <div class="col-md-6  d-flex justify-content-end">
                                    <a class="btn btn-primary mt-2 " href="{{ route('resignation-reason.create') }}">Add</a>
                            </div>
                            
                        </div>

                        <div class="table-responsive mt-3">
                            <table id="example1" class="table table-bordered table-hover dataTable dtr-inline w-100"
                                aria-describedby="example2_info">
                                <thead>
                                    <tr>
                                        <th scope="col">S.No.</th>
                                        <th scope="col">Reason</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reason as $res)
                                        <tr>
                                            <td scope="row">{{ $loop->iteration }}</td>
                                            <td scope="row">{{ $res->name }}</td>
                                            <td scope="row" class="d-flex">
                                                @if ($res->trashed())
                                                    <!-- Restore icon when the record is trashed -->
                                                    <a href="{{ route('resignation-reason.restore', $res->id) }}"
                                                        data-bs-toggle="tooltip" title="Restore Resignation Reason"
                                                        class="text-success btn">
                                                        <i class="fas fa-trash-restore"></i>
                                                    </a>
                                                @else
                                                    <!-- Edit and Delete icons when the record is not trashed -->
                                                    <a href="{{ route('resignation-reason.edit', $res->id) }}"
                                                        data-bs-toggle="tooltip" title="Edit Resignation Reason"
                                                        class="text-primary btn"><i class="fa fa-edit"></i></a>
                                                    <form action="{{ route('resignation-reason.destroy', $res->id) }}" method="post">
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
<script src="https://cdn.datatables.net/1.11.6/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#example1').DataTable();
    });
</script>
@endsection