@extends('layouts.master')

@section('content')

    <div class="row">
        <div class="col-12">
            <span class="font-weight-bold">Department List</span>
            <nav aria-label="breadcrumb" class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Department List</li>
                </ol>
            </nav>
        </div>
        <div class="col-12">
            <div class="card" >
                <div class="card-body">
                    <p class="card-title">
                        <a href="{{ route('departments.create') }}" class="btn btn-facebook">Create New</a>
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Short Name</th>
                                    <th>Manager</th>
                                    <th>Team Leader</th>
                                    <th>Line Manager</th>
                                    <th>Total Employees</th>
                                    <th>Total Resignation</th>
                                    <th>Required HC</th>
                                    <th>Vaccencies</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- @foreach ($departments as $department)
                                    <tr>
                                        <td>{{ $department->name }}</td>
                                        <td>{{ $department->short_name }}</td>
                                        <td>{{ $department->manager_name }}</td>
                                        <td>{{ $department->team_leader_name }}</td>
                                        <td>{{ $department->line_manager_name }}</td>
                                        <td>{{ $department->employees_count}}</td>
                                        <td>
                                            @if ($department->total_resignations !== 0)
                                            {{ $department->total_resignations ?? null }}
                                        @else
                                            N/A
                                        @endif
                                        </td>
                                        <td>
                                            @if ($department->required_emp !== null)
                                            {{ $department->required_emp ?? null }}
                                        @else
                                            N/A
                                        @endif
                                        </td>
                                        <td>
                                        @if ($department->required_emp !== null)
                                            {{ ($department->required_emp - $department->employees_count) + $department->total_resignations }}
                                        @else
                                            N/A
                                        @endif
                                        </td>
                                        <td>
                                            <a class="btn btn-danger" href="{{ route('departments.edit', $department) }}">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@php
        $query = http_build_query(request()->query());
    @endphp
@section('footerScripts')
<script>
    $(document).ready(function() {
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{!! route('departments.index') !!}",
            columns: [
                { data: 'name', name: 'name' },
                { data: 'short_name', name: 'short_name' },
                { data: 'manager_name', name: 'manager_name' },
                { data: 'team_leader_name', name: 'team_leader_name' },
                { data: 'line_manager_name', name: 'line_manager_name' },
                { data: 'total_employees', name: 'total_employees' },
                { data: 'total_resignations', name: 'total_resignations' },
                { data: 'required_hc', name: 'required_hc' },
                { data: 'vacancies', name: 'vacancies' },
                { data: 'action', name: 'action' },
            ]
        });
    });
</script>
@endsection
