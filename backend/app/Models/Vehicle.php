<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table = 'vehicle';
    protected $primaryKey = 'id_vehicle';
    public $timestamps = false;

    protected $fillable = [
        'vehicle_name',
        'vehicle_number',
        'license_plate',
        'is_deleted',
    ];

    protected $casts = [
        'id_vehicle' => 'int',
        'is_deleted' => 'int',
    ];
}
