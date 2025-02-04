<?php

return [
    'days' => [
        'Monday' => 'Monday', 'Tuesday' => 'Tuesday', ' Wednesday' => 'Wednesday',
        ' Thursday' => 'Thursday', ' Friday' => 'Friday', 'Saturday' => 'Saturday', 'Sunday' => 'Sunday'
    ],
    'userTypes' => ['Employee' => 'Employee', 'Office Junior' => 'Office Junior', 'External' => 'External'],
    'gender' => ['male' => 'Male', 'female' => 'Female'],
    'shift_types'   =>  [
        'Morning'   =>  'Morning',
        'Evening'   =>  'Evening',
        'Afternoon' =>  'Afternoon'
    ],

    'laptop'        =>  [
        'Office Laptop' =>  'Office Laptop',
        'Personal'      =>  'Personal',
        'Office Desktop'=>  'Office Desktop'
    ],

    'branch'        =>  [
        'Bengaluru' =>  'Bengaluru',
        'Jalandhar' =>  'Jalandhar'
    ],

    'time_slots'    =>  [
        '4:00 AM to 1:00 PM'    => '4:00 AM to 1:00 PM',
        '6:00 AM to 3:00 PM'    => '6:00 AM to 3:00 PM',
        '7:30 AM to 4:30 PM'    => '7:30 AM to 4:30 PM',
        '1:00 PM to 10:30 PM'   => '1:00 PM to 10:30 PM',
        '9:00 AM to 6:00 PM'    => '9:00 AM to 6:00 PM',
        '10:00 AM to 7:00 PM'   => '10:00 AM to 7:00 PM',
        '7:00 PM to 4:00 AM'    => '7:00 PM to 4:00 AM'
    ],

    'status' => [
        '1' => 'Active',
        '0' => 'Inactive'
    ],

    'teams' => [
        'B2B' => 'B2B',
        'B2C' => 'B2C'
    ],

    'working_days' => [
        'Mon - Fri' => 'Mon - Fri',
        'Sun - Thu' => 'Sun - Thu',
        'Mon - Sat' => 'Mon - Sat'
    ],
    'interview_type' => [
        'WalkIn' => 'WalkIn',
        'Virtual' => 'Virtual',

    ],
    'off_day'=>[

        'Sat - Sun' => 'Sat - Sun',
        'Fri - Sat' => 'Fri - Sat',
        'Sun'       => 'Sun'

    ],
    'on_board_status' => [
        'Pending to start - All Signed'     => 'Pending to start - All Signed',
        'Pending with Recruiter'            => 'Pending with Recruiter',
        'Started'                           => 'Started',
        'Pending for Salary Negotiation'    => 'Pending for Salary Negotiation',
        'Pending with HR'                   => 'Pending with HR',
        'Resigned'                          => 'Resigned',
        'Absconded'                         => 'Absconded',
        'Leaver'                            => 'Leaver',
        'Fired'                             => 'Fired',
        'Waiting on employee'               =>'Waiting on employee',
        'Rejected'                          => 'Rejected'
    ],

    'contract_status' => [
        'Pending'   => 'Pending',
        'Sent'      => 'Sent',
        'Signed'    => 'Signed'
    ],

    'work_location' => [
        'Home'       =>'Home',
        'Office'     =>'Office'
    ],

    'attendance-status' => [
        'Present'           => 'Present',
        // 'Absent'            => 'Absent',
        // 'Vacation'          => 'Vacation',
        'Sick'              => 'Sick',
        'Leave without pay' =>  'Leave without pay',
        'Auth LWP' =>  'Auth LWP',
        // 'Half Day'          =>  'Half Day',
        // 'Off Duty'          =>  'Off Duty',
        'Company Off'       =>  'Company Off',
        'Off Day'           =>  'Off Day',
        'Optional Leave'    =>  'Optional Leave',
        'Earned Leave'      =>  'Earned Leave',
        'Festival Holiday'  => 'Festival Holiday',
        'National/State Holiday Leave' => 'National/State Holiday Leave'
    ],

    'allowance_type'   =>   [

         'monthly'   =>'monthly',
         'quarterly'  =>'quarterly',
         'yearly'    =>'yearly'

    ],
    'attendance-status-color' => [
        'Present'           => 'green',
        'Absent'            => 'red',
        'Vacation'          => 'orange',
        'Sick'              => 'pink',
        'Leave without pay' =>  'yellow',
        'Half Day'          =>  'yellow',
        'Off Duty'          =>  'orange',
        'Company Off'       =>  'Purple',
        'Off Day'           =>  'black',
        'Optional Leave'    =>  '#44318D',
        'Earned Leave'      =>  '#7a65ed',
    ],
    'first_sessions' => [

        '9:00 AM to 1:00 PM'  =>'9:00 AM to 1:00 PM',
        '9:30 AM to 1:30 PM'  =>'9:30 AM to 1:30 PM',
        '10:00 AM to 1:30 PM'  =>'10:00 AM to 1:30 PM',
        '10:30 AM to 1:30 PM'  =>'10:00 AM to 1:30 PM',

    ],
    'second_sessions' => [

        '1:00 PM to 5:00 PM'  =>'1:00 PM to 5:00 PM',
        '1:30 PM to 5:00 PM'  =>'1:30 PM to 5:00 PM',
        '2:00 PM to 5:00 PM'  =>'2:00 PM to 5:00 PM',
        '2:30 PM to 5:00 PM'  =>'2:30 PM to 5:00 PM',
    ],
    'full_sessions' => [

        '9:00 AM to 6:00 PM'  =>'9:00 AM to 6:00 PM',
        '9:30 AM to 6:30 PM'  =>'9:30 AM to 6:30 PM',
        '10:00 AM to 7:-0 PM'  =>'10:00 AM to 7:30 PM',
        '10:30 AM to 7:30 PM'  =>'10:00 AM to 7:30 PM',

    ],
    'leave_status'  =>[
        'Pending' => 'Pending',
        'Approved' => 'Approved',
        'Rejected' => 'Rejected',
        'Cancelled' => 'Cancelled'
    ],
    'b2b_team_types'    => [
        'Inbound' => 'Inbound', 
        'Outbound' => 'Outbound'
    ]

];
