<table class="table table-bordered ">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Biometric Id</th>
            @foreach ($dateArray as $date)
                <th class="text-center">{{ Carbon\Carbon::parse($date)->format('D, d-M') }}</th>
            @endforeach
            <th>Half Days</th>
            <th>Full Days</th>
            <th>Working Days</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($userArray as $name => $attendances)
            <tr>
                <td>{{ $name }}</td>
                @php
                    $fullDays = 0;
                    $halfDays = 0;
                @endphp
                @foreach ($attendances as $key => $attendance)
                    @if ($loop->first)
                        <td>{{ $attendance }}</td>
                    @endif
                    @if (!is_array($attendance))
                        @continue
                    @endif
                    @if ($attendance['session'] == 'Full day')
                        @php $fullDays+=1; @endphp
                        <td>Full Day</td>
                    @elseif (!empty($attendance['punch_in']) && empty($attendance['session']))
                        <td>{{ Carbon\Carbon::parse($attendance['punch_in'])->format('h:iA') }}
                            <br><span>
                                {{ !empty($attendance['punch_out']) ? Carbon\Carbon::parse($attendance['punch_out'])->format('h:iA') : '' }}
                            </span>
                        </td>
                    @elseif(
                        (!empty($attendance['punch_in']) && $attendance['session'] == 'Second half') ||
                            $attendance['session'] == 'First half')
                        @php $halfDays+=1; @endphp
                        <td>
                            @if (!empty($attendance['punch_in']))
                                {{ Carbon\Carbon::parse($attendance['punch_in'])->format('h:iA') }}
                            @else
                                {{ $attendance['session'] }}
                            @endif
                            <br><span>
                                @if (!empty($attendance['punch_out']))
                                    {{ !empty($attendance['punch_out']) ? Carbon\Carbon::parse($attendance['punch_out'])->format('h:iA') : '' }}
                                @endif
                            </span>
                        </td>
                    @else
                        <td>--:--</td>
                    @endif
                @endforeach
                <td>{{ $halfDays }}</td>
                <td>{{ $fullDays }}</td>
                <td>{{ $workingDays }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
