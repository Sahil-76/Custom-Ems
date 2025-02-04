@extends('layouts.master')
@section('content-header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0 text-dark">Email Management</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item">Email Management</li>
        </ol>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <a href="{{route('email.create')}}" class="btn btn-dark"><i class="fa fa-plus"></i> Add Email</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table data-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th>Reference</th>
                                        <th>Subject</th>
                                        <th class="text-center">Template Exists</th>
                                        <th class="text-center">To</th>
                                        <th class="text-center">CC</th>
                                        <th class="text-center">BCC</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($emails as $email)
                                        <tr>
                                            <td class="text-center">{{ $email->id }}</td>
                                            <td>{{ $email->reference }}</td>
                                            <td>{{ $email->subject }}</td>
                                            <td class="text-center">{!! activeInactiveHTML($email->isTemplateExists()) !!}</td>
                                            <td class="text-center">{{ $email->to_recipients_count }}</td>
                                            <td class="text-center">{{ $email->cc_recipients_count }}</td>
                                            <td class="text-center">{{ $email->bcc_recipients_count }}</td>
                                            <td class="text-center"> 
                                                <a href="{{ route('email.edit', $email->id) }}" class="btn btn-dark">Edit</a> 
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
</div>
@endsection

@section('footer')
<script>
    $('.data-table').dataTable();

</script>

@endsection
