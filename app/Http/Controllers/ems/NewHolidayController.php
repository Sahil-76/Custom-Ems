<?php

namespace App\Http\Controllers\ems;

use App\Models\NewHoliday;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;

class NewHolidayController extends Controller
{
    public function index()
    {
        // $this->authorize('view', new NewHoliday());
        $data['holidays']= NewHoliday::all();

        return view('Holiday.index',$data);
    }

    public function create()
    {
        $data['holiday']        =   new NewHoliday();
        $data['holiday_types']  =   Config::get('leave.holiday_types');
        $data['submitRoute']    =   'holiday.store';
        $data['method']         =   'POST';

        return view('Holiday.form', $data);
    }

    public function  store(Request $request)
    {
        NewHoliday::updateOrCreate(['title'    =>  $request->title,
                                'date'      =>  $request->date],[
                                'type'      =>  $request->type,
                                'is_active' =>  $request->is_active ?? null
                                ]);

        return redirect()->route('holiday.index')->with('success','Holiday Added.');
    }

    public function edit($id)
    {
        $data['holiday']        =   NewHoliday::find($id);
        $data['holiday_types']  =   Config::get('leave.holiday_types');
        $data['submitRoute']    =   ['holiday.update',$id];
        $data['method']         =   'PUT';

        return view('Holiday.form', $data);
    }

    public function update(Request $request, $id)
    {
        $holiday            =   NewHoliday::find($id);
        $holiday->title     =   $request->title;
        $holiday->date      =   $request->date;
        $holiday->type      =   $request->type;
        $holiday->is_active =   $request->is_active ?? null;
        $holiday->update();

        return redirect()->route('holiday.index')->with('success','Holiday Updated.');
    }
}
