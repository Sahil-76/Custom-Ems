<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewLeaveReqest extends FormRequest
{
   /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        //  dd(request()->all());
        $rules = [
            // 'leave_nature'  =>  'required',
            // 'leave_type'    =>  'required',
            'reason'        =>  'required',
            'from_date'     =>  'required',
            'to_date'       =>  'required|after_or_equal:from_date',
        ];
        if (request()->leave_type == 'Half day') {
            $rules += ['halfDayType' => 'required'];
        }
        if (request()->has('attachment')) {
            $rules += ['attachment' => 'mimes:pdf'];
        }
        return $rules;
    }

    public function attributes()
    {
        return [
            'attachment' => 'File must be type of pdf',
        ];
    }
}
