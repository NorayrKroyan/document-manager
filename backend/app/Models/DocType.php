<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocType extends Model
{
    protected $table = 'doctypes';
    protected $primaryKey = 'id_doctype';
    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'type_extension',
        'require_expire',
    ];

    protected $casts = [
        'id_doctype' => 'int',
        'require_expire' => 'int',
    ];
}
