<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EditProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()->hasRole('employee')) {
            $employee = auth()->user()->employee;

            if (!empty($employee) && !in_array(Route::currentRouteName(),  ['editProfile','updateProfile'])) {
                $employee_id = $employee->id;
                if (is_null($employee->room_available) || is_null($employee->wifi_availability) || is_null($employee->marital_status)) {
                    return redirect()->route('editProfile', $employee_id);
                }
            }
        }


        return $next($request);
    }
}
