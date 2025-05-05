<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempCredential extends Model
{
    use HasFactory;

    protected $table = 'temp-credentials';

   protected  $fillable = ['email', 'password', 'expires_at'];

   protected $dates = ['expires_at'];
}
