<html>
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
<p>Your leave request has been rejected.</p>
{{-- @if($leave->from_date==$leave->to_date)
<p>I need {{ $leave->leaveType->name}}  leave  on  {{ date('d/m/Y', strtotime($leave->from_date)) }}
@else
<p>I need {{ $leave->leaveType->name}}  leave  from  {{ date('d/m/Y', strtotime($leave->from_date)) }} to {{ date('d/m/Y', strtotime($leave->to_date)) }}
@endif --}}
</p>
</body>
</html>