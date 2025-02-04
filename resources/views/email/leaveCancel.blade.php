<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Leave Cancelled</title>
</head>
<body>
    Hi,
    <br><br>
    Leave has been Cancelled  {{$leave->user->name}} click <a href="{{route('leave.request')}}">here</a>
</body>
</html>