<?php

namespace App\Models\Azure;

use Illuminate\Database\Eloquent\Model;

class RestauranteDomicilio extends Model
{
    protected $connection="sqlsrv_az_ec";
    protected $primaryKey="IDRestaurante";
    protected $table="webservices.RestauranteDomicilio";

    public function scopeDomicilioActivo($query, $activo=true)
    {
        return $query->where('TieneDomicilio', $activo);
    }
}
