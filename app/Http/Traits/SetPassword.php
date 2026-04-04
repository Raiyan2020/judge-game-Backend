<?php


namespace App\Http\Traits;

trait SetPassword
{


    public function setPasswordAttribute($value)
    {
        if ($value)
            $this->attributes['password'] = bcrypt($value);
    }


}
