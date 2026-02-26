<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocOwner extends Model
{
    protected $table = 'docowners';
    protected $primaryKey = 'id_docowner';
    public $timestamps = false;

    protected $fillable = [
        'owner_name',
        'table_reference',
    ];

    protected $casts = [
        'id_docowner' => 'int',
    ];
}
