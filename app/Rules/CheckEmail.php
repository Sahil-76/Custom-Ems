<?php

namespace App\Rules;

use App\User;
use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Rule;

class CheckEmail implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if($value!='')
        {
            if(Str::contains($value,'theknowledgeacademy.com') )
            {
                // check if the leaver email or new user email same
                $inactiveUser = User::withoutGlobalScope('is_active')->where('is_active', '<>', 1)->where('email', $value)->first();

                if ($inactiveUser) {
                    $inactiveUser->update(['email' => $inactiveUser->email.'_old']);

                }

                return true;
            }

            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ' Please enter tka email';
    }
}
