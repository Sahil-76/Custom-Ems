{{-- <html>
<head>
    <style>
        body
        {
            font-family: monospace;
        }
        table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
    </style>
</head>
<body>
<p>
   Hi,<br><br>
</p>
@if($leave->from_date==$leave->to_date)
<p>I need {{ $leave->leaveType->name}}  leave  on  {{ date('d/m/Y', strtotime($leave->from_date)) }}
@else
<p>I need {{ $leave->leaveType->name}}  leave  from  {{ date('d/m/Y', strtotime($leave->from_date)) }} to {{ date('d/m/Y', strtotime($leave->to_date)) }}
@endif

</p>


<p><h3>Reason:</h3></h3>{{$leave->reason ?? null}}</p>
<p>Click <a href="{{$link}}">here</a></p>
<p>
   Kind Regards,<br>
   {{$leave->user->name}}<br>
</p>
</body>
</html> --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Leave Applied</title>
</head>
<body>
    Hi,
    <br><br>
    Leave has been applied by {{$leave->user->name}} click <a href="{{route('leave.request')}}">here</a>
</body>
</html>