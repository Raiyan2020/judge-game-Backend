<?php

namespace App\Models;

use App\Http\Traits\SetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'is_active'])]

class Admin extends  Authenticatable
{
    use HasFactory, Notifiable,SetPassword;
   
}
