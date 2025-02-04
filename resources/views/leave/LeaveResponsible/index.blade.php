@extends('layouts.master')
@section('content-header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">Leave Responsible</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
            <li class="breadcrumb-item active">Leave Responsible</li>
        </ol>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="card-title">
                    List
                </div>
                <div class="card-tools h5 ">
                    <a href="{{route('leave-responsible.create')}}" class="text-body"><i class="fas fa-user-plus"></i> </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="background-color: white">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>Employee Role</th>
                                <th>First Approval</th>
                                <th>Second Approval</th>
                                <th>Third Approval</th>
                                <th>Fourth Approval</th>
                                <th>Fifth Approval</th>
                                <th>Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leaveResponsibles as $leaveResponsible)
                            <tr>
                                <td>{{$leaveResponsible->employeeRole->name ?? ''}}</td>
                                <td>{{$leaveResponsible->firstApproval->name ?? ''}}</td>
                                <td>{{$leaveResponsible->secondApproval->name ?? ''}}</td>
                                <td>{{$leaveResponsible->thirdApproval->name ?? ''}}</td>
                                <td>{{$leaveResponsible->fourthApproval->name ?? ''}}</td>
                                <td>{{$leaveResponsible->fithApproval->name ?? ''}}</td>
                                
                                <td>
                                    <a href="{{ route('leave-responsible.edit', ['leave_responsible' => $leaveResponsible->id]) }}" class="m-1"><i class="fa fa-edit"></i></a>
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

@endsection

@section('footer')
<script>
    $('#data-table').DataTable();
</script>

@endsection
