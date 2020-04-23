<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ConexionDomicilio extends Model
{
    protected $connection="sqlite";
    protected $primaryKey="id";
    protected $table="ConexionesDomicilio";
    protected $guarded=[];
}
