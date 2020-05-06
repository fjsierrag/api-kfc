<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    protected $connection="sqlite";
    protected $guarded=[];
}
