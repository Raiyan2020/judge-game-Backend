<?php


namespace App\Http\Traits;

trait AvatarOperations
{

    public function getImageAttribute($value)
    {
        if ($value)
            return getimg($value);
        elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        } else
        return asset('placeholders/logo.jpg');
    }


    public function setImageAttribute($value)
    {
        if (is_file($value))
            $this->attributes['image'] = uploader($value);
        else
            $this->attributes['image'] = $value;

    }


}
