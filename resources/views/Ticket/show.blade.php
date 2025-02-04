@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Ticket No. {{ $ticket->id }}</h3>
                    <div class="card-tools">
                        <div class="row">
                            
                            @if ($ticket->status != 'Closed')
                                {{ Form::open(['route' => ['ticket.destroy', $ticket->id], 'method' => 'Delete']) }}
                                @method('delete')
                                @if (auth()->user()->hasRole('hr'))
                                {{-- @if ($ticket->type->name == 'HR Queries' && $ticket->status != 'Waiting on Decision') --}}
                                <a href="{{ route('statusUpdate', $ticket->id) }}" class="btn btn-danger">Waiting on
                                    Decision</a>
                                @endif
                                {{-- @endcan --}}
                                {{-- @if (empty($teamOnly) && auth()->user()->id !== $ticket->created_by) --}}
                                @if (auth()->user()->hasRole('employee') ||
                                auth()->user()->hasRole('hr'))
                                <button onclick="return confirm('Are you sure you want to close ticket?');"
                                    class="btn btn-dark" type="submit">Close</button>

                                @endif

                                {{ Form::close() }}
                            @endif

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 ">
                            <div class="row">
                                <div class="col-md-6 font-weight-bold ">
                                    Category
                                </div>
                                <div class="col-md-6 ">
                                    {{ $ticket->type->name }}
                                    @if (
                                        $currenResponsibleUser ||
                                            auth()->user()->hasRole('admin'))
                                        &nbsp;<i onclick="forwardTicket('{{ $ticket->id }}')"
                                            style="color: rgb(31, 128, 238);cursor: pointer;" class="fa fa-edit"></i>
                                    @endif
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 font-weight-bold ">
                                    Priority
                                </div>
                                <div class="col-md-6 ">
                                    {{ $ticket->priority }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 font-weight-bold ">
                                    Status
                                </div>
                                <div class="col-md-6 ">
                                    {{ $ticket->status }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 font-weight-bold ">
                                    Raised By
                                </div>
                                <div class="col-md-6 ">
                                    {{ $ticket->createdBy->name }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 ">
                            @if (
                                $ticket->created_by != auth()->user()->id ||
                                    auth()->user()->hasRole('admin'))
                                <div class="row">
                                    <div class="col-md-6 font-weight-bold ">
                                        Responsible Person
                                    </div>
                                    <div class="col-md-6 ">
                                        {{ $responsibles }}
                                    </div>

                                </div>
                            @endif
                            @if (!empty($ticket->files))
                                @foreach ($ticket->files as $file)
                                    <div class="row">
                                        <div class="col-md-6 font-weight-bold ">
                                            {{ Str::after($file, '-') }}
                                        </div>
                                        <div class="col-md-6 ">
                                            <a
                                                href="{{ route('fileDownload', ['folder' => 'ticket', 'fileName' => $file]) }}"><i
                                                    class="fa fa-download"></i></a>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        {{-- @if (!empty($ticket->files))
                            @foreach ($ticket->files as $file)
                                <li class="detail-list col-md-6 "> {{ Str::after($file, '-') }}
                                    <span>
                                        <a href="{{ route('fileDownload', ['folder' => 'ticket', 'fileName' => $file]) }}"><i
                                                class="fa fa-download"></i></a>
                                    </span>
                                </li>
                            @endforeach
                        @endif --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-5">

            <div class="card-body">
                <h4 class="card-title">Updates</h4>
                <ul class="bullet-line-list">
                    @foreach ($ticket->logs as $activityLog)
                        <li>
                            <div class="card mb-2">
                                <div class="card-body">
                                    <h5 class="timeline-header" style="font-size: 14px;">
                                        @if ($activityLog->actionBy->hasRole('HR Admin'))
                                            HR and Payroll Team
                                            @if (auth()->user()->hasRole('HR Admin') ||
                                                    auth()->user()->hasRole('Admin'))
                                                ({{ $activityLog->actionBy->name }})
                                            @endif
                                        @else
                                            {{ $activityLog->actionBy->name }}
                                        @endif
                                        @isset($activityLog->actionBy->employee)
                                            - ( {{ $activityLog->actionBy->employee->biometric_id }} )
                                        @endisset

                                        <small class="float-right">
                                            {{ getDateTime($activityLog->created_at) }}
                                        </small>
                                    </h5>
                                    <div class="table-responsive timeline-body">
                                        {!! $activityLog->description !!}
        
                                        @if (!empty($activityLog->document))
                                            <span class="mr-1">View Files <a onclick="openModel('{{ $activityLog->id }}')"><i
                                                        class="fa fa-eye"></i></a></span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Reply</h4>
                </div>
                @if (empty($teamOnly) || auth()->user()->id == $ticket->created_by)
                    {{ Form::open(['route' => ['ticket.update', $ticket->id], 'files' => true]) }}
                @endif
                @method('put')
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Reply To:-</label>
                        <div class="col-sm-10">
                            @if ($ticket->created_by == auth()->user()->id)
                                {{ Form::text('email', 'TKA', ['class' => 'form-control', 'readonly' => true]) }}
                            @else
                                {{ Form::email('email', $ticket->createdBy->email, ['class' => 'form-control']) }}
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Comment:-</label>
                        <div class="col-sm-10">
                            {{ Form::textarea('comment', null, ['class' => 'form-control summernote', 'placeholder' => 'comment', 'required']) }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Files:-</label>
                        <div class="col-sm-10">
                            <input name="documents[]" type="file" multiple>
                        </div>
                    </div>
                    @if (empty($teamOnly) || auth()->user()->id == $ticket->created_by)
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary float-right">Submit</button>
                            </div>
                    @endif

                </div>

            </div>
            {{-- @if (empty($teamOnly) || auth()->user()->id == $ticket->created_by)
            <div class="card-footer ">
                <button type="submit" class="btn btn-primary float-right">Submit</button>
            </div> --}}
            {{ Form::close() }}

            {{-- @endif --}}
        </div>
    </div>

    </div>

    <div class="modal fade" id="modal-default" aria-modal="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body" id="log-files">
                </div>
                <div class="modal-footer justify-content-between">



                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endsection


@section('footerScripts')
    <script>
        $('.summernote').summernote({
            height: 200
        });

        var fileDownloadRoute = '{{ route('fileDownload', ['folder' => 'ticket', 'fileName' => ':id']) }}';
        var url = "{{ route('ticketDocuments') }}";

        function openModel(id) {
            $.ajax({
                url: url,
                data: {
                    'id': id
                },
                type: 'GET',
                dataType: "json",
                success: function(response) {
                    response = JSON.parse(response);
                    html = `<table class="table table-hover table-responsive">
                     <thead>
                      <tr>
                          <th style="position: sticky; top: 0; z-index: 1;" class="bg-gray-light">Document</th>
                          <th style="position: sticky; top: 0; z-index: 1;" class="bg-gray-light">Link</th>

                      </tr>
                    </thead>
                    <tbody id="CommentList">`;

                    $.each(response, function(index, data) {
                        var downloadUri = fileDownloadRoute.replace(':id', data);
                        html += `<tr>
                      <td><span class="text-break">${data}</span></td>

                      <td><a  href='${downloadUri}' class=""><i class="fa fa-download btn-icon-prepend"></i></a></td>

                  </tr>`
                    });

                    html += `</tbody>
                      </table>`;
                    $('#log-files').html(html);
                    $('#modal-default').find('.modal-title').html('Ticket Log Document');

                },
                error: function() {


                }

            });
            $('#modal-default').modal('show');
        }

        function forwardTicket(id) {
            var html = `
        {{ Form::open(['route' => 'ticketForward']) }}

            <div class="form-group">
                <input type='hidden' name='ticket_id' value='{{ $ticket->id }}'>
                {{ Form::label('ticket_type', 'Ticket Type', ['class' => 'col-sm-3 col-form-label']) }}
                {{ Form::select('ticket_type', $ticketTypes, null, ['class' => 'form-control', 'required', 'data-placeholder' => 'Select an Option', 'placeholder' => 'Select an Option']) }}
            </div>



            <button type="submit" class="btn btn-primary">Forward</button>
        {{ Form::close() }}`;
            $('#modal-default').find('.modal-title').html('Ticket Forward');
            $('#modal-default').find('.modal-body').html(html);
            $('.select2').select2({
                theme: 'bootstrap4',
            });
            $('#modal-default').modal('show');
        }
    </script>
@endSection
